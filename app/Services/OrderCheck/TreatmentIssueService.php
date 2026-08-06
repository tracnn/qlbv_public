<?php

namespace App\Services\OrderCheck;

use App\Models\CheckBHYT\check_hein_card;
use App\Models\OrderCheck\OrderCheckViolation;
use Illuminate\Support\Facades\DB;

/**
 * Gop ba nguon loi cua CUNG MOT dot dieu tri: sai sot y lenh, loi tra the BHYT, loi
 * XML3176. Ba bang khoa theo cung mot gia tri (ma_lk = treatment_code).
 *
 * Khong phu thuoc Request va khong tu boc HTTP: test duoc khong can goi HTTP, va man
 * hinh noi bo khac dung lai duoc.
 */
class TreatmentIssueService
{
    /** Tran cung so dong moi nhom - may chu gioi han PHP 128MB/120s. */
    const TRAN_MOI_NHOM = 500;

    /** Trang thai nguoi dung da xac nhan khong phai loi; day sang HIS chi gay nhieu. */
    const BO_QUA = 'false_positive';

    /**
     * @param  string|null $treatmentCode Ma dot dieu tri (= ma_lk)
     * @param  int|string|null $treatmentId ID dot dieu tri tren HIS
     * @param  array $tuyChon ['status' => string|null]
     * @return array ['data' => [...], 'summary' => [...]]
     */
    public function cua($treatmentCode = null, $treatmentId = null, array $tuyChon = [])
    {
        $status = isset($tuyChon['status']) ? $tuyChon['status'] : null;

        $viPham = $this->viPhamYLenh($treatmentCode, $treatmentId, $status);

        // Hai nhom kia khoa theo ma_lk. Ben goi chi dua treatment_id thi suy nguoc tu
        // dong vi pham; khong suy ra duoc thi de rong chu KHONG truy HIS - mot lan goi
        // API khong dang doi mot vong sang Oracle.
        $maLk = $this->rong($treatmentCode) ? $this->suyRaMaLk($treatmentId) : $treatmentCode;

        $traThe = $this->rong($maLk) ? [] : $this->loiTraThe($maLk);
        $xml = $this->rong($maLk) ? [] : $this->loiXml3176($maLk);

        return [
            'data' => [
                'treatment_code' => $this->rong($maLk) ? null : $maLk,
                'order_check' => $viPham,
                'hein_card' => $traThe,
                'xml3176' => $xml,
            ],
            'summary' => $this->tomTat($viPham, $traThe, $xml),
        ];
    }

    protected function suyRaMaLk($treatmentId)
    {
        if ($this->rong($treatmentId)) {
            return null;
        }

        $ma = OrderCheckViolation::where('treatment_id', $treatmentId)
            ->whereNotNull('treatment_code')
            ->value('treatment_code');

        return $this->rong($ma) ? null : $ma;
    }

    protected function tomTat(array $viPham, array $traThe, array $xml)
    {
        $critical = 0;

        foreach ($viPham as $d) {
            if ($d['severity'] === 'critical') {
                $critical++;
            }
        }

        foreach ($xml as $d) {
            if ($d['critical_error']) {
                $critical++;
            }
        }

        $tong = count($viPham) + count($traThe) + count($xml);

        return [
            'total' => $tong,
            'order_check' => count($viPham),
            'hein_card' => count($traThe),
            'xml3176' => count($xml),
            'critical' => $critical,
            'has_error' => $tong > 0,
            // Nhom tra the unique theo ma_lk nen khong bao gio cham tran.
            'truncated' => count($viPham) >= self::TRAN_MOI_NHOM
                || count($xml) >= self::TRAN_MOI_NHOM,
        ];
    }

    protected function viPhamYLenh($treatmentCode, $treatmentId, $status)
    {
        $q = OrderCheckViolation::query();

        if (!$this->rong($treatmentCode)) {
            $q->where('treatment_code', $treatmentCode);
        }
        if (!$this->rong($treatmentId)) {
            $q->where('treatment_id', $treatmentId);
        }

        if ($this->rong($status)) {
            $q->where('status', '!=', self::BO_QUA);
        } else {
            $q->where('status', $status);
        }

        $dong = $q->orderBy('detected_at', 'desc')
            ->limit(self::TRAN_MOI_NHOM)
            ->get([
                'id', 'rule_code', 'severity', 'order_ref_type', 'order_ref_id',
                'message', 'detail', 'status', 'detected_at',
            ]);

        $ra = [];

        foreach ($dong as $v) {
            $ra[] = [
                'id' => (int) $v->id,
                'rule_code' => $v->rule_code,
                'severity' => $v->severity,
                'order_ref_type' => $v->order_ref_type,
                'order_ref_id' => (int) $v->order_ref_id,
                'message' => $v->message,
                'detail' => $this->giaiMaChiTiet($v->detail),
                'status' => $v->status,
                'detected_at' => (string) $v->detected_at,
            ];
        }

        return $ra;
    }

    /**
     * Chi tra dong CO BAT THUONG. Dung lai scope chiLoi() cua model: quy tac "khac 000
     * HOAC khac 00" da duoc can nhac va ghi chu ky trong do, viet lai o day la nhan doi
     * mot quy tac de lech.
     */
    protected function loiTraThe($maLk)
    {
        $dong = check_hein_card::where('ma_lk', $maLk)->chiLoi()->get();

        $ra = [];

        foreach ($dong as $t) {
            $ra[] = [
                'ma_tracuu' => $t->ma_tracuu,
                'ma_kiemtra' => $t->ma_kiemtra,
                'ma_ketqua' => $t->ma_ketqua,
                'ghi_chu' => $t->ghi_chu,
                'ma_the_masked' => self::cheMaThe($t->ma_the),
                'checked_at' => $t->updated_at ? (string) $t->updated_at : null,
            ];
        }

        return $ra;
    }

    /**
     * JOIN THEO CAP (xml, error_code) - danh muc unique theo cap nay. Noi chi bang
     * error_code se nhan mot dong loi thanh nhieu dong khi ma loi ton tai o nhieu loai
     * XML. Quan he hasOne trong model Xml3176ErrorResult noi thieu cot xml nen khong
     * dung lai duoc o day.
     *
     * critical_error lay tu ban ghi ket qua (gia tri tai thoi diem kiem), khong lay tu
     * danh muc - danh muc co the da doi sau do.
     */
    protected function loiXml3176($maLk)
    {
        $dong = DB::table('xml3176_error_results as r')
            ->leftJoin('xml3176_error_catalogs as c', function ($j) {
                $j->on('c.xml', '=', 'r.xml')
                  ->on('c.error_code', '=', 'r.error_code');
            })
            ->where('r.ma_lk', $maLk)
            ->orderBy('r.xml')
            ->orderBy('r.stt')
            ->limit(self::TRAN_MOI_NHOM)
            ->get([
                'r.xml', 'r.stt', 'r.error_code', 'c.error_name', 'r.description',
                'r.critical_error', 'r.ngay_yl', 'r.ngay_kq',
            ]);

        $ra = [];

        foreach ($dong as $d) {
            $ra[] = [
                'xml' => $d->xml,
                'stt' => (int) $d->stt,
                'error_code' => $d->error_code,
                'error_name' => $d->error_name,
                'description' => $d->description,
                'critical_error' => (bool) $d->critical_error,
                'ngay_yl' => $d->ngay_yl,
                'ngay_kq' => $d->ngay_kq,
            ];
        }

        return $ra;
    }

    /** Chi giu 4 ky tu cuoi - du de doi chieu, khong du de tai su dung. */
    public static function cheMaThe($maThe)
    {
        $maThe = trim((string) $maThe);

        return $maThe === '' ? null : '****' . substr($maThe, -4);
    }

    /** JSON hong o MOT dong khong duoc lam chet ca lan goi: tra null cho rieng dong do. */
    protected function giaiMaChiTiet($detail)
    {
        if ($this->rong($detail)) {
            return null;
        }

        if (is_array($detail)) {
            return $detail;
        }

        $giaiMa = json_decode($detail, true);

        return json_last_error() === JSON_ERROR_NONE && is_array($giaiMa) ? $giaiMa : null;
    }

    protected function rong($gt)
    {
        return $gt === null || $gt === '';
    }
}

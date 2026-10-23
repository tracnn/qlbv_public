<?php

namespace App\Services\SinhXml3176;

use App\Models\SinhXml3176\LanSinh;
use Illuminate\Support\Facades\Log;

/**
 * Ban sao luu tru cua tep XML3176 moi lan sinh: <goc>\<yyyyMMdd theo created_at cua dong>\<id dong>_<ten_tep>.
 * Id dong nam trong ten nen hai lan sinh cung giay cua cung ho so (cung ten_tep) van ra hai tep khac nhau.
 * Cot tep_luu chi giu phan tuong doi (dau phan cach cua he dieu hanh); moi duong doc/xoa deu di qua
 * duongTuyetDoi() de khong the thoat ra ngoai goc. Ghi theo kieu an toan: ghi tep .tmp trong thu muc ngay roi rename.
 * Loi ghi chi Log::warning va tra null: luu tru khong bao gio lam hong lan sinh chinh.
 */
class LuuTruXml
{
    private $goc;

    public function __construct($goc)
    {
        $goc = $goc === null ? '' : rtrim(trim((string) $goc), '/\\');
        $this->goc = $goc === '' ? null : $goc;
    }

    /**
     * Ghi ban sao cua $dong; tra duong tuong doi (yyyyMMdd\id_ten) hoac null khi khong cau hinh goc / ghi loi.
     * Khong cap nhat cot tep_luu (noi goi lam).
     */
    public function luu(LanSinh $dong, $noiDung)
    {
        if ($this->goc === null) {
            return null;
        }

        try {
            $ten = basename(str_replace('\\', '/', (string) $dong->ten_tep));
            if ($ten === '' || $dong->id === null) {
                throw new \RuntimeException('Dong theo doi thieu id hoac ten tep');
            }
            $ngay = $dong->created_at !== null ? $dong->created_at->format('Ymd') : date('Ymd');
            $thuMucNgay = $this->goc . DIRECTORY_SEPARATOR . $ngay;
            if (!is_dir($thuMucNgay) && !@mkdir($thuMucNgay, 0775, true) && !is_dir($thuMucNgay)) {
                throw new \RuntimeException('Khong tao duoc thu muc ' . $thuMucNgay);
            }

            $tenLuu = $dong->id . '_' . $ten;
            $dich = $thuMucNgay . DIRECTORY_SEPARATOR . $tenLuu;
            $tam = $dich . '.tmp';
            if (@file_put_contents($tam, (string) $noiDung, LOCK_EX) === false) {
                @unlink($tam);
                throw new \RuntimeException('Khong ghi duoc tep ' . $tam);
            }
            if (!@rename($tam, $dich)) {
                if (is_file($dich)) {
                    @unlink($dich);
                }
                if (!@rename($tam, $dich)) {
                    @unlink($tam);
                    throw new \RuntimeException('Khong doi ten duoc sang ' . $dich);
                }
            }

            return $ngay . DIRECTORY_SEPARATOR . $tenLuu;
        } catch (\Throwable $e) {
            Log::warning('sinhxml3176 luu tru ban sao dong ' . $dong->id . ' loi: ' . $e->getMessage());

            return null;
        }
    }

    /**
     * Duong tuyet doi cua duong tuong doi, hoac null neu khong hop le: rong, tuyet doi (\x, /x, C:\x),
     * co thanh phan "..", hoac (tep da co) sau realpath nam ngoai goc. Chap nhan ca '/' lan '\'.
     */
    public function duongTuyetDoi($tuongDoi)
    {
        if ($this->goc === null) {
            return null;
        }
        $tuongDoi = (string) $tuongDoi;
        if (trim($tuongDoi) === '' || strpos($tuongDoi, "\0") !== false) {
            return null;
        }
        $chuan = str_replace('\\', '/', $tuongDoi);
        if ($chuan[0] === '/' || preg_match('#^[A-Za-z]:#', $chuan)) {
            return null;
        }
        $phan = [];
        foreach (explode('/', $chuan) as $p) {
            if ($p === '..') {
                return null;
            }
            if ($p !== '' && $p !== '.') {
                $phan[] = $p;
            }
        }
        if (empty($phan)) {
            return null;
        }

        $duong = $this->goc . DIRECTORY_SEPARATOR . implode(DIRECTORY_SEPARATOR, $phan);
        $that = @realpath($duong);
        if ($that !== false) {
            $gocThat = @realpath($this->goc);
            if ($gocThat === false
                || !CauHinhSinhXml3176::namTrong($that, $gocThat)
                || CauHinhSinhXml3176::chuanHoaDuongDan($that) === CauHinhSinhXml3176::chuanHoaDuongDan($gocThat)) {
                return null;
            }
        }

        return $duong;
    }

    /** Noi dung ban sao cua $dong; null khi chua luu, duong khong hop le hoac tep khong con tren dia. */
    public function doc(LanSinh $dong)
    {
        $duong = $this->duongTep($dong);
        if ($duong === null) {
            return null;
        }
        $nd = @file_get_contents($duong);

        return $nd === false ? null : $nd;
    }

    public function coTep(LanSinh $dong)
    {
        return $this->duongTep($dong) !== null;
    }

    /**
     * Xoa tep ban sao, ghi xoa_luu_luc/xoa_luu_boi, xoa thu muc ngay neu da rong.
     * Ghi dau xoa vao CSDL TRUOC khi xoa tep: loi CSDL nem ra ngoai va tep con nguyen; xoa tep that bai thi
     * hoan lai dau. Tep da bi go tay van duoc danh dau (tra true). False khi dong chua co tep luu,
     * da danh dau xoa, duong khong hop le (ke ca ra ngoai goc) hoac khong xoa duoc tep.
     */
    public function xoa(LanSinh $dong, $userId)
    {
        if ($dong->xoa_luu_luc !== null || trim((string) $dong->tep_luu) === '') {
            return false;
        }
        $duong = $this->duongTuyetDoi($dong->tep_luu);
        if ($duong === null) {
            return false;
        }

        $dong->xoa_luu_luc = date('Y-m-d H:i:s');
        $dong->xoa_luu_boi = (int) $userId;
        try {
            $dong->save();
        } catch (\Throwable $e) {
            $dong->xoa_luu_luc = null;
            $dong->xoa_luu_boi = null;
            throw $e;
        }

        if (is_file($duong) && !@unlink($duong)) {
            $dong->xoa_luu_luc = null;
            $dong->xoa_luu_boi = null;
            $dong->save();

            return false;
        }

        $thuMuc = dirname($duong);
        $ds = @scandir($thuMuc);
        if ($ds !== false && count(array_diff($ds, ['.', '..'])) === 0) {
            @rmdir($thuMuc);
        }

        return true;
    }

    /** Duong tuyet doi cua tep ban sao neu hop le va dang co tren dia, nguoc lai null. */
    private function duongTep(LanSinh $dong)
    {
        $tuongDoi = trim((string) $dong->tep_luu);
        if ($tuongDoi === '') {
            return null;
        }
        $duong = $this->duongTuyetDoi($tuongDoi);

        return $duong !== null && is_file($duong) ? $duong : null;
    }
}

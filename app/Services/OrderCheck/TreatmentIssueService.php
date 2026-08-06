<?php

namespace App\Services\OrderCheck;

use App\Models\OrderCheck\OrderCheckViolation;

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

        return [
            'data' => [
                'treatment_code' => $this->rong($treatmentCode) ? null : $treatmentCode,
                'order_check' => $viPham,
                'hein_card' => [],
                'xml3176' => [],
            ],
            'summary' => [],
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

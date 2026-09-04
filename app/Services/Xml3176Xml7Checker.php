<?php

namespace App\Services;

use App\Models\BHYT\Xml3176Xml7;
use Illuminate\Support\Collection;

use DateTime;
use App\Services\Xml3176\Support\Xml3176DateHelper;

class Xml3176Xml7Checker
{
    protected $xmlErrorService;
    protected $commonValidationService;
    protected $prefix;

    protected $xmlType;

    public function __construct(Xml3176ErrorService $xmlErrorService, CommonValidationService $commonValidationService)
    {
        $this->xmlErrorService = $xmlErrorService;
        $this->commonValidationService = $commonValidationService;
        $this->setConditions();
    }

    protected function setConditions()
    {
        $this->xmlType = 'XML7';
        $this->prefix = $this->xmlType . '_';
    }

    protected function generateErrorCode(string $errorKey): string
    {
        return $this->prefix . $errorKey;
    }

    /**
     * Check Xml3176Xml7 Errors
     *
     * @param Xml3176Xml7 $data
     * @return void
     */
    public function checkErrors(Xml3176Xml7 $data): void
    {
        // Thực hiện kiểm tra lỗi
        $errors = collect();

        $errors = $errors->merge($this->infoChecker($data));
        $errors = $errors->merge($this->checkNghiNgoaiTru($data));

        // Save errors to xml_error_checks table
        $this->xmlErrorService->saveErrors($this->xmlType, $data->ma_lk, 1, $errors);
    }

    /**
     * Check for reason for admission errors
     *
     * @param Xml3176Xml7 $data
     * @return Collection
     */
    private function infoChecker(Xml3176Xml7 $data): Collection
    {
        $errors = collect();

        if (empty($data->pp_dieutri)) {
            $errorCode = $this->generateErrorCode('INFO_ERROR_PP_DIEUTRI');
            $errors->push((object)[
                'error_code' => $errorCode,
                'error_name' => 'Thiếu phương pháp điều trị',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description' => 'Phương pháp điều trị không được để trống'
            ]);
        }

        if (empty($data->ma_ttdv)) {
            $errorCode = $this->generateErrorCode('INFO_ERROR_MA_TTDV');
            $errors->push((object)[
                'error_code' => $errorCode,
                'error_name' => 'Thiếu mã thủ trưởng đơn vị',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description' => 'Thủ trưởng đơn vị không được để trống'
            ]);
        } else {
            if (!$this->commonValidationService->isMedicalStaffValid($data->ma_ttdv)) {
                $errorCode = $this->generateErrorCode('INFO_ERROR_MA_TTDV_NOT_FOUND');
                $errors->push((object)[
                    'error_code' => $errorCode,
                    'error_name' => 'Mã thủ trưởng đơn vị chưa được duyệt',
                    'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                    'description' => 'Mã thủ trưởng đơn vị chưa được duyệt danh mục (Mã CCHN: ' . $data->ma_ttdv . ')'
                ]);
            }
        }

        if (empty($data->ma_bs)) {
            $errorCode = $this->generateErrorCode('INFO_ERROR_MA_BS');
            $errors->push((object)[
                'error_code' => $errorCode,
                'error_name' => 'Thiếu mã bác sĩ',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description' => 'Mã bác sĩ không được để trống'
            ]);
        } else {
            if (!$this->commonValidationService->isMedicalStaffValid($data->ma_bs)) {
                $errorCode = $this->generateErrorCode('INFO_ERROR_MA_BS_NOT_FOUND');
                $errors->push((object)[
                    'error_code' => $errorCode,
                    'error_name' => 'Mã bác sĩ chưa được duyệt',
                    'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                    'description' => 'Mã bác sĩ chưa được duyệt danh mục NVYT: ' . $data->ma_bs
                ]);
            }
        }

        return $errors;
    }

    /**
     * #2163 số ngày nghỉ ≠ (đến − từ + 1); #2313/#2314 ngoại trú từ/đến ngày < ngày ra.
     */
    private function checkNghiNgoaiTru(Xml3176Xml7 $data): Collection
    {
        $errors = collect();

        if ($data->so_ngay_nghi !== null && $data->so_ngay_nghi !== ''
            && !empty($data->ngoaitru_tungay) && !empty($data->ngoaitru_denngay)) {
            $days = Xml3176DateHelper::diffDays($data->ngoaitru_tungay, $data->ngoaitru_denngay);
            if ($days !== null && (int) $data->so_ngay_nghi !== $days + 1) {
                $code = $this->generateErrorCode('SO_NGAY_NGHI_MISMATCH');
                $errors->push((object) [
                    'error_code' => $code, 'error_name' => 'Số ngày nghỉ không đúng (đến − từ)',
                    'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($code),
                    'description' => 'Khai ' . $data->so_ngay_nghi . ' ngày, nhưng từ ' . strtodatetime($data->ngoaitru_tungay) . ' đến ' . strtodatetime($data->ngoaitru_denngay) . ' là ' . ($days + 1) . ' ngày',
                ]);
            }
        }

        $ra = Xml3176DateHelper::datePart($data->ngay_ra);
        if ($ra !== null) {
            $tu = Xml3176DateHelper::datePart($data->ngoaitru_tungay);
            if ($tu !== null && $tu < $ra) {
                $code = $this->generateErrorCode('NGOAITRU_TUNGAY_BEFORE_NGAY_RA');
                $errors->push((object) [
                    'error_code' => $code, 'error_name' => 'Bắt đầu nghỉ ngoại trú trước ngày ra viện',
                    'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($code),
                    'description' => 'Từ ngày ngoại trú (' . strtodatetime($data->ngoaitru_tungay) . ') < ngày ra (' . strtodatetime($data->ngay_ra) . ')',
                ]);
            }
            $den = Xml3176DateHelper::datePart($data->ngoaitru_denngay);
            if ($den !== null && $den < $ra) {
                $code = $this->generateErrorCode('NGOAITRU_DENNGAY_BEFORE_NGAY_RA');
                $errors->push((object) [
                    'error_code' => $code, 'error_name' => 'Đến ngày nghỉ ngoại trú trước ngày ra viện',
                    'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($code),
                    'description' => 'Đến ngày ngoại trú (' . strtodatetime($data->ngoaitru_denngay) . ') < ngày ra (' . strtodatetime($data->ngay_ra) . ')',
                ]);
            }
        }

        return $errors;
    }

    // Thêm các phương thức kiểm tra khác ở đây
}
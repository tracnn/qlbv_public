<?php

namespace App\Services;

use App\Models\BHYT\Qd130Xml7;
use Illuminate\Support\Collection;

use DateTime;

class Qd130Xml7Checker
{
    protected $xmlErrorService;
    protected $commonValidationService;
    protected $prefix;

    protected $xmlType;

    public function __construct(Qd130XmlErrorService $xmlErrorService, CommonValidationService $commonValidationService)
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
     * Check Qd130Xml7 Errors
     *
     * @param Qd130Xml7 $data
     * @return void
     */
    public function checkErrors(Qd130Xml7 $data): void
    {
        // Thực hiện kiểm tra lỗi
        $errors = collect();

        $errors = $errors->merge($this->infoChecker($data));

        // Save errors to xml_error_checks table
        $this->xmlErrorService->saveErrors($this->xmlType, $data->ma_lk, 1, $errors);
    }

    /**
     * Check for reason for admission errors
     *
     * @param Qd130Xml7 $data
     * @return Collection
     */
    private function infoChecker(Qd130Xml7 $data): Collection
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

    // Thêm các phương thức kiểm tra khác ở đây
}
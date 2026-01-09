<?php

namespace App\Services;

use App\Models\BHYT\Xml3176Xml8;
use Illuminate\Support\Collection;

use DateTime;

class Xml3176Xml8Checker
{
    protected $xmlErrorService;
    protected $commonValidationService;
    protected $prefix;

    protected $xmlType;

    public function __construct(Xml3176XmlErrorService $xmlErrorService, CommonValidationService $commonValidationService)
    {
        $this->xmlErrorService = $xmlErrorService;
        $this->commonValidationService = $commonValidationService;
        $this->setConditions();
    }

    protected function setConditions()
    {
        $this->xmlType = 'XML8';
        $this->prefix = $this->xmlType . '_';
    }

    protected function generateErrorCode(string $errorKey): string
    {
        return $this->prefix . $errorKey;
    }

    /**
     * Check Xml3176Xml8 Errors
     *
     * @param Xml3176Xml8 $data
     * @return void
     */
    public function checkErrors(Xml3176Xml8 $data): void
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
     * @param Xml3176Xml8 $data
     * @return Collection
     */
    private function infoChecker(Xml3176Xml8 $data): Collection
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

        if (empty($data->tomtat_kq)) {
            $errorCode = $this->generateErrorCode('INFO_ERROR_TOMTAT_KQ');
            $errors->push((object)[
                'error_code' => $errorCode,
                'error_name' => 'Thiếu tóm tắt kết quả',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description' => 'Tóm tắt kết quả không được để trống'
            ]);
        } elseif (mb_strlen($data->tomtat_kq) > 4000) {
            $errorCode = $this->generateErrorCode('INFO_ERROR_TOMTAT_KQ_LENGTH');
            $errors->push((object)[
                'error_code' => $errorCode,
                'error_name' => 'Tóm tắt kết quả vượt quá 4000 ký tự',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description' => 'Tóm tắt kết quả không được vượt quá 4000 ký tự'
            ]);
        }

        if (empty($data->ket_qua_dtri)) {
            $errorCode = $this->generateErrorCode('INFO_ERROR_KET_QUA_DTRI');
            $errors->push((object)[
                'error_code' => $errorCode,
                'error_name' => 'Thiếu kết quả điều trị',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description' => 'Kết quả điều trị không được để trống'
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

        return $errors;
    }

    // Thêm các phương thức kiểm tra khác ở đây
}
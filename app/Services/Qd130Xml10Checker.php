<?php

namespace App\Services;

use App\Models\BHYT\Qd130Xml10;
use Illuminate\Support\Collection;

use DateTime;

class Qd130Xml10Checker
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
        $this->xmlType = 'XML10';
        $this->prefix = $this->xmlType . '_';
    }

    protected function generateErrorCode(string $errorKey): string
    {
        return $this->prefix . $errorKey;
    }

    /**
     * Check Qd130Xml10 Errors
     *
     * @param Qd130Xml10 $data
     * @return void
     */
    public function checkErrors(Qd130Xml10 $data): void
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
     * @param Qd130Xml10 $data
     * @return Collection
     */
    private function infoChecker(Qd130Xml10 $data): Collection
    {
        $errors = collect();

        if (empty($data->so_seri)) {
            $errorCode = $this->generateErrorCode('INFO_ERROR_SO_SERI');
            $errors->push((object)[
                'error_code' => $errorCode,
                'error_name' => 'Thiếu số seri',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description' => 'Số seri không được để trống'
            ]);
        }

        if (empty($data->so_ct)) {
            $errorCode = $this->generateErrorCode('INFO_ERROR_SO_CT');
            $errors->push((object)[
                'error_code' => $errorCode,
                'error_name' => 'Thiếu số chứng từ',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description' => 'Số chứng từ không được để trống'
            ]);
        }

        if (empty($data->so_ngay)) {
            $errorCode = $this->generateErrorCode('INFO_ERROR_SO_NGAY');
            $errors->push((object)[
                'error_code' => $errorCode,
                'error_name' => 'Thiếu số ngày',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description' => 'Số ngày không được để trống'
            ]);
        } elseif ($data->so_ngay > 180) {
            $errorCode = $this->generateErrorCode('INFO_ERROR_SO_NGAY_EXCEEDS');
            $errors->push((object)[
                'error_code' => $errorCode,
                'error_name' => 'Số ngày vượt quá giới hạn cho phép',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description' => 'Số ngày không được vượt quá 180 ngày. Giá trị hiện tại: ' . $data->so_ngay
            ]);
        }

        if (empty($data->don_vi)) {
            $errorCode = $this->generateErrorCode('INFO_ERROR_DON_VI');
            $errors->push((object)[
                'error_code' => $errorCode,
                'error_name' => 'Thiếu đơn vị',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description' => 'Đơn vị không được để trống'
            ]);
        }

        if (empty($data->chan_doan_rv)) {
            $errorCode = $this->generateErrorCode('INFO_ERROR_CHAN_DOAN_RV');
            $errors->push((object)[
                'error_code' => $errorCode,
                'error_name' => 'Thiếu chẩn đoán ra viện',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description' => 'Chẩn đoán ra viện không được để trống'
            ]);
        }

        if (empty($data->tu_ngay)) {
            $errorCode = $this->generateErrorCode('INFO_ERROR_TU_NGAY');
            $errors->push((object)[
                'error_code' => $errorCode,
                'error_name' => 'Thiếu từ ngày',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description' => 'Từ ngày không được để trống'
            ]);
        }

        if (empty($data->den_ngay)) {
            $errorCode = $this->generateErrorCode('INFO_ERROR_DEN_NGAY');
            $errors->push((object)[
                'error_code' => $errorCode,
                'error_name' => 'Thiếu đến ngày',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description' => 'Đến ngày không được để trống'
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
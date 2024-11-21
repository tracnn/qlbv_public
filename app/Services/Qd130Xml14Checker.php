<?php

namespace App\Services;

use App\Models\BHYT\Qd130Xml14;
use Illuminate\Support\Collection;

class Qd130Xml14Checker
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
        $this->xmlType = 'XML14';
        $this->prefix = $this->xmlType . '_';

    }

    protected function generateErrorCode(string $errorKey): string
    {
        return $this->prefix . $errorKey;
    }

    /**
     * Check Qd130Xml14 Errors
     *
     * @param Qd130Xml14 $data
     * @return void
     */
    public function checkErrors(Qd130Xml14 $data): void
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
     * @param Qd130Xml14 $data
     * @return Collection
     */
    private function infoChecker(Qd130Xml14 $data): Collection
    {
        $errors = collect();

        if (empty($data->so_giayhen_kl)) {
            $errorCode = $this->generateErrorCode('INFO_ERROR_SO_GIAYHEN_KL');
            $errors->push((object)[
                'error_code' => $errorCode,
                'error_name' => 'Thiếu số giấy hẹn khám lại',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description' => 'Số giấy hẹn khám lại không được để trống'
            ]);
        }

        if (empty($data->ma_cskcb)) {
            $errorCode = $this->generateErrorCode('INFO_ERROR_MA_CSKCB');
            $errors->push((object)[
                'error_code' => $errorCode,
                'error_name' => 'Thiếu mã CSKCB',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description' => 'CSKCB không được để trống'
            ]);
        }

        if (empty($data->ngay_vao)) {
            $errorCode = $this->generateErrorCode('INFO_ERROR_NGAY_VAO');
            $errors->push((object)[
                'error_code' => $errorCode,
                'error_name' => 'Thiếu ngày vào',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description' => 'Ngày vào không được để trống'
            ]);
        }

        if (empty($data->ngay_ra)) {
            $errorCode = $this->generateErrorCode('INFO_ERROR_NGAY_RA');
            $errors->push((object)[
                'error_code' => $errorCode,
                'error_name' => 'Thiếu ngày ra',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description' => 'Ngày ra không được để trống'
            ]);
        }

        if (empty($data->ngay_hen_kl)) {
            $errorCode = $this->generateErrorCode('INFO_ERROR_NGAY_HEN_KL');
            $errors->push((object)[
                'error_code' => $errorCode,
                'error_name' => 'Thiếu ngày hẹn khám lại',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description' => 'Ngày hẹn khám lại không được để trống'
            ]);
        } else {
            if (!empty($data->ngay_ra) && ($data->ngay_hen_kl <= $data->ngay_ra)) {
                $errorCode = $this->generateErrorCode('INFO_ERROR_NGAY_HEN_KL_SMALLER_NGAY_RA');
                $errors->push((object)[
                    'error_code' => $errorCode,
                    'error_name' => 'Ngày hẹn khám lại nhỏ hơn hoặc bằng ngày ra',
                    'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                    'description' => 'Ngày hẹn khám lại không được nhỏ hơn hoặc bằng ngày ra: ' . strtodatetime($data->ngay_hen_kl) . ' <= ' . strtodatetime($data->ngay_ra)
                ]);
            }
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

        if (empty($data->ma_benh_chinh)) {
            $errorCode = $this->generateErrorCode('INFO_ERROR_MA_BENH_CHINH');
            $errors->push((object)[
                'error_code' => $errorCode,
                'error_name' => 'Thiếu mã bệnh chính',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description' => 'Mã bệnh chính không được để trống'
            ]);
        }

        if (!empty($data->ma_benh_kt)) {
            $ma_benh_kt_array = explode(';', $data->ma_benh_kt);
            if (count($ma_benh_kt_array) > 12) {
                $errorCode = $this->generateErrorCode('INFO_ERROR_MA_BENH_KT');
                $errors->push((object)[
                    'error_code' => $errorCode,
                    'error_name' => 'Mã bệnh kèm theo vượt quá 12 mã',
                    'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                    'description' => 'Mã bệnh kèm theo không được vượt quá 12 mã'
                ]);
            }
        }

        if (empty($data->ma_doituong_kcb)) {
            $errorCode = $this->generateErrorCode('INFO_ERROR_MA_DOITUONG_KCB');
            $errors->push((object)[
                'error_code' => $errorCode,
                'error_name' => 'Thiếu mã đối tượng KCB',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description' => 'Đối tượng KCB không được để trống'
            ]);
        }

        if (empty($data->ma_bac_si)) {
            $errorCode = $this->generateErrorCode('INFO_ERROR_MA_BAC_SI');
            $errors->push((object)[
                'error_code' => $errorCode,
                'error_name' => 'Thiếu mã bác sĩ (CCHN)',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description' => 'Mã bác sĩ (CCHN) không được để trống'
            ]);
        } else {
            if (!$this->commonValidationService->isMedicalStaffValid($data->ma_bac_si)) {
                $errorCode = $this->generateErrorCode('INVALID_MEDICAL_STAFF_MA_BAC_SI');
                $errors->push((object)[
                    'error_code' => $errorCode,
                    'error_name' => 'Mã bác sĩ (CCHN) chưa được duyệt',
                    'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                    'description' => 'Mã bác sĩ chưa được duyệt danh mục (Mã CCHN: ' . $data->ma_bac_si . ')'
                ]);
            }
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
                $errorCode = $this->generateErrorCode('INVALID_MEDICAL_STAFF_MA_TTDV');
                $errors->push((object)[
                    'error_code' => $errorCode,
                    'error_name' => 'Mã thủ trưởng đơn vị chưa được duyệt',
                    'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                    'description' => 'Mã thủ trưởng đơn vị chưa được duyệt danh mục (Mã CCHN: ' . $data->ma_ttdv . ')'
                ]);
            }
        }

        if (empty($data->ngay_ct)) {
            $errorCode = $this->generateErrorCode('INFO_ERROR_NGAY_CT');
            $errors->push((object)[
                'error_code' => $errorCode,
                'error_name' => 'Thiếu ngày chứng từ',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description' => 'Ngày chứng từ không được để trống'
            ]);
        }

        return $errors;
    }

    // Thêm các phương thức kiểm tra khác ở đây
}
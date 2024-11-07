<?php

namespace App\Services;

use App\Models\BHYT\Qd130Xml1;
use App\Models\BHYT\Icd10Category;
use App\Models\BHYT\IcdYhctCategory;
use App\Models\BHYT\MedicalStaff;
use App\Models\BHYT\MedicalOrganization;
use App\Models\BHYT\AdministrativeUnit;
use App\Models\BHYT\JobCategory;
use Illuminate\Support\Collection;

class Qd130Xml1Checker
{
    protected $xmlErrorService;
    protected $commonValidationService;
    protected $prefix;

    protected $xmlType;
    protected $maxWeight;
    protected $specialDKBD;
    protected $admissionReasons;
    protected $invalidKetQuaDtri;
    protected $invalidMaLoaiRV;
    protected $bedGroupCodes;
    protected $treatmentTypeInpatient;

    public function __construct(Qd130XmlErrorService $xmlErrorService, CommonValidationService $commonValidationService)
    {
        $this->xmlErrorService = $xmlErrorService;
        $this->commonValidationService = $commonValidationService;
        $this->setConditions();
    }

    protected function setConditions()
    {
        $this->xmlType = 'XML1';
        $this->prefix = $this->xmlType . '_';

        $this->maxWeight = config('qd130xml.max_weight_patient');
        $this->specialDKBD = config('organization.correct_facility_code');
        $this->admissionReasons = [3, 4];
        $this->invalidKetQuaDtri = config('qd130xml.invalid_treatment_result');
        $this->invalidMaLoaiRV = config('qd130xml.invalid_end_type_treatment');
        $this->bedGroupCodes = config('qd130xml.bed_group_code');
        $this->treatmentTypeInpatient = config('qd130xml.treatment_type_inpatient');
    }

    protected function generateErrorCode(string $errorKey): string
    {
        return $this->prefix . $errorKey;
    }

    /**
     * Check XML1 Errors
     *
     * @param XML1 $data
     * @return void
     */
    public function checkErrors(Qd130Xml1 $data): void
    {
        // Delete errors to xml_error_checks table
        $this->xmlErrorService->deleteErrors($data->ma_lk);

        // Thực hiện kiểm tra lỗi
        $errors = collect();

        $errors = $errors->merge($this->infoChecker($data));
        //$errors = $errors->merge($this->checkReasonForAdmission($data));
        $errors = $errors->merge($this->checkLongTermTreatment($data));
        //$errors = $errors->merge($this->checkInvalidBedDays($data));
        $errors = $errors->merge($this->checkSpecialInpatientConditions($data));
        $errors = $errors->merge($this->checkDiseaseIcdCodes($data));

        // Save errors to xml_error_checks table
        $this->xmlErrorService->saveErrors($this->xmlType, $data->ma_lk, $data->stt, $errors);
    }


    private function infoChecker(Qd130Xml1 $data): Collection
    {
        $errors = collect();

        if (empty($data->ma_bn)) {
            $errorCode = $this->generateErrorCode('ADMIN_INFO_ERROR_MA_BN');
            $errors->push((object)[
                'error_code' => $errorCode,
                'error_name' => 'Thiếu mã bệnh nhân',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description' => 'Mã bệnh nhân không được để trống'
            ]);
        }

        if (empty($data->ho_ten)) {
            $errorCode = $this->generateErrorCode('ADMIN_INFO_ERROR_HO_TEN');
            $errors->push((object)[
                'error_code' => $errorCode,
                'error_name' => 'Thiếu họ tên',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description' => 'Họ tên không được để trống'
            ]);
        }

        if (empty($data->ngay_sinh)) {
            $errorCode = $this->generateErrorCode('ADMIN_INFO_ERROR_NGAY_SINH');
            $errors->push((object)[
                'error_code' => $errorCode,
                'error_name' => 'Thiếu ngày sinh',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description' => 'Ngày sinh không được để trống'
            ]);
        }

        if (empty($data->gioi_tinh)) {
            $errorCode = $this->generateErrorCode('ADMIN_INFO_ERROR_GIOI_TINH');
            $errors->push((object)[
                'error_code' => $errorCode,
                'error_name' => 'Thiếu giới tính',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description' => 'Giới tính không được để trống'
            ]);
        }

        if (empty($data->can_nang)) {
            $errorCode = $this->generateErrorCode('ADMIN_INFO_ERROR_CAN_NANG');
            $errors->push((object)[
                'error_code' => $errorCode,
                'error_name' => 'Thiếu cân nặng',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description' => 'Cân nặng không được để trống'
            ]);
        } else {
            if (is_numeric($data->can_nang)) {
                $can_nang_value = (double)$data->can_nang;
                if ($can_nang_value > $this->maxWeight) {
                    $errorCode = $this->generateErrorCode('ADMIN_INFO_ERROR_TO_MUCH_CAN_NANG');
                    $errors->push((object)[
                        'error_code' => $errorCode,
                        'error_name' => 'Cân nặng không hợp lệ',
                        'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                        'description' => 'Cân nặng không hợp lệ: ' . number_format($can_nang_value)
                    ]);
                }
            } else {
                $errorCode = $this->generateErrorCode('ADMIN_INFO_ERROR_INVALID_CAN_NANG');
                $errors->push((object)[
                    'error_code' => $errorCode,
                    'error_name' => 'Cân nặng không phải là số',
                    'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                    'description' => 'Giá trị cân nặng không phải là số: ' . $data->can_nang
                ]);
            }
        }

        if (empty($data->dia_chi)) {
            $errorCode = $this->generateErrorCode('ADMIN_INFO_ERROR_DIA_CHI');
            $errors->push((object)[
                'error_code' => $errorCode,
                'error_name' => 'Thiếu địa chỉ',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description' => 'Địa chỉ không được để trống'
            ]);
        }

        if (empty($data->so_cccd)) {
            $errorCode = $this->generateErrorCode('ADMIN_INFO_ERROR_SO_CCCD');
            $errors->push((object)[
                'error_code' => $errorCode,
                'error_name' => 'Thiếu số CCCD/Định danh cá nhân',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description' => 'Số CCCD/định danh cá nhân không được để trống'
            ]);
        }

        if (empty($data->matinh_cu_tru)) {
            $errorCode = $this->generateErrorCode('ADMIN_INFO_ERROR_MATINH_CU_TRU');
            $errors->push((object)[
                'error_code' => $errorCode,
                'error_name' => 'Thiếu mã tỉnh',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description' => 'Mã tỉnh không được để trống'
            ]);
        } else {
            $provinceExists = AdministrativeUnit::where('province_code', $data->matinh_cu_tru)
                ->where('is_active', true)
                ->exists();
            if (!$provinceExists) {
                $errorCode = $this->generateErrorCode('ADMIN_INFO_ERROR_MATINH_CU_TRU_NOT_FOUND');
                $errors->push((object)[
                    'error_code' => $errorCode,
                    'error_name' => 'Mã tỉnh không tồn tại',
                    'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                    'description' => 'Mã tỉnh không tồn tại trong danh mục: ' . $data->matinh_cu_tru
                ]);
            }
        }

        if (empty($data->mahuyen_cu_tru)) {
            $errorCode = $this->generateErrorCode('ADMIN_INFO_ERROR_MAHUYEN_CU_TRU');
            $errors->push((object)[
                'error_code' => $errorCode,
                'error_name' => 'Thiếu mã quận huyện',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description' => 'Mã quận huyện không được để trống'
            ]);
        } else {
            $districtExists = AdministrativeUnit::where('district_code', $data->mahuyen_cu_tru)
                ->where('is_active', true)
                ->exists();
            if (!$districtExists) {
                $errorCode = $this->generateErrorCode('ADMIN_INFO_ERROR_MAHUYEN_CU_TRU_NOT_FOUND');
                $errors->push((object)[
                    'error_code' => $errorCode,
                    'error_name' => 'Mã quận huyện không tồn tại',
                    'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                    'description' => 'Mã quận huyện không tồn tại trong danh mục: ' . $data->mahuyen_cu_tru
                ]);
            } else {
                $districtInProvinceExists = AdministrativeUnit::where('province_code', $data->matinh_cu_tru)
                    ->where('district_code', $data->mahuyen_cu_tru)
                    ->where('is_active', true)
                    ->exists();
                if (!$districtInProvinceExists) {
                    $errorCode = $this->generateErrorCode('ADMIN_INFO_ERROR_MAHUYEN_CU_TRU_NOT_FOUND_IN_MATINH_CU_TRU');
                    $errors->push((object)[
                        'error_code' => $errorCode,
                        'error_name' => 'Mã quận huyện không thuộc mã tỉnh',
                        'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                        'description' => 'Mã quận huyện không thuộc mã tỉnh: ' . $data->mahuyen_cu_tru . '/' . $data->matinh_cu_tru
                    ]);
                }
            }
        }

        if (empty($data->maxa_cu_tru)) {
            $errorCode = $this->generateErrorCode('ADMIN_INFO_ERROR_MAXA_CU_TRU');
            $errors->push((object)[
                'error_code' => $errorCode,
                'error_name' => 'Thiếu mã phường xã',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description' => 'Mã phường xã không được để trống'
            ]);
        } else {
            $wardExists = AdministrativeUnit::where('commune_code', $data->maxa_cu_tru)
                ->where('is_active', true)
                ->exists();
            if (!$wardExists) {
                $errorCode = $this->generateErrorCode('ADMIN_INFO_ERROR_MAXA_CU_TRU_NOT_FOUND');
                $errors->push((object)[
                    'error_code' => $errorCode,
                    'error_name' => 'Mã phường xã không tồn tại',
                    'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                    'description' => 'Mã phường xã không tồn tại trong danh mục: ' . $data->maxa_cu_tru
                ]);
            } else {
                $wardExistsInDistrict = AdministrativeUnit::where('district_code', $data->mahuyen_cu_tru)
                    ->where('commune_code', $data->maxa_cu_tru)
                    ->where('is_active', true)
                    ->exists();
                if (!$wardExistsInDistrict) {
                    $errorCode = $this->generateErrorCode('ADMIN_INFO_ERROR_MAXA_CU_TRU_NOT_FOUND_IN_MAHUYEN_CU_TRU');
                    $errors->push((object)[
                        'error_code' => $errorCode,
                        'error_name' => 'Mã phường xã không thuộc mã quận huyện',
                        'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                        'description' => 'Mã phường xã không thuộc mã quận huyện: ' . $data->maxa_cu_tru . '/' . $data->mahuyen_cu_tru
                    ]);
                }
            }
        }
        
        if (empty($data->ma_quoctich)) {
            $errorCode = $this->generateErrorCode('ADMIN_INFO_ERROR_MA_QUOCTICH');
            $errors->push((object)[
                'error_code' => $errorCode,
                'error_name' => 'Thiếu mã quốc tịch',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description' => 'Mã quốc tịch không được để trống hoặc không đúng định dạng'
            ]);
        }

        if (empty($data->ma_dantoc)) {
            $errorCode = $this->generateErrorCode('ADMIN_INFO_ERROR_MA_DANTOC');
            $errors->push((object)[
                'error_code' => $errorCode,
                'error_name' => 'Thiếu mã dân tộc',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description' => 'Mã dân tộc không được để trống'
            ]);
        }

        if (empty($data->ma_nghe_nghiep)) {
            $errorCode = $this->generateErrorCode('ADMIN_INFO_ERROR_MA_NGHE_NGHIEP');
            $errors->push((object)[
                'error_code' => $errorCode,
                'error_name' => 'Thiếu mã nghề nghiệp',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description' => 'Mã nghề nghiệp không được để trống'
            ]);
        } else {
            $jobExists = JobCategory::where('job_code', $data->ma_nghe_nghiep)->exists();
            if (!$jobExists) {
                $errorCode = $this->generateErrorCode('ADMIN_INFO_ERROR_MA_NGHE_NGHIEP_NOT_FOUND');
                $errors->push((object)[
                    'error_code' => $errorCode,
                    'error_name' => 'Mã nghề nghiệp không tồn tại',
                    'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                    'description' => 'Mã nghề nghiệp không tồn tại trong danh mục nghề nghiệp: ' . $data->ma_nghe_nghiep
                ]);
            }
        }

        if (empty($data->chan_doan_vao)) {
            $errorCode = $this->generateErrorCode('ADMIN_INFO_ERROR_CHAN_DOAN_VAO');
            $errors->push((object)[
                'error_code' => $errorCode,
                'error_name' => 'Thiếu chẩn đoán vào',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description' => 'Chẩn đoán vào không được để trống'
            ]);
        }

        if (empty($data->chan_doan_rv)) {
            $errorCode = $this->generateErrorCode('ADMIN_INFO_ERROR_CHAN_DOAN_RV');
            $errors->push((object)[
                'error_code' => $errorCode,
                'error_name' => 'Thiếu chẩn đoán ra viện',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description' => 'Chẩn đoán ra viện không được để trống'
            ]);
        }

        if (empty($data->ma_benh_chinh)) {
            $errorCode = $this->generateErrorCode('ADMIN_INFO_ERROR_MA_BENH_CHINH');
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
                $errorCode = $this->generateErrorCode('ADMIN_INFO_ERROR_MA_BENH_KT');
                $errors->push((object)[
                    'error_code' => $errorCode,
                    'error_name' => 'Mã bệnh kèm theo vượt quá 12 mã',
                    'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                    'description' => 'Mã bệnh kèm theo không được vượt quá 12 mã'
                ]);
            }
        }

        if (empty($data->ma_doituong_kcb)) {
            $errorCode = $this->generateErrorCode('ADMIN_INFO_ERROR_MA_DOITUONG_KCB');
            $errors->push((object)[
                'error_code' => $errorCode,
                'error_name' => 'Thiếu mã đối tượng KCB',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description' => 'Mã đối tượng KCB không được để trống'
            ]);
        } else {
            $is_ma_doituong_kcb_invalid = false;

            foreach (config('qd130xml.xml1.ma_doituong_kcb_trai_tuyen') as $ma_doituong) {
                if (strpos($data->ma_doituong_kcb, (string)$ma_doituong) === 0) {
                    $is_ma_doituong_kcb_invalid = true;
                    break;
                }
            }

            if ($is_ma_doituong_kcb_invalid && in_array($data->ma_dkbd, $this->specialDKBD)) {
                $errorCode = $this->generateErrorCode('ADMIN_INFO_ERROR_MA_DOITUONG_KCB_INVALID');
                $errors->push((object)[
                    'error_code' => $errorCode,
                    'error_name' => 'Mã đối tượng KCB không hợp lệ',
                    'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                    'description' => 'Mã đối tượng KCB Trái tuyến nhưng thẻ BHYT Đúng tuyến'
                ]);                
            }
        }

        if (empty($data->ngay_vao)) {
            $errorCode = $this->generateErrorCode('ADMIN_INFO_ERROR_NGAY_VAO');
            $errors->push((object)[
                'error_code' => $errorCode,
                'error_name' => 'Thiếu ngày vào',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description' => 'Ngày vào không được để trống'
            ]);
        }

        if (empty($data->ngay_ra)) {
            $errorCode = $this->generateErrorCode('ADMIN_INFO_ERROR_NGAY_RA');
            $errors->push((object)[
                'error_code' => $errorCode,
                'error_name' => 'Thiếu ngày ra',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description' => 'Ngày ra không được để trống'
            ]);
        }

        if (empty($data->nam_qt)) {
            $errorCode = $this->generateErrorCode('ADMIN_INFO_ERROR_NAM_QT');
            $errors->push((object)[
                'error_code' => $errorCode,
                'error_name' => 'Thiếu năm quyết toán',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description' => 'Năm quyết toán không được để trống'
            ]);
        }

        if (empty($data->thang_qt)) {
            $errorCode = $this->generateErrorCode('ADMIN_INFO_ERROR_THANG_QT');
            $errors->push((object)[
                'error_code' => $errorCode,
                'error_name' => 'Thiếu tháng quyết toán',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description' => 'Tháng quyết toán không được để trống'
            ]);
        }

        if (empty($data->ma_loai_kcb)) {
            $errorCode = $this->generateErrorCode('ADMIN_INFO_ERROR_MA_LOAI_KCB');
            $errors->push((object)[
                'error_code' => $errorCode,
                'error_name' => 'Thiếu mã loại KCB',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description' => 'Mã loại KCB không được để trống'
            ]);
        }

        if (empty($data->ket_qua_dtri)) {
            $errorCode = $this->generateErrorCode('ADMIN_INFO_ERROR_KET_QUA_DTRI');
            $errors->push((object)[
                'error_code' => $errorCode,
                'error_name' => 'Thiếu kết quả điều trị',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description' => 'Kết quả điều trị không được để trống'
            ]);
        }

        if (empty($data->ma_loai_rv)) {
            $errorCode = $this->generateErrorCode('ADMIN_INFO_ERROR_MA_LOAI_RV');
            $errors->push((object)[
                'error_code' => $errorCode,
                'error_name' => 'Thiếu mã loại ra viện',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description' => 'Mã loại ra viện không được để trống'
            ]);
        }

        if (empty($data->ma_khoa)) {
            $errorCode = $this->generateErrorCode('ADMIN_INFO_ERROR_MA_KHOA');
            $errors->push((object)[
                'error_code' => $errorCode,
                'error_name' => 'Thiếu mã khoa',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description' => 'Mã khoa không được để trống'
            ]);
        }

        if (empty($data->ma_cskcb)) {
            $errorCode = $this->generateErrorCode('ADMIN_INFO_ERROR_MA_CSKCB');
            $errors->push((object)[
                'error_code' => $errorCode,
                'error_name' => 'Thiếu mã cơ sở KCB',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description' => 'Mã cơ sở KCB không được để trống'
            ]);
        } else {
            // Kiểm tra nếu ma_cskcb không có trong MedicalOrganization
            $organizationExists = MedicalOrganization::where('ma_cskcb', $data->ma_cskcb)->exists();

            if (!$organizationExists) {
                $errorCode = $this->generateErrorCode('ADMIN_INFO_ERROR_MA_CSKCB_NOT_FOUND');
                $errors->push((object)[
                    'error_code' => $errorCode,
                    'error_name' => 'Mã cơ sở KCB không có trong danh mục',
                    'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                    'description' => 'Mã cơ sở KCB: ' . $data->ma_cskcb . ' không có trong danh mục CSKCB'
                ]);
            }
        }

        // Kiểm tra mã nơi đến
        if (!empty($data->ma_noi_den)) {
            $destinationExists = MedicalOrganization::where('ma_cskcb', $data->ma_noi_den)->exists();

            if (!$destinationExists) {
                $errorCode = $this->generateErrorCode('ADMIN_INFO_ERROR_MA_NOI_DEN_NOT_FOUND');
                $errors->push((object)[
                    'error_code' => $errorCode,
                    'error_name' => 'Mã nơi đến không có trong danh mục',
                    'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                    'description' => 'Mã nơi đến: ' . $data->ma_noi_den . ' không có trong danh mục CSKCB'
                ]);
            }
        }

        // Kiểm tra mã đơn vị khám bệnh đa khoa ban đầu
        if (!empty($data->ma_dkbd)) {
            $maDkbdList = explode(';', $data->ma_dkbd); // Tách các mã DKBD phân cách bởi dấu ";"
            foreach ($maDkbdList as $maDkbd) {
                $initialExaminationUnitExists = MedicalOrganization::where('ma_cskcb', trim($maDkbd))->exists();

                if (!$initialExaminationUnitExists) {
                    $errorCode = $this->generateErrorCode('ADMIN_INFO_ERROR_MA_DKBD_NOT_FOUND');
                    $errors->push((object)[
                        'error_code' => $errorCode,
                        'error_name' => 'Mã đăng ký ban đầu không có trong danh mục',
                        'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                        'description' => 'Mã đăng ký ban đầu: ' . $maDkbd . ' không có trong danh mục CSKCB'
                    ]);
                }
            }
        }

        // Kiểm tra mã nơi đi
        if (!empty($data->ma_noi_di)) {
            $departureExists = MedicalOrganization::where('ma_cskcb', $data->ma_noi_di)->exists();

            if (!$departureExists) {
                $errorCode = $this->generateErrorCode('ADMIN_INFO_ERROR_MA_NOI_DI_NOT_FOUND');
                $errors->push((object)[
                    'error_code' => $errorCode,
                    'error_name' => 'Mã nơi đi không có trong danh mục',
                    'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                    'description' => 'Mã nơi đi: ' . $data->ma_noi_di . ' không có trong danh mục CSKCB'
                ]);
            }

            // Kiểm tra giấy chuyển tuyến
            if (empty($data->giay_chuyen_tuyen)) {
                $errorCode = $this->generateErrorCode('ADMIN_INFO_ERROR_GIAY_CHUYEN_TUYEN');
                $errors->push((object)[
                    'error_code' => $errorCode,
                    'error_name' => 'Thiếu số giấy chuyển tuyến',
                    'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                    'description' => 'Số giấy chuyển tuyến không được để trống khi chuyển từ CSKCB khác'
                ]);
            }
        }

        if (empty($data->ma_hsba)) {
            $errorCode = $this->generateErrorCode('ADMIN_INFO_ERROR_MA_HSBA');
            $errors->push((object)[
                'error_code' => $errorCode,
                'error_name' => 'Thiếu mã hồ sơ bệnh án',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description' => 'Mã hồ sơ bệnh án không được để trống'
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
            if (!$this->commonValidationService->isMedicalStaffValid($data->ma_ttdv, 'ma_bhxh')) {
                $errorCode = $this->generateErrorCode('INVALID_MEDICAL_STAFF_MA_TTDV');
                $errors->push((object)[
                    'error_code' => $errorCode,
                    'error_name' => 'Mã thủ trưởng đơn vị chưa được duyệt',
                    'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                    'description' => 'Mã thủ trưởng đơn vị chưa được duyệt danh mục: ' . $data->ma_ttdv
                ]);
            }
        }

        if (!empty($data->ma_the_bhyt)) {
            $maTheBhytList = explode(';', $data->ma_the_bhyt);
            $maDkbdList = explode(';', $data->ma_dkbd);
            $gtTheTuList = explode(';', $data->gt_the_tu);
            $gtTheDenList = explode(';', $data->gt_the_den);

            $numberOfElements = count($maTheBhytList);

            if ($numberOfElements >= 1) {
                if (
                    count($maDkbdList) != $numberOfElements || 
                    count($gtTheTuList) != $numberOfElements || 
                    count($gtTheDenList) != $numberOfElements
                ) {
                    $errorCode = $this->generateErrorCode('ADMIN_INFO_ERROR_MISMATCH_COUNT');
                    $errors->push((object)[
                        'error_code' => $errorCode,
                        'error_name' => 'Số lượng các thành phần không khớp',
                        'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                        'description' => 'Mã thẻ BHYT: ' . $data->ma_the_bhyt . ', mã ĐKBĐ: ' .$data->ma_dkbd  . ', GT từ ngày : ' . $data->gt_the_tu . ', GT đến ngày: ' . $data->gt_the_den . ' phải tương đồng'
                    ]);
                }
            }

            if (empty($data->ma_dkbd)) {
                $errorCode = $this->generateErrorCode('ADMIN_INFO_ERROR_MA_DKBD');
                $errors->push((object)[
                    'error_code' => $errorCode,
                    'error_name' => 'Thiếu mã đăng ký khám bệnh ban đầu',
                    'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                    'description' => 'Mã đăng ký khám bệnh ban đầu không được để trống khi có mã thẻ BHYT'
                ]);
            }

            if (empty($data->gt_the_tu)) {
                $errorCode = $this->generateErrorCode('ADMIN_INFO_ERROR_GT_THE_TU');
                $errors->push((object)[
                    'error_code' => $errorCode,
                    'error_name' => 'Thiếu giá trị thẻ từ ngày',
                    'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                    'description' => 'Giá trị thẻ từ ngày không được để trống khi có mã thẻ BHYT'
                ]);
            }

            if (empty($data->gt_the_den)) {
                $errorCode = $this->generateErrorCode('ADMIN_INFO_ERROR_GT_THE_DEN');
                $errors->push((object)[
                    'error_code' => $errorCode,
                    'error_name' => 'Thiếu giá trị thẻ đến ngày',
                    'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                    'description' => 'Giá trị thẻ đến ngày không được để trống khi có mã thẻ BHYT'
                ]);
            }

            if (empty($data->ly_do_vv)) {
                $errorCode = $this->generateErrorCode('ADMIN_INFO_ERROR_LY_DO_VV');
                $errors->push((object)[
                    'error_code' => $errorCode,
                    'error_name' => 'Thiếu lý do vào viện',
                    'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                    'description' => 'Lý do vào viện không được để trống'
                ]);
            }
        }
        
        return $errors;
    }

    /**
     * Check for reason for admission errors
     *
     * @param XML1 $data
     * @return Collection
     */
    private function checkReasonForAdmission(Qd130Xml1 $data): Collection
    {
        $errors = collect();

        if (in_array($data->ma_dkbd, $this->specialDKBD) && in_array($data->ma_loai_kcb, $this->admissionReasons)) {
            $errorCode = $this->generateErrorCode('REASON_ERROR_SPECIAL');
            $errors->push((object)[
                'error_code' => $errorCode,
                'error_name' => 'Lý do vào viện không hợp lệ với MA_DKBD đúng tuyến',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description' => 'Lý do vào viện không hợp lệ với MA_DKBD thuộc danh sách đặc biệt'
            ]);
        } elseif (!in_array($data->ma_dkbd, $this->specialDKBD) && $data->ma_loai_kcb == 4) {
            $errorCode = $this->generateErrorCode('REASON_ERROR_NON_SPECIAL');
            $errors->push((object)[
                'error_code' => $errorCode,
                'error_name' => 'Lý do vào viện không hợp lệ với MA_DKBD trái tuyến',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description' => 'Lý do vào viện không hợp lệ với MA_DKBD không thuộc danh sách đặc biệt'
            ]);
        } elseif ($data->ma_loai_kcb == 1 && !in_array($data->ma_dkbd, $this->specialDKBD) && empty($data->ma_noi_di)) {
            $errorCode = $this->generateErrorCode('REASON_ERROR_LYDO_1');
            $errors->push((object)[
                'error_code' => $errorCode,
                'error_name' => 'Đúng tuyến nhưng Nơi DKBD <> CSKCB và Không có nơi chuyển đến',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description' => 'Lý do vào viện không hợp lệ: Đúng tuyến nhưng Nơi DKBD <> CSKCB và Không có nơi chuyển đến'
            ]);
        }

        return $errors;
    }

    /**
     * Check for long-term treatment errors
     *
     * @param XML1 $data
     * @return Collection
     */
    private function checkLongTermTreatment(Qd130Xml1 $data): Collection
    {
        $errors = collect();

        if ($data->ma_loai_kcb == 1) {
            $ngayVao = \DateTime::createFromFormat('YmdHi', $data->ngay_vao);
            $ngayRa = \DateTime::createFromFormat('YmdHi', $data->ngay_ra);

            if ($ngayVao && $ngayRa) {
                $diff = $ngayRa->diff($ngayVao);
                if ($diff->days > 1) {
                    $errorCode = $this->generateErrorCode('LONG_TERM_TREATMENT');
                    $errors->push((object)[
                        'error_code' => $errorCode,
                        'error_name' => 'Khám bệnh dài ngày',
                        'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                        'description' => 'Điều trị kéo dài quá 1 ngày với MA_LOAI_KCB là 1'
                    ]);
                }
            }
        }

        return $errors;
    }

    /**
     * Check for invalid bed days errors
     *
     * @param XML1 $data
     * @return Collection
     */
    private function checkInvalidBedDays(Qd130Xml1 $data): Collection
    {
        $errors = collect();

        if (in_array($data->ma_loai_kcb, $this->treatmentTypeInpatient) && $data->so_ngay_dtri >= 2 &&
            (!in_array($data->ket_qua_dtri, $this->invalidKetQuaDtri) ||
            !in_array($data->ma_loai_rv, $this->invalidMaLoaiRV))) {

            $totalBedDays = $data->Qd130Xml3()->whereIn('ma_nhom', $this->bedGroupCodes)->sum('so_luong');

            if ($totalBedDays >= $data->so_ngay_dtri) {
                $errorCode = $this->generateErrorCode('INVALID_BED_DAYS');
                $errors->push((object)[
                    'error_code' => $errorCode,
                    'error_name' => 'Thanh toán ngày giường sai quy định (trừ trường hợp đặc biệt)',
                    'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                    'description' => 'Thanh toán ngày giường sai quy định (trừ trường hợp đặc biệt)'
                ]);
            }
        }

        return $errors;
    }
    
    /**
     * Check for special inpatient conditions errors
     *
     * @param Qd130Xml1 $data
     * @return Collection
     */
    private function checkSpecialInpatientConditions(Qd130Xml1 $data): Collection
    {
        $errors = collect();

        if (in_array($data->ma_loai_kcb, $this->treatmentTypeInpatient)) {
            if (empty($data->ly_do_vnt)) {
                $errorCode = $this->generateErrorCode('SPECIAL_INPATIENT_ERROR_LY_DO_VNT');
                $errors->push((object)[
                    'error_code' => $errorCode,
                    'error_name' => 'Thiếu lý do vào nội trú',
                    'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                    'description' => 'Lý do vào nội trú không được để trống'
                ]);
            }

            if (empty($data->ma_ly_do_vnt)) {
                $errorCode = $this->generateErrorCode('SPECIAL_INPATIENT_ERROR_MA_LY_DO_VNT');
                $errors->push((object)[
                    'error_code' => $errorCode,
                    'error_name' => 'Thiếu mã lý do vào nội trú',
                    'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                    'description' => 'Mã lý do vào nội trú không được để trống'
                ]);
            } elseif (strlen($data->ma_ly_do_vnt) > 5) {
                $errorCode = $this->generateErrorCode('SPECIAL_INPATIENT_ERROR_MA_LY_DO_VNT_LENGTH');
                $errors->push((object)[
                    'error_code' => $errorCode,
                    'error_name' => 'Mã lý do vào nội trú không hợp lệ',
                    'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                    'description' => 'Mã lý do vào nội trú: ' . $data->ma_ly_do_vnt . ' vượt quá 5 ký tự'
                ]);
            }

            if (empty($data->ngay_vao_noi_tru)) {
                $errorCode = $this->generateErrorCode('SPECIAL_INPATIENT_ERROR_NGAY_VAO_NOI_TRU');
                $errors->push((object)[
                    'error_code' => $errorCode,
                    'error_name' => 'Thiếu ngày vào nội trú',
                    'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                    'description' => 'Ngày vào nội trú không được để trống'
                ]);
            }

            if (empty($data->pp_dieu_tri)) {
                $errorCode = $this->generateErrorCode('SPECIAL_INPATIENT_ERROR_PP_DIEU_TRI');
                $errors->push((object)[
                    'error_code' => $errorCode,
                    'error_name' => 'Thiếu phương pháp điều trị',
                    'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                    'description' => 'Phương pháp điều trị không được để trống'
                ]);
            }
        }

        return $errors;
    }

    /**
     * Check for disease codes errors
     *
     * @param Qd130Xml1 $data
     * @return Collection
     */
    private function checkDiseaseIcdCodes(Qd130Xml1 $data): Collection
    {
        $errors = collect();

        // Check ma_benh_chinh
        if (!Icd10Category::where('icd_code', $data->ma_benh_chinh)->where('is_active', true)->exists()) {
            $existIcdYhct = IcdYhctCategory::where('icd_code', $data->ma_benh_chinh)->where('is_active', true)->first();
            if($existIcdYhct) {
                $errorCode = $this->generateErrorCode('DISEASE_ICD_CODE_ERROR_MA_BENH_CHINH_IN_YHCT');
                $errors->push((object)[
                    'error_code' => $errorCode,
                    'error_name' => 'Mã bệnh chính thuộc bệnh YHCT',
                    'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                    'description' => 'Mã bệnh chính: ' . $data->ma_benh_chinh . ' thuộc DM YHCT tương đương với: ' . $existIcdYhct->icd10_code . ' trong DM ICD10'
                ]); 
            } else {
                $errorCode = $this->generateErrorCode('DISEASE_ICD_CODE_ERROR_MA_BENH_CHINH');
                $errors->push((object)[
                    'error_code' => $errorCode,
                    'error_name' => 'Mã bệnh chính không tồn tại',
                    'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                    'description' => 'Mã bệnh chính không tồn tại trong danh mục ICD10: ' . $data->ma_benh_chinh
                ]);                
            }
        }

        // Check ma_benh_kt
        if (!empty($data->ma_benh_kt)) {
            $ma_benh_kt_array = explode(';', $data->ma_benh_kt);
            foreach ($ma_benh_kt_array as $ma_benh_kt) {
                if (!Icd10Category::where('icd_code', $ma_benh_kt)->where('is_active', true)->exists()) {
                    $existIcdYhct = IcdYhctCategory::where('icd_code', $ma_benh_kt)->where('is_active', true)->first();
                    if($existIcdYhct) {
                        $errorCode = $this->generateErrorCode('DISEASE_ICD_CODE_ERROR_MA_BENH_KT_IN_YHCT');
                        $errors->push((object)[
                            'error_code' => $errorCode,
                            'error_name' => 'Mã bệnh kèm theo thuộc bệnh YHCT',
                            'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                            'description' => 'Mã bệnh kèm theo: ' . $ma_benh_kt .' thuộc DM YHCT tương đương với: ' . $existIcdYhct->icd10_code . ' trong DM ICD10'
                        ]);
                    } else {
                        $errorCode = $this->generateErrorCode('DISEASE_ICD_CODE_ERROR_MA_BENH_KT');
                        $errors->push((object)[
                            'error_code' => $errorCode,
                            'error_name' => 'Mã bệnh kèm theo không tồn tại',
                            'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                            'description' => 'Mã bệnh kèm theo không tồn tại trong danh mục ICD10: ' . $ma_benh_kt
                        ]);                        
                    }
                }
            }
        }

        // Check ma_benh_yhct
        if (!empty($data->ma_benh_yhct)) {
            $ma_benh_yhct_array = explode(';', $data->ma_benh_yhct);
            foreach ($ma_benh_yhct_array as $ma_benh_yhct) {
                if (!IcdYhctCategory::where('icd_code', $ma_benh_yhct)->where('is_active', true)->exists()) {
                    $errorCode = $this->generateErrorCode('DISEASE_ICD_CODE_ERROR_MA_BENH_YHCT');
                    $errors->push((object)[
                        'error_code' => $errorCode,
                        'error_name' => 'Mã bệnh YHCT không tồn tại',
                        'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                        'description' => 'Mã bệnh YHCT không tồn tại trong danh mục ICD YHCT: ' . $ma_benh_yhct
                    ]);
                }
            }
        }

        return $errors;
    }

    // Thêm các phương thức kiểm tra khác ở đây
}
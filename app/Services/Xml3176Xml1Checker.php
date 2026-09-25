<?php

namespace App\Services;

use App\Models\BHYT\Xml3176Xml1;
use App\Services\Xml3176\Support\BenhPl1Matcher;
use App\Services\Xml3176\Support\DanhSachPhanCachParser;
use App\Services\Xml3176\Support\DoiTuongKcbCatalog;
use App\Services\Xml3176\Support\TheTamSoSinh;
use App\Services\Xml3176\Support\Xml3176DateHelper;
use Illuminate\Support\Collection;

class Xml3176Xml1Checker
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

    public function __construct(Xml3176ErrorService $xmlErrorService, CommonValidationService $commonValidationService)
    {
        $this->xmlErrorService = $xmlErrorService;
        $this->commonValidationService = $commonValidationService;
        $this->setConditions();
    }

    protected function setConditions()
    {
        $this->xmlType = 'XML1';
        $this->prefix = $this->xmlType . '_';

        $this->maxWeight = config('xml3176.max_weight_patient');
        $this->specialDKBD = config('organization.correct_facility_code');
        $this->admissionReasons = [3, 4];
        $this->invalidKetQuaDtri = config('xml3176.invalid_treatment_result');
        $this->invalidMaLoaiRV = config('xml3176.invalid_end_type_treatment');
        $this->bedGroupCodes = config('xml3176.bed_group_code');
        $this->treatmentTypeInpatient = config('xml3176.treatment_type_inpatient');
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
    public function checkErrors(Xml3176Xml1 $data): void
    {
        // Khong con xoa loi o day: deleteErrors() xoa TOAN BO loi cua ho so, nen mot lan
        // retry cua job XML1 se xoa sach ket qua ma 11 job kia vua tim ra. Viec don loi
        // nay do CheckXml3176TypeJob lam theo tung loai, va do luc nhap lai ho so.

        // Thực hiện kiểm tra lỗi
        $errors = collect();

        $errors = $errors->merge($this->infoChecker($data));
        //$errors = $errors->merge($this->checkReasonForAdmission($data));
        $errors = $errors->merge($this->checkLongTermTreatment($data));
        //$errors = $errors->merge($this->checkInvalidBedDays($data));
        $errors = $errors->merge($this->checkSpecialInpatientConditions($data));
        $errors = $errors->merge($this->checkDiseaseIcdCodes($data));
        $errors = $errors->merge($this->checkWarningDiseaseCodes($data));
        $errors = $errors->merge($this->checkMaLoaiKcbKhongTinhNgayDieuTri($data));
        $errors = $errors->merge($this->checkNgaySinhVsNgayVao($data));
        $errors = $errors->merge($this->checkMaKhuVuc($data));
        $errors = $errors->merge($this->checkDoiTuongKcb($data));

        // Save errors to xml_error_checks table
        $this->xmlErrorService->saveErrors($this->xmlType, $data->ma_lk, $data->stt, $errors);
    }


    private function infoChecker(Xml3176Xml1 $data): Collection
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
            $provinceExists = $this->commonValidationService->isAdministrativeUnitProvinceValid($data->matinh_cu_tru);
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

        if (empty($data->maxa_cu_tru)) {
            $errorCode = $this->generateErrorCode('ADMIN_INFO_ERROR_MAXA_CU_TRU');
            $errors->push((object)[
                'error_code' => $errorCode,
                'error_name' => 'Thiếu mã phường xã',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description' => 'Mã phường xã không được để trống'
            ]);
        } else {
            $wardExists = $this->commonValidationService->isAdministrativeUnitCommuneValid($data->maxa_cu_tru);
            if (!$wardExists) {
                $errorCode = $this->generateErrorCode('ADMIN_INFO_ERROR_MAXA_CU_TRU_NOT_FOUND');
                $errors->push((object)[
                    'error_code' => $errorCode,
                    'error_name' => 'Mã phường xã không tồn tại',
                    'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                    'description' => 'Mã phường xã không tồn tại trong danh mục: ' . $data->maxa_cu_tru
                ]);
            }
        }

        // Xa phai thuoc tinh - quan he long nhau duy nhat con lai sau khi bo cap huyen.
        // CHI chay khi ca hai ma da hop le rieng le: neu mot ma sai thi loi do da duoc bao
        // roi, bao them loi long nhau chi la nhieu tren cung mot nguyen nhan.
        // Dung lai $provinceExists/$wardExists da tinh o hai khoi tren thay vi truy van lai:
        // PHP pham vi bien theo HAM, va guard !empty(...) dung truoc bao dam hai bien do da
        // duoc gan. Goi lai la +2 truy van moi ho so, tra ve dung thu vua co.
        if (!empty($data->matinh_cu_tru) && !empty($data->maxa_cu_tru)
            && $provinceExists && $wardExists
            && !$this->commonValidationService->isAdministrativeUnitWardInProvinceValid($data->matinh_cu_tru, $data->maxa_cu_tru)) {
            $errorCode = $this->generateErrorCode('ADMIN_INFO_ERROR_MAXA_NOT_IN_MATINH');
            $errors->push((object)[
                'error_code' => $errorCode,
                'error_name' => 'Mã xã không thuộc tỉnh cư trú',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description' => 'Mã xã ' . $data->maxa_cu_tru . ' không thuộc tỉnh ' . $data->matinh_cu_tru
            ]);
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
            $jobExists = $this->commonValidationService->isJobCategoryValid($data->ma_nghe_nghiep);

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
            // Khop DUNG BANG, khong khop tien to: ma doi tuong dung dang phan cap co dau
            // cham nen strpos()===0 lam '3' nuot ca 3.1, 3.2, 3.3, 3.6 - trong khi danh
            // muc chi giam muc huong cho 3.1, ba ma kia huong 100%.
            $is_ma_doituong_kcb_invalid = in_array(
                trim((string) $data->ma_doituong_kcb),
                (array) config('xml3176.xml1.ma_doituong_kcb_trai_tuyen', []),
                true
            );

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
            if (!$this->commonValidationService->isMedicalOrganizationValid($data->ma_cskcb)) {
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
            if (!$this->commonValidationService->isMedicalOrganizationValid($data->ma_noi_den)) {
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
        $errors = $errors->merge($this->checkMaDkbdKhongCoTrongDanhMuc($data));

        // Kiểm tra mã nơi đi
        if (!empty($data->ma_noi_di)) {
            if (!$this->commonValidationService->isMedicalOrganizationValid($data->ma_noi_di)) {
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
            if (!$this->commonValidationService->isMedicalStaffValid($data->ma_ttdv)) {
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
     * Mã ĐKBĐ không có trong danh mục CSKCB. Ghép mỗi mã ĐKBĐ với mã thẻ CÙNG VỊ TRÍ (hồ sơ
     * nhiều thẻ nối bằng ';'); thẻ tạm trẻ sơ sinh (TE1 + ĐKBĐ XX000) bỏ qua vì XX000 không
     * phải CSKCB thật - cả 3/3 hồ sơ XX000 trên CSDL thật từng bị báo giả.
     */
    private function checkMaDkbdKhongCoTrongDanhMuc(Xml3176Xml1 $data): Collection
    {
        $errors = collect();
        if (empty($data->ma_dkbd)) {
            return $errors;
        }

        $maTheList = explode(';', (string) $data->ma_the_bhyt);
        foreach (explode(';', $data->ma_dkbd) as $i => $maDkbd) {
            $maThe = isset($maTheList[$i]) ? $maTheList[$i] : null;
            if (TheTamSoSinh::la($maThe, $maDkbd)) {
                continue;
            }

            if (!$this->commonValidationService->isMedicalOrganizationValid(trim($maDkbd))) {
                $errorCode = $this->generateErrorCode('ADMIN_INFO_ERROR_MA_DKBD_NOT_FOUND');
                $errors->push((object)[
                    'error_code' => $errorCode,
                    'error_name' => 'Mã đăng ký ban đầu không có trong danh mục',
                    'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                    'description' => 'Mã đăng ký ban đầu: ' . $maDkbd . ' không có trong danh mục CSKCB'
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
    private function checkReasonForAdmission(Xml3176Xml1 $data): Collection
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
    private function checkLongTermTreatment(Xml3176Xml1 $data): Collection
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
    private function checkInvalidBedDays(Xml3176Xml1 $data): Collection
    {
        $errors = collect();

        if (in_array($data->ma_loai_kcb, $this->treatmentTypeInpatient) && $data->so_ngay_dtri >= 2 &&
            (!in_array($data->ket_qua_dtri, $this->invalidKetQuaDtri) ||
            !in_array($data->ma_loai_rv, $this->invalidMaLoaiRV))) {

            $totalBedDays = $data->Xml3176Xml3()->whereIn('ma_nhom', $this->bedGroupCodes)->sum('so_luong');

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
     * @param Xml3176Xml1 $data
     * @return Collection
     */
    private function checkSpecialInpatientConditions(Xml3176Xml1 $data): Collection
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
     * @param Xml3176Xml1 $data
     * @return Collection
     */
    private function checkDiseaseIcdCodes(Xml3176Xml1 $data): Collection
    {
        $errors = collect();

        // Check ma_benh_chinh
        if (!$this->commonValidationService->isIcd10CategoryValid($data->ma_benh_chinh)) {
            $existIcdYhct = $this->commonValidationService->isIcdYhctCategoryValue($data->ma_benh_chinh);
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
                if (!$this->commonValidationService->isIcd10CategoryValid($ma_benh_kt)) {
                    $existIcdYhct = $this->commonValidationService->isIcdYhctCategoryValue($ma_benh_kt);
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
                if (!$this->commonValidationService->isIcdYhctCategoryValid($ma_benh_yhct)) {
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

    /**
     * Cảnh báo khi ma_benh_chinh hoặc ma_benh_kt thuộc danh sách mã bệnh cảnh báo (config xml3176.xml1.ma_benh_canh_bao)
     *
     * @param Xml3176Xml1 $data
     * @return Collection
     */
    private function checkWarningDiseaseCodes(Xml3176Xml1 $data): Collection
    {
        $errors = collect();

        $warningCodes = config('xml3176.xml1.ma_benh_canh_bao', []);
        if (empty($warningCodes)) {
            return $errors;
        }

        // Chuẩn hoá danh sách cảnh báo về 3 ký tự đầu, viết hoa
        $warningPrefixes = array_unique(array_filter(array_map(function ($code) {
            return strtoupper(substr(trim($code), 0, 3));
        }, $warningCodes)));

        // Check ma_benh_chinh - so khớp 3 ký tự đầu
        if (!empty($data->ma_benh_chinh)) {
            $prefix = strtoupper(substr(trim($data->ma_benh_chinh), 0, 3));
            if (in_array($prefix, $warningPrefixes, true)) {
                $errorCode = $this->generateErrorCode('WARNING_MA_BENH_CHINH');
                $errors->push((object)[
                    'error_code' => $errorCode,
                    'error_name' => 'Mã bệnh chính thuộc nhóm bệnh cảnh báo',
                    'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                    'description' => 'Mã bệnh chính: ' . $data->ma_benh_chinh . ' thuộc nhóm cảnh báo (' . $prefix . ')'
                ]);
            }
        }

        // Check ma_benh_kt - gộp tất cả mã cảnh báo vào 1 error record
        if (!empty($data->ma_benh_kt)) {
            $ma_benh_kt_array = array_filter(array_map('trim', preg_split('/[;,|\s]+/', $data->ma_benh_kt)));
            $matched = [];
            foreach ($ma_benh_kt_array as $code) {
                $prefix = strtoupper(substr($code, 0, 3));
                if (in_array($prefix, $warningPrefixes, true)) {
                    $matched[] = $code . ' (' . $prefix . ')';
                }
            }

            if (!empty($matched)) {
                $errorCode = $this->generateErrorCode('WARNING_MA_BENH_KT');
                $errors->push((object)[
                    'error_code' => $errorCode,
                    'error_name' => 'Mã bệnh kèm theo thuộc nhóm bệnh cảnh báo',
                    'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                    'description' => 'Mã bệnh kèm theo thuộc nhóm cảnh báo: ' . implode(', ', $matched)
                ]);
            }
        }

        return $errors;
    }

    private function checkMaLoaiKcbKhongTinhNgayDieuTri(Xml3176Xml1 $data): Collection
    {
        $errors = collect();

        if (in_array($data->ma_loai_kcb, config('xml3176.xml1.ma_loai_kcb_khong_tinh_ngay_dieu_tri'))
        && ($data->so_ngay_dtri > 0)) {
            $errorCode = $this->generateErrorCode('MA_LOAI_KCB_KHONG_TINH_NGAY_DIEU_TRI');
            $errors->push((object)[
                'error_code' => $errorCode,
                'error_name' => 'Loại KCB không tính ngày điều trị',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description' => 'Mã loại KCB không tính ngày điều trị: ' . $data->ma_loai_kcb . ' và số ngày điều trị: ' . $data->so_ngay_dtri
            ]);
        }

        return $errors;
    }

    /**
     * #140 — Ngày sinh không được lớn hơn ngày vào viện.
     */
    private function checkNgaySinhVsNgayVao(Xml3176Xml1 $data): Collection
    {
        $errors = collect();

        $ns = Xml3176DateHelper::datePart($data->ngay_sinh);
        $nv = Xml3176DateHelper::datePart($data->ngay_vao);
        if ($ns !== null && $nv !== null && $ns > $nv) {
            $errorCode = $this->generateErrorCode('NGAY_SINH_GREATER_NGAY_VAO');
            $errors->push((object) [
                'error_code'     => $errorCode,
                'error_name'     => 'Ngày sinh lớn hơn ngày vào viện',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description'    => 'Ngày sinh (' . strtodatetime($data->ngay_sinh) . ') lớn hơn ngày vào viện (' . strtodatetime($data->ngay_vao) . ')',
            ]);
        }

        return $errors;
    }

    /**
     * MA_KHUVUC ghi noi sinh song cua nguoi benh theo the BHYT: K1, K2 hoac K3.
     */
    private function checkMaKhuVuc(Xml3176Xml1 $data): Collection
    {
        $errors = collect();
        $ma = strtoupper(trim((string) $data->ma_khuvuc));

        if ($ma === '') {
            return $errors;
        }

        if (!in_array($ma, ['K1', 'K2', 'K3'], true)) {
            $errorCode = $this->generateErrorCode('ADMIN_INFO_ERROR_MA_KHUVUC');
            $errors->push((object)[
                'error_code' => $errorCode,
                'error_name' => 'Mã khu vực không hợp lệ',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description' => 'MA_KHUVUC = ' . $data->ma_khuvuc . '. Chuẩn chỉ quy định K1, K2 hoặc K3',
            ]);
        }

        return $errors;
    }

    /**
     * Kiem MA_DOITUONG_KCB theo danh muc ma doi tuong den KCB do Bo Y te ban hanh.
     *
     * Ma rong thi im lang - da co ADMIN_INFO_ERROR_MA_DOITUONG_KCB lo viec do.
     */
    private function checkDoiTuongKcb(Xml3176Xml1 $data): Collection
    {
        $errors = collect();
        $ma = DoiTuongKcbCatalog::chuanHoa($data->ma_doituong_kcb);

        if ($ma === '') {
            return $errors;
        }

        $danhMuc = (array) config('doi_tuong_kcb', []);

        if (!DoiTuongKcbCatalog::coTrongDanhMuc($ma, $danhMuc)) {
            $errorCode = $this->generateErrorCode('DOI_TUONG_KCB_NGOAI_DANH_MUC');
            $errors->push((object)[
                'error_code' => $errorCode,
                'error_name' => 'Mã đối tượng KCB ngoài danh mục',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description' => 'Mã đối tượng KCB "' . $ma . '" không có trong danh mục mã đối tượng '
                    . 'đến khám bệnh, chữa bệnh do Bộ Y tế ban hành',
            ]);

            return $errors; // ma la thi moi kiem tra dua tren thuoc tinh deu vo nghia
        }

        $coNoiDi = !empty($data->ma_noi_di);
        $coThe   = !empty($data->ma_the_bhyt);
        $tBhtt   = (float) $data->t_bhtt;

        // Ma doi hoi phai co co so noi chuyen nguoi benh di (hien chi ma 1.3).
        if (DoiTuongKcbCatalog::thuocTinh($ma, $danhMuc, 'can_noi_di', false) && !$coNoiDi) {
            $errorCode = $this->generateErrorCode('DOI_TUONG_KCB_THIEU_NOI_DI');
            $errors->push((object)[
                'error_code' => $errorCode,
                'error_name' => 'Đến KCB có phiếu chuyển nhưng thiếu mã nơi chuyển đi',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description' => 'Mã đối tượng ' . $ma . ' (' . DoiTuongKcbCatalog::thuocTinh($ma, $danhMuc, 'ten')
                    . ') nhưng MA_NOI_DI để trống',
            ]);
        }

        // Ma khai san rang nguoi benh den kem mot to phieu (1.3 phieu chuyen co so KCB,
        // 1.5 phieu hen kham lai) thi so phieu phai duoc ghi. Chuan du lieu dau ra (QD 130
        // sua doi theo QD 4750), truong 38 GIAY_CHUYEN_TUYEN: "Ghi so giay chuyen tuyen
        // cua co so KBCB/So giay chuyen co so KBCB noi chuyen nguoi benh di (trong truong
        // hop nguoi benh co giay chuyen tuyen) hoac so giay hen kham lai (neu co)."
        // Cum "(neu co)" noi ve truong hop chung; voi hai ma nay to phieu ton tai theo
        // dinh nghia cua chinh ma do, nen khong mien tru.
        //
        // Do tren du lieu that (1.213 ho so): 758/761 ma 1.5 va 180/184 ma 1.3 bo trong
        // truong nay, va 7 ho so co gia tri deu do nhap tay. Tuc bo xuat HIS chua map
        // truong nay - do CHINH LA thu quy tac can chi ra. Ma loi duoc nap voi
        // critical_error = false nen khong chan xuat/ky so/gui cong.
        if (DoiTuongKcbCatalog::thuocTinh($ma, $danhMuc, 'can_giay_chuyen_tuyen', false)
            && trim((string) $data->giay_chuyen_tuyen) === '') {
            $errorCode = $this->generateErrorCode('DOI_TUONG_KCB_THIEU_GIAY_CHUYEN_TUYEN');
            $errors->push((object)[
                'error_code' => $errorCode,
                'error_name' => 'Thiếu số phiếu chuyển cơ sở KCB hoặc số phiếu hẹn khám lại',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description' => 'Mã đối tượng ' . $ma . ' (' . DoiTuongKcbCatalog::thuocTinh($ma, $danhMuc, 'ten')
                    . ') nhưng GIAY_CHUYEN_TUYEN để trống',
            ]);
        }

        // Ma 1.1 (den dung noi DKBD): MOI ma trong MA_DKBD phai bang MA_CSKCB. Nguoi dung chot
        // nghia CHAT - ho so doi the giua dot sang noi DKBD khac cung bi bao (muc canh bao).
        // MA_DKBD hoac MA_CSKCB rong thi im lang: thieu can cu.
        if (DoiTuongKcbCatalog::thuocTinh($ma, $danhMuc, 'dkbd_phai_la_cskcb', false)) {
            $maCoSoKcb = trim((string) $data->ma_cskcb);
            $dkbdLech = array_values(array_filter(
                DanhSachPhanCachParser::tach($data->ma_dkbd),
                function ($m) use ($maCoSoKcb) {
                    return $m !== $maCoSoKcb;
                }
            ));

            if ($maCoSoKcb !== '' && !empty($dkbdLech)) {
                $errorCode = $this->generateErrorCode('DOI_TUONG_KCB_DKBD_KHAC_CSKCB');
                $errors->push((object)[
                    'error_code' => $errorCode,
                    'error_name' => 'Đến đúng nơi đăng ký ban đầu nhưng MA_DKBD khác MA_CSKCB',
                    'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                    'description' => 'Mã đối tượng ' . $ma . ' (' . DoiTuongKcbCatalog::thuocTinh($ma, $danhMuc, 'ten')
                        . ') nhưng MA_DKBD có mã khác MA_CSKCB ' . $maCoSoKcb . ': ' . implode(', ', $dkbdLech),
                ]);
            }
        }

        // Ma 1.7 (tre so sinh phai dieu tri ngay sau khi sinh ra): nguoi benh phai con la tre so
        // sinh luc vao vien. BHXH tra loi 000007199029 - nguoi benh sinh 1973 khai 1.7. Ngay
        // sinh chi co nam (thang/ngay 00) hoac ngay vao hong thi im lang: thieu can cu.
        $toiDaNgay = DoiTuongKcbCatalog::thuocTinh($ma, $danhMuc, 'so_sinh_toi_da_ngay');
        if ($toiDaNgay !== null) {
            $tuoiNgay = Xml3176DateHelper::diffDays($data->ngay_sinh, $data->ngay_vao);

            if ($tuoiNgay !== null && $tuoiNgay > (int) $toiDaNgay) {
                $sinh = Xml3176DateHelper::datePart($data->ngay_sinh);
                $errorCode = $this->generateErrorCode('DOI_TUONG_KCB_KHONG_PHAI_SO_SINH');
                $errors->push((object)[
                    'error_code' => $errorCode,
                    'error_name' => 'Không phải đối tượng trẻ sơ sinh phải điều trị ngay sau khi sinh ra',
                    'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                    'description' => 'Mã đối tượng ' . $ma . ' (' . DoiTuongKcbCatalog::thuocTinh($ma, $danhMuc, 'ten')
                        . ') nhưng người bệnh sinh ngày ' . substr($sinh, 6, 2) . '/' . substr($sinh, 4, 2) . '/' . substr($sinh, 0, 4)
                        . ', đã ' . number_format($tuoiNgay, 0, ',', '.') . ' ngày tuổi khi vào viện (tối đa '
                        . (int) $toiDaNgay . ' ngày)',
                ]);
            }
        }

        // Ma 3.6: MA_KHUVUC bat buoc. Gia tri khac K1/K2/K3 van do ADMIN_INFO_ERROR_MA_KHUVUC lo.
        if (DoiTuongKcbCatalog::thuocTinh($ma, $danhMuc, 'can_ma_khuvuc', false)
            && trim((string) $data->ma_khuvuc) === '') {
            $errorCode = $this->generateErrorCode('DOI_TUONG_KCB_THIEU_MA_KHUVUC');
            $errors->push((object)[
                'error_code' => $errorCode,
                'error_name' => 'Thiếu mã khu vực',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description' => 'Mã đối tượng ' . $ma . ' (' . DoiTuongKcbCatalog::thuocTinh($ma, $danhMuc, 'ten')
                    . ') nhưng MA_KHUVUC để trống',
            ]);
        }

        // Ma 1.17: MA_BENH_CHINH phai thuoc danh muc benh Phu luc I Thong tu 01/2025/TT-BYT.
        // Chua nap danh muc hoac thieu ma benh chinh thi im lang - khong co can cu ket luan.
        if (DoiTuongKcbCatalog::thuocTinh($ma, $danhMuc, 'benh_pl1', false)) {
            $maBenh = trim((string) $data->ma_benh_chinh);
            $dmPl1 = $maBenh === '' ? [] : $this->commonValidationService->danhMucBenhPl1();

            if (!empty($dmPl1)) {
                $tuoi = Xml3176DateHelper::tuoiDuNam($data->ngay_sinh, $data->ngay_vao);
                $kq = BenhPl1Matcher::kiemTra($maBenh, $dmPl1, $tuoi);

                if (!$kq['khop']) {
                    $moTa = 'Mã đối tượng ' . $ma . ' nhưng MA_BENH_CHINH = ' . $maBenh
                        . ' không thuộc Phụ lục I Thông tư 01/2025/TT-BYT';

                    if (!empty($kq['stt_sai_tuoi'])) {
                        $moTa .= ' (thuộc dòng ' . implode(', ', $kq['stt_sai_tuoi'])
                            . ' nhưng người bệnh ' . $tuoi . ' tuổi, dòng này chỉ áp dụng người dưới 18 tuổi)';
                    }

                    $errorCode = $this->generateErrorCode('DOI_TUONG_KCB_BENH_NGOAI_PL1');
                    $errors->push((object)[
                        'error_code' => $errorCode,
                        'error_name' => 'Tự đến cơ sở cấp chuyên sâu nhưng bệnh không thuộc Phụ lục I TT 01/2025',
                        'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                        'description' => $moTa,
                    ]);
                }
            }
        }

        // Tu den thi khong the co co so chuyen di.
        if (DoiTuongKcbCatalog::laTuDen($ma, $danhMuc) && $coNoiDi) {
            $errorCode = $this->generateErrorCode('DOI_TUONG_KCB_TU_DEN_CO_NOI_DI');
            $errors->push((object)[
                'error_code' => $errorCode,
                'error_name' => 'Người bệnh tự đến nhưng lại có mã nơi chuyển đi',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description' => 'Mã đối tượng ' . $ma . ' (' . DoiTuongKcbCatalog::thuocTinh($ma, $danhMuc, 'ten')
                    . ') nhưng MA_NOI_DI = ' . $data->ma_noi_di,
            ]);
        }

        // $khongBhyt van giu vi nhanh THIEU_THE_BHYT ben duoi con dung.
        //
        // Da bo quy tac DOI_TUONG_KCB_KHONG_BHYT_CO_THE (ma 9 co the BHYT): ho so ma 9
        // bi chan tu DIEM PHAT JOB boi cong xml3176.ma_doituong_kcb_khong_kiem trong
        // Xml3176Importer::canKiemLoi(), nen checker nay khong bao gio chay tren chung -
        // quy tac o day la ma chet.
        $khongBhyt = (bool) DoiTuongKcbCatalog::thuocTinh($ma, $danhMuc, 'khong_bhyt', false);

        // Doi quy thanh toan ma khong co the moi la mau thuan. Cap cuu chua xuat trinh
        // the la ngoai le da biet - chuan cho phep tra cuu the truoc khi nguoi benh ra
        // vien - nen chi bao khi T_BHTT > 0.
        if (!$khongBhyt && !$coThe && $tBhtt > 0) {
            $errorCode = $this->generateErrorCode('DOI_TUONG_KCB_THIEU_THE_BHYT');
            $errors->push((object)[
                'error_code' => $errorCode,
                'error_name' => 'Đề nghị quỹ BHYT thanh toán nhưng không có mã thẻ',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description' => 'Mã đối tượng ' . $ma . ' đề nghị quỹ thanh toán '
                    . number_format($tBhtt) . ' đồng nhưng MA_THE_BHYT để trống',
            ]);
        }

        // MA_DKBD co the chua nhieu ma ngan boi ';' khi nguoi benh doi the giua dot, nen
        // phai tach roi moi so - so chuoi tho se bo sot.
        $dkbd = DanhSachPhanCachParser::tach($data->ma_dkbd);
        $cskcb = trim((string) $data->ma_cskcb);

        // Dung noi dang ky ban dau (MA_CSKCB trong MA_DKBD) khong tu no la vi pham: nhieu
        // ma hop le cho phep dung noi DKBD (vd ma 2 cap cuu, ma 1.4/1.5/1.6/1.7/8/10 - do
        // lai tren du lieu that cho thay day la BAO OAN, khong phai vi pham that). Chi bao
        // khi ma khai KHANG DINH nguoi benh den tu noi khac: co thuoc tinh 'tu_den' (tu
        // den KHONG qua co so DKBD nay) hoac 'can_noi_di' (co phieu chuyen tu co so khac).
        // Doc 'dung_dkbd' qua config thay vi mang cung de tranh doi hanh vi trong im lang
        // khi co ai "don dep" bang cach doc config (mã 1.2 da duoc them 'dung_dkbd' o T5).
        $dungDkbd = (bool) DoiTuongKcbCatalog::thuocTinh($ma, $danhMuc, 'dung_dkbd', false);
        $khangDinhTuNoiKhac = DoiTuongKcbCatalog::laTuDen($ma, $danhMuc)
            || (bool) DoiTuongKcbCatalog::thuocTinh($ma, $danhMuc, 'can_noi_di', false);

        if ($cskcb !== '' && in_array($cskcb, $dkbd, true) && !$dungDkbd && $khangDinhTuNoiKhac) {
            $errorCode = $this->generateErrorCode('DOI_TUONG_KCB_DUNG_DKBD_SAI_MA');
            $errors->push((object)[
                'error_code' => $errorCode,
                'error_name' => 'Đến đúng nơi đăng ký ban đầu nhưng khai mã đối tượng khẳng định đến từ nơi khác',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description' => 'MA_CSKCB ' . $cskcb . ' nằm trong MA_DKBD (' . $data->ma_dkbd
                    . ') nhưng mã đối tượng khai là ' . $ma
                    . ', vốn khẳng định người bệnh đến từ nơi khác (tự đến hoặc có phiếu chuyển)',
            ]);
        }

        // Ma 3.1: 40% noi tru, 0% ngoai tru. Nhanh noi tru da co
        // Xml3176CompleteChecker::checkMucHuong() lo, o day chi bu nhanh ngoai tru.
        // MA_LOAI_KCB rong thi im lang: khong co can cu de ket luan "ngoai tru" tu du
        // lieu vang - truoc day in_array('', [...]) tra false nen coi rong la ngoai tru,
        // vi pham chinh nguyen tac cua spec la khong suy dien tu du lieu thieu.
        $maLoaiKcb = trim((string) $data->ma_loai_kcb);
        $noiTru = in_array($maLoaiKcb, (array) config('xml3176.treatment_type_inpatient', []));

        if ($maLoaiKcb !== ''
            && DoiTuongKcbCatalog::thuocTinh($ma, $danhMuc, 'ngoai_tru_khong_huong', false)
            && !$noiTru && $tBhtt > 0) {
            $errorCode = $this->generateErrorCode('DOI_TUONG_KCB_31_NGOAI_TRU_CO_BHTT');
            $errors->push((object)[
                'error_code' => $errorCode,
                'error_name' => 'Đối tượng này khám ngoại trú không được quỹ BHYT thanh toán',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description' => 'Mã đối tượng ' . $ma . ' khám ngoại trú (MA_LOAI_KCB = '
                    . $data->ma_loai_kcb . ') thì mức hưởng là 0% nhưng T_BHTT = '
                    . number_format($tBhtt) . ' đồng',
            ]);
        }

        return $errors;
    }

    // Thêm các phương thức kiểm tra khác ở đây
}
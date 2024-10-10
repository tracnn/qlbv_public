<?php

namespace App\Services;

use App\Models\BHYT\Qd130Xml13;
use App\Models\BHYT\MedicalStaff;
use Illuminate\Support\Collection;
use App\Models\BHYT\MedicalOrganization;
use App\Models\BHYT\Icd10Category;
use App\Models\BHYT\IcdYhctCategory;

class Qd130Xml13Checker
{
    protected $xmlErrorService;
    protected $prefix;

    protected $xmlType;

    public function __construct(Qd130XmlErrorService $xmlErrorService)
    {
        $this->xmlErrorService = $xmlErrorService;
        $this->setConditions();
    }

    protected function setConditions()
    {
        $this->xmlType = 'XML13';
        $this->prefix = $this->xmlType . '_';

    }

    protected function generateErrorCode(string $errorKey): string
    {
        return $this->prefix . $errorKey;
    }

    /**
     * Check Qd130Xml13 Errors
     *
     * @param Qd130Xml13 $data
     * @return void
     */
    public function checkErrors(Qd130Xml13 $data): void
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
     * @param Qd130Xml13 $data
     * @return Collection
     */
    private function infoChecker(Qd130Xml13 $data): Collection
    {
        $errors = collect();

        if (!empty($data->ma_the_bhyt)) {
            if (empty($data->gt_the_den)) {
                $errorCode = $this->generateErrorCode('INFO_ERROR_GT_THE_DEN');
                $errors->push((object)[
                    'error_code' => $errorCode,
                    'error_name' => 'Thiếu giá trị thẻ đến ngày',
                    'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                    'description' => 'Giá trị thẻ đến ngày không được để trống khi có mã thẻ BHYT'
                ]);
            } else {
                $maTheBhytList = explode(';', $data->ma_the_bhyt);
                $gtTheDenList = explode(';', $data->gt_the_den);

                $numberOfElements = count($maTheBhytList);

                if ($numberOfElements >= 1) {
                    if (count($gtTheDenList) != $numberOfElements) {
                        $errorCode = $this->generateErrorCode('INFO_ERROR_MISMATCH_COUNT');
                        $errors->push((object)[
                            'error_code' => $errorCode,
                            'error_name' => 'Số lượng các thành phần không khớp',
                            'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                            'description' => 'Mã thẻ BHYT: ' . $data->ma_the_bhyt . ', GT đến ngày: ' . $data->gt_the_den . ' phải tương đồng'
                        ]);
                    }
                }
            }
        }    

        if (empty($data->so_hoso)) {
            $errorCode = $this->generateErrorCode('INFO_ERROR_SO_HOSO');
            $errors->push((object)[
                'error_code' => $errorCode,
                'error_name' => 'Thiếu số hồ sơ',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description' => 'Số hồ sơ không được để trống'
            ]);
        }

        if (empty($data->so_chuyentuyen)) {
            $errorCode = $this->generateErrorCode('INFO_ERROR_SO_CHUYENTUYEN');
            $errors->push((object)[
                'error_code' => $errorCode,
                'error_name' => 'Thiếu số chuyển tuyến',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description' => 'Số chuyển tuyến không được để trống'
            ]);
        } elseif (strlen($data->so_chuyentuyen) > 50) {
            $errorCode = $this->generateErrorCode('INFO_ERROR_SO_CHUYENTUYEN_LENGTH');
            $errors->push((object)[
                'error_code' => $errorCode,
                'error_name' => 'Số chuyển tuyến vượt quá 50 ký tự',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description' => 'Số chuyển tuyến không được vượt quá 50 ký tự'
            ]);
        }

        if (empty($data->giay_chuyen_tuyen)) {
            $errorCode = $this->generateErrorCode('INFO_ERROR_GIAY_CHUYEN_TUYEN');
            $errors->push((object)[
                'error_code' => $errorCode,
                'error_name' => 'Thiếu giấy chuyển tuyến',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description' => 'Giấy chuyển tuyến không được để trống'
            ]);
        } elseif (strlen($data->giay_chuyen_tuyen) > 50) {
            $errorCode = $this->generateErrorCode('INFO_ERROR_GIAY_CHUYEN_TUYEN_LENGTH');
            $errors->push((object)[
                'error_code' => $errorCode,
                'error_name' => 'Giấy chuyển tuyến vượt quá 50 ký tự',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description' => 'Giấy chuyển tuyến không được vượt quá 50 ký tự'
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
        } else {
            // Kiểm tra nếu ma_cskcb không có trong MedicalOrganization
            $organizationExists = MedicalOrganization::where('ma_cskcb', $data->ma_cskcb)->exists();

            if (!$organizationExists) {
                $errorCode = $this->generateErrorCode('INFO_ERROR_MA_CSKCB_NOT_FOUND');
                $errors->push((object)[
                    'error_code' => $errorCode,
                    'error_name' => 'Mã cơ sở KCB không có trong danh mục',
                    'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                    'description' => 'Mã cơ sở KCB: ' . $data->ma_cskcb . ' không có trong danh mục CSKCB'
                ]);
            }
        }

        if (empty($data->ma_noi_den)) {
            $errorCode = $this->generateErrorCode('INFO_ERROR_MA_NOI_DEN');
            $errors->push((object)[
                'error_code' => $errorCode,
                'error_name' => 'Thiếu mã nơi đến',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description' => 'Mã nơi đến không được để trống'
            ]);
        } else {
            // Kiểm tra nếu ma_noi_den không có trong MedicalOrganization
            $organizationExists = MedicalOrganization::where('ma_cskcb', $data->ma_noi_den)->exists();

            if (!$organizationExists) {
                $errorCode = $this->generateErrorCode('INFO_ERROR_MA_NOI_DEN_NOT_FOUND');
                $errors->push((object)[
                    'error_code' => $errorCode,
                    'error_name' => 'Mã nơi đến không có trong danh mục',
                    'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                    'description' => 'Mã cơ sở KCB: ' . $data->ma_noi_den . ' không có trong danh mục CSKCB'
                ]);
            }
        }

        if (!empty($data->ngay_vao_noi_tru)) {
            if (empty($data->tomtat_kq)) {
                $errorCode = $this->generateErrorCode('INFO_ERROR_TOMTAT_KQ');
                $errors->push((object)[
                    'error_code' => $errorCode,
                    'error_name' => 'Thiếu tóm tắt kết quả',
                    'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                    'description' => 'Tóm tắt kết quả không được để trống đối với điều trị nội trú'
                ]);
            }
        }

        if (empty($data->dau_hieu_ls)) {
            $errorCode = $this->generateErrorCode('INFO_ERROR_DAU_HIEU_LS');
            $errors->push((object)[
                'error_code' => $errorCode,
                'error_name' => 'Thiếu dấu hiệu lâm sàng',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description' => 'Dấu hiệu lâm sàng không được để trống'
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

        if (empty($data->qt_benhly)) {
            $errorCode = $this->generateErrorCode('INFO_ERROR_QT_BENHLY');
            $errors->push((object)[
                'error_code' => $errorCode,
                'error_name' => 'Thiếu quá trình bệnh lý',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description' => 'Quá trình bệnh lý không được để trống'
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
        } elseif (!Icd10Category::where('icd_code', $data->ma_benh_chinh)->where('is_active', true)->exists()) {
            $errorCode = $this->generateErrorCode('INFO_ERROR_MA_BENH_CHINH_NOT_FOUND');
            $errors->push((object)[
                'error_code' => $errorCode,
                'error_name' => 'Mã bệnh chính không tồn tại',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description' => 'Mã bệnh chính không tồn tại trong danh mục ICD10: ' . $data->ma_benh_chinh
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
            foreach ($ma_benh_kt_array as $ma_benh_kt) {
                if (!Icd10Category::where('icd_code', $ma_benh_kt)->where('is_active', true)->exists()) {
                    $errorCode = $this->generateErrorCode('INFO_ERROR_MA_BENH_KT_NOT_FOUND');
                    $errors->push((object)[
                        'error_code' => $errorCode,
                        'error_name' => 'Mã bệnh kèm theo không tồn tại',
                        'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                        'description' => 'Mã bệnh kèm theo không tồn tại trong danh mục ICD10: ' . $ma_benh_kt
                    ]);
                }
            }
        }

        // Check ma_benh_yhct
        if (!empty($data->ma_benh_yhct)) {
            $ma_benh_yhct_array = explode(';', $data->ma_benh_yhct);
            foreach ($ma_benh_yhct_array as $ma_benh_yhct) {
                if (!IcdYhctCategory::where('icd_code', $ma_benh_yhct)->where('is_active', true)->exists()) {
                    $errorCode = $this->generateErrorCode('INFO_ERROR_MA_BENH_YHCT_NOT_FOUND');
                    $errors->push((object)[
                        'error_code' => $errorCode,
                        'error_name' => 'Mã bệnh YHCT không tồn tại',
                        'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                        'description' => 'Mã bệnh YHCT không tồn tại trong danh mục ICD YHCT: ' . $ma_benh_yhct
                    ]);
                }
            }
        }

        if (empty($data->ten_dich_vu)) {
            $errorCode = $this->generateErrorCode('INFO_ERROR_TEN_DICH_VU');
            $errors->push((object)[
                'error_code' => $errorCode,
                'error_name' => 'Thiếu dịch vụ',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description' => 'Dịch vụ không được để trống'
            ]);
        } elseif (mb_strlen($data->ten_dich_vu) > 1024) {
            $errorCode = $this->generateErrorCode('INFO_ERROR_TEN_DICH_VU_LENGTH');
            $errors->push((object)[
                'error_code' => $errorCode,
                'error_name' => 'Tên dịch vụ vượt quá 1024 ký tự',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description' => 'Tên dịch vụ không được vượt quá 1024 ký tự'
            ]);
        }

        if (!empty($data->ten_thuoc) && mb_strlen($data->ten_thuoc) > 1024) {
            $errorCode = $this->generateErrorCode('INFO_ERROR_TEN_THUOC_LENGTH');
            $errors->push((object)[
                'error_code' => $errorCode,
                'error_name' => 'Tên thuốc vượt quá 1024 ký tự',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description' => 'Tên thuốc không được vượt quá 1024 ký tự'
            ]);
        }

        if (empty($data->ma_loai_rv)) {
            $errorCode = $this->generateErrorCode('INFO_ERROR_MA_LOAI_RV');
            $errors->push((object)[
                'error_code' => $errorCode,
                'error_name' => 'Thiếu mã loại ra viện',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description' => 'Mã loại ra viện không được để trống'
            ]);
        }

        if (empty($data->ma_lydo_ct)) {
            $errorCode = $this->generateErrorCode('INFO_ERROR_MA_LYDO_CT');
            $errors->push((object)[
                'error_code' => $errorCode,
                'error_name' => 'Thiếu mã lý do chuyển tuyến',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description' => 'Mã lý do chuyển tuyến không được để trống'
            ]);
        }

        if (empty($data->huong_dieu_tri)) {
            $errorCode = $this->generateErrorCode('INFO_ERROR_HUONG_DIEU_TRI');
            $errors->push((object)[
                'error_code' => $errorCode,
                'error_name' => 'Thiếu hướng điều trị',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description' => 'Hướng điều trị không được để trống'
            ]);
        }

        if (empty($data->phuongtien_vc)) {
            $errorCode = $this->generateErrorCode('INFO_ERROR_PHUONGTIEN_VC');
            $errors->push((object)[
                'error_code' => $errorCode,
                'error_name' => 'Thiếu phương tiện vận chuyển',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description' => 'Phương tiện vận chuyển không được để trống'
            ]);
        } elseif (strlen($data->phuongtien_vc) > 255) {
            $errorCode = $this->generateErrorCode('INFO_ERROR_PHUONGTIEN_VC_LENGTH');
            $errors->push((object)[
                'error_code' => $errorCode,
                'error_name' => 'Phương tiện vận chuyển vượt quá 255 ký tự',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description' => 'Phương tiện vận chuyển không được vượt quá 255 ký tự'
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
            $staff = MedicalStaff::where('macchn', $data->ma_bac_si)->exists();
            if (!$staff) {
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
            $staff = MedicalStaff::where('ma_bhxh', $data->ma_ttdv)->exists();
            if (!$staff) {
                $errorCode = $this->generateErrorCode('INVALID_MEDICAL_STAFF_MA_TTDV');
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
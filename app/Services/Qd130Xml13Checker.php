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

        if (empty($data->so_hoso)) {
            $errors->push((object)[
                'error_code' => $this->prefix . 'INFO_ERROR_SO_HOSO',
                'error_name' => 'Thiếu số hồ sơ',
                'description' => 'Số hồ sơ không được để trống'
            ]);
        }

        if (empty($data->so_chuyentuyen)) {
            $errors->push((object)[
                'error_code' => $this->prefix . 'INFO_ERROR_SO_CHUYENTUYEN',
                'error_name' => 'Thiếu số chuyển tuyến',
                'description' => 'Số chuyển tuyến không được để trống'
            ]);
        }

        if (empty($data->giay_chuyen_tuyen)) {
            $errors->push((object)[
                'error_code' => $this->prefix . 'INFO_ERROR_GIAY_CHUYEN_TUYEN',
                'error_name' => 'Thiếu giấy chuyển tuyến',
                'description' => 'Giấy chuyển tuyến không được để trống'
            ]);
        }

        if (empty($data->ma_cskcb)) {
            $errors->push((object)[
                'error_code' => $this->prefix . 'INFO_ERROR_MA_CSKCB',
                'error_name' => 'Thiếu mã CSKCB',
                'description' => 'CSKCB không được để trống'
            ]);
        }  else {
            // Kiểm tra nếu ma_cskcb không có trong MedicalOrganization
            $organizationExists = MedicalOrganization::where('ma_cskcb', $data->ma_cskcb)->exists();

            if (!$organizationExists) {
                $errors->push((object)[
                    'error_code' => $this->prefix . 'INFO_ERROR_MA_CSKCB_NOT_FOUND',
                    'error_name' => 'Mã cơ sở KCB không có trong danh mục',
                    'description' => 'Mã cơ sở KCB: ' . $data->ma_cskcb . ' không có trong danh mục CSKCB'
                ]);
            }
        }

        if (empty($data->ma_noi_den)) {
            $errors->push((object)[
                'error_code' => $this->prefix . 'INFO_ERROR_MA_NOI_DEN',
                'error_name' => 'Thiếu mã nơi đến',
                'description' => 'Mã nơi đến không được để trống'
            ]);
        }  else {
            // Kiểm tra nếu ma_cskcb không có trong MedicalOrganization
            $organizationExists = MedicalOrganization::where('ma_cskcb', $data->ma_noi_den)->exists();

            if (!$organizationExists) {
                $errors->push((object)[
                    'error_code' => $this->prefix . 'INFO_ERROR_MA_NOI_DEN_NOT_FOUND',
                    'error_name' => 'Mã nơi đến không có trong danh mục',
                    'description' => 'Mã cơ sở KCB: ' . $data->ma_noi_den . ' không có trong danh mục CSKCB'
                ]);
            }
        }

        if (!empty($data->ngay_vao_noi_tru)) {
            if (empty($data->tomtat_kq)) {
                $errors->push((object)[
                    'error_code' => $this->prefix . 'INFO_ERROR_TOMTAT_KQ',
                    'error_name' => 'Thiếu tóm tắt kết quả',
                    'description' => 'Tóm tắt kết quả không được để trống đối với điều trị nội trú'
                ]);
            }
        }

        if (empty($data->dau_hieu_ls)) {
            $errors->push((object)[
                'error_code' => $this->prefix . 'INFO_ERROR_DAU_HIEU_LS',
                'error_name' => 'Thiếu dấu hiệu lâm sàng',
                'description' => 'Dấu hiệu lâm sàng không được để trống'
            ]);
        }

        if (empty($data->chan_doan_rv)) {
            $errors->push((object)[
                'error_code' => $this->prefix . 'INFO_ERROR_CHAN_DOAN_RV',
                'error_name' => 'Thiếu chẩn đoán ra viện',
                'description' => 'Chẩn đoán ra viện không được để trống'
            ]);
        }

        if (empty($data->qt_benhly)) {
            $errors->push((object)[
                'error_code' => $this->prefix . 'INFO_ERROR_QT_BENHLY',
                'error_name' => 'Thiếu quá trình bệnh lý',
                'description' => 'Quá trình bệnh lý không được để trống'
            ]);
        }

        if (empty($data->ma_benh_chinh)) {
            $errors->push((object)[
                'error_code' => $this->prefix . 'INFO_ERROR_MA_BENH_CHINH',
                'error_name' => 'Thiếu mã bệnh chính',
                'description' => 'Mã bệnh chính không được để trống'
            ]);
        } elseif (!Icd10Category::where('icd_code', $data->ma_benh_chinh)->exists()) {
            $errors->push((object)[
                'error_code' => $this->prefix . 'INFO_ERROR_MA_BENH_CHINH_NOT_FOUND',
                'error_name' => 'Mã bệnh chính không tồn tại',
                'description' => 'Mã bệnh chính không tồn tại trong danh mục ICD10: ' . $data->ma_benh_chinh
            ]);
        }


        if (!empty($data->ma_benh_kt)) {
            $ma_benh_kt_array = explode(';', $data->ma_benh_kt);
            if (count($ma_benh_kt_array) > 12) {
                $errors->push((object)[
                    'error_code' => $this->prefix . 'INFO_ERROR_MA_BENH_KT',
                    'error_name' => 'Mã bệnh kèm theo vượt quá 12 mã',
                    'description' => 'Mã bệnh kèm theo không được vượt quá 12 mã'
                ]);
            }
            foreach ($ma_benh_kt_array as $ma_benh_kt) {
                if (!Icd10Category::where('icd_code', $ma_benh_kt)->exists()) {
                    $errors->push((object)[
                        'error_code' => $this->prefix . 'INFO_ERROR_MA_BENH_KT_NOT_FOUND',
                        'error_name' => 'Mã bệnh kèm theo không tồn tại',
                        'description' => 'Mã bệnh kèm theo không tồn tại trong danh mục ICD10: ' . $ma_benh_kt
                    ]);
                }
            }
        }

        // Check ma_benh_yhct
        if (!empty($data->ma_benh_yhct)) {
            $ma_benh_yhct_array = explode(';', $data->ma_benh_yhct);
            foreach ($ma_benh_yhct_array as $ma_benh_yhct) {
                if (!IcdYhctCategory::where('icd_code', $ma_benh_yhct)->exists()) {
                    $errors->push((object)[
                        'error_code' => $this->prefix . 'INFO_ERROR_MA_BENH_YHCT_NOT_FOUND',
                        'error_name' => 'Mã bệnh YHCT không tồn tại',
                        'description' => 'Mã bệnh YHCT không tồn tại trong danh mục ICD YHCT: ' . $ma_benh_yhct
                    ]);
                }
            }
        }

        if (empty($data->ten_dich_vu)) {
            $errors->push((object)[
                'error_code' => $this->prefix . 'INFO_ERROR_TEN_DICH_VU',
                'error_name' => 'Thiếu dịch vụ',
                'description' => 'Dịch vụ không được để trống'
            ]);
        }

        if (empty($data->ma_loai_rv)) {
            $errors->push((object)[
                'error_code' => $this->prefix . 'INFO_ERROR_MA_LOAI_RV',
                'error_name' => 'Thiếu mã loại ra viện',
                'description' => 'Mã loại ra viện không được để trống'
            ]);
        }

        if (empty($data->ma_lydo_ct)) {
            $errors->push((object)[
                'error_code' => $this->prefix . 'INFO_ERROR_MA_LYDO_CT',
                'error_name' => 'Thiếu mã lý do chuyển tuyến',
                'description' => 'Mã lý do chuyển tuyến không được để trống'
            ]);
        }

        if (empty($data->huong_dieu_tri)) {
            $errors->push((object)[
                'error_code' => $this->prefix . 'INFO_ERROR_HUONG_DIEU_TRI',
                'error_name' => 'Thiếu hướng điều trị',
                'description' => 'Hướng điều trị không được để trống'
            ]);
        }

        if (empty($data->phuongtien_vc)) {
            $errors->push((object)[
                'error_code' => $this->prefix . 'INFO_ERROR_PHUONGTIEN_VC',
                'error_name' => 'Thiếu phương tiện vận chuyển',
                'description' => 'Phương tiện vận chuyển không được để trống'
            ]);
        }

        if (empty($data->ma_bac_si)) {
            $errors->push((object)[
                'error_code' => $this->prefix . 'INFO_ERROR_MA_BAC_SI',
                'error_name' => 'Thiếu mã bác sĩ (CCHN)',
                'description' => 'Mã bác sĩ (CCHN) không được để trống'
            ]);
        } else {
            $staff = MedicalStaff::where('macchn', $data->ma_bac_si)->first();
            if (!$staff) {
                $errors->push((object)[
                    'error_code' => $this->prefix . 'INVALID_MEDICAL_STAFF_MA_BAC_SI',
                    'error_name' => 'Mã bác sĩ (CCHN) chưa được duyệt',
                    'description' => 'Mã bác sĩ chưa được duyệt danh mục (Mã CCHN: ' . $data->ma_bac_si . ')'
                ]);
            }
        }

        if (empty($data->ma_ttdv)) {
            $errors->push((object)[
                'error_code' => $this->prefix . 'INFO_ERROR_MA_TTDV',
                'error_name' => 'Thiếu mã thủ trưởng đơn vị',
                'description' => 'Thủ trưởng đơn vị không được để trống'
            ]);
        } else {
            $staff = MedicalStaff::where('ma_bhxh', $data->ma_ttdv)->first();
            if (!$staff) {
                $errors->push((object)[
                    'error_code' => $this->prefix . 'INVALID_MEDICAL_STAFF_MA_TTDV',
                    'error_name' => 'Mã thủ trưởng đơn vị chưa được duyệt',
                    'description' => 'Mã thủ trưởng đơn vị chưa được duyệt danh mục (Mã CCHN: ' . $data->ma_ttdv . ')'
                ]);
            }
        }


        return $errors;
    }

    // Thêm các phương thức kiểm tra khác ở đây
}
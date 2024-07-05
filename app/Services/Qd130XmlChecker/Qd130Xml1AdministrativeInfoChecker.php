<?php

namespace App\Services\Qd130XmlChecker;

use Illuminate\Support\Collection;
use App\Models\BHYT\Qd130Xml1;
use App\Models\BHYT\MedicalStaff;
use App\Models\BHYT\MedicalOrganization;

class Qd130Xml1AdministrativeInfoChecker
{
    protected $prefix;

    public function __construct($prefix)
    {
        $this->prefix = $prefix;
    }

    /**
     * Check for administrative information errors
     *
     * @param Qd130Xml1 $data
     * @return Collection
     */
    public function check(Qd130Xml1 $data): Collection
    {
        $errors = collect();

        if (empty($data->ma_bn)) {
            $errors->push((object)[
                'error_code' => $this->prefix . 'ADMIN_INFO_ERROR_MA_BN',
                'error_name' => 'Thiếu mã bệnh nhân',
                'description' => 'Mã bệnh nhân không được để trống'
            ]);
        }

        if (empty($data->ho_ten)) {
            $errors->push((object)[
                'error_code' => $this->prefix . 'ADMIN_INFO_ERROR_HO_TEN',
                'error_name' => 'Thiếu họ tên',
                'description' => 'Họ tên không được để trống'
            ]);
        }

        if (empty($data->ngay_sinh)) {
            $errors->push((object)[
                'error_code' => $this->prefix . 'ADMIN_INFO_ERROR_NGAY_SINH',
                'error_name' => 'Thiếu ngày sinh',
                'description' => 'Ngày sinh không được để trống'
            ]);
        }

        if (empty($data->gioi_tinh)) {
            $errors->push((object)[
                'error_code' => $this->prefix . 'ADMIN_INFO_ERROR_GIOI_TINH',
                'error_name' => 'Thiếu giới tính',
                'description' => 'Giới tính không được để trống'
            ]);
        }

        if (empty($data->can_nang)) {
            $errors->push((object)[
                'error_code' => $this->prefix . 'ADMIN_INFO_ERROR_CAN_NANG',
                'error_name' => 'Thiếu cân nặng',
                'description' => 'Cân nặng không được để trống'
            ]);
        } else {
            // Ensure can_nang is numeric before casting
            if (is_numeric($data->can_nang)) {
                $can_nang_value = (double)$data->can_nang;
                if ($can_nang_value > 200) {
                    $errors->push((object)[
                        'error_code' => $this->prefix . 'ADMIN_INFO_ERROR_TO_MUCH_CAN_NANG',
                        'error_name' => 'Cân nặng không hợp lệ',
                        'description' => 'Cân nặng không hợp lệ: ' . number_format($can_nang_value)
                    ]);
                }
            } else {
                $errors->push((object)[
                    'error_code' => $this->prefix . 'ADMIN_INFO_ERROR_INVALID_CAN_NANG',
                    'error_name' => 'Cân nặng không hợp lệ',
                    'description' => 'Giá trị cân nặng không hợp lệ: ' . $data->can_nang
                ]);
            }
        }

        if (empty($data->dia_chi)) {
            $errors->push((object)[
                'error_code' => $this->prefix . 'ADMIN_INFO_ERROR_DIA_CHI',
                'error_name' => 'Thiếu địa chỉ',
                'description' => 'Địa chỉ không được để trống'
            ]);
        }

        if (empty($data->so_cccd)) {
            $errors->push((object)[
                'error_code' => $this->prefix . 'ADMIN_INFO_ERROR_SO_CCCD',
                'error_name' => 'Thiếu số CCCD/Định danh cá nhân',
                'description' => 'Số CCCD/định danh cá nhân không được để trống'
            ]);
        }

        if (empty($data->matinh_cu_tru)) {
            $errors->push((object)[
                'error_code' => $this->prefix . 'ADMIN_INFO_ERROR_MATINH_CU_TRU',
                'error_name' => 'Thiếu mã tỉnh cư trú',
                'description' => 'Mã tỉnh cư trú không được để trống'
            ]);
        }

        if (empty($data->mahuyen_cu_tru)) {
            $errors->push((object)[
                'error_code' => $this->prefix . 'ADMIN_INFO_ERROR_MAHUYEN_CU_TRU',
                'error_name' => 'Thiếu mã huyện cư trú',
                'description' => 'Mã huyện cư trú không được để trống'
            ]);
        }

        if (empty($data->maxa_cu_tru)) {
            $errors->push((object)[
                'error_code' => $this->prefix . 'ADMIN_INFO_ERROR_MAXA_CU_TRU',
                'error_name' => 'Thiếu mã xã cư trú',
                'description' => 'Mã xã cư trú không được để trống'
            ]);
        }

        if (empty($data->ma_quoctich)) {
            $errors->push((object)[
                'error_code' => $this->prefix . 'ADMIN_INFO_ERROR_MA_QUOCTICH',
                'error_name' => 'Thiếu mã quốc tịch',
                'description' => 'Mã quốc tịch không được để trống hoặc không đúng định dạng'
            ]);
        }

        if (empty($data->ma_dantoc)) {
            $errors->push((object)[
                'error_code' => $this->prefix . 'ADMIN_INFO_ERROR_MA_DANTOC',
                'error_name' => 'Thiếu mã dân tộc',
                'description' => 'Mã dân tộc không được để trống'
            ]);
        }

        if (empty($data->ma_nghe_nghiep)) {
            $errors->push((object)[
                'error_code' => $this->prefix . 'ADMIN_INFO_ERROR_MA_NGHE_NGHIEP',
                'error_name' => 'Thiếu mã nghề nghiệp',
                'description' => 'Mã nghề nghiệp không được để trống'
            ]);
        } elseif (strlen($data->ma_nghe_nghiep) !== 5) {
            $errors->push((object)[
                'error_code' => $this->prefix . 'ADMIN_INFO_ERROR_MA_NGHE_NGHIEP_LENGTH',
                'error_name' => 'Mã nghề nghiệp không hợp lệ',
                'description' => 'Mã nghề nghiệp phải có 5 ký tự'
            ]);
        }

        if (empty($data->chan_doan_vao)) {
            $errors->push((object)[
                'error_code' => $this->prefix . 'ADMIN_INFO_ERROR_CHAN_DOAN_VAO',
                'error_name' => 'Thiếu chẩn đoán vào',
                'description' => 'Chẩn đoán vào không được để trống'
            ]);
        }

        if (empty($data->chan_doan_rv)) {
            $errors->push((object)[
                'error_code' => $this->prefix . 'ADMIN_INFO_ERROR_CHAN_DOAN_RV',
                'error_name' => 'Thiếu chẩn đoán ra viện',
                'description' => 'Chẩn đoán ra viện không được để trống'
            ]);
        }

        if (empty($data->ma_benh_chinh)) {
            $errors->push((object)[
                'error_code' => $this->prefix . 'ADMIN_INFO_ERROR_MA_BENH_CHINH',
                'error_name' => 'Thiếu mã bệnh chính',
                'description' => 'Mã bệnh chính không được để trống'
            ]);
        }

        if (!empty($data->ma_benh_kt)) {
            $ma_benh_kt_array = explode(';', $data->ma_benh_kt);
            if (count($ma_benh_kt_array) > 12) {
                $errors->push((object)[
                    'error_code' => $this->prefix . 'ADMIN_INFO_ERROR_MA_BENH_KT',
                    'error_name' => 'Mã bệnh kèm theo vượt quá 12 mã',
                    'description' => 'Mã bệnh kèm theo không được vượt quá 12 mã'
                ]);
            }
        }

        if (empty($data->ma_doituong_kcb)) {
            $errors->push((object)[
                'error_code' => $this->prefix . 'ADMIN_INFO_ERROR_MA_DOITUONG_KCB',
                'error_name' => 'Thiếu mã đối tượng KCB',
                'description' => 'Mã đối tượng KCB không được để trống'
            ]);
        }

        if (empty($data->ngay_vao)) {
            $errors->push((object)[
                'error_code' => $this->prefix . 'ADMIN_INFO_ERROR_NGAY_VAO',
                'error_name' => 'Thiếu ngày vào',
                'description' => 'Ngày vào không được để trống'
            ]);
        }

        if (empty($data->ngay_ra)) {
            $errors->push((object)[
                'error_code' => $this->prefix . 'ADMIN_INFO_ERROR_NGAY_RA',
                'error_name' => 'Thiếu ngày ra',
                'description' => 'Ngày ra không được để trống'
            ]);
        }

        if (empty($data->nam_qt)) {
            $errors->push((object)[
                'error_code' => $this->prefix . 'ADMIN_INFO_ERROR_NAM_QT',
                'error_name' => 'Thiếu năm quyết toán',
                'description' => 'Năm quyết toán không được để trống'
            ]);
        }

        if (empty($data->thang_qt)) {
            $errors->push((object)[
                'error_code' => $this->prefix . 'ADMIN_INFO_ERROR_THANG_QT',
                'error_name' => 'Thiếu tháng quyết toán',
                'description' => 'Tháng quyết toán không được để trống'
            ]);
        }

        if (empty($data->ma_loai_kcb)) {
            $errors->push((object)[
                'error_code' => $this->prefix . 'ADMIN_INFO_ERROR_MA_LOAI_KCB',
                'error_name' => 'Thiếu mã loại KCB',
                'description' => 'Mã loại KCB không được để trống'
            ]);
        }

        if (empty($data->ket_qua_dtri)) {
            $errors->push((object)[
                'error_code' => $this->prefix . 'ADMIN_INFO_ERROR_KET_QUA_DTRI',
                'error_name' => 'Thiếu kết quả điều trị',
                'description' => 'Kết quả điều trị không được để trống'
            ]);
        }

        if (empty($data->ma_loai_rv)) {
            $errors->push((object)[
                'error_code' => $this->prefix . 'ADMIN_INFO_ERROR_MA_LOAI_RV',
                'error_name' => 'Thiếu mã loại ra viện',
                'description' => 'Mã loại ra viện không được để trống'
            ]);
        }

        if (empty($data->ma_khoa)) {
            $errors->push((object)[
                'error_code' => $this->prefix . 'ADMIN_INFO_ERROR_MA_KHOA',
                'error_name' => 'Thiếu mã khoa',
                'description' => 'Mã khoa không được để trống'
            ]);
        }

        if (empty($data->ma_cskcb)) {
            $errors->push((object)[
                'error_code' => $this->prefix . 'ADMIN_INFO_ERROR_MA_CSKCB',
                'error_name' => 'Thiếu mã cơ sở KCB',
                'description' => 'Mã cơ sở KCB không được để trống'
            ]);
        }  else {
            // Kiểm tra nếu ma_cskcb không có trong MedicalOrganization
            $organizationExists = MedicalOrganization::where('ma_cskcb', $data->ma_cskcb)->exists();

            if (!$organizationExists) {
                $errors->push((object)[
                    'error_code' => $this->prefix . 'ADMIN_INFO_ERROR_MA_CSKCB_NOT_FOUND',
                    'error_name' => 'Mã cơ sở KCB không có trong danh mục',
                    'description' => 'Mã cơ sở KCB: ' . $data->ma_cskcb . ' không có trong danh mục CSKCB'
                ]);
            }
        }

        // Kiểm tra mã nơi đến
        if (!empty($data->ma_noi_den)) {
            $destinationExists = MedicalOrganization::where('ma_cskcb', $data->ma_noi_den)->exists();

            if (!$destinationExists) {
                $errors->push((object)[
                    'error_code' => $this->prefix . 'ADMIN_INFO_ERROR_MA_NOI_DEN_NOT_FOUND',
                    'error_name' => 'Mã nơi đến không có trong danh mục',
                    'description' => 'Mã nơi đến: ' . $data->ma_noi_den . ' không có trong danh mục CSKCB'
                ]);
            }
        }

        // Kiểm tra mã đơn vị khám bệnh đa khoa ban đầu
        if (!empty($data->ma_dkbd)) {
            $initialExaminationUnitExists = MedicalOrganization::where('ma_cskcb', $data->ma_dkbd)->exists();

            if (!$initialExaminationUnitExists) {
                $errors->push((object)[
                    'error_code' => $this->prefix . 'ADMIN_INFO_ERROR_MA_DKBD_NOT_FOUND',
                    'error_name' => 'Mã đăng ký ban đầu không có trong danh mục',
                    'description' => 'Mã đăng ký ban đầu: ' . $data->ma_dkbd . ' không có trong danh mục CSKCB'
                ]);
            }
        }

        // Kiểm tra mã nơi đi
        if (!empty($data->ma_noi_di)) {
            $departureExists = MedicalOrganization::where('ma_cskcb', $data->ma_noi_di)->exists();

            if (!$departureExists) {
                $errors->push((object)[
                    'error_code' => $this->prefix . 'ADMIN_INFO_ERROR_MA_NOI_DI_NOT_FOUND',
                    'error_name' => 'Mã nơi đi không có trong danh mục',
                    'description' => 'Mã nơi đi: ' . $data->ma_noi_di . ' không có trong danh mục CSKCB'
                ]);
            }

            // Kiểm tra giấy chuyển tuyến
            if (empty($data->giay_chuyen_tuyen)) {
                $errors->push((object)[
                    'error_code' => $this->prefix . 'ADMIN_INFO_ERROR_GIAY_CHUYEN_TUYEN',
                    'error_name' => 'Thiếu số giấy chuyển tuyến',
                    'description' => 'Số giấy chuyển tuyến không được để trống khi BN đến KCB tại CS'
                ]);
            }
        }

        if (empty($data->ma_hsba)) {
            $errors->push((object)[
                'error_code' => $this->prefix . 'ADMIN_INFO_ERROR_MA_HSBA',
                'error_name' => 'Thiếu mã hồ sơ bệnh án',
                'description' => 'Mã hồ sơ bệnh án không được để trống'
            ]);
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
                    'description' => 'Mã thủ trưởng đơn vị chưa được duyệt danh mục (Mã BHXH: ' . $data->ma_ttdv . ')'
                ]);
            }
        }

        if (!empty($data->ma_the_bhyt)) {
            if (empty($data->ma_dkbd)) {
                $errors->push((object)[
                    'error_code' => $this->prefix . 'ADMIN_INFO_ERROR_MA_DKBD',
                    'error_name' => 'Thiếu mã đăng ký khám bệnh ban đầu',
                    'description' => 'Mã đăng ký khám bệnh ban đầu không được để trống khi có mã thẻ BHYT'
                ]);
            }

            if (empty($data->gt_the_tu)) {
                $errors->push((object)[
                    'error_code' => $this->prefix . 'ADMIN_INFO_ERROR_GT_THE_TU',
                    'error_name' => 'Thiếu giá trị thẻ từ ngày',
                    'description' => 'Giá trị thẻ từ ngày không được để trống khi có mã thẻ BHYT'
                ]);
            }

            if (empty($data->gt_the_den)) {
                $errors->push((object)[
                    'error_code' => $this->prefix . 'ADMIN_INFO_ERROR_GT_THE_DEN',
                    'error_name' => 'Thiếu giá trị thẻ đến ngày',
                    'description' => 'Giá trị thẻ đến ngày không được để trống khi có mã thẻ BHYT'
                ]);
            }

            if (empty($data->ly_do_vv)) {
                $errors->push((object)[
                    'error_code' => $this->prefix . 'ADMIN_INFO_ERROR_LY_DO_VV',
                    'error_name' => 'Thiếu lý do vào viện',
                    'description' => 'Lý do vào viện không được để trống'
                ]);
            }
        }
        
        return $errors;
    }
}
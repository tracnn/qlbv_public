<?php

namespace App\Services;

use App\Models\BHYT\Qd130Xml14;
use App\Models\BHYT\MedicalStaff;
use Illuminate\Support\Collection;

class Qd130Xml14Checker
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
        $this->xmlType = 'XML14';
        $this->prefix = $this->xmlType . '_';

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
            $errors->push((object)[
                'error_code' => $this->prefix . 'INFO_ERROR_SO_GIAYHEN_KL',
                'error_name' => 'Thiếu số giấy hẹn khám lại',
                'description' => 'Số giấy hẹn khám lại không được để trống'
            ]);
        }

        if (empty($data->ma_cskcb)) {
            $errors->push((object)[
                'error_code' => $this->prefix . 'INFO_ERROR_MA_CSKCB',
                'error_name' => 'Thiếu mã CSKCB',
                'description' => 'CSKCB không được để trống'
            ]);
        }

        if (empty($data->ngay_vao)) {
            $errors->push((object)[
                'error_code' => $this->prefix . 'INFO_ERROR_NGAY_VAO',
                'error_name' => 'Thiếu ngày vào',
                'description' => 'Ngày vào không được để trống'
            ]);
        }

        if (empty($data->ngay_ra)) {
            $errors->push((object)[
                'error_code' => $this->prefix . 'INFO_ERROR_NGAY_RA',
                'error_name' => 'Thiếu ngày ra',
                'description' => 'Ngày ra không được để trống'
            ]);
        }

        if (empty($data->ngay_hen_kl)) {
            $errors->push((object)[
                'error_code' => $this->prefix . 'INFO_ERROR_NGAY_HEN_KL',
                'error_name' => 'Thiếu ngày hẹn khám lại',
                'description' => 'Ngày hẹn khám lại không được để trống'
            ]);
        } else {
            if (!empty($data->ngay_ra) && ($data->ngay_hen_kl < $data->ngay_ra)) {
                $errors->push((object)[
                    'error_code' => $this->prefix . 'INFO_ERROR_NGAY_HEN_KL_SMALLER_NGAY_RA',
                    'error_name' => 'Ngày hẹn khám lại nhỏ hơn ngày ra',
                    'description' => 'Ngày hẹn khám lại không được nhỏ hơn ngày ra: ' . $data->ngay_hen_kl . ' < ' . $data->ngay_ra
                ]);
            }
        }

        if (empty($data->chan_doan_rv)) {
            $errors->push((object)[
                'error_code' => $this->prefix . 'INFO_ERROR_CHAN_DOAN_RV',
                'error_name' => 'Thiếu chẩn đoán ra viện',
                'description' => 'Chẩn đoán ra viện không được để trống'
            ]);
        }

        if (empty($data->ma_benh_chinh)) {
            $errors->push((object)[
                'error_code' => $this->prefix . 'INFO_ERROR_MA_BENH_CHINH',
                'error_name' => 'Thiếu mã bệnh chính',
                'description' => 'Mã bệnh chính không được để trống'
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
        }

        if (empty($data->ma_doituong_kcb)) {
            $errors->push((object)[
                'error_code' => $this->prefix . 'INFO_ERROR_MA_DOITUONG_KCB',
                'error_name' => 'Thiếu mã đối tượng KCB',
                'description' => 'Đối tượng KCB không được để trống'
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

        if (empty($data->ngay_ct)) {
            $errors->push((object)[
                'error_code' => $this->prefix . 'INFO_ERROR_NGAY_CT',
                'error_name' => 'Thiếu ngày chứng từ',
                'description' => 'Ngày chứng từ không được để trống'
            ]);
        }

        return $errors;
    }

    // Thêm các phương thức kiểm tra khác ở đây
}
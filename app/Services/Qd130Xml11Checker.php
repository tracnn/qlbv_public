<?php

namespace App\Services;

use App\Models\BHYT\Qd130Xml11;
use App\Models\BHYT\MedicalStaff;
use Illuminate\Support\Collection;

class Qd130Xml11Checker
{
    protected $xmlErrorService;
    protected $prefix;

    protected $xmlType;

    protected $docXml11Type;

    public function __construct(Qd130XmlErrorService $xmlErrorService)
    {
        $this->xmlErrorService = $xmlErrorService;
        $this->setConditions();
    }

    protected function setConditions()
    {
        $this->xmlType = 'XML11';
        $this->prefix = $this->xmlType . '_';
        $this->docXml11Type = ['CT07'];

    }

    /**
     * Check Qd130Xml11 Errors
     *
     * @param Qd130Xml11 $data
     * @return void
     */
    public function checkErrors(Qd130Xml11 $data): void
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
     * @param Qd130Xml11 $data
     * @return Collection
     */
    private function infoChecker(Qd130Xml11 $data): Collection
    {
        $errors = collect();

        if (empty($data->so_ct)) {
            $errors->push((object)[
                'error_code' => $this->prefix . 'INFO_ERROR_SO_CT',
                'error_name' => 'Thiếu số chứng từ',
                'description' => 'Số chứng từ không được để trống'
            ]);
        }

        if (empty($data->so_seri)) {
            $errors->push((object)[
                'error_code' => $this->prefix . 'INFO_ERROR_SO_SERI',
                'error_name' => 'Thiếu số serial',
                'description' => 'Số serial không được để trống'
            ]);
        }

        if (empty($data->so_kcb)) {
            $errors->push((object)[
                'error_code' => $this->prefix . 'INFO_ERROR_SO_KCB',
                'error_name' => 'Thiếu số KCB',
                'description' => 'Số KCB không được để trống'
            ]);
        }

        if (empty($data->ma_bhxh)) {
            $errors->push((object)[
                'error_code' => $this->prefix . 'INFO_ERROR_MA_BHXH',
                'error_name' => 'Thiếu mã BHXH',
                'description' => 'Mã BHXH không được để trống'
            ]);
        } elseif (strlen($data->ma_bhxh) !== 10) {
            $errors->push((object)[
                'error_code' => $this->prefix . 'INFO_ERROR_MA_BHXH_LENGTH',
                'error_name' => 'Mã BHXH không hợp lệ',
                'description' => 'Mã BHXH phải có độ dài là 10 ký tự: ' . $data->ma_bhxh
            ]);
        }

        if (empty($data->ma_the_bhyt)) {
            $errors->push((object)[
                'error_code' => $this->prefix . 'INFO_ERROR_MA_THE_BHYT',
                'error_name' => 'Thiếu mã thẻ BHYT',
                'description' => 'Mã thẻ BHYT không được để trống'
            ]);
        } elseif (strlen($data->ma_the_bhyt) !== 15) {
            $errors->push((object)[
                'error_code' => $this->prefix . 'INFO_ERROR_MA_THE_BHYT_LENGTH',
                'error_name' => 'Mã thẻ BHYT không hợp lệ',
                'description' => 'Mã thẻ BHYT phải có độ dài là 15 ký tự: ' . $data->ma_the_bhyt
            ]);
        }

        if (empty($data->chan_doan_rv)) {
            $errors->push((object)[
                'error_code' => $this->prefix . 'INFO_ERROR_CHAN_DOAN_RV',
                'error_name' => 'Thiếu chẩn đoán ra viện',
                'description' => 'Chẩn đoán ra viện không được để trống'
            ]);
        }

        if (empty($data->pp_dieutri)) {
            $errors->push((object)[
                'error_code' => $this->prefix . 'INFO_ERROR_PP_DIEUTRI',
                'error_name' => 'Thiếu phương pháp điều trị',
                'description' => 'Phương pháp điều trị không được để trống'
            ]);
        }

        if (empty($data->so_ngay_nghi)) {
            $errors->push((object)[
                'error_code' => $this->prefix . 'INFO_ERROR_SO_NGAY_NGHI',
                'error_name' => 'Thiếu số ngày nghỉ',
                'description' => 'Số ngày nghỉ không được để trống'
            ]);
        } elseif (!ctype_digit((string)$data->so_ngay_nghi) || (int)$data->so_ngay_nghi <= 0) {
            $errors->push((object)[
                'error_code' => $this->prefix . 'INFO_ERROR_SO_NGAY_NGHI_NOT_INT',
                'error_name' => 'Số ngày nghỉ không hợp lệ',
                'description' => 'Số ngày nghỉ phải là một số nguyên lớn hơn 0: ' . $data->so_ngay_nghi
            ]);
        }

        if (empty($data->tu_ngay)) {
            $errors->push((object)[
                'error_code' => $this->prefix . 'INFO_ERROR_TU_NGAY',
                'error_name' => 'Thiếu từ ngày',
                'description' => 'Từ ngày không được để trống'
            ]);
        }

        if (empty($data->den_ngay)) {
            $errors->push((object)[
                'error_code' => $this->prefix . 'INFO_ERROR_DEN_NGAY',
                'error_name' => 'Thiếu đến ngày',
                'description' => 'Đến ngày không được để trống'
            ]);
        }

        if (empty($data->ngay_ct)) {
            $errors->push((object)[
                'error_code' => $this->prefix . 'INFO_ERROR_NGAY_CT',
                'error_name' => 'Thiếu ngày chứng từ',
                'description' => 'Ngày chứng từ không được để trống'
            ]);
        }

        if (!empty($data->tu_ngay) && !empty($data->den_ngay)) {
            $tu_ngay_date = \DateTime::createFromFormat('Ymd', $data->tu_ngay);
            $den_ngay_date = \DateTime::createFromFormat('Ymd', $data->den_ngay);

            if ($tu_ngay_date && $den_ngay_date) {
                if ($den_ngay_date < $tu_ngay_date) {
                    $errors->push((object)[
                        'error_code' => $this->prefix . 'INFO_ERROR_DATE_RANGE',
                        'error_name' => 'Ngày không hợp lệ',
                        'description' => 'Đến ngày phải lớn hơn hoặc bằng từ ngày'
                    ]);
                }
            } else {
                $errors->push((object)[
                    'error_code' => $this->prefix . 'INFO_ERROR_DATE_FORMAT',
                    'error_name' => 'Định dạng ngày không hợp lệ',
                    'description' => 'Định dạng ngày phải là Ymd'
                ]);
            }
        }

        if (empty($data->ma_bs)) {
            $errors->push((object)[
                'error_code' => $this->prefix . 'INFO_ERROR_MA_BS',
                'error_name' => 'Thiếu mã bác sĩ (Mã BHXH)',
                'description' => 'Mã bác sĩ (Mã BHXH) không được để trống'
            ]);
        } else {
            $staff = MedicalStaff::where('ma_bhxh', $data->ma_bs)->exists();
            if (!$staff) {
                $errors->push((object)[
                    'error_code' => $this->prefix . 'INVALID_MEDICAL_STAFF_MA_BS',
                    'error_name' => 'Mã bác sĩ (Mã BHXH) chưa được duyệt',
                    'description' => 'Mã bác sĩ chưa được duyệt danh mục (Mã BHXH: ' . $data->ma_bs . ')'
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
            $staff = MedicalStaff::where('ma_bhxh', $data->ma_ttdv)->exists();
            if (!$staff) {
                $errors->push((object)[
                    'error_code' => $this->prefix . 'INVALID_MEDICAL_STAFF_MA_TTDV',
                    'error_name' => 'Mã thủ trưởng đơn vị chưa được duyệt',
                    'description' => 'Mã thủ trưởng đơn vị chưa được duyệt danh mục (Mã CCHN: ' . $data->ma_ttdv . ')'
                ]);
            }
        }

        if (empty($data->mau_so)) {
            $errors->push((object)[
                'error_code' => $this->prefix . 'INFO_ERROR_MAU_SO',
                'error_name' => 'Thiếu mẫu số',
                'description' => 'Mẫu số không được để trống'
            ]);
        } elseif (!in_array($data->mau_so, $this->docXml11Type)) {
            $errors->push((object)[
                'error_code' => $this->prefix . 'INFO_ERROR_INVALID_MAU_SO',
                'error_name' => 'Mẫu số không hợp lệ',
                'description' => 'Mẫu số không thuộc loại hợp lệ. Mẫu số: ' . $data->mau_so . '. Loại hợp lệ: ' . $this->docXml11Type
            ]);
        }

        return $errors;
    }

    // Thêm các phương thức kiểm tra khác ở đây
}
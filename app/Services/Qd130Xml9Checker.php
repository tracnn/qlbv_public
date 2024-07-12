<?php

namespace App\Services;

use App\Models\BHYT\Qd130Xml9;
use App\Models\BHYT\MedicalStaff;
use Illuminate\Support\Collection;

use DateTime;

class Qd130Xml9Checker
{
    protected $xmlErrorService;
    protected $prefix;

    protected $xmlType;
    protected $heinCardTempPattern;

    public function __construct(Qd130XmlErrorService $xmlErrorService)
    {
        $this->xmlErrorService = $xmlErrorService;
        $this->setConditions();
    }

    protected function setConditions()
    {
        $this->xmlType = 'XML9';
        $this->prefix = $this->xmlType . '_';
        $this->heinCardTempPattern = '/^TE101\d{10}$/';
    }

    /**
     * Check Qd130Xml11 Errors
     *
     * @param Qd130Xml11 $data
     * @return void
     */
    public function checkErrors(Qd130Xml9 $data): void
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
    private function infoChecker(Qd130Xml9 $data): Collection
    {
        $errors = collect();

        if (empty($data->ma_bhxh_nnd)) {
            $errors->push((object)[
                'error_code' => $this->prefix . 'INFO_ERROR_MA_BHXH_NND',
                'error_name' => 'Thiếu mã BHXH Người nuôi dưỡng',
                'critical_error' => true,
                'description' => 'Mã BHXH người nuôi dưỡng không được để trống'
            ]);
        } elseif (strlen($data->ma_bhxh_nnd) !== 10) {
            $errors->push((object)[
                'error_code' => $this->prefix . 'INFO_ERROR_MA_BHXH_NND_LENGTH',
                'error_name' => 'Mã BHXH Người nuôi dưỡng không hợp lệ',
                'critical_error' => true,
                'description' => 'Mã BHXH Người nuôi dưỡng phải có độ dài là 10 ký tự: ' . $data->ma_bhxh_nnd
            ]);
        }

        if (empty($data->ma_the_nnd)) {
            $errors->push((object)[
                'error_code' => $this->prefix . 'INFO_ERROR_MA_THE_NND',
                'error_name' => 'Thiếu mã thẻ BHYT Người nuôi dưỡng',
                'critical_error' => true,
                'description' => 'Mã thẻ BHYT Người nuôi dưỡng không được để trống'
            ]);
        } elseif (strlen($data->ma_the_nnd) !== 15) {
            $errors->push((object)[
                'error_code' => $this->prefix . 'INFO_ERROR_MA_THE_BHYT_NND_LENGTH',
                'error_name' => 'Mã thẻ BHYT Người nuôi dưỡng không hợp lệ',
                'critical_error' => true,
                'description' => 'Mã thẻ BHYT Người nuôi dưỡng phải có độ dài là 15 ký tự: ' . $data->ma_the_nnd
            ]);
        }

        if (empty($data->ho_ten_nnd)) {
            $errors->push((object)[
                'error_code' => $this->prefix . 'INFO_ERROR_HO_TEN_NND',
                'error_name' => 'Thiếu họ tên người nuôi dưỡng',
                'critical_error' => true,
                'description' => 'Họ tên người nuôi dưỡng không được để trống'
            ]);
        }

        if (empty($data->ngaysinh_nnd)) {
            $errors->push((object)[
                'error_code' => $this->prefix . 'INFO_ERROR_NGAYSINH_NND',
                'error_name' => 'Thiếu ngày sinh người nuôi dưỡng',
                'critical_error' => true,
                'description' => 'Ngày sinh người nuôi dưỡng không được để trống'
            ]);
        } else {
            $ngaysinh_nnd_date = DateTime::createFromFormat('Ymd', $data->ngaysinh_nnd);
            
            // Check if the date is valid and matches the Ymd format
            if (!$ngaysinh_nnd_date || $ngaysinh_nnd_date->format('Ymd') !== $data->ngaysinh_nnd) {
                $errors->push((object)[
                    'error_code' => $this->prefix . 'INFO_ERROR_INVALID_NGAYSINH_NND',
                    'error_name' => 'Định dạng ngày sinh không hợp lệ',
                    'critical_error' => true,
                    'description' => 'Ngày sinh người nuôi dưỡng phải đúng định dạng Ymd: ' . $data->ngaysinh_nnd
                ]);
            }
        }

        if (empty($data->ma_dantoc_nnd)) {
            $errors->push((object)[
                'error_code' => $this->prefix . 'INFO_ERROR_MA_DANTOC_NND',
                'error_name' => 'Thiếu mã dân tộc người nuôi dưỡng',
                'critical_error' => true,
                'description' => 'Mã dân tộc người nuôi dưỡng không được để trống'
            ]);
        } else {
            // Check if ma_dantoc_nnd is exactly 2 digits
            if (!preg_match('/^\d{2}$/', $data->ma_dantoc_nnd)) {
                $errors->push((object)[
                    'error_code' => $this->prefix . 'INFO_ERROR_INVALID_MA_DANTOC_NND',
                    'error_name' => 'Định dạng mã dân tộc không hợp lệ',
                    'critical_error' => true,
                    'description' => 'Mã dân tộc người nuôi dưỡng phải là 2 ký tự số: ' . $data->ma_dantoc_nnd
                ]);
            }
        }

        if (empty($data->so_cccd_nnd)) {
            $errors->push((object)[
                'error_code' => $this->prefix . 'INFO_ERROR_SO_CCCD_NND',
                'error_name' => 'Thiếu CCCD người nuôi dưỡng',
                'critical_error' => true,
                'description' => 'CCCD người nuôi dưỡng không được để trống'
            ]);
        }

        if (empty($data->ngaycap_cccd_nnd)) {
            $errors->push((object)[
                'error_code' => $this->prefix . 'INFO_ERROR_NGAYCAP_CCCD_NND',
                'error_name' => 'Thiếu ngày cấp CCCD người nuôi dưỡng',
                'critical_error' => true,
                'description' => 'Ngày ngày cấp CCCD người nuôi dưỡng không được để trống'
            ]);
        } else {
            $ngaycap_cccd_nnd_date = DateTime::createFromFormat('Ymd', $data->ngaycap_cccd_nnd);
            
            // Check if the date is valid and matches the Ymd format
            if (!$ngaycap_cccd_nnd_date || $ngaycap_cccd_nnd_date->format('Ymd') !== $data->ngaycap_cccd_nnd) {
                $errors->push((object)[
                    'error_code' => $this->prefix . 'INFO_ERROR_INVALID_NGAYCAP_CCCD_NND',
                    'error_name' => 'Định dạng ngày cấp CCCD không hợp lệ',
                    'critical_error' => true,
                    'description' => 'Ngày cấp CCCD người nuôi dưỡng phải đúng định dạng Ymd: ' . $data->ngaycap_cccd_nnd
                ]);
            }
        }

        if (empty($data->noicap_cccd_nnd)) {
            $errors->push((object)[
                'error_code' => $this->prefix . 'INFO_ERROR_NOICAP_CCCD_NND',
                'error_name' => 'Thiếu nơi cấp CCCD người nuôi dưỡng',
                'critical_error' => true,
                'description' => 'Nơi cấp CCCD người nuôi dưỡng không được để trống'
            ]);
        }

        if (empty($data->noi_cu_tru_nnd)) {
            $errors->push((object)[
                'error_code' => $this->prefix . 'INFO_ERROR_NOI_CU_TRU_NND',
                'error_name' => 'Thiếu nơi cư trú người nuôi dưỡng',
                'critical_error' => true,
                'description' => 'Nơi cư trú người nuôi dưỡng không được để trống'
            ]);
        }

        if (empty($data->ma_quoctich)) {
            $errors->push((object)[
                'error_code' => $this->prefix . 'INFO_ERROR_MA_QUOCTICH',
                'error_name' => 'Thiếu mã quốc tịch',
                'critical_error' => true,
                'description' => 'Mã quốc tịch không được để trống'
            ]);
        } else {
            if (!preg_match('/^\d{3}$/', $data->ma_quoctich)) {
                $errors->push((object)[
                    'error_code' => $this->prefix . 'INFO_ERROR_INVALID_MA_QUOCTICH',
                    'error_name' => 'Định dạng mã quốc tịch không hợp lệ',
                    'critical_error' => true,
                    'description' => 'Mã quốc tịch phải là 3 ký tự số: ' . $data->ma_quoctich
                ]);
            }
        }

        if (empty($data->matinh_cu_tru)) {
            $errors->push((object)[
                'error_code' => $this->prefix . 'INFO_ERROR_MATINH_CU_TRU',
                'error_name' => 'Thiếu mã tỉnh cư trú',
                'critical_error' => true,
                'description' => 'Mã tỉnh cư trú không được để trống'
            ]);
        } else {
            if (!preg_match('/^\d{2}$/', $data->matinh_cu_tru)) {
                $errors->push((object)[
                    'error_code' => $this->prefix . 'INFO_ERROR_INVALID_MATINH_CU_TRU',
                    'error_name' => 'Định dạng mã tỉnh cư trú không hợp lệ',
                    'critical_error' => true,
                    'description' => 'Mã tỉnh cư trú phải là 2 ký tự số: ' . $data->matinh_cu_tru
                ]);
            }
        }

        if (empty($data->mahuyen_cu_tru)) {
            $errors->push((object)[
                'error_code' => $this->prefix . 'INFO_ERROR_MAHUYEN_CU_TRU',
                'error_name' => 'Thiếu mã huyện cư trú',
                'critical_error' => true,
                'description' => 'Mã huyện cư trú không được để trống'
            ]);
        } else {
            if (!preg_match('/^\d{3}$/', $data->mahuyen_cu_tru)) {
                $errors->push((object)[
                    'error_code' => $this->prefix . 'INFO_ERROR_INVALID_MAHUYEN_CU_TRU',
                    'error_name' => 'Định dạng mã huyện cư trú không hợp lệ',
                    'critical_error' => true,
                    'description' => 'Mã huyện cư trú phải là 3 ký tự số: ' . $data->mahuyen_cu_tru
                ]);
            }
        }

        if (empty($data->maxa_cu_tru)) {
            $errors->push((object)[
                'error_code' => $this->prefix . 'INFO_ERROR_MAXA_CU_TRU',
                'error_name' => 'Thiếu mã xã cư trú',
                'critical_error' => true,
                'description' => 'Mã xã cư trú không được để trống'
            ]);
        } else {
            if (!preg_match('/^\d{5}$/', $data->maxa_cu_tru)) {
                $errors->push((object)[
                    'error_code' => $this->prefix . 'INFO_ERROR_INVALID_MAXA_CU_TRU',
                    'error_name' => 'Định dạng mã xã cư trú không hợp lệ',
                    'critical_error' => true,
                    'description' => 'Mã xã cư trú phải là 5 ký tự số: ' . $data->maxa_cu_tru
                ]);
            }
        }

        if (empty($data->ma_the_tam)) {
            $errors->push((object)[
                'error_code' => $this->prefix . 'INFO_ERROR_MA_THE_TAM',
                'error_name' => 'Thiếu mã thẻ tạm',
                'critical_error' => true,
                'description' => 'Mã thẻ tạm không được để trống'
            ]);
        } else {
            if (!preg_match($this->heinCardTempPattern, $data->ma_the_tam)) {
                $errors->push((object)[
                    'error_code' => $this->prefix . 'INFO_ERROR_INVALID_MA_THE_TAM',
                    'error_name' => 'Định dạng mã thẻ tạm không hợp lệ',
                    'critical_error' => true,
                    'description' => 'Mã thẻ tạm không đúng theo quy định: ' . $data->ma_the_tam
                ]);
            }
        }

        if (empty($data->ho_ten_con)) {
            $errors->push((object)[
                'error_code' => $this->prefix . 'INFO_ERROR_HO_TEN_CON',
                'error_name' => 'Thiếu họ tên con',
                'critical_error' => true,
                'description' => 'Họ tên con không được để trống'
            ]);
        }

        if (empty($data->ma_ttdv)) {
            $errors->push((object)[
                'error_code' => $this->prefix . 'INFO_ERROR_MA_TTDV',
                'error_name' => 'Thiếu mã thủ trưởng đơn vị',
                'critical_error' => true,
                'description' => 'Thủ trưởng đơn vị không được để trống'
            ]);
        } else {
            $staff = MedicalStaff::where('ma_bhxh', $data->ma_ttdv)->exists();
            if (!$staff) {
                $errors->push((object)[
                    'error_code' => $this->prefix . 'INVALID_MEDICAL_STAFF_MA_TTDV',
                    'error_name' => 'Mã thủ trưởng đơn vị chưa được duyệt',
                    'critical_error' => true,
                    'description' => 'Mã thủ trưởng đơn vị chưa được duyệt danh mục (Mã CCHN: ' . $data->ma_ttdv . ')'
                ]);
            }
        }

        return $errors;
    }

    // Thêm các phương thức kiểm tra khác ở đây
}
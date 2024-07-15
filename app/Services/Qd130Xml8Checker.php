<?php

namespace App\Services;

use App\Models\BHYT\Qd130Xml8;
use App\Models\BHYT\MedicalStaff;
use Illuminate\Support\Collection;

use DateTime;

class Qd130Xml8Checker
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
        $this->xmlType = 'XML8';
        $this->prefix = $this->xmlType . '_';
    }

    /**
     * Check Qd130Xml8 Errors
     *
     * @param Qd130Xml8 $data
     * @return void
     */
    public function checkErrors(Qd130Xml8 $data): void
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
     * @param Qd130Xml8 $data
     * @return Collection
     */
    private function infoChecker(Qd130Xml8 $data): Collection
    {
        $errors = collect();

        if (empty($data->pp_dieutri)) {
            $errors->push((object)[
                'error_code' => $this->prefix . 'INFO_ERROR_PP_DIEUTRI',
                'error_name' => 'Thiếu phương pháp điều trị',
                'critical_error' => true,
                'description' => 'Phương pháp điều trị không được để trống'
            ]);
        }

        if (empty($data->tomtat_kq)) {
            $errors->push((object)[
                'error_code' => $this->prefix . 'INFO_ERROR_TOMTAT_KQ',
                'error_name' => 'Thiếu tóm tắt kết quả',
                'critical_error' => true,
                'description' => 'Tóm tắt kết quả không được để trống'
            ]);
        } elseif (strlen($data->tomtat_kq) > 4000) {
            $errors->push((object)[
                'error_code' => $this->prefix . 'INFO_ERROR_TOMTAT_KQ_LENGTH',
                'error_name' => 'Tóm tắt kết quả vượt quá 4000 ký tự',
                'critical_error' => true,
                'description' => 'Tóm tắt kết quả không được vượt quá 4000 ký tự'
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
            if(!MedicalStaff::where('ma_bhxh', $data->ma_ttdv)->exists()) {
                $errors->push((object)[
                    'error_code' => $this->prefix . 'INFO_ERROR_MA_TTDV_NOT_FOUND',
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
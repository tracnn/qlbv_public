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
                'description' => 'Phương pháp điều trị không được để trống'
            ]);
        }

        if (empty($data->tomtat_kq)) {
            $errors->push((object)[
                'error_code' => $this->prefix . 'INFO_ERROR_TOMTAT_KQ',
                'error_name' => 'Thiếu tóm tắt kết quả',
                'description' => 'Tóm tắt kết quả không được để trống'
            ]);
        }

        if (empty($data->ma_ttdv)) {
            $errors->push((object)[
                'error_code' => $this->prefix . 'INFO_ERROR_MA_TTDV',
                'error_name' => 'Thiếu mã thủ trưởng đơn vị',
                'description' => 'Thủ trưởng đơn vị không được để trống'
            ]);
        } else {
            if(!MedicalStaff::where('ma_bhxh', $data->ma_ttdv)->exists()) {
                $errors->push((object)[
                    'error_code' => $this->prefix . 'INFO_ERROR_MA_TTDV_NOT_FOUND',
                    'error_name' => 'Mã thủ trưởng đơn vị chưa được duyệt',
                    'description' => 'Mã thủ trưởng đơn vị chưa được duyệt danh mục (Mã CCHN: ' . $data->ma_ttdv . ')'
                ]);
            }
        }

        return $errors;
    }

    // Thêm các phương thức kiểm tra khác ở đây
}
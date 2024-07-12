<?php

namespace App\Services;

use App\Models\BHYT\Qd130Xml7;
use App\Models\BHYT\MedicalStaff;
use Illuminate\Support\Collection;

use DateTime;

class Qd130Xml7Checker
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
        $this->xmlType = 'XML7';
        $this->prefix = $this->xmlType . '_';
    }

    /**
     * Check Qd130Xml7 Errors
     *
     * @param Qd130Xml7 $data
     * @return void
     */
    public function checkErrors(Qd130Xml7 $data): void
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
     * @param Qd130Xml7 $data
     * @return Collection
     */
    private function infoChecker(Qd130Xml7 $data): Collection
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

        if (empty($data->ma_bs)) {
            $errors->push((object)[
                'error_code' => $this->prefix . 'INFO_ERROR_MA_BS',
                'error_name' => 'Thiếu mã bác sĩ',
                'critical_error' => true,
                'description' => 'Mã bác sĩ không được để trống'
            ]);
        } else {
            if (!MedicalStaff::where('ma_bhxh', $data->ma_bs)->exists()) {
                $errors->push((object)[
                    'error_code' => $this->prefix . 'INFO_ERROR_MA_BS_NOT_FOUND',
                    'error_name' => 'Mã bác sĩ chưa được duyệt',
                    'critical_error' => true,
                    'description' => 'Mã bác sí chưa được duyệt danh mục NVYT: ' . $data->ma_bs
                ]);
            }
        }

        return $errors;
    }

    // Thêm các phương thức kiểm tra khác ở đây
}
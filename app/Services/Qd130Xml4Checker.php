<?php

namespace App\Services;

use App\Models\BHYT\Qd130Xml4;
use App\Models\BHYT\ServiceCatalog;
use App\Models\BHYT\MedicalStaff;
use Illuminate\Support\Collection;

class Qd130Xml4Checker
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
        $this->xmlType = 'XML4';
        $this->prefix = $this->xmlType . '_';
    }

    /**
     * Check Qd130Xml4 Errors
     *
     * @param Qd130Xml4 $data
     * @return void
     */
    public function checkErrors(Qd130Xml4 $data): void
    {
        // Thực hiện kiểm tra lỗi
        $errors = collect();

        $errors = $errors->merge($this->infoChecker($data));

        $additionalData = [
            'ngay_yl' => $data->ngay_yl
        ];

        if (!empty($data->ngay_kq)) {
            $additionalData['ngay_kq'] = $data->ngay_kq;
        }

        // Save errors to xml_error_checks table
        $this->xmlErrorService->saveErrors($this->xmlType, $data->ma_lk, $data->stt, $errors, $additionalData);
    }

    /**
     * Check for outpatient bed day errors
     *
     * @param Qd130Xml4 $data
     * @return Collection
     */
    private function infoChecker(Qd130Xml4 $data): Collection
    {
        $errors = collect();


        if (empty($data->ma_dich_vu)) {
            $errors->push((object)[
                'error_code' => $this->prefix . 'INFO_ERROR_MA_DICH_VU',
                'error_name' => 'Thiếu mã dịch vụ',
                'critical_error' => true,
                'description' => 'Mã dịch vụ không được để trống'
            ]);
        } else {
            if (!ServiceCatalog::where('ma_dich_vu', $data->ma_dich_vu)->exists()) {
                $errors->push((object)[
                    'error_code' => $this->prefix . 'INFO_ERROR_MA_DICH_VU_NOT_FOUND',
                    'error_name' => 'Mã dịch vụ không tồn tại',
                    'critical_error' => true,
                    'description' => 'Mã dịch vụ không tồn tại trong danh mục DVKT: ' . $data->ma_dich_vu
                ]);
            }
        }

        if (empty($data->ngay_kq)) {
            $errors->push((object)[
                'error_code' => $this->prefix . 'INFO_ERROR_NGAY_KQ',
                'error_name' => 'Thiếu ngày trả kết quả',
                'critical_error' => true,
                'description' => 'Ngày trả kết quả không được để trống'
            ]);
        }

        if (empty($data->ma_bs_doc_kq)) {
            $errors->push((object)[
                'error_code' => $this->prefix . 'INFO_ERROR_MA_BS_DOC_KQ',
                'error_name' => 'Thiếu mã bác sĩ đọc kết quả',
                'critical_error' => true,
                'description' => 'Mã bác sĩ đọc kết quả không được để trống'
            ]);
        } else {
            if (!MedicalStaff::where('ma_bhxh', $data->ma_bs_doc_kq)->exists()) {
                $errors->push((object)[
                    'error_code' => $this->prefix . 'INFO_ERROR_MA_BS_DOC_KQ_NOT_FOUND',
                    'error_name' => 'Mã bác sĩ đọc kết quả chưa được duyệt',
                    'critical_error' => true,
                    'description' => 'Mã bác sĩ đọc kết quả chưa được duyệt danh mục NVYT: ' . $data->ma_bs_doc_kq
                ]);
            }
        }

        return $errors;
    }

    // Thêm các phương thức kiểm tra khác ở đây
}
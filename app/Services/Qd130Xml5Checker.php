<?php

namespace App\Services;

use App\Models\BHYT\Qd130Xml5;
use App\Models\BHYT\MedicalStaff;
use Illuminate\Support\Collection;

class Qd130Xml5Checker
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
        $this->xmlType = 'XML5';
        $this->prefix = $this->xmlType . '_';
    }

    protected function generateErrorCode(string $errorKey): string
    {
        return $this->prefix . $errorKey;
    }

    /**
     * Check Qd130Xml4 Errors
     *
     * @param Qd130Xml4 $data
     * @return void
     */
    public function checkErrors(Qd130Xml5 $data): void
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
     * @param Qd130Xml5 $data
     * @return Collection
     */
    private function infoChecker(Qd130Xml5 $data): Collection
    {
        $errors = collect();


        if (empty($data->dien_bien_ls)) {
            $errorCode = $this->generateErrorCode('INFO_ERROR_DIEN_BIEN_LS');
            $errors->push((object)[
                'error_code' => $errorCode,
                'error_name' => 'Thiếu diễn biến lâm sàng',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description' => 'Diễn biến lâm sàng không được để trống'
            ]);
        }

        if (empty($data->thoi_diem_dbls)) {
            $errorCode = $this->generateErrorCode('INFO_ERROR_THOI_DIEM_DBLS');
            $errors->push((object)[
                'error_code' => $errorCode,
                'error_name' => 'Thiếu thời điểm diễn biến lâm sàng',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description' => 'Thời điểm diễn biến lâm sàng không được để trống'
            ]);
        } else {
            if ($data->Qd130Xml1) {
                $ngayVao = $data->Qd130Xml1->ngay_vao;
                $ngayRa = $data->Qd130Xml1->ngay_ra;
                $thoi_diem_dbls = $data->thoi_diem_dbls;

                if ($thoi_diem_dbls < $ngayVao || $thoi_diem_dbls > $ngayRa) {
                    $errorCode = $this->generateErrorCode('INVALID_THOI_DIEM_BDLS');
                    $errors->push((object)[
                        'error_code' => $errorCode,
                        'error_name' => 'Thời điểm BĐLS không hợp lệ',
                        'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                        'description' => 'THOI_DIEM_BDLS: ' . strtodatetime($thoi_diem_dbls) . ' không nằm trong khoảng thời gian vào (' . strtodatetime($ngayVao) . ') và ra (' . strtodatetime($ngayRa) . ')'
                    ]);
                }
            }
        }

        if (empty($data->nguoi_thuc_hien)) {
            $errorCode = $this->generateErrorCode('INFO_ERROR_NGUOI_THUC_HIEN');
            $errors->push((object)[
                'error_code' => $errorCode,
                'error_name' => 'Thiếu người thực hiện',
                'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                'description' => 'Người thực hiện không được để trống'
            ]);
        } else {
            if (!MedicalStaff::where('ma_bhxh', $data->nguoi_thuc_hien)->exists()) {
                $errorCode = $this->generateErrorCode('INFO_ERROR_NGUOI_THUC_HIEN_NOT_FOUND');
                $errors->push((object)[
                    'error_code' => $errorCode,
                    'error_name' => 'Người thực hiện chưa được duyệt',
                    'critical_error' => $this->xmlErrorService->getCriticalErrorStatus($errorCode),
                    'description' => 'Người thực hiện chưa được duyệt danh mục NVYT: ' . $data->nguoi_thuc_hien
                ]);
            }
        }

        return $errors;
    }

    // Thêm các phương thức kiểm tra khác ở đây
}
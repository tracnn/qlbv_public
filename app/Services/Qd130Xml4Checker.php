<?php

namespace App\Services;

use App\Models\BHYT\Qd130Xml4;
use App\Models\BHYT\Qd130Xml3;
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
            if (strlen($data->ma_dich_vu) > 15) {
                $errors->push((object)[
                    'error_code' => $this->prefix . 'INFO_ERROR_MA_DICH_VU_LENGTH',
                    'error_name' => 'Mã dịch vụ quá dài',
                    'critical_error' => true,
                    'description' => 'Mã dịch vụ không được lớn hơn 15 kí tự: ' . $data->ma_dich_vu
                ]);
            }

            if (!ServiceCatalog::where('ma_dich_vu', $data->ma_dich_vu)->exists()) {
                $errors->push((object)[
                    'error_code' => $this->prefix . 'INFO_ERROR_MA_DICH_VU_NOT_FOUND',
                    'error_name' => 'Mã dịch vụ không tồn tại',
                    'critical_error' => true,
                    'description' => 'Mã dịch vụ không tồn tại trong danh mục DVKT: ' . $data->ma_dich_vu
                ]);
            }
        }

        //ket_luan & mo_ta không được để trống
        if (!empty($data->ma_dich_vu)) {
            $require_mo_ta_ket_luan = Qd130Xml3::where('ma_lk', $data->ma_lk)
            ->where('ma_dich_vu', $data->ma_dich_vu)
            ->whereIn('ma_nhom', config('qd130xml.xml4.xml3_ma_nhom_require_ket_luan'))
            ->exists();

            if ($require_mo_ta_ket_luan && empty($data->mo_ta)) {
                $errors->push((object)[
                    'error_code' => $this->prefix . 'INFO_ERROR_MO_TA_EMPTY',
                    'error_name' => 'Mô tả không được để trống',
                    'critical_error' => true,
                    'description' => 'Mô tả không được để trống đối với DVKT: ' . $data->ma_dich_vu
                ]);
            }
            
            if ($require_mo_ta_ket_luan && empty($data->ket_luan)) {
                $errors->push((object)[
                    'error_code' => $this->prefix . 'INFO_ERROR_KET_LUAN_EMPTY',
                    'error_name' => 'Kết luận không được để trống',
                    'critical_error' => true,
                    'description' => 'Kết luận không được để trống đối với DVKT: ' . $data->ma_dich_vu
                ]);
            }
        }

        if (!empty($data->ma_chi_so)) {
            if (strlen($data->ma_chi_so) > 50) {
                $errors->push((object)[
                    'error_code' => $this->prefix . 'INFO_ERROR_MA_CHI_SO_LENGTH',
                    'error_name' => 'Mã chỉ số quá dài',
                    'critical_error' => true,
                    'description' => 'Mã chỉ số không được lớn hơn 50 kí tự: ' . $data->ma_chi_so
                ]);
            }
        }

        if (!empty($data->ten_chi_so)) {
            if (strlen($data->ten_chi_so) > 255) {
                $errors->push((object)[
                    'error_code' => $this->prefix . 'INFO_ERROR_TEN_CHI_SO_LENGTH',
                    'error_name' => 'Tên chỉ số quá dài',
                    'critical_error' => true,
                    'description' => 'Tên chỉ số không được lớn hơn 255 kí tự: ' . $data->ten_chi_so
                ]);
            }
        }

        if (!empty($data->gia_tri)) {
            if (strlen($data->gia_tri) > 50) {
                $errors->push((object)[
                    'error_code' => $this->prefix . 'INFO_ERROR_GIA_TRI_LENGTH',
                    'error_name' => 'Giá trị quá dài',
                    'critical_error' => true,
                    'description' => 'Giá trị không được lớn hơn 50 kí tự: ' . $data->gia_tri
                ]);
            }
        }

        if (!empty($data->don_vi_do)) {
            if (strlen($data->don_vi_do) > 50) {
                $errors->push((object)[
                    'error_code' => $this->prefix . 'INFO_ERROR_DON_VI_DO_LENGTH',
                    'error_name' => 'Đơn vị đo quá dài',
                    'critical_error' => true,
                    'description' => 'Đơn vị đo không được lớn hơn 50 kí tự: ' . $data->don_vi_do
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
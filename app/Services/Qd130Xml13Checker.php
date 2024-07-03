<?php

namespace App\Services;

use App\Models\BHYT\Qd130Xml13;
use Illuminate\Support\Collection;

class Qd130Xml13Checker
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
        $this->xmlType = 'XML13';
        $this->prefix = $this->xmlType . '_';

    }

    /**
     * Check Qd130Xml13 Errors
     *
     * @param Qd130Xml13 $data
     * @return void
     */
    public function checkErrors(Qd130Xml13 $data): void
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
     * @param Qd130Xml13 $data
     * @return Collection
     */
    private function infoChecker(Qd130Xml13 $data): Collection
    {
        $errors = collect();

        if (empty($data->so_hoso)) {
            $errors->push((object)[
                'error_code' => $this->prefix . 'INFO_ERROR_SO_HOSO',
                'error_name' => 'Thiếu số hồ sơ',
                'description' => 'Số hồ sơ không được để trống'
            ]);
        }

        if (empty($data->so_chuyentuyen)) {
            $errors->push((object)[
                'error_code' => $this->prefix . 'INFO_ERROR_SO_CHUYENTUYEN',
                'error_name' => 'Thiếu số chuyển tuyến',
                'description' => 'Số chuyển tuyến không được để trống'
            ]);
        }

        if (empty($data->giay_chuyen_tuyen)) {
            $errors->push((object)[
                'error_code' => $this->prefix . 'INFO_ERROR_GIAY_CHUYEN_TUYEN',
                'error_name' => 'Thiếu giấy chuyển tuyến',
                'description' => 'Giấy chuyển tuyến không được để trống'
            ]);
        }

        if (empty($data->ma_cskcb)) {
            $errors->push((object)[
                'error_code' => $this->prefix . 'INFO_ERROR_MA_CSKCB',
                'error_name' => 'Thiếu mã CSKCB',
                'description' => 'CSKCB không được để trống'
            ]);
        }

        if (empty($data->ma_noi_den)) {
            $errors->push((object)[
                'error_code' => $this->prefix . 'INFO_ERROR_MA_NOI_DEN',
                'error_name' => 'Thiếu mã nơi đến',
                'description' => 'Mã nơi đến không được để trống'
            ]);
        }

        if (empty($data->dau_hieu_ls)) {
            $errors->push((object)[
                'error_code' => $this->prefix . 'INFO_ERROR_DAU_HIEU_LS',
                'error_name' => 'Thiếu dấu hiệu lâm sàng',
                'description' => 'Dấu hiệu lâm sàng không được để trống'
            ]);
        }

        if (empty($data->chan_doan_rv)) {
            $errors->push((object)[
                'error_code' => $this->prefix . 'INFO_ERROR_CHAN_DOAN_RV',
                'error_name' => 'Thiếu chẩn đoán ra viện',
                'description' => 'Chẩn đoán ra viện không được để trống'
            ]);
        }

        if (empty($data->qt_benhly)) {
            $errors->push((object)[
                'error_code' => $this->prefix . 'INFO_ERROR_QT_BENHLY',
                'error_name' => 'Thiếu quá trình bệnh lý',
                'description' => 'Quá trình bệnh lý không được để trống'
            ]);
        }

        if (empty($data->ma_loai_rv)) {
            $errors->push((object)[
                'error_code' => $this->prefix . 'INFO_ERROR_MA_LOAI_RV',
                'error_name' => 'Thiếu mã loại ra viện',
                'description' => 'Mã loại ra viện không được để trống'
            ]);
        }

        if (empty($data->ma_lydo_ct)) {
            $errors->push((object)[
                'error_code' => $this->prefix . 'INFO_ERROR_MA_LYDO_CT',
                'error_name' => 'Thiếu mã lý do chuyển tuyến',
                'description' => 'Mã lý do chuyển tuyến không được để trống'
            ]);
        }

        if (empty($data->huong_dieu_tri)) {
            $errors->push((object)[
                'error_code' => $this->prefix . 'INFO_ERROR_HUONG_DIEU_TRI',
                'error_name' => 'Thiếu hướng điều trị',
                'description' => 'Hướng điều trị không được để trống'
            ]);
        }

        if (empty($data->phuongtien_vc)) {
            $errors->push((object)[
                'error_code' => $this->prefix . 'INFO_ERROR_PHUONGTIEN_VC',
                'error_name' => 'Thiếu phương tiện vận chuyển',
                'description' => 'Phương tiện vận chuyển không được để trống'
            ]);
        }


        return $errors;
    }

    // Thêm các phương thức kiểm tra khác ở đây
}
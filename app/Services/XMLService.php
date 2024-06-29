<?php

namespace App\Services;

use App\Models\BHYT\XML1;
use App\Models\BHYT\XML2;
use App\Models\BHYT\XML3;
use App\Models\BHYT\XML4;
use App\Models\BHYT\XML5;

use App\Jobs\CheckXmlErrorsJob;

class XMLService
{
    public function saveXML1($data)
    {
        try {

            $attributes = [
                'ma_lk' => $data->MA_LK
            ];

            $values = [
                'stt' => $data->STT,
                'ma_bn' => $data->MA_BN,
                'ho_ten' => mb_strtoupper($data->HO_TEN),
                'ngay_sinh' => $data->NGAY_SINH,
                'gioi_tinh' => $data->GIOI_TINH,
                'dia_chi' => $data->DIA_CHI,
                'ma_the' => $data->MA_THE,
                'ma_dkbd' => $data->MA_DKBD,
                'gt_the_tu' => $data->GT_THE_TU,
                'gt_the_den' => $data->GT_THE_DEN,
                'mien_cung_ct' => $data->MIEN_CUNG_CT,
                'ten_benh' => $data->TEN_BENH,
                'ma_benh' => $data->MA_BENH,
                'ma_benhkhac' => $data->MA_BENHKHAC,
                'ma_lydo_vvien' => $data->MA_LYDO_VVIEN,
                'ma_noi_chuyen' => $data->MA_NOI_CHUYEN,
                'ma_tai_nan' => intval($data->MA_TAI_NAN) ?: null,
                'ngay_vao' => $data->NGAY_VAO,
                'ngay_ra' => $data->NGAY_RA,
                'so_ngay_dtri' => $data->SO_NGAY_DTRI,
                'ket_qua_dtri' => $data->KET_QUA_DTRI,
                'tinh_trang_rv' => $data->TINH_TRANG_RV,
                'ngay_ttoan' => $data->NGAY_TTOAN,
                't_thuoc' => doubleval($data->T_THUOC) ?: null,
                't_vtyt' => doubleval($data->T_VTYT) ?: null,
                't_tongchi' => $data->T_TONGCHI,
                't_bntt' => doubleval($data->T_BNTT) ?: null,
                't_bncct' => doubleval($data->T_BNCCT) ?: null,
                't_bhtt' => $data->T_BHTT,
                't_nguonkhac' => doubleval($data->T_NGUONKHAC) ?: null,
                't_ngoaids' => doubleval($data->T_NGOAIDS) ?: null,
                'nam_qt' => $data->NAM_QT,
                'thang_qt' => $data->THANG_QT,
                'ma_loai_kcb' => $data->MA_LOAI_KCB,
                'ma_khoa' => $data->MA_KHOA,
                'ma_cskcb' => $data->MA_CSKCB,
                'ma_khuvuc' => $data->MA_KHUVUC,
                'ma_pttt_qt' => $data->MA_PTTT_QT,
                'can_nang' => doubleval($data->CAN_NANG) ?: null,
            ];

            $xml1 = XML1::updateOrCreate($attributes, $values);

            // Đẩy công việc kiểm tra vào hàng đợi
            CheckXmlErrorsJob::dispatch($xml1, 'XML1')
            ->onQueue('JobXmlCheckError');

        } catch (\Exception $e) {
            \Log::error('Error in saveXML1: ' . $e->getMessage());
        }
    }

    public function saveXML2($data)
    {
        foreach ($data->CHI_TIET_THUOC as $thuoc) {
            try {

                $attributes = [
                    'ma_lk' => $thuoc->MA_LK,
                    'stt' => intval($thuoc->STT) ?: null,
                ];

                $values = [
                    'ma_thuoc' => $thuoc->MA_THUOC,
                    'ma_nhom' => $thuoc->MA_NHOM,
                    'ten_thuoc' => $thuoc->TEN_THUOC,
                    'don_vi_tinh' => $thuoc->DON_VI_TINH,
                    'ham_luong' => $thuoc->HAM_LUONG,
                    'duong_dung' => $thuoc->DUONG_DUNG,
                    'lieu_dung' => $thuoc->LIEU_DUNG,
                    'so_dang_ky' => $thuoc->SO_DANG_KY,
                    'tt_thau' => $thuoc->TT_THAU,
                    'pham_vi' => $thuoc->PHAM_VI,
                    'so_luong' => $thuoc->SO_LUONG,
                    'don_gia' => $thuoc->DON_GIA,
                    'tyle_tt' => $thuoc->TYLE_TT,
                    'thanh_tien' => $thuoc->THANH_TIEN,
                    'muc_huong' => $thuoc->MUC_HUONG,
                    't_nguon_khac' => doubleval($thuoc->T_NGUON_KHAC) ?: null,
                    't_bntt' => doubleval($thuoc->T_BNTT) ?: null,
                    't_bhtt' => $thuoc->T_BHTT,
                    't_bncct' => doubleval($thuoc->T_BNCCT) ?: null,
                    't_ngoaids' => doubleval($thuoc->T_NGOAIDS) ?: null,
                    'ma_khoa' => $thuoc->MA_KHOA,
                    'ma_bac_si' => $thuoc->MA_BAC_SI,
                    'ma_benh' => $thuoc->MA_BENH,
                    'ngay_yl' => $thuoc->NGAY_YL,
                    'ma_pttt' => $thuoc->MA_PTTT,
                ];

                $xml2 = XML2::updateOrCreate($attributes, $values);

                //Đẩy công việc kiểm tra vào hàng đợi
                CheckXmlErrorsJob::dispatch($xml2, 'XML2')
                ->onQueue('JobXmlCheckError');
            } catch (\Exception $e) {
                \Log::error('Error in saveXML2: ' . $e->getMessage());
            }
        }
    }

    public function saveXML3($data)
    {
        foreach ($data->CHI_TIET_DVKT as $dvkt) {
            try {
                $attributes = [
                    'ma_lk' => $dvkt->MA_LK,
                    'stt' => intval($dvkt->STT) ?: null,
                ];

                $values = [
                    'ma_dich_vu' => $dvkt->MA_DICH_VU,
                    'ma_vat_tu' => $dvkt->MA_VAT_TU,
                    'ma_nhom' => $dvkt->MA_NHOM,
                    'goi_vtyt' => $dvkt->GOI_VTYT,
                    'ten_vat_tu' => $dvkt->TEN_VAT_TU,
                    'ten_dich_vu' => $dvkt->TEN_DICH_VU,
                    'don_vi_tinh' => $dvkt->DON_VI_TINH,
                    'pham_vi' => $dvkt->PHAM_VI,
                    'so_luong' => $dvkt->SO_LUONG,
                    'don_gia' => $dvkt->DON_GIA,
                    'tt_thau' => $dvkt->TT_THAU,
                    'tyle_tt' => $dvkt->TYLE_TT,
                    'thanh_tien' => $dvkt->THANH_TIEN,
                    't_trantt' => doubleval($dvkt->T_TRANTT) ?: null,
                    'muc_huong' => $dvkt->MUC_HUONG,
                    't_nguonkhac' => doubleval($dvkt->T_NGUONKHAC) ?: null,
                    't_bntt' => doubleval($dvkt->T_BNTT) ?: null,
                    't_bhtt' => $dvkt->T_BHTT,
                    't_bncct' => doubleval($dvkt->T_BNCCT) ?: null,
                    't_ngoaids' => doubleval($dvkt->T_NGOAIDS) ?: null,
                    'ma_khoa' => $dvkt->MA_KHOA,
                    'ma_giuong' => $dvkt->MA_GIUONG,
                    'ma_bac_si' => $dvkt->MA_BAC_SI,
                    'ma_benh' => $dvkt->MA_BENH,
                    'ngay_yl' => $dvkt->NGAY_YL,
                    'ngay_kq' => $dvkt->NGAY_KQ,
                    'ma_pttt' => $dvkt->MA_PTTT,
                ];

                $xml3 = XML3::updateOrCreate($attributes, $values);

                // Đẩy công việc kiểm tra vào hàng đợi
                CheckXmlErrorsJob::dispatch($xml3, 'XML3')
                ->onQueue('JobXmlCheckError');

            } catch (\Exception $e) {

            }
        }
    }

    public function saveXML4($data)
    {
        foreach ($data->CHI_TIET_CLS as $cls) {
            try {
                $attributes = [
                    'ma_lk' => $cls->MA_LK,
                    'stt' => intval($cls->STT) ?: null,
                ];

                $values = [
                    'ma_dich_vu' => $cls->MA_DICH_VU,
                    'ma_chi_so' => $cls->MA_CHI_SO,
                    'ten_chi_so' => $cls->TEN_CHI_SO,
                    'gia_tri' => $cls->GIA_TRI,
                    'ma_may' => $cls->MA_MAY,
                    'mo_ta' => $cls->MO_TA,
                    'ket_luan' => $cls->KET_LUAN,
                    'ngay_kq' => $cls->NGAY_KQ,
                ];

                $xml4 = XML4::updateOrCreate($attributes, $values);

            } catch (\Exception $e) {

            }
        }
    }

    public function saveXML5($data)
    {
        foreach ($data->CHI_TIET_DIEN_BIEN_BENH as $dienbien) {
            try {
                $attributes = [
                    'ma_lk' => $dienbien->MA_LK,
                    'stt' => intval($dienbien->STT) ?: null,
                ];

                $values = [
                    'dien_bien' => $dienbien->DIEN_BIEN,
                    'hoi_chan' => $dienbien->HOI_CHAN,
                    'phau_thuat' => $dienbien->PHAU_THUAT,
                    'ngay_yl' => $dienbien->NGAY_YL,
                ];

                $xml5 = XML5::updateOrCreate($attributes, $values);

            } catch (\Exception $e) {

            }
        }
    }
}
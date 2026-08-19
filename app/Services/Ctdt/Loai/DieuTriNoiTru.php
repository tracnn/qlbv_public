<?php

namespace App\Services\Ctdt\Loai;

use App\Models\BHYT\Ctdt\CtdtDieuTriNoiTru;

/**
 * Giay xac nhan qua trinh dieu tri noi tru (Mau so 06 - TT25).
 *
 * LECH TEN: LOAIHOSO la 'GIAYDIEUTRINOITRU' nhung the goc trong base64 la
 * '<CTGiayDieuTriNoiTru>'. Khai ca hai de registry doi chieu cheo duoc.
 */
class DieuTriNoiTru implements LoaiChungTu
{
    public static function maLoaiHoSo()
    {
        return 'GIAYDIEUTRINOITRU';
    }

    public static function theGoc()
    {
        return 'CTGiayDieuTriNoiTru';
    }

    public static function dichVu()
    {
        return 'CT2025';
    }

    public static function bang()
    {
        return 'ctdt_dieu_tri_noi_tru';
    }

    public static function model()
    {
        return CtdtDieuTriNoiTru::class;
    }

    public static function tenTab()
    {
        return 'Điều trị nội trú';
    }

    public static function truong()
    {
        return [
            'SO_LUU_TRU'               => 'so_luu_tru',
            'MA_YTE'                   => 'ma_yte',
            'MA_BHXH'                  => 'ma_bhxh',
            'MA_THE'                   => 'ma_the',
            'HO_TEN'                   => 'ho_ten',
            'NGAY_SINH'                => 'ngay_sinh',
            'GIOI_TINH'                => 'gioi_tinh',
            'MA_KHOA'                  => 'ma_khoa',
            'TEN_DAN_TOC'              => 'ten_dan_toc',
            'MA_DAN_TOC'               => 'ma_dan_toc',
            'NGHE_NGHIEP'              => 'nghe_nghiep',
            'DIA_CHI'                  => 'dia_chi',
            'NGAY_VAO'                 => 'ngay_vao',
            'NGAY_RA'                  => 'ngay_ra',
            'CHAN_DOAN'                => 'chan_doan',
            'PP_DIEUTRI'               => 'pp_dieutri',
            'MO_TA'                    => 'mo_ta',
            'GHI_CHU'                  => 'ghi_chu',
            'DAI_DIEN_DVI'             => 'dai_dien_dvi',
            'MA_CCHN_BS'               => 'ma_cchn_bs',
            'TEN_BS'                   => 'ten_bs',
            'LOAI_GIAYTO'              => 'loai_giayto',
            'SO_CCCD'                  => 'so_cccd',
            'NGAYCAP_CCCD'             => 'ngaycap_cccd',
            'NOICAP_CCCD'              => 'noicap_cccd',
            'BENH_ICD10_MA'            => 'benh_icd10_ma',
            'BENH_ICD10_TEN'           => 'benh_icd10_ten',
            'MA_CT'                    => 'ma_ct',
            'NGAY_CT'                  => 'ngay_ct',
            'SO_SERI'                  => 'so_seri',
            'TUOI_THAI'                => 'tuoi_thai',
            'LOAI_PHUONG_PHAP'         => 'loai_phuong_phap',
            'LOAI_PP_DIEU_TRI_VOSINH'  => 'loai_pp_dieu_tri_vosinh',
            'NGAY_DINH_CHI_THAINGHEN'  => 'ngay_dinh_chi_thainghen',
            'IS_NGHIDUONGTHAI'         => 'is_nghiduongthai',
            'SO_NGAY_NGHIDUONGTHAI'    => 'so_ngay_nghiduongthai',
        ];
    }

    public static function maChungTu(\SimpleXMLElement $xml)
    {
        return DocThe::chuoi($xml, 'MA_YTE');
    }

    public static function rutGon(\SimpleXMLElement $xml)
    {
        return [
            'ma_the'    => DocThe::chuoi($xml, 'MA_THE'),
            'ho_ten'    => DocThe::chuoi($xml, 'HO_TEN'),
            'ngay_sinh' => DocThe::chuoi($xml, 'NGAY_SINH'),
            'ngay_vao'  => DocThe::chuoi($xml, 'NGAY_VAO'),
            'ngay_ra'   => DocThe::chuoi($xml, 'NGAY_RA'),
        ];
    }
}

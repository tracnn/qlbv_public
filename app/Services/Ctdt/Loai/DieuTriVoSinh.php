<?php

namespace App\Services\Ctdt\Loai;

use App\Models\BHYT\Ctdt\CtdtDieuTriVoSinh;

/**
 * Giay xac nhan qua trinh dieu tri vo sinh cua lao dong nu (Mau so 09 - TT25).
 * LECH TEN: LOAIHOSO 'GIAYDIEUTRIVOSINH' vs the goc '<CTGiayDieuTriVoSinh>'.
 * Dac ta KHONG khai GIOI_TINH cho loai nay.
 */
class DieuTriVoSinh implements LoaiChungTu
{
    public static function maLoaiHoSo()
    {
        return 'GIAYDIEUTRIVOSINH';
    }

    public static function theGoc()
    {
        return 'CTGiayDieuTriVoSinh';
    }

    public static function dichVu()
    {
        return 'CT2025';
    }

    public static function bang()
    {
        return 'ctdt_dieu_tri_vo_sinh';
    }

    public static function model()
    {
        return CtdtDieuTriVoSinh::class;
    }

    public static function tenTab()
    {
        return 'Điều trị vô sinh';
    }

    public static function truong()
    {
        return [
            'SO_LUU_TRU'       => 'so_luu_tru',
            'MA_YTE'           => 'ma_yte',
            'MA_BHXH'          => 'ma_bhxh',
            'MA_THE'           => 'ma_the',
            'HO_TEN'           => 'ho_ten',
            'NGAY_SINH'        => 'ngay_sinh',
            'MA_KHOA'          => 'ma_khoa',
            'MA_TINHCUTRU'     => 'ma_tinhcutru',
            'MA_XACUTRU'       => 'ma_xacutru',
            'NGHE_NGHIEP'      => 'nghe_nghiep',
            'DIA_CHI'          => 'dia_chi',
            'NGAY_VAO'         => 'ngay_vao',
            'NGAY_RA'          => 'ngay_ra',
            'CHAN_DOAN'        => 'chan_doan',
            'PP_DIEUTRI'       => 'pp_dieutri',
            'GHI_CHU'          => 'ghi_chu',
            'DAI_DIEN_DVI'     => 'dai_dien_dvi',
            'MA_CCHN_BS'       => 'ma_cchn_bs',
            'TEN_BS'           => 'ten_bs',
            'LOAI_GIAYTO'      => 'loai_giayto',
            'SO_CCCD'          => 'so_cccd',
            'NGAYCAP_CCCD'     => 'ngaycap_cccd',
            'NOICAP_CCCD'      => 'noicap_cccd',
            'BENH_ICD10_MA'    => 'benh_icd10_ma',
            'BENH_ICD10_TEN'   => 'benh_icd10_ten',
            'MA_CT'            => 'ma_ct',
            'NGAY_CT'          => 'ngay_ct',
            'SO_SERI'          => 'so_seri',
            'LOAI_PHUONG_PHAP' => 'loai_phuong_phap',
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
            'so_cccd'   => DocThe::chuoi($xml, 'SO_CCCD'),
            'ma_bhxh'   => DocThe::chuoi($xml, 'MA_BHXH'),
        ];
    }
}

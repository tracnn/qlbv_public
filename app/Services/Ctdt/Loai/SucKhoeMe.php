<?php

namespace App\Services\Ctdt\Loai;

use App\Models\BHYT\Ctdt\CtdtSucKhoeMe;

/**
 * Giay xac nhan nguoi me khong du suc khoe cham soc con (Mau so 10 - TT25).
 * LECH TEN: LOAIHOSO 'GIAYSUCKHOEME' vs the goc '<CTGiaySucKhoeMe>'.
 */
class SucKhoeMe implements LoaiChungTu
{
    public static function maLoaiHoSo()
    {
        return 'GIAYSUCKHOEME';
    }

    public static function theGoc()
    {
        return 'CTGiaySucKhoeMe';
    }

    public static function dichVu()
    {
        return 'CT2025';
    }

    public static function bang()
    {
        return 'ctdt_suc_khoe_me';
    }

    public static function model()
    {
        return CtdtSucKhoeMe::class;
    }

    public static function tenTab()
    {
        return 'Sức khỏe người mẹ';
    }

    public static function truong()
    {
        return [
            'SO_LUU_TRU'           => 'so_luu_tru',
            'MA_YTE'               => 'ma_yte',
            'MA_BHXH'              => 'ma_bhxh',
            'MA_THE'               => 'ma_the',
            'HO_TEN'               => 'ho_ten',
            'NGAY_SINH'            => 'ngay_sinh',
            'MA_KHOA'              => 'ma_khoa',
            'MA_TINHCUTRU'         => 'ma_tinhcutru',
            'MA_XACUTRU'           => 'ma_xacutru',
            'NGHE_NGHIEP'          => 'nghe_nghiep',
            'DIA_CHI'              => 'dia_chi',
            'NGAY_VAO'             => 'ngay_vao',
            'NGAY_RA'              => 'ngay_ra',
            'CHAN_DOAN'            => 'chan_doan',
            'PP_DIEUTRI'           => 'pp_dieutri',
            'KET_LUAN'             => 'ket_luan',
            'TINHTRANGBENHHIENTAI' => 'tinhtrangbenhhientai',
            'DAI_DIEN_DVI'         => 'dai_dien_dvi',
            'MA_CCHN_BS'           => 'ma_cchn_bs',
            'TEN_BS'               => 'ten_bs',
            'LOAI_GIAYTO'          => 'loai_giayto',
            'SO_CCCD'              => 'so_cccd',
            'NGAYCAP_CCCD'         => 'ngaycap_cccd',
            'NOICAP_CCCD'          => 'noicap_cccd',
            'BENH_ICD10_MA'        => 'benh_icd10_ma',
            'BENH_ICD10_TEN'       => 'benh_icd10_ten',
            'MA_CT'                => 'ma_ct',
            'NGAY_CT'              => 'ngay_ct',
            'SO_SERI'              => 'so_seri',
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

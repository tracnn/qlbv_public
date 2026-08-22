<?php

namespace App\Services\Ctdt\Loai;

use App\Models\BHYT\Ctdt\CtdtCt06;

/** CT06 - Giay xac nhan nghi duong thai (Mau so 11 - TT25). KHONG co MA_YTE. */
class Ct06 implements LoaiChungTu
{
    public static function maLoaiHoSo()
    {
        return 'CT06';
    }

    public static function theGoc()
    {
        return 'CT06';
    }

    public static function dichVu()
    {
        return 'CT2025';
    }

    public static function bang()
    {
        return 'ctdt_ct06';
    }

    public static function model()
    {
        return CtdtCt06::class;
    }

    public static function tenTab()
    {
        return 'Nghỉ dưỡng thai';
    }

    public static function truong()
    {
        return [
            'MA_BHXH'        => 'ma_bhxh',
            'MA_THE'         => 'ma_the',
            'HO_TEN'         => 'ho_ten',
            'NGAY_SINH'      => 'ngay_sinh',
            'NGAY_VAO'       => 'ngay_vao',
            'NGAY_RA'        => 'ngay_ra',
            'CHAN_DOAN'      => 'chan_doan',
            'NGUOI_DAI_DIEN' => 'nguoi_dai_dien',
            'MA_BS'          => 'ma_bs',
            'TEN_BS'         => 'ten_bs',
            'TEN_DVI'        => 'ten_dvi',
            'SO_KCB'         => 'so_kcb',
            'NGAY_CT'        => 'ngay_ct',
            'SO_SERI'        => 'so_seri',
            'MA_CT'          => 'ma_ct',
            'LOAI_GIAYTO'    => 'loai_giayto',
            'SO_CCCD'        => 'so_cccd',
            'NGAYCAP_CCCD'   => 'ngaycap_cccd',
            'NOI_CU_TRU_NND' => 'noi_cu_tru_nnd',
            'MATINH_CU_TRU'  => 'matinh_cu_tru',
            'MAXA_CU_TRU'    => 'maxa_cu_tru',
            'TUOI_THAI'      => 'tuoi_thai',
            'BENH_ICD10_ID'  => 'benh_icd10_id',
            'BENH_ICD10_TEN' => 'benh_icd10_ten',
        ];
    }

    public static function maChungTu(\SimpleXMLElement $xml)
    {
        return null;
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

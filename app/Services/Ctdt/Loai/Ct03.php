<?php

namespace App\Services\Ctdt\Loai;

use App\Models\BHYT\Ctdt\CtdtCt03;

/**
 * CT03 - Giay ra vien (Mau so 02 - TT25).
 *
 * BENHICD10_ID / TENBENHICD10 dat ten khac cac loai khac. Giu nguyen theo dac ta.
 */
class Ct03 implements LoaiChungTu
{
    public static function maLoaiHoSo()
    {
        return 'CT03';
    }

    public static function theGoc()
    {
        return 'CT03';
    }

    public static function dichVu()
    {
        return 'CT2025';
    }

    public static function bang()
    {
        return 'ctdt_ct03';
    }

    public static function model()
    {
        return CtdtCt03::class;
    }

    public static function tenTab()
    {
        return 'Giấy ra viện';
    }

    public static function truong()
    {
        return [
            'SO_LUU_TRU'          => 'so_luu_tru',
            'MA_YTE'              => 'ma_yte',
            'MA_KHOA'             => 'ma_khoa',
            'MA_BHXH'             => 'ma_bhxh',
            'MA_THE'              => 'ma_the',
            'HO_TEN'              => 'ho_ten',
            'NGAY_SINH'           => 'ngay_sinh',
            'GIOI_TINH'           => 'gioi_tinh',
            'MA_DANTOC'           => 'ma_dantoc',
            'NGHE_NGHIEP'         => 'nghe_nghiep',
            'DIA_CHI'             => 'dia_chi',
            'NGAY_VAO'            => 'ngay_vao',
            'NGAY_RA'             => 'ngay_ra',
            'DINH_CHI_THAI_NGHEN' => 'dinh_chi_thai_nghen',
            'TUOI_THAI'           => 'tuoi_thai',
            'CHAN_DOAN'           => 'chan_doan',
            'PP_DIEUTRI'          => 'pp_dieutri',
            'GHI_CHU'             => 'ghi_chu',
            'THU_TRUONG_DVI'      => 'thu_truong_dvi',
            'MA_CCHN_TRUONGKHOA'  => 'ma_cchn_truongkhoa',
            'TEN_TRUONGKHOA'      => 'ten_truongkhoa',
            'NGAY_CHUNG_TU'       => 'ngay_chung_tu',
            'TEKT'                => 'tekt',
            'HO_TEN_CHA'          => 'ho_ten_cha',
            'HO_TEN_ME'           => 'ho_ten_me',
            'NGOAITRU_TUNGAY'     => 'ngoaitru_tungay',
            'NGOAITRU_DENNGAY'    => 'ngoaitru_denngay',
            'LOAI_GIAYTO'         => 'loai_giayto',
            'SO_CCCD'             => 'so_cccd',
            'NGAYCAP_CCCD'        => 'ngaycap_cccd',
            'NOICAP_CCCD'         => 'noicap_cccd',
            'BENHICD10_ID'        => 'benhicd10_id',
            'TENBENHICD10'       => 'tenbenhicd10',
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

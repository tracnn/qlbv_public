<?php

namespace App\Services\Ctdt\Loai;

use App\Models\BHYT\Ctdt\CtdtGiayBaoTu;

/**
 * Giay bao tu - dich vu rieng, loaiHs=60, goi HSDLGBT.
 *
 * Khong co NGAY_RA: NGAY_TV (ngay tu vong) dong vai tro ket thuc dot dieu tri trong
 * cot rut gon, de man danh sach loc theo mot bo cot duy nhat cho ca chin loai.
 */
class GiayBaoTu implements LoaiChungTu
{
    public static function maLoaiHoSo()
    {
        return 'GIAYBAOTU';
    }

    public static function theGoc()
    {
        return 'GIAYBAOTU';
    }

    public static function dichVu()
    {
        return 'GBT';
    }

    public static function bang()
    {
        return 'ctdt_giay_bao_tu';
    }

    public static function model()
    {
        return CtdtGiayBaoTu::class;
    }

    public static function tenTab()
    {
        return 'Giấy báo tử';
    }

    public static function truong()
    {
        return [
            'MA_GBT'             => 'ma_gbt',
            'MA_BN'              => 'ma_bn',
            'MA_HSBA'            => 'ma_hsba',
            'HO_TEN'             => 'ho_ten',
            'NGAY_SINH'          => 'ngay_sinh',
            'GIOI_TINH'          => 'gioi_tinh',
            'MA_THE'             => 'ma_the',
            'MA_DANTOC'          => 'ma_dantoc',
            'MA_QUOCTICH'        => 'ma_quoctich',
            'DCHI_THUONGTRU'     => 'dchi_thuongtru',
            'MATINH_THUONGTRU'   => 'matinh_thuongtru',
            'MAHUYEN_THUONGTRU'  => 'mahuyen_thuongtru',
            'MAXA_THUONGTRU'     => 'maxa_thuongtru',
            'DCHI_HIENTAI'       => 'dchi_hientai',
            'MATINH_HIENTAI'     => 'matinh_hientai',
            'MAHUYEN_HIENTAI'    => 'mahuyen_hientai',
            'MAXA_HIENTAI'       => 'maxa_hientai',
            'LOAI_GIAYTO'        => 'loai_giayto',
            'SO_GIAYTO'          => 'so_giayto',
            'NGAY_CAP'           => 'ngay_cap',
            'NOI_CAP'            => 'noi_cap',
            'NGAYGIO_VV'         => 'ngaygio_vv',
            'NGAY_TV'            => 'ngay_tv',
            'TINH_TRANG_TV'      => 'tinh_trang_tv',
            'NGUYENNHAN_TV'      => 'nguyennhan_tv',
            'NGUOI_GHIGIAY'      => 'nguoi_ghigiay',
            'NGUOI_THANTHICH'    => 'nguoi_thanthich',
            'TTRUONG_DVI'        => 'ttruong_dvi',
            'SO_BAOTU'           => 'so_baotu',
            'QUYEN_SO'           => 'quyen_so',
            'NGAY_CAPGIAYBT'     => 'ngay_capgiaybt',
            'SO_BAOTU_BD'        => 'so_baotu_bd',
            'QUYEN_SO_BD'        => 'quyen_so_bd',
            'MACSKCB'            => 'macskcb',
            'DIACHI_CSKCB'       => 'diachi_cskcb',
            'MA_BHXH'            => 'ma_bhxh',
            'BENH_ICD10_ID'      => 'benh_icd10_id',
            'BENH_ICD10_TEN'     => 'benh_icd10_ten',
        ];
    }

    public static function maChungTu(\SimpleXMLElement $xml)
    {
        return DocThe::chuoi($xml, 'MA_GBT');
    }

    public static function rutGon(\SimpleXMLElement $xml)
    {
        return [
            'ma_the'    => DocThe::chuoi($xml, 'MA_THE'),
            'ho_ten'    => DocThe::chuoi($xml, 'HO_TEN'),
            'ngay_sinh' => DocThe::chuoi($xml, 'NGAY_SINH'),
            'ngay_vao'  => DocThe::chuoi($xml, 'NGAYGIO_VV'),
            'ngay_ra'   => DocThe::chuoi($xml, 'NGAY_TV'),
            'so_cccd'   => DocThe::chuoi($xml, 'SO_GIAYTO'),
            'ma_bhxh'   => DocThe::chuoi($xml, 'MA_BHXH'),
        ];
    }
}

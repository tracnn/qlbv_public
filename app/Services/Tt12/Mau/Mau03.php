<?php

namespace App\Services\Tt12\Mau;

/** Mau 03/DM - Thuoc, mau, che pham mau ap dung trong thanh toan BHYT */
class Mau03 extends MauCoSo
{
    public static function ma()           { return 'MAU_03'; }
    public static function ten()          { return 'Mẫu 03/DM - Thuốc, máu, chế phẩm máu'; }
    public static function theDanhSach()  { return 'DANHSACH_DMTHUOCMAUCHEPHAMMAU'; }
    public static function theDong()      { return 'DMTHUOCMAUCHEPHAMMAU'; }
    public static function danhMuc()      { return 'medicine'; }
    public static function bangDanhMuc()  { return 'medicine_catalogs'; }

    public static function cot()
    {
        return array(
            array('the' => 'STT',            'kieu' => 'so',    'max' => 6,    'bat_buoc' => true),
            array('the' => 'MA_THUOC',       'kieu' => 'chuoi', 'max' => 255,  'bat_buoc' => true),
            array('the' => 'TEN_HOAT_CHAT',  'kieu' => 'chuoi', 'max' => 1024, 'bat_buoc' => false),
            array('the' => 'TEN_THUOC',      'kieu' => 'chuoi', 'max' => 1024, 'bat_buoc' => true),
            array('the' => 'DON_VI_TINH',    'kieu' => 'chuoi', 'max' => 50,   'bat_buoc' => false),
            array('the' => 'HAM_LUONG',      'kieu' => 'chuoi', 'max' => 1024, 'bat_buoc' => false),
            array('the' => 'DUONG_DUNG',     'kieu' => 'chuoi', 'max' => 255,  'bat_buoc' => false),
            array('the' => 'MA_DUONG_DUNG',  'kieu' => 'chuoi', 'max' => 10,   'bat_buoc' => false),
            array('the' => 'DANG_BAO_CHE',   'kieu' => 'chuoi', 'max' => 1024, 'bat_buoc' => false),
            array('the' => 'SO_DANG_KY',     'kieu' => 'chuoi', 'max' => 1024, 'bat_buoc' => false),
            array('the' => 'SO_LUONG',       'kieu' => 'so',    'max' => 10,   'bat_buoc' => false),
            array('the' => 'DON_GIA',        'kieu' => 'so',    'max' => 15,   'bat_buoc' => false),
            array('the' => 'DON_GIA_BH',     'kieu' => 'so',    'max' => 10,   'bat_buoc' => false),
            array('the' => 'QUY_CACH',       'kieu' => 'chuoi', 'max' => 1024, 'bat_buoc' => false),
            array('the' => 'NHA_SX',         'kieu' => 'chuoi', 'max' => 1024, 'bat_buoc' => false),
            array('the' => 'NUOC_SX',        'kieu' => 'chuoi', 'max' => 100,  'bat_buoc' => false),
            array('the' => 'NHA_THAU',       'kieu' => 'chuoi', 'max' => 1024, 'bat_buoc' => false),
            array('the' => 'TT_THAU',        'kieu' => 'chuoi', 'max' => 50,   'bat_buoc' => false),
            array('the' => 'TU_NGAY_HD',     'kieu' => 'ngay8', 'max' => 8,    'bat_buoc' => false),
            array('the' => 'DEN_NGAY_HD',    'kieu' => 'ngay8', 'max' => 8,    'bat_buoc' => false),
            array('the' => 'MA_CSKCB',       'kieu' => 'chuoi', 'max' => 5,    'bat_buoc' => true),
            array('the' => 'LOAI_THUOC',     'kieu' => 'so',    'max' => 2,    'bat_buoc' => false),
            array('the' => 'LOAI_THAU',      'kieu' => 'so',    'max' => 1,    'bat_buoc' => false),
            array('the' => 'HT_THAU',        'kieu' => 'so',    'max' => 1,    'bat_buoc' => false),
            array('the' => 'MA_DVKT',        'kieu' => 'chuoi', 'max' => 1024, 'bat_buoc' => false),
            array('the' => 'TCCL',           'kieu' => 'chuoi', 'max' => 50,   'bat_buoc' => false),
            array('the' => 'BO_PHAN_VT',     'kieu' => 'so',    'max' => 1,    'bat_buoc' => false),
            array('the' => 'TEN_KHOA_HOC',   'kieu' => 'chuoi', 'max' => 500,  'bat_buoc' => false),
            array('the' => 'NGUON_GOC',      'kieu' => 'chuoi', 'max' => 500,  'bat_buoc' => false),
            array('the' => 'PP_CHEBIEN',     'kieu' => 'chuoi', 'max' => 1024, 'bat_buoc' => false),
            array('the' => 'MA_DL_NHAP',     'kieu' => 'chuoi', 'max' => 3,    'bat_buoc' => false),
            array('the' => 'MA_DL_CB',       'kieu' => 'chuoi', 'max' => 3,    'bat_buoc' => false),
            array('the' => 'TLHH_CB',        'kieu' => 'so',    'max' => 4,    'bat_buoc' => false),
            array('the' => 'TLHH_BQ',        'kieu' => 'so',    'max' => 4,    'bat_buoc' => false),
            array('the' => 'MA_CSKCB_THUOC', 'kieu' => 'chuoi', 'max' => 7,   'bat_buoc' => false),
            array('the' => 'TU_NGAY',        'kieu' => 'ngay8', 'max' => 8,    'bat_buoc' => true),
            array('the' => 'DEN_NGAY',       'kieu' => 'ngay8', 'max' => 8,    'bat_buoc' => false),
        );
    }

    /**
     * Ma LOAI_THUOC ung voi vi thuoc / duoc lieu co hoc (thuoc y hoc co truyen).
     *
     * Dung o LuatRiengMau: chi nhung loai nay moi bat buoc nhom cot duoc lieu. Khai o
     * day chu khong o lop luat vi day la thuoc tinh cua MAU_03, khong phai cua phep kiem.
     *
     * @return array cac gia tri LOAI_THUOC dang chuoi
     */
    public static function loaiThuocDuocLieu()
    {
        return array('3', '4');
    }

    /** @return array cac the chi bat buoc khi LOAI_THUOC nam trong loaiThuocDuocLieu() */
    public static function theDuocLieu()
    {
        return array('TEN_KHOA_HOC', 'NGUON_GOC', 'PP_CHEBIEN', 'TLHH_CB', 'TLHH_BQ');
    }
}

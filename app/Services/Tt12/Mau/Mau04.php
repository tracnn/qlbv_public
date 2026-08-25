<?php

namespace App\Services\Tt12\Mau;

/** Mau 04/DM - Thiet bi y te (vat tu y te) ap dung trong thanh toan BHYT */
class Mau04 extends MauCoSo
{
    public static function ma()           { return 'MAU_04'; }
    public static function ten()          { return 'Mẫu 04/DM - Thiết bị y tế (vật tư y tế)'; }

    // TIEN TO 'DSACH_' CHU KHONG PHAI 'DANHSACH_', va the dong co gach duoi. Chi MAU_04
    // va MAU_06 nhu vay; bon mau con lai theo quy uoc DANHSACH_ + DM... Go theo quan tinh
    // la sai, nen khai tuong minh chu khong ghep chuoi.
    public static function theDanhSach()  { return 'DSACH_TBYT'; }
    public static function theDong()      { return 'DM_TBYT'; }
    public static function danhMuc()      { return 'medical_supply'; }
    public static function bangDanhMuc()  { return 'medical_supply_catalogs'; }

    public static function cot()
    {
        return array(
            array('the' => 'STT',            'kieu' => 'so',    'max' => 10,   'bat_buoc' => true),
            array('the' => 'MA_VAT_TU',      'kieu' => 'chuoi', 'max' => 50,   'bat_buoc' => true),
            array('the' => 'NHOM_VAT_TU',    'kieu' => 'chuoi', 'max' => 1024, 'bat_buoc' => false),
            array('the' => 'TEN_VAT_TU',     'kieu' => 'chuoi', 'max' => null, 'bat_buoc' => true),
            array('the' => 'MA_HIEU',        'kieu' => 'chuoi', 'max' => 1024, 'bat_buoc' => false),
            array('the' => 'SO_LUU_HANH',    'kieu' => 'chuoi', 'max' => 20,   'bat_buoc' => false),
            array('the' => 'TINHNANG_KT',    'kieu' => 'chuoi', 'max' => null, 'bat_buoc' => false),
            array('the' => 'QUY_CACH',       'kieu' => 'chuoi', 'max' => 1024, 'bat_buoc' => false),
            array('the' => 'HANG_SX',        'kieu' => 'chuoi', 'max' => 1024, 'bat_buoc' => false),
            array('the' => 'NUOC_SX',        'kieu' => 'chuoi', 'max' => 100,  'bat_buoc' => false),
            array('the' => 'DON_VI_TINH',    'kieu' => 'chuoi', 'max' => 50,   'bat_buoc' => false),
            array('the' => 'DON_GIA',        'kieu' => 'so',    'max' => 10,   'bat_buoc' => false),
            array('the' => 'DON_GIA_BH',     'kieu' => 'so',    'max' => 10,   'bat_buoc' => false),
            array('the' => 'TYLE_TT_BH',     'kieu' => 'so',    'max' => 3,    'bat_buoc' => false),
            array('the' => 'SO_LUONG',       'kieu' => 'so',    'max' => 10,   'bat_buoc' => false),
            array('the' => 'DINH_MUC',       'kieu' => 'so',    'max' => 4,    'bat_buoc' => false),
            array('the' => 'NHA_THAU',       'kieu' => 'chuoi', 'max' => 1024, 'bat_buoc' => false),
            array('the' => 'TT_THAU',        'kieu' => 'chuoi', 'max' => 50,   'bat_buoc' => false),
            array('the' => 'TU_NGAY_HD',     'kieu' => 'ngay8', 'max' => 8,    'bat_buoc' => false),
            array('the' => 'DEN_NGAY_HD',    'kieu' => 'ngay8', 'max' => 8,    'bat_buoc' => false),
            array('the' => 'MA_CSKCB',       'kieu' => 'chuoi', 'max' => 5,    'bat_buoc' => true),
            array('the' => 'LOAI_THAU',      'kieu' => 'so',    'max' => 2,    'bat_buoc' => false),
            array('the' => 'HT_THAU',        'kieu' => 'so',    'max' => 2,    'bat_buoc' => false),
            array('the' => 'MA_CSKCB_TBYT',  'kieu' => 'chuoi', 'max' => 5,    'bat_buoc' => false),
            array('the' => 'TU_NGAY',        'kieu' => 'ngay8', 'max' => 8,    'bat_buoc' => true),
            array('the' => 'DEN_NGAY',       'kieu' => 'ngay8', 'max' => 8,    'bat_buoc' => false),
        );
    }
}

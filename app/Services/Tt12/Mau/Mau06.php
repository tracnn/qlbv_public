<?php

namespace App\Services\Tt12\Mau;

/** Mau 06/DM - Thiet bi y te de thuc hien dich vu ky thuat */
class Mau06 extends MauCoSo
{
    public static function ma()           { return 'MAU_06'; }
    public static function ten()          { return 'Mẫu 06/DM - Thiết bị y tế thực hiện DVKT'; }

    // TIEN TO 'DSACH_' - xem ghi chu cung loai trong Mau04.
    public static function theDanhSach()  { return 'DSACH_TBYTTHDV'; }
    public static function theDong()      { return 'DM_TBYTTHDV'; }
    public static function danhMuc()      { return 'equipment'; }
    public static function bangDanhMuc()  { return 'equipment_catalogs'; }

    // MAU_06 KHONG theo quy uoc "the thu hai la ma": the thu hai la TEN_TB, con ma may
    // nam o the thu tam. Khong ghi de hai ham nay thi cot 'ma' tren man danh sach se
    // chua ten thiet bi va o tim theo ma khong bao gio ra ket qua.
    public static function theMa()        { return 'MA_MAY'; }
    public static function theTen()       { return 'TEN_TB'; }

    public static function cot()
    {
        return array(
            array('the' => 'STT',         'kieu' => 'so',    'max' => 10,   'bat_buoc' => true),
            array('the' => 'TEN_TB',      'kieu' => 'chuoi', 'max' => null, 'bat_buoc' => true),
            array('the' => 'KY_HIEU',     'kieu' => 'chuoi', 'max' => 1024, 'bat_buoc' => false),
            array('the' => 'CONGTY_SX',   'kieu' => 'chuoi', 'max' => 1024, 'bat_buoc' => false),
            array('the' => 'NUOC_SX',     'kieu' => 'chuoi', 'max' => 100,  'bat_buoc' => false),
            array('the' => 'NAM_SX',      'kieu' => 'so',    'max' => 4,    'bat_buoc' => false),
            array('the' => 'NAM_SD',      'kieu' => 'so',    'max' => 4,    'bat_buoc' => false),
            array('the' => 'MA_MAY',      'kieu' => 'chuoi', 'max' => null, 'bat_buoc' => true),
            array('the' => 'SO_LUU_HANH', 'kieu' => 'chuoi', 'max' => 100,   'bat_buoc' => false),
            array('the' => 'HD_TU',       'kieu' => 'ngay8', 'max' => 8,    'bat_buoc' => false),
            array('the' => 'HD_DEN',      'kieu' => 'ngay8', 'max' => 8,    'bat_buoc' => false),
            array('the' => 'TU_NGAY',     'kieu' => 'ngay8', 'max' => 8,    'bat_buoc' => true),
            array('the' => 'DEN_NGAY',    'kieu' => 'ngay8', 'max' => 8,    'bat_buoc' => false),
            array('the' => 'MA_CSKCB',    'kieu' => 'chuoi', 'max' => 5,    'bat_buoc' => true),
        );
    }
}

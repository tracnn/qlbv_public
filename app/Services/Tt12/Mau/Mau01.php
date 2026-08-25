<?php

namespace App\Services\Tt12\Mau;

/** Mau 01/DM - Bo phan chuyen mon kham benh, chua benh bao hiem y te */
class Mau01 extends MauCoSo
{
    public static function ma()           { return 'MAU_01'; }
    public static function ten()          { return 'Mẫu 01/DM - Bộ phận chuyên môn KBCB BHYT'; }
    public static function theDanhSach()  { return 'DANHSACH_DMBOPHANCHUYENMON'; }
    public static function theDong()      { return 'DMBOPHANCHUYENMON'; }
    public static function danhMuc()      { return 'department_bed'; }
    public static function bangDanhMuc()  { return 'department_bed_catalogs'; }

    public static function cot()
    {
        return array(
            array('the' => 'STT',         'kieu' => 'so',    'max' => 3,    'bat_buoc' => true),
            array('the' => 'MA_KHOA',     'kieu' => 'chuoi', 'max' => 50,   'bat_buoc' => true),
            array('the' => 'TEN_KHOA',    'kieu' => 'chuoi', 'max' => null, 'bat_buoc' => true),
            array('the' => 'BAN_KHAM',    'kieu' => 'so',    'max' => 3,    'bat_buoc' => false),
            array('the' => 'GIUONG_PD',   'kieu' => 'so',    'max' => 5,    'bat_buoc' => false),
            array('the' => 'GIUONG_TK',   'kieu' => 'so',    'max' => 5,    'bat_buoc' => false),
            array('the' => 'GIUONG_HSTC', 'kieu' => 'so',    'max' => 3,    'bat_buoc' => false),
            array('the' => 'GIUONG_HSCC', 'kieu' => 'so',    'max' => 3,    'bat_buoc' => false),
            array('the' => 'TU_NGAY',     'kieu' => 'ngay8', 'max' => 8,    'bat_buoc' => true),
            array('the' => 'DEN_NGAY',    'kieu' => 'ngay8', 'max' => 8,    'bat_buoc' => false),
            array('the' => 'MA_CSKCB',    'kieu' => 'chuoi', 'max' => 5,    'bat_buoc' => true),
        );
    }
}

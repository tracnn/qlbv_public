<?php

namespace App\Services\Tt12\Mau;

/** Mau 02/DM - Nhan luc thuc hien kham benh, chua benh bao hiem y te */
class Mau02 extends MauCoSo
{
    public static function ma()           { return 'MAU_02'; }
    public static function ten()          { return 'Mẫu 02/DM - Nhân lực thực hiện KBCB BHYT'; }
    public static function theDanhSach()  { return 'DANHSACH_DMNHANLUCKBCB'; }
    public static function theDong()      { return 'DMNHANLUCKBCB'; }
    public static function danhMuc()      { return 'medical_staff'; }
    public static function bangDanhMuc()  { return 'medical_staffs'; }

    public static function cot()
    {
        return array(
            array('the' => 'STT',            'kieu' => 'so',    'max' => 10,   'bat_buoc' => true),
            array('the' => 'MA_KHOA',        'kieu' => 'chuoi', 'max' => 100,  'bat_buoc' => true),
            array('the' => 'TEN_KHOA',       'kieu' => 'chuoi', 'max' => null, 'bat_buoc' => false),
            array('the' => 'HO_TEN',         'kieu' => 'chuoi', 'max' => 250,  'bat_buoc' => true),
            array('the' => 'GIOI_TINH',      'kieu' => 'so',    'max' => 1,    'bat_buoc' => false),
            array('the' => 'SO_DINH_DANH',   'kieu' => 'chuoi', 'max' => 15,   'bat_buoc' => true),
            array('the' => 'CHUCDANH_NN',    'kieu' => 'chuoi', 'max' => 2,    'bat_buoc' => false),
            array('the' => 'VI_TRI',         'kieu' => 'chuoi', 'max' => 5,    'bat_buoc' => false),
            array('the' => 'MACCHN',         'kieu' => 'chuoi', 'max' => 250,  'bat_buoc' => false),
            array('the' => 'NGAYCAP_CCHN',   'kieu' => 'ngay8', 'max' => 8,    'bat_buoc' => false),
            array('the' => 'NOICAP_CCHN',    'kieu' => 'chuoi', 'max' => 250,  'bat_buoc' => false),
            array('the' => 'PHAMVI_CM',      'kieu' => 'chuoi', 'max' => 15,   'bat_buoc' => false),
            array('the' => 'PHAMVI_CMBS',    'kieu' => 'chuoi', 'max' => 50,   'bat_buoc' => false),
            array('the' => 'DVKT_KHAC',      'kieu' => 'chuoi', 'max' => null, 'bat_buoc' => false),
            array('the' => 'VB_PHANCONG',    'kieu' => 'chuoi', 'max' => 50,   'bat_buoc' => false),
            array('the' => 'THOIGIAN_DK',    'kieu' => 'so',    'max' => 1,    'bat_buoc' => false),
            array('the' => 'THOIGIAN_NGAY',  'kieu' => 'chuoi', 'max' => 200,  'bat_buoc' => false),
            array('the' => 'THOIGIAN_TUAN',  'kieu' => 'chuoi', 'max' => 200,  'bat_buoc' => false),
            array('the' => 'CSKCB_KHAC',     'kieu' => 'chuoi', 'max' => 30,   'bat_buoc' => false),
            array('the' => 'CSKCB_CGKT',     'kieu' => 'chuoi', 'max' => 5,    'bat_buoc' => false),
            array('the' => 'QD_CGKT',        'kieu' => 'chuoi', 'max' => 50,   'bat_buoc' => false),
            array('the' => 'TU_NGAY',        'kieu' => 'ngay8', 'max' => 8,    'bat_buoc' => true),
            array('the' => 'DEN_NGAY',       'kieu' => 'ngay8', 'max' => 8,    'bat_buoc' => false),
            array('the' => 'MA_CSKCB',       'kieu' => 'chuoi', 'max' => 5,    'bat_buoc' => true),
        );
    }
}

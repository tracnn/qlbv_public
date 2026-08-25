<?php

namespace App\Services\Tt12\Mau;

/** Mau 05/DM - Dich vu kham benh, chua benh ap dung trong thanh toan BHYT */
class Mau05 extends MauCoSo
{
    public static function ma()             { return 'MAU_05'; }
    public static function ten()            { return 'Mẫu 05/DM - Dịch vụ khám bệnh, chữa bệnh'; }
    public static function theDanhSach()    { return 'DANHSACH_DMDICHVUKBCB'; }
    public static function theDong()        { return 'DMDICHVUKBCB'; }
    public static function danhMuc()        { return 'service'; }
    public static function bangDanhMuc()    { return 'service_catalogs'; }
    public static function theDanhSachCon() { return 'DS_THUOCPX'; }
    public static function theDongCon()     { return 'TT_THUOCPX'; }

    public static function cot()
    {
        return array(
            array('the' => 'STT',            'kieu' => 'so',    'max' => 6,    'bat_buoc' => true),
            array('the' => 'MA_DICH_VU',     'kieu' => 'chuoi', 'max' => 20,   'bat_buoc' => true),
            array('the' => 'TEN_DICH_VU',    'kieu' => 'chuoi', 'max' => null, 'bat_buoc' => true),
            array('the' => 'TEN_DVKT_GIA',   'kieu' => 'chuoi', 'max' => null, 'bat_buoc' => false),
            array('the' => 'DON_GIA',        'kieu' => 'so',    'max' => 15,   'bat_buoc' => false),
            array('the' => 'QUY_TRINH',      'kieu' => 'chuoi', 'max' => 50,   'bat_buoc' => false),
            array('the' => 'SO_LUONG_CGKT',  'kieu' => 'so',    'max' => 4,    'bat_buoc' => false),
            array('the' => 'CSKCB_CGKT',     'kieu' => 'chuoi', 'max' => 5,    'bat_buoc' => false),
            array('the' => 'CSKCB_CLS',      'kieu' => 'chuoi', 'max' => 5,    'bat_buoc' => false),
            array('the' => 'QD_DVKT',        'kieu' => 'chuoi', 'max' => 50,   'bat_buoc' => false),
            array('the' => 'QD_PD_GIA',      'kieu' => 'chuoi', 'max' => 50,   'bat_buoc' => false),
            array('the' => 'GHI_CHU',        'kieu' => 'chuoi', 'max' => null, 'bat_buoc' => false),
            array('the' => 'TU_NGAY',        'kieu' => 'ngay8', 'max' => 8,    'bat_buoc' => true),
            array('the' => 'DEN_NGAY',       'kieu' => 'ngay8', 'max' => 8,    'bat_buoc' => false),
            array('the' => 'MA_CSKCB',       'kieu' => 'chuoi', 'max' => 5,    'bat_buoc' => true),
            array('the' => 'GIA_THANH_TOAN', 'kieu' => 'so',    'max' => 15,   'bat_buoc' => false),
        );
    }

    public static function cotCon()
    {
        return array(
            array('the' => 'STT',              'kieu' => 'so',    'max' => 6,    'bat_buoc' => false),
            array('the' => 'MA_THUOC',         'kieu' => 'chuoi', 'max' => 15,   'bat_buoc' => false),
            array('the' => 'TEN_THUOC',        'kieu' => 'chuoi', 'max' => 1024, 'bat_buoc' => false),
            array('the' => 'SO_DANG_KY',       'kieu' => 'chuoi', 'max' => 50,   'bat_buoc' => false),
            array('the' => 'DON_VI_TINH',      'kieu' => 'chuoi', 'max' => 1024, 'bat_buoc' => false),
            array('the' => 'TT_THAU',          'kieu' => 'chuoi', 'max' => 1024, 'bat_buoc' => false),
            array('the' => 'DON_GIA_THUOC',    'kieu' => 'so',    'max' => 15,   'bat_buoc' => false),
            array('the' => 'DM_NSX_CDD',       'kieu' => 'so',    'max' => 15,   'bat_buoc' => false),
            array('the' => 'DM_THUCTE_CDD',    'kieu' => 'so',    'max' => 15,   'bat_buoc' => false),
            array('the' => 'LIEU_BQ_PX',       'kieu' => 'so',    'max' => 8,    'bat_buoc' => false),
            array('the' => 'TL_THUCTE_BQ_PX',  'kieu' => 'so',    'max' => 15,   'bat_buoc' => false),
            array('the' => 'THANH_TIEN_THUOC', 'kieu' => 'so',    'max' => 15,   'bat_buoc' => false),
        );
    }

    /**
     * The bang con tro thanh BAT BUOC khi dong co bat ky du lieu thuoc phong xa nao.
     *
     * Khong danh dau bat_buoc = true trong cotCon() vi phan lon dong MAU_05 khong co
     * thuoc phong xa; danh dau cung se chan moi dich vu ky thuat thong thuong.
     *
     * @return array
     */
    public static function theConBatBuocKhiCo()
    {
        return array('MA_THUOC', 'DON_GIA_THUOC');
    }
}

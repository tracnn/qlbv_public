<?php

namespace App\Services\Tt12\Bh01;

/**
 * Dac ta 20 the CHITIET_HS01BH (tai lieu ky thuat muc VIII.5, spec 2.3).
 * Giu MOT cho duy nhat de chuan hoa, kiem va in XML cung doc: thu tu o day la thu tu in trong XML.
 */
class Bh01DacTa
{
    public static function the()
    {
        return array(
            array('the' => 'STT', 'kieu' => 'so', 'max' => 10),
            array('the' => 'HO_TEN', 'kieu' => 'chuoi', 'max' => 255),
            array('the' => 'NGAY_SINH', 'kieu' => 'ngay12', 'max' => 12),
            array('the' => 'GIOI_TINH', 'kieu' => 'chuoi', 'max' => 1),
            array('the' => 'MA_THE_BHYT', 'kieu' => 'chuoi', 'max' => 15),
            array('the' => 'MA_BENH_CHINH', 'kieu' => 'chuoi', 'max' => 7),
            array('the' => 'NGAY_VAO', 'kieu' => 'ngay12', 'max' => 12),
            array('the' => 'NGAY_VAO_NOI_TRU', 'kieu' => 'ngay12', 'max' => 12),
            array('the' => 'NGAY_RA', 'kieu' => 'ngay12', 'max' => 12),
            array('the' => 'SO_NGAY_DTRI', 'kieu' => 'chuoi', 'max' => 3),
            array('the' => 'MA_LOAI_KCB', 'kieu' => 'so', 'max' => 2),
            array('the' => 'T_TONGCHI_BV', 'kieu' => 'tien', 'max' => 15),
            array('the' => 'T_TONGCHI_BH', 'kieu' => 'tien', 'max' => 15),
            array('the' => 'T_BHTT', 'kieu' => 'tien', 'max' => 15),
            array('the' => 'T_BNCCT', 'kieu' => 'tien', 'max' => 15),
            array('the' => 'T_BNTT', 'kieu' => 'tien', 'max' => 15),
            array('the' => 'T_NGUONKHAC', 'kieu' => 'tien', 'max' => 15),
            array('the' => 'MA_CSKCB', 'kieu' => 'chuoi', 'max' => 5),
            array('the' => 'NAM_QT', 'kieu' => 'so', 'max' => 4),
            array('the' => 'THANG_QT', 'kieu' => 'so', 'max' => 2),
        );
    }

    /**
     * Muc hien thi tren mau (spec 2.1): I (01,06,07), II (02,05,08), III (04,09), IV (03).
     * null = khong xep duoc vao muc nao.
     */
    public static function muc($maLoaiKcb)
    {
        $bang = array(
            '01' => 1, '06' => 1, '07' => 1,
            '02' => 2, '05' => 2, '08' => 2,
            '04' => 3, '09' => 3,
            '03' => 4,
        );
        $ma = (string) $maLoaiKcb;
        return isset($bang[$ma]) ? $bang[$ma] : null;
    }
}

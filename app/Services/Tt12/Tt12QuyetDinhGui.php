<?php

namespace App\Services\Tt12;

/**
 * MOT noi tra loi "co duoc ky khong" va "co duoc gui khong".
 *
 * Job ky, job gui va nut bam tren man hinh deu hoi o day. Viet lai luat o ba cho la ba
 * ban se lech nhau, va ban lech se cho gui mot ho so con loi.
 *
 * Ham THUAN de kiem duoc.
 */
class Tt12QuyetDinhGui
{
    const KY        = 'KY';
    const CHUA_KIEM = 'CHUA_KIEM';
    const CON_LOI   = 'CON_LOI';

    const GUI           = 'GUI';
    const CHUA_KY       = 'CHUA_KY';
    const DA_TIEP_NHAN  = 'DA_TIEP_NHAN';

    /**
     * @param mixed $checkedAt thoi diem da kiem, rong nghia la chua kiem
     * @param int   $soLoi     so loi MUC 'loi'
     * @return string KY | CHUA_KIEM | CON_LOI
     */
    public static function nenKy($checkedAt, $soLoi)
    {
        if (empty($checkedAt)) {
            return self::CHUA_KIEM;
        }

        if ((int) $soLoi > 0) {
            return self::CON_LOI;
        }

        return self::KY;
    }

    /**
     * @param bool  $daKy
     * @param mixed $maKetQua ma ket qua lan gui gan nhat, rong nghia la chua gui
     * @return string GUI | CHUA_KY | DA_TIEP_NHAN
     */
    public static function nenGui($daKy, $maKetQua)
    {
        if (!$daKy) {
            return self::CHUA_KY;
        }

        if (self::daTiepNhan($maKetQua)) {
            return self::DA_TIEP_NHAN;
        }

        return self::GUI;
    }

    /**
     * @param mixed $maKetQua
     * @return bool
     */
    public static function daTiepNhan($maKetQua)
    {
        if ($maKetQua === null || $maKetQua === '') {
            return false;
        }

        // in_array SO SANH LONG (khong truyen tham so thu ba): ma ket qua co the la
        // chuoi '200' tu cong, hoac int 200 sau khi di qua mot lop ep kieu nao do. Dung
        // so sanh nghiem ngat o day la de mot trong hai dang truot qua va ho so duoc gui
        // lai lan hai.
        return in_array($maKetQua, config('tt12.ma_thanh_cong', array('200')));
    }

    /** @return string thong diep hien cho nguoi dung */
    public static function moTa($quyetDinh)
    {
        $bang = array(
            self::KY            => 'Đủ điều kiện ký',
            self::CHUA_KIEM     => 'Hồ sơ chưa được kiểm',
            self::CON_LOI       => 'Hồ sơ còn lỗi, không ký được',
            self::GUI           => 'Đủ điều kiện gửi',
            self::CHUA_KY       => 'Hồ sơ chưa ký số',
            self::DA_TIEP_NHAN  => 'Hồ sơ đã được cổng tiếp nhận, không gửi lại',
        );

        return isset($bang[$quyetDinh]) ? $bang[$quyetDinh] : (string) $quyetDinh;
    }
}

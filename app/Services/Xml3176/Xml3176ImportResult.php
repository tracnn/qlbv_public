<?php

namespace App\Services\Xml3176;

/**
 * Ket qua nhap MOT ho so XML3176.
 *
 * Tra ve doi tuong thay vi bool: controller can thong diep loi de hien len giao dien,
 * con lenh console can biet co duoc xoa file nguon hay khong.
 */
class Xml3176ImportResult
{
    /** @var bool */
    public $thanhCong;

    /** @var string|null */
    public $maLk;

    /** @var array */
    public $loaiDaXuLy = [];

    /** @var string|null */
    public $lyDoThatBai;

    public static function thanhCong($maLk, array $loaiDaXuLy)
    {
        $kq = new self();
        $kq->thanhCong  = true;
        $kq->maLk       = $maLk;
        $kq->loaiDaXuLy = $loaiDaXuLy;

        return $kq;
    }

    public static function thatBai($lyDo)
    {
        $kq = new self();
        $kq->thanhCong   = false;
        $kq->lyDoThatBai = $lyDo;

        return $kq;
    }
}

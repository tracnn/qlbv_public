<?php

namespace App\Services\Ctdt;

/**
 * Ket qua nhap MOT ho so.
 *
 * Tra doi tuong thay vi bool: giao dien can ly do CU THE de hien len, con lenh console
 * can biet co duoc chuyen tep nguon sang thu muc "da nhap" hay khong.
 */
class CtdtImportResult
{
    /** @var bool */
    public $thanhCong;

    /** @var string|null */
    public $maHoSo;

    /** @var array Cac gia tri LOAIHOSO da xu ly duoc */
    public $loaiDaXuLy = [];

    /** @var string|null */
    public $lyDoThatBai;

    /** @var string|null MaGD cua ban da gui vua bi ghi de, null neu chua tung gui */
    public $maGdBiGhiDe;

    public static function thanhCong($maHoSo, array $loaiDaXuLy, $maGdBiGhiDe = null)
    {
        $kq = new self();
        $kq->thanhCong    = true;
        $kq->maHoSo       = $maHoSo;
        $kq->loaiDaXuLy   = $loaiDaXuLy;
        $kq->maGdBiGhiDe  = $maGdBiGhiDe;

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

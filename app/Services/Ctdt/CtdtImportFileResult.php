<?php

namespace App\Services\Ctdt;

/**
 * Ket qua nhap MOT TEP - mot goi HSCHUNGTU co the chua NHIEU ho so.
 *
 * Giu dung hai ten thuoc tinh 'thanhCong' va 'lyDoThatBai' nhu CtdtImportResult, nen noi
 * goi khong phai phan biet minh dang nhan lop nao.
 */
class CtdtImportFileResult
{
    /** @var bool Moi ho so deu thanh cong VA so luong khong thieu so voi khai bao */
    public $thanhCong;

    /** @var string|null Ly do gop */
    public $lyDoThatBai;

    /** @var array<CtdtImportResult> Ket qua tung ho so */
    public $ketQua = [];

    /** @var int */
    public $soThanhCong = 0;

    /** @var int */
    public $soThatBai = 0;

    /** @var array Cac ma_ho_so nhap thanh cong */
    public $dsMaHoSo = [];

    /** Hong ngay tu dau tep, chua xu ly ho so nao. */
    public static function thatBaiSom($lyDo)
    {
        $kq = new self();
        $kq->thanhCong   = false;
        $kq->lyDoThatBai = $lyDo;

        return $kq;
    }

    /**
     * @param array $ketQua    CtdtImportResult cho tung ho so
     * @param int   $soKhaiBao Gia tri SOLUONGHOSO trong goi
     * @param int   $soThucTe  So the HOSO dem duoc
     */
    public static function tu(array $ketQua, $soKhaiBao, $soThucTe)
    {
        $kq = new self();
        $kq->ketQua = $ketQua;

        $lyDo = [];

        foreach ($ketQua as $i => $r) {
            if ($r->thanhCong) {
                $kq->soThanhCong++;
                $kq->dsMaHoSo[] = $r->maHoSo;
            } else {
                $kq->soThatBai++;
                $lyDo[] = 'Ho so #' . ($i + 1) . ': ' . $r->lyDoThatBai;
            }
        }

        // Bat doi xung CO CHU DICH:
        //  - thuc te IT hon khai bao -> tep co the bi cat cut, tu choi ca tep. Nhap mot
        //    phan roi bao thanh cong la kieu hong nguy hiem nhat.
        //  - thuc te NHIEU hon khai bao -> metadata sai nhung du lieu du, chan o day la
        //    chan nham.
        if ((int) $soThucTe < (int) $soKhaiBao) {
            array_unshift(
                $lyDo,
                'SOLUONGHOSO khai bao ' . $soKhaiBao . ' nhung tep chi co ' . $soThucTe . ' ho so'
            );
        }

        $kq->thanhCong   = empty($lyDo);
        $kq->lyDoThatBai = empty($lyDo) ? null : implode('; ', $lyDo);

        return $kq;
    }
}

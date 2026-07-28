<?php

namespace App\Services\Xml3176;

/**
 * Ket qua nhap MOT FILE - mot file GIAMDINHHS co the chua NHIEU ho so.
 *
 * Truoc day ma nguon duyet $xmldata->THONGTINHOSO->DANHSACHHOSO->HOSO->FILEHOSO, ma
 * trong SimpleXML thi ->HOSO tren mot tap nhieu phan tu TU LAY PHAN TU DAU - khong canh
 * bao, khong loi. File thuc te co 2 ho so thi ho so thu hai bi bo hoan toan, va nguoi
 * dung van nhan "processed successfully".
 *
 * Giu dung hai ten thuoc tinh 'thanhCong' va 'lyDoThatBai' nhu Xml3176ImportResult, nen
 * cac noi goi hien tai khong phai sua vi ly do doi kieu.
 */
class Xml3176ImportFileResult
{
    /** @var bool Moi ho so deu thanh cong VA so luong khop */
    public $thanhCong;

    /** @var string|null Ly do gop */
    public $lyDoThatBai;

    /** @var array<Xml3176ImportResult> Ket qua tung ho so */
    public $ketQua = [];

    /** @var int */
    public $soThanhCong = 0;

    /** @var int */
    public $soThatBai = 0;

    /** @var array Cac ma_lk nhap thanh cong */
    public $dsMaLk = [];

    /**
     * Truong hop hong ngay tu dau file, chua xu ly ho so nao.
     */
    public static function thatBaiSom($lyDo)
    {
        $kq = new self();
        $kq->thanhCong   = false;
        $kq->lyDoThatBai = $lyDo;

        return $kq;
    }

    /**
     * Tong hop tu ket qua tung ho so.
     *
     * @param array $ketQua     Xml3176ImportResult cho tung ho so
     * @param int   $soKhaiBao  Gia tri SOLUONGHOSO trong file
     * @param int   $soThucTe   So the HOSO dem duoc
     */
    public static function tu(array $ketQua, int $soKhaiBao, int $soThucTe)
    {
        $kq = new self();
        $kq->ketQua = $ketQua;

        $lyDo = [];

        foreach ($ketQua as $i => $r) {
            if ($r->thanhCong) {
                $kq->soThanhCong++;
                $kq->dsMaLk[] = $r->maLk;
            } else {
                $kq->soThatBai++;
                $lyDo[] = 'Ho so #' . ($i + 1) . ': ' . $r->lyDoThatBai;
            }
        }

        // Bat doi xung CO CHU DICH:
        //  - thuc te IT hon khai bao -> file co the bi cat cut, tu choi ca file. Nhap mot
        //    phan roi bao thanh cong chinh la loi ma dot nay di chua.
        //  - thuc te NHIEU hon khai bao -> metadata sai nhung du lieu du, chan o day la
        //    chan nham.
        if ($soThucTe < $soKhaiBao) {
            array_unshift(
                $lyDo,
                'SOLUONGHOSO khai bao ' . $soKhaiBao . ' nhung file chi co ' . $soThucTe . ' ho so'
            );
        }

        $kq->thanhCong   = empty($lyDo);
        $kq->lyDoThatBai = empty($lyDo) ? null : implode('; ', $lyDo);

        return $kq;
    }
}

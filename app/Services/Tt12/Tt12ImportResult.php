<?php

namespace App\Services\Tt12;

/**
 * Ket qua nap MOT tep. Doi tuong bat bien.
 *
 * VI SAO KHONG TRA MANG: noi goi (controller, test) doc ket qua bang ten phuong thuc nen
 * go nham ten se hong ngay, con go nham khoa mang chi tra ve null.
 */
class Tt12ImportResult
{
    private $thanhCong;
    private $maHoSo;
    private $mau;
    private $soDong;
    private $loi;
    private $soODienThem;

    private function __construct($thanhCong, $maHoSo, $mau, $soDong, $loi, $soODienThem = 0)
    {
        $this->thanhCong   = (bool) $thanhCong;
        $this->maHoSo      = $maHoSo;
        $this->mau         = $mau;
        $this->soDong      = (int) $soDong;
        $this->loi         = $loi;
        $this->soODienThem = (int) $soODienThem;
    }

    /**
     * Ten 'tot' chu khong 'thanhCong': ham truy van da mang ten thanhCong() o duoi, va
     * PHP khong cho mot lop co hai phuong thuc cung ten du mot cai la static.
     */
    public static function tot($maHoSo, $mau, $soDong, $soODienThem = 0)
    {
        return new self(true, $maHoSo, $mau, $soDong, null, $soODienThem);
    }

    /** Hong truoc khi tao duoc ho so nao: khong co ma ho so de tra ve */
    public static function thatBaiSom($moTa)
    {
        return new self(false, null, null, 0, $moTa);
    }

    /** Hong sau khi da tao ho so: giu lai ma de nguoi dung xoa hoac xem */
    public static function thatBai($maHoSo, $mau, $moTa)
    {
        return new self(false, $maHoSo, $mau, 0, $moTa);
    }

    public function thanhCong() { return $this->thanhCong; }
    public function maHoSo()    { return $this->maHoSo; }
    public function mau()       { return $this->mau; }
    public function soDong()    { return $this->soDong; }
    public function loi()       { return $this->loi; }

    /**
     * So o MA_CSKCB de trong da duoc dien theo co so nguoi dung chon.
     *
     * Bao ra chu khong dien im lang: nguoi dung can biet he thong da tu ghi vao bao nhieu
     * o ma tep goc bo trong.
     */
    public function soODienThem() { return $this->soODienThem; }
}

<?php

namespace App\Services\Tt12\Kiem;

/**
 * Mot loi tim duoc. Doi tuong BAT BIEN, chua cham CSDL.
 *
 * Tach khoi model App\Models\BHYT\Tt12\Tt12Loi de cac lop luat la HAM THUAN: kiem duoc
 * ma khong can dung bang.
 */
class Tt12Loi
{
    const MUC_DO_LOI      = 'loi';
    const MUC_DO_CANH_BAO = 'canh_bao';

    private $maLoi;
    private $moTa;
    private $sttDong;
    private $cot;
    private $mucDo;

    private function __construct($maLoi, $moTa, $sttDong, $cot, $mucDo)
    {
        $this->maLoi   = $maLoi;
        $this->moTa    = $moTa;
        $this->sttDong = $sttDong;
        $this->cot     = $cot;
        $this->mucDo   = $mucDo;
    }

    /** Chan ky va gui */
    public static function loi($maLoi, $moTa, $sttDong = null, $cot = null)
    {
        return new self($maLoi, $moTa, $sttDong, $cot, self::MUC_DO_LOI);
    }

    /** Hien tren man hinh nhung KHONG chan ky va gui */
    public static function canhBao($maLoi, $moTa, $sttDong = null, $cot = null)
    {
        return new self($maLoi, $moTa, $sttDong, $cot, self::MUC_DO_CANH_BAO);
    }

    public function maLoi()   { return $this->maLoi; }
    public function moTa()    { return $this->moTa; }
    public function sttDong() { return $this->sttDong; }
    public function cot()     { return $this->cot; }
    public function mucDo()   { return $this->mucDo; }

    public function laLoi()
    {
        return $this->mucDo === self::MUC_DO_LOI;
    }

    /** @return array de ghi thang vao bang tt12_loi */
    public function thanhMang($hoSoId)
    {
        return array(
            'ho_so_id' => $hoSoId,
            'stt_dong' => $this->sttDong,
            'cot'      => $this->cot,
            'ma_loi'   => $this->maLoi,
            'muc_do'   => $this->mucDo,
            'mo_ta'    => $this->moTa,
        );
    }
}

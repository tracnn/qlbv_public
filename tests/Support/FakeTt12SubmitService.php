<?php

namespace Tests\Support;

use App\Services\Tt12\Tt12SubmitService;

/**
 * Dich vu gui gia, KE THUA lop that de giu dung chu ky phuong thuc.
 *
 * Ke thua chu khong viet lop rieng: neu chu ky gui() doi ma ban gia khong doi theo thi
 * test van xanh trong khi ma san pham da hong.
 */
class FakeTt12SubmitService extends Tt12SubmitService
{
    public $ketQua = array(
        'ma_ket_qua'          => '200',
        'ma_gd'               => 'DANHMUC01_01929',
        'thoi_gian_tiep_nhan' => '20260825083000',
        'thong_diep'          => 'Tiếp nhận thành công',
        'nguyen_van'          => '{"maKetQua":"200"}',
    );

    public $nem = null;
    public $soLanGoi = 0;
    public $xmlNhanDuoc = null;
    public $mauNhanDuoc = null;
    public $maCskcbNhanDuoc = null;

    public function __construct()
    {
        // Bo qua constructor cha de khong khoi tao Guzzle that.
    }

    public function gui($xmlDaKy, $mau, $maCskcb)
    {
        $this->soLanGoi++;
        $this->xmlNhanDuoc = $xmlDaKy;
        $this->mauNhanDuoc = $mau;
        $this->maCskcbNhanDuoc = $maCskcb;

        if ($this->nem !== null) {
            throw $this->nem;
        }

        return $this->ketQua;
    }
}

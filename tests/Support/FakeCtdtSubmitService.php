<?php

namespace Tests\Support;

use App\Services\Ctdt\CtdtSubmitService;

/**
 * Dich vu gui gia, ke thua lop that de giu dung chu ky phuong thuc.
 */
class FakeCtdtSubmitService extends CtdtSubmitService
{
    public $ketQua = [
        'ma_ket_qua' => '200', 'ma_gd' => 'GD-001',
        'thoi_gian_tiep_nhan' => '20260820083000',
        'thong_diep' => 'Mã 200: Thành công', 'nguyen_van' => '{"MaKetQua":"200"}',
    ];
    public $nem = null;
    public $soLanGoi = 0;
    public $xmlNhanDuoc = null;
    public $dichVuNhanDuoc = null;
    public $maCskcbNhanDuoc = null;

    public function __construct()
    {
        // Bo qua constructor cha de khong khoi tao Guzzle that.
    }

    public function gui($xmlDaKy, $dichVu, $maCskcb)
    {
        $this->soLanGoi++;
        $this->xmlNhanDuoc = $xmlDaKy;
        $this->dichVuNhanDuoc = $dichVu;
        $this->maCskcbNhanDuoc = $maCskcb;

        if ($this->nem !== null) {
            throw $this->nem;
        }

        return $this->ketQua;
    }
}

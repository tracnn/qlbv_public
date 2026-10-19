<?php

namespace Tests\Unit\Dashboard\LanhDao;

use App\Services\Dashboard\LanhDao\KyLoc;
use App\Services\Dashboard\LanhDao\TheSo;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class TheSoTest extends TestCase
{
    private function ky($coSo)
    {
        return new KyLoc('20261001000000', '20261019235959', $coSo, Carbon::create(2026, 10, 19, 12));
    }

    private function phan()
    {
        return [
            'ngay_dieu_tri' => ['hn' => 5.5, 'nb' => 3.2, '2cs' => 4.8],
            'cong_suat_giuong' => ['hn' => 80.0, 'nb' => 60.0, '2cs' => 74.0],
            'tg_kham_phut' => ['hn' => 10, 'nb' => 30, '2cs' => 12],
            'tg_kham_so_ho_so' => ['hn' => 100, 'nb' => 10, '2cs' => 110],
            'ke_don_phut' => ['hn' => 15, 'nb' => null, '2cs' => 15],
            'ke_don_so_phieu' => ['hn' => 40, 'nb' => 0, '2cs' => 40],
            'moi' => ['hn' => 30, 'nb' => 10, '2cs' => 40],
            'cu' => ['hn' => 60, 'nb' => 0, '2cs' => 60],
            'y_lenh_pt' => ['hn' => 7, 'nb' => 2, '2cs' => 9],
            'ca_pt' => ['hn' => 5, 'nb' => 1, '2cs' => 6],
            'can_thiep_tm' => ['hn' => 3, 'nb' => 0, '2cs' => 3],
        ];
    }

    /** @test */
    public function gia_tri_2cs_lay_so_gop_khong_trung_binh()
    {
        $kq = TheSo::dinhDang($this->phan(), $this->ky('2cs'));

        $this->assertSame(12.0, $kq['tg_kham_phut']['gia_tri']);
        $this->assertSame(10.0, $kq['tg_kham_phut']['hn']);
        $this->assertSame(30.0, $kq['tg_kham_phut']['nb']);
        $this->assertSame(110, $kq['tg_kham_phut']['so_ho_so']);
        $this->assertSame(['moi' => 30, 'cu' => 60], $kq['kham_moi_cu']['hn']);
        $this->assertSame(['moi' => 10, 'cu' => 0], $kq['kham_moi_cu']['nb']);
        $this->assertSame(['y_lenh_pt' => 7, 'ca_pt' => 5, 'can_thiep_tm' => 3], $kq['he_ngoai']['hn']);
        $this->assertSame(['y_lenh_pt' => 2, 'ca_pt' => 1, 'can_thiep_tm' => 0], $kq['he_ngoai']['nb']);
        $this->assertSame(4.8, $kq['ngay_dieu_tri']['gia_tri']);
        $this->assertSame(40, $kq['ke_don_mua_thuoc_phut']['so_phieu']);
    }

    /** @test */
    public function co_so_nb_gia_tri_bang_nb()
    {
        $kq = TheSo::dinhDang($this->phan(), $this->ky('nb'));

        $this->assertSame(30.0, $kq['tg_kham_phut']['gia_tri']);
        $this->assertSame(10, $kq['tg_kham_phut']['so_ho_so']);
        $this->assertNull($kq['ke_don_mua_thuoc_phut']['gia_tri']);
        $this->assertSame(10, $kq['kham_moi_cu']['moi']);
        $this->assertSame(0, $kq['kham_moi_cu']['cu']);
        $this->assertSame(100.0, $kq['kham_moi_cu']['ty_le_moi']);
        $this->assertSame(2, $kq['he_ngoai']['y_lenh_pt']);
        $this->assertSame(1, $kq['he_ngoai']['ca_pt']);
        $this->assertSame(0, $kq['he_ngoai']['can_thiep_tm']);
    }

    /** @test */
    public function ty_le_moi_mau_0_null()
    {
        $phan = $this->phan();
        $phan['moi'] = ['hn' => 0, 'nb' => 0, '2cs' => 0];
        $phan['cu'] = ['hn' => 0, 'nb' => 0, '2cs' => 0];

        $kq = TheSo::dinhDang($phan, $this->ky('2cs'));

        $this->assertNull($kq['kham_moi_cu']['ty_le_moi']);
        $this->assertSame(0, $kq['kham_moi_cu']['moi']);
    }

    /** @test */
    public function ty_le_moi_2cs_tinh_tu_so_gop()
    {
        $kq = TheSo::dinhDang($this->phan(), $this->ky('2cs'));

        $this->assertSame(40.0, $kq['kham_moi_cu']['ty_le_moi']);
    }
}

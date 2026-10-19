<?php

namespace Tests\Unit\Dashboard\LanhDao;

use Tests\TestCase;
use App\Services\Dashboard\LanhDao\KyLoc;
use App\Services\Dashboard\LanhDao\TabKhamThuoc;

class TabKhamThuocTest extends TestCase
{
    private function ky($coSo = '2cs')
    {
        return new KyLoc('20261001000000', '20261019235959', $coSo);
    }

    private function kham($branch, $gio, $trungVi, $so)
    {
        return (object) ['branch_id' => $branch, 'gio' => $gio, 'trung_vi' => $trungVi, 'so_ho_so' => $so];
    }

    private function thuoc($branch, $nhom, $trungVi, $so)
    {
        return (object) ['branch_id' => $branch, 'nhom' => $nhom, 'trung_vi' => $trungVi, 'so_phieu' => $so];
    }

    /** @test */
    public function bieu_do_gio_lay_dong_gop_khi_2cs()
    {
        $kham = [
            $this->kham(null, 8, 40.0, 100),
            $this->kham(1, 8, 30.0, 60),
            $this->kham(81, 8, 55.0, 40),
        ];

        $kq = TabKhamThuoc::dinhDang($kham, [], ['moi' => [], 'cu' => []], $this->ky('2cs'));
        $this->assertSame(range(6, 17), $kq['tg_kham_theo_gio']['gio']);
        $this->assertSame(40.0, $kq['tg_kham_theo_gio']['trung_vi'][2]);
        $this->assertSame(100, $kq['tg_kham_theo_gio']['so_ho_so'][2]);

        $kq = TabKhamThuoc::dinhDang($kham, [], ['moi' => [], 'cu' => []], $this->ky('hn'));
        $this->assertSame(30.0, $kq['tg_kham_theo_gio']['trung_vi'][2]);
        $this->assertSame(60, $kq['tg_kham_theo_gio']['so_ho_so'][2]);
    }

    /** @test */
    public function dong_tong_theo_co_so_hoac_theo_gio_khong_lot_vao_bieu_do()
    {
        // dùng cờ GROUPING: dòng (branch) có gg=1, dòng (gio) có gb=1, dòng () có cả hai
        $kham = [
            (object) ['branch_id' => 1, 'gio' => null, 'trung_vi' => 99.0, 'so_ho_so' => 999, 'gb' => 0, 'gg' => 1],
            (object) ['branch_id' => 1, 'gio' => 9, 'trung_vi' => 20.0, 'so_ho_so' => 5, 'gb' => 0, 'gg' => 0],
            (object) ['branch_id' => null, 'gio' => 9, 'trung_vi' => 21.0, 'so_ho_so' => 8, 'gb' => 1, 'gg' => 0],
        ];

        $kq = TabKhamThuoc::dinhDang($kham, [], ['moi' => [], 'cu' => []], $this->ky('hn'));
        $this->assertSame(20.0, $kq['tg_kham_theo_gio']['trung_vi'][3]);
        $this->assertSame(0, $kq['tg_kham_theo_gio']['so_ho_so'][0]);
    }

    /** @test */
    public function gio_thieu_du_lieu_la_null()
    {
        $kq = TabKhamThuoc::dinhDang([$this->kham(null, 8, 40.0, 100)], [], ['moi' => [], 'cu' => []], $this->ky());

        $this->assertNull($kq['tg_kham_theo_gio']['trung_vi'][3]);
        $this->assertSame(0, $kq['tg_kham_theo_gio']['so_ho_so'][3]);
    }

    /** @test */
    public function nhom_thuoc_du_6_nhom_dung_thu_tu()
    {
        $thuoc = [$this->thuoc(null, 1, 15.0, 7), $this->thuoc(null, 5, 150.0, 2), $this->thuoc(null, null, 16.0, 9)];

        $kq = TabKhamThuoc::dinhDang([], $thuoc, ['moi' => [], 'cu' => []], $this->ky());

        $this->assertSame(['<10', '10–20', '20–30', '30–60', '60–120', '>120'], $kq['ke_don_mua_thuoc']['nhom']);
        $this->assertSame([0, 7, 0, 0, 0, 2], $kq['ke_don_mua_thuoc']['so_phieu']);
    }

    /** @test */
    public function moi_cu_theo_co_so()
    {
        $moiCu = ['moi' => [1 => 39885, 81 => 13464], 'cu' => [1 => 53694, 81 => 11148]];

        $kq = TabKhamThuoc::dinhDang([], [], $moiCu, $this->ky('2cs'));
        $this->assertSame(['HN', 'NB'], $kq['moi_cu']['co_so']);
        $this->assertSame([39885, 13464], $kq['moi_cu']['moi']);
        $this->assertSame([53694, 11148], $kq['moi_cu']['cu']);

        $kq = TabKhamThuoc::dinhDang([], [], $moiCu, $this->ky('nb'));
        $this->assertSame(['NB'], $kq['moi_cu']['co_so']);
        $this->assertSame([13464], $kq['moi_cu']['moi']);
    }

    /** @test */
    public function the_so_lay_2cs_tu_dong_gop_khong_trung_binh()
    {
        $kham = [
            $this->kham(1, null, 30.0, 60), $this->kham(81, null, 50.0, 40), $this->kham(null, null, 33.0, 100),
            $this->kham(1, 8, 25.0, 10), // dòng theo giờ không được lẫn vào thẻ
        ];
        $thuoc = [$this->thuoc(1, null, 16.0, 10), $this->thuoc(null, null, 18.0, 15)];
        $moiCu = ['moi' => [1 => 5, 81 => 3], 'cu' => [1 => 7]];

        $kq = TabKhamThuoc::tongHop($kham, $thuoc, $moiCu);

        $this->assertSame(['hn' => 30.0, 'nb' => 50.0, '2cs' => 33.0], $kq['tg_kham_phut']);
        $this->assertSame(['hn' => 60, 'nb' => 40, '2cs' => 100], $kq['tg_kham_so_ho_so']);
        $this->assertSame(['hn' => 16.0, 'nb' => null, '2cs' => 18.0], $kq['ke_don_phut']);
        $this->assertSame(['hn' => 10, 'nb' => 0, '2cs' => 15], $kq['ke_don_so_phieu']);
        $this->assertSame(['hn' => 5, 'nb' => 3, '2cs' => 8], $kq['moi']);
        $this->assertSame(['hn' => 7, 'nb' => 0, '2cs' => 7], $kq['cu']);
    }
}

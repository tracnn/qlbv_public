<?php

namespace Tests\Unit\Dashboard\LanhDao;

use Tests\TestCase;
use App\Services\Dashboard\LanhDao\KyLoc;
use App\Services\Dashboard\LanhDao\TabThanhToan;

class TabThanhToanTest extends TestCase
{
    private function danhMuc()
    {
        return [
            'khoa' => [10 => '1|K04', 11 => '1|K04', 20 => '81|K05', 30 => '1|K06'],
            'don_vi' => [
                '1|K04' => ['ma' => 'K04', 'ten' => 'Tim mạch', 'branch_id' => 1, 'giuong_pd' => 50],
                '81|K05' => ['ma' => 'K05', 'ten' => 'Nội', 'branch_id' => 81, 'giuong_pd' => 40],
                '1|K06' => ['ma' => 'K06', 'ten' => 'Ngoại', 'branch_id' => 1, 'giuong_pd' => 30],
            ],
        ];
    }

    private function ky($coSo = 'hn')
    {
        return new KyLoc('20261001000000', '20261019235959', $coSo);
    }

    private function dong($khoa, $nhom, $online, $soGd, $tien = 1000)
    {
        return (object) [
            'last_department_id' => $khoa, 'nhom' => $nhom, 'la_online' => $online,
            'so_gd' => $soGd, 'tien' => $tien,
        ];
    }

    /** @test */
    public function gop_khoa_con_va_tinh_ty_le_qr()
    {
        $dong = [
            $this->dong(10, 'thu', 1, 30, 300), $this->dong(10, 'thu', 0, 20, 200),
            $this->dong(11, 'thu', 1, 20, 100), $this->dong(11, 'thu', 0, 30, 400),
        ];

        $kq = TabThanhToan::dinhDang($dong, $this->danhMuc(), $this->ky());

        $this->assertSame(['Tim mạch'], $kq['qr']['categories']);
        $this->assertSame([50.0], $kq['qr']['ty_le']);
        $this->assertSame([50], $kq['qr']['so_gd']);
        $this->assertSame([100], $kq['qr']['tong_gd']);
        $this->assertEquals([400.0], $kq['qr']['tien']);
        $this->assertEquals([1000.0], $kq['qr']['tong_tien']);
    }

    /** @test */
    public function hoan_online_tach_rieng_qr()
    {
        $dong = [
            $this->dong(10, 'thu', 1, 10), $this->dong(10, 'thu', 0, 10),
            $this->dong(10, 'hoan', 1, 3), $this->dong(10, 'hoan', 0, 1),
        ];

        $kq = TabThanhToan::dinhDang($dong, $this->danhMuc(), $this->ky());

        $this->assertSame([50.0], $kq['qr']['ty_le']);
        $this->assertSame([20], $kq['qr']['tong_gd']);
        $this->assertSame([75.0], $kq['hoan_online']['ty_le']);
        $this->assertSame([3], $kq['hoan_online']['so_gd']);
        $this->assertSame([4], $kq['hoan_online']['tong_gd']);
    }

    /** @test */
    public function loc_co_so_theo_khoa()
    {
        $dong = [
            $this->dong(10, 'thu', 1, 5), $this->dong(20, 'thu', 1, 5), $this->dong(99, 'thu', 1, 5),
        ];

        $nb = TabThanhToan::dinhDang($dong, $this->danhMuc(), $this->ky('nb'));
        $this->assertSame(['Nội'], $nb['qr']['categories']);

        $hai = TabThanhToan::dinhDang($dong, $this->danhMuc(), $this->ky('2cs'));
        $this->assertSame(['Tim mạch (HN)', 'Nội (NB)'], $hai['qr']['categories']);
    }

    /** @test */
    public function don_vi_khong_co_giao_dich_hoan_khong_hien()
    {
        $dong = [$this->dong(10, 'thu', 1, 5), $this->dong(30, 'thu', 0, 4)];

        $kq = TabThanhToan::dinhDang($dong, $this->danhMuc(), $this->ky());

        $this->assertSame([], $kq['hoan_online']['categories']);
        $this->assertSame([], $kq['hoan_online']['ty_le']);
        $this->assertCount(2, $kq['qr']['categories']);
    }

    /** @test */
    public function sap_giam_theo_ty_le()
    {
        $dong = [
            $this->dong(10, 'thu', 1, 1), $this->dong(10, 'thu', 0, 9),
            $this->dong(30, 'thu', 1, 8), $this->dong(30, 'thu', 0, 2),
        ];

        $kq = TabThanhToan::dinhDang($dong, $this->danhMuc(), $this->ky());

        $this->assertSame(['Ngoại', 'Tim mạch'], $kq['qr']['categories']);
        $this->assertSame([80.0, 10.0], $kq['qr']['ty_le']);
    }
}

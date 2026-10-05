<?php

namespace Tests\Unit\BHYT;

use App\Services\BHYT\KhoaDieuTriHis;
use Tests\Support\FakeKhoaDieuTriHis;
use Tests\TestCase;

class KhoaDieuTriHisTest extends TestCase
{
    const K01 = ['ma_khoa' => 'K01', 'ten_khoa' => 'Khoa Noi'];

    /**
     * File loi XML3176 dung chung MOT instance cho 17 sheet: mot ho so loi o XML1, XML3, XML4
     * chi duoc tra HIS mot lan.
     */
    /** @test */
    public function ma_da_tra_khong_tra_lai_ke_ca_ma_khong_co_trong_his()
    {
        $s = new FakeKhoaDieuTriHis(['LK1' => self::K01]);

        $this->assertSame(['LK1' => self::K01], $s->theoMaLk(['LK1', 'LK2']));
        $this->assertSame(['LK1' => self::K01], $s->theoMaLk(['LK1', 'LK2']));
        $s->theoMaLk(['LK2', 'LK3']);

        $this->assertSame([['LK1', 'LK2'], ['LK3']], $s->cacLo);
    }

    /** Loi giua chung khong duoc nho thanh "khong co khoa": lan sau phai tra lai. */
    /** @test */
    public function tra_loi_thi_khong_nho_va_nem_lai()
    {
        $s = new FakeKhoaDieuTriHis([], new \RuntimeException('ORA-12541'));

        try {
            $s->theoMaLk(['LK1']);
            $this->fail('Phai nem lai loi');
        } catch (\RuntimeException $e) {
        }

        try {
            $s->theoMaLk(['LK1']);
        } catch (\RuntimeException $e) {
        }

        $this->assertCount(2, $s->cacLo);
    }

    /** @test */
    public function khoa_cho_xuat_tra_khoa_hoac_rong()
    {
        $s = new FakeKhoaDieuTriHis(['LK1' => self::K01]);

        $this->assertSame(
            ['LK1' => self::K01, 'LK2' => ['ma_khoa' => null, 'ten_khoa' => null]],
            $s->khoaChoXuat(['LK1', 'LK2', 'LK1', ''])
        );
    }

    /** Mat ket noi HIS: van xuat, o khoa ghi ro loi - de trong se trong nhu "khong co khoa". */
    /** @test */
    public function khoa_cho_xuat_loi_his_ghi_ro_trong_o()
    {
        $s = new FakeKhoaDieuTriHis([], new \RuntimeException('ORA-12541'));
        $loi = ['ma_khoa' => KhoaDieuTriHis::LOI, 'ten_khoa' => KhoaDieuTriHis::LOI];

        $this->assertSame('Lỗi tra HIS', KhoaDieuTriHis::LOI);
        $this->assertSame(['LK1' => $loi, 'LK2' => $loi], $s->khoaChoXuat(['LK1', 'LK2']));
    }
}

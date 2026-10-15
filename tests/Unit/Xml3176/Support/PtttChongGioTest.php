<?php

namespace Tests\Unit\Xml3176\Support;

use App\Services\Xml3176\Support\PtttChongGio;
use Tests\TestCase;

/**
 * Do 15/10/2026 tren CSDL that: dong Hut dom ghi NGAY_KQ don ve cuoi dot (toi 1.228 gio) nen
 * chong voi moi dong khac - ho so 000007038497 ra 372.807 cap. Nguong tach cac dong do ra,
 * quet mot luot thay so tung cap.
 */
class PtttChongGioTest extends TestCase
{
    const THEO_MA = ['01.0181' => 72, '01.0182' => 72, '01.0183' => 72, '11.0118' => 48];

    /** @test */
    public function nguong_theo_tien_to_ma_dai_nhat_thang()
    {
        $this->assertSame(72, PtttChongGio::nguongGio('01.0181.0118', 24, self::THEO_MA));
        $this->assertSame(72, PtttChongGio::nguongGio('01.0181.0118_GT', 24, self::THEO_MA), 'ma co hau to');
        $this->assertSame(48, PtttChongGio::nguongGio('11.0118.1159', 24, self::THEO_MA));
        $this->assertSame(24, PtttChongGio::nguongGio('02.0150.0114', 24, self::THEO_MA));
        $this->assertSame(72, PtttChongGio::nguongGio('01.0181.0118', 24, ['01' => 10, '01.0181' => 72]));
        $this->assertSame(10, PtttChongGio::nguongGio('01.0001.0001', 24, ['01' => 10, '01.0181' => 72]));
    }

    private function d($id, $tu, $den)
    {
        return ['id' => $id, 'tu' => strtotime('2026-01-01 ' . $tu), 'den' => strtotime('2026-01-01 ' . $den)];
    }

    /** @test */
    public function quet_bao_doi_xung_hai_phia()
    {
        $kq = PtttChongGio::quet([
            $this->d(1, '08:00', '09:00'),
            $this->d(2, '08:30', '09:30'),
            $this->d(3, '08:45', '08:50'),
            $this->d(6, '10:00', '11:00'),
        ], 5);

        $this->assertSame([
            1 => ['so' => 2, 'neu' => [2, 3]],
            2 => ['so' => 2, 'neu' => [1, 3]],
            3 => ['so' => 2, 'neu' => [1, 2]],
        ], $kq);
    }

    /** @test */
    public function noi_tiep_dung_moc_khong_chong()
    {
        $this->assertSame([], PtttChongGio::quet([
            $this->d(1, '08:00', '09:00'),
            $this->d(2, '09:00', '10:00'),
        ], 5));
    }

    /** @test */
    public function sap_theo_gio_bat_dau_khong_theo_id()
    {
        // id 9 bat dau truoc id 1: doi tac cua 5 phai la [9, 1]
        $kq = PtttChongGio::quet([
            $this->d(1, '08:10', '09:00'),
            $this->d(5, '08:20', '08:30'),
            $this->d(9, '08:00', '09:00'),
        ], 5);

        $this->assertSame([9, 1], $kq[5]['neu']);
        $this->assertSame([9, 5], $kq[1]['neu']);
    }

    /** @test */
    public function cung_gio_bat_dau_la_chong()
    {
        $kq = PtttChongGio::quet([$this->d(2, '08:00', '08:10'), $this->d(1, '08:00', '09:00')], 5);

        $this->assertSame(['so' => 1, 'neu' => [2]], $kq[1]);
        $this->assertSame(['so' => 1, 'neu' => [1]], $kq[2]);
    }

    /** @test */
    public function chi_neu_toi_da_nhung_dem_du()
    {
        $ds = [];
        for ($i = 1; $i <= 8; $i++) {
            $ds[] = $this->d($i, '08:0' . $i, '10:00');
        }
        $kq = PtttChongGio::quet($ds, 5);

        $this->assertSame(7, $kq[8]['so']);
        $this->assertSame([1, 2, 3, 4, 5], $kq[8]['neu']);
        $this->assertSame([2, 3, 4, 5, 6], $kq[1]['neu']);
    }

    /** @test */
    public function khoang_nguoc_hoac_rong_bi_bo_qua()
    {
        $this->assertSame([], PtttChongGio::quet([
            $this->d(1, '08:00', '09:00'),
            $this->d(2, '08:30', '08:30'),
            $this->d(3, '08:40', '08:10'),
        ], 5));
    }
}

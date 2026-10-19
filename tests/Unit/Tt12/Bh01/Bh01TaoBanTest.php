<?php

namespace Tests\Unit\Tt12\Bh01;

use App\Models\BHYT\Tt12\Bh01Ban;
use App\Models\BHYT\Tt12\Bh01Dong;
use App\Services\Tt12\Bh01\Bh01GhiBan;
use App\Services\Tt12\Bh01\Bh01KhongTaoDuocException;
use App\Services\Tt12\Bh01\Bh01Ky;
use App\Services\Tt12\Bh01\Bh01TaoBan;
use Illuminate\Support\Facades\DB;
use Tests\Support\DungBangBh01Sqlite;
use Tests\TestCase;

class Bh01TaoBanTest extends TestCase
{
    use DungBangBh01Sqlite;

    public function setUp()
    {
        parent::setUp();
        $this->dungBangBh01();
    }

    private function dong(array $ghiDe = array())
    {
        DB::table('xml3176_xml1s')->insert(array_merge(array(
            'ma_lk' => 'LK', 'ho_ten' => 'NGUYEN VAN A', 'ngay_sinh' => '198001010000',
            'gioi_tinh' => '1', 'ma_the_bhyt' => 'DN4010000000001', 'ma_benh_chinh' => 'E11',
            'ngay_vao' => '202609010800', 'ngay_ra' => '202609010900',
            'ma_cskcb' => '01929', 'nam_qt' => '2026', 'thang_qt' => '09', 'ma_loai_kcb' => '01',
            't_tongchi_bv' => 100, 't_tongchi_bh' => 80, 't_bhtt' => 70,
            't_bncct' => 10, 't_bntt' => 20, 't_nguonkhac' => 0,
        ), $ghiDe));
    }

    private function ky()
    {
        return Bh01Ky::tu(2026, 9);
    }

    private function bayDongHopLe()
    {
        $this->dong(array('ma_lk' => 'C', 'ngay_ra' => '202609030900'));
        $this->dong(array('ma_lk' => 'A', 'ngay_ra' => '202609010900'));
        $this->dong(array('ma_lk' => 'B', 'ngay_ra' => '202609020900'));
    }

    /** @test */
    public function tao_ban_dau_tien_so_ban_1_va_chep_dong()
    {
        $this->bayDongHopLe();

        $ban = (new Bh01TaoBan())->tao('01929', $this->ky(), 'tester');

        $this->assertSame(1, $ban->so_ban);
        $this->assertSame(3, $ban->so_dong);
        $this->assertSame('BH01_01929_202609_01', $ban->ma_ban);
        $this->assertSame(Bh01Ban::KY_CHO, $ban->trang_thai_ky);
        $luu = Bh01Ban::find($ban->id);
        $this->assertSame(300.0, (float) $luu->t_tongchi_bv);
        $this->assertSame(210.0, (float) $luu->t_bhtt);
        $this->assertSame(0, $luu->so_canh_bao);
        $this->assertStringStartsWith('Id-', $ban->id_ds_chitiet);

        $dong = Bh01Dong::where('ban_id', $ban->id)->orderBy('stt')->get();
        $this->assertCount(3, $dong);
        $this->assertEquals(array(1, 2, 3), $dong->pluck('stt')->all());
        $this->assertSame(array('A', 'B', 'C'), $dong->pluck('ma_lk')->all());
        $this->assertSame('100', $dong[0]->t_tongchi_bv);
    }

    /** @test */
    public function ban_thu_hai_cung_ky_la_so_2()
    {
        $this->bayDongHopLe();
        $s = new Bh01TaoBan();
        $s->tao('01929', $this->ky(), 'tester');
        $ban2 = $s->tao('01929', $this->ky(), 'tester');

        $this->assertSame(2, $ban2->so_ban);
        $this->assertSame('BH01_01929_202609_02', $ban2->ma_ban);
    }

    /** @test */
    public function ky_khong_co_ho_so_thi_nem()
    {
        $this->expectException(Bh01KhongTaoDuocException::class);
        (new Bh01TaoBan())->tao('01929', $this->ky(), 'tester');
    }

    /** @test */
    public function co_dong_bi_chan_thi_nem_va_khong_ghi_gi()
    {
        $this->bayDongHopLe();
        $this->dong(array('ma_lk' => 'X', 'ho_ten' => ''));

        try {
            (new Bh01TaoBan())->tao('01929', $this->ky(), 'tester');
            $this->fail('Phai nem Bh01KhongTaoDuocException');
        } catch (Bh01KhongTaoDuocException $e) {
            $this->assertContains('1 hồ sơ bị chặn', $e->getMessage());
        }

        $this->assertSame(0, Bh01Ban::count());
        $this->assertSame(0, Bh01Dong::count());
    }

    /** @test */
    public function dong_canh_bao_van_tao_va_ghi_canh_bao()
    {
        $this->dong(array('ma_lk' => 'A', 'ma_the_bhyt' => 'DN401000000000123'));
        $this->dong(array('ma_lk' => 'B'));

        $ban = (new Bh01TaoBan())->tao('01929', $this->ky(), 'tester');

        $this->assertSame(1, Bh01Ban::find($ban->id)->so_canh_bao);
        $this->assertNotEmpty(Bh01Dong::where('ma_lk', 'A')->first()->canh_bao);
        $this->assertNull(Bh01Dong::where('ma_lk', 'B')->first()->canh_bao);
    }

    /** @test */
    public function du_lieu_nguon_doi_sau_khi_tao_khong_lam_doi_ban()
    {
        $this->dong(array('ma_lk' => 'A'));
        $ban = (new Bh01TaoBan())->tao('01929', $this->ky(), 'tester');

        DB::table('xml3176_xml1s')->where('ma_lk', 'A')->update(array('t_bhtt' => 999));

        $this->assertSame('70', Bh01Dong::where('ban_id', $ban->id)->first()->t_bhtt);
    }

    /** @test */
    public function trung_so_ban_thi_thu_lai_lay_so_ke_tiep()
    {
        $this->dong(array('ma_lk' => 'A'));

        $s = new class extends Bh01GhiBan {
            public $lan = 0;

            protected function soBanKeTiep($maCskcb, array $ky)
            {
                $this->lan++;
                if ($this->lan === 1) {
                    // Nguoi khac vua chen so 1 sau khi ta doc max.
                    Bh01Ban::create(array(
                        'ma_ban' => 'BH01_01929_202609_01', 'ma_cskcb' => $maCskcb,
                        'nam_qt' => $ky['nam'], 'thang_qt' => $ky['thang'], 'so_ban' => 1,
                    ));

                    return 1;
                }

                return parent::soBanKeTiep($maCskcb, $ky);
            }
        };

        $ban = (new Bh01TaoBan($s))->tao('01929', $this->ky(), 'tester');

        $this->assertSame(2, $ban->so_ban);
        $this->assertSame(2, $s->lan);
    }

    /** @test */
    public function dong_bi_chan_xuat_hien_giua_hai_luot_thi_rollback()
    {
        // XML1 bi sua giua luot kiem va luot ghi: luot ghi phai tu kiem lai, khong ghi dong chan.
        $this->dong(array('ma_lk' => 'A'));

        $s = new class extends Bh01GhiBan {
            protected function soBanKeTiep($maCskcb, array $ky)
            {
                DB::table('xml3176_xml1s')->insert(array(
                    'ma_lk' => 'Z', 'ho_ten' => '', 'ma_the_bhyt' => 'DN4010000000001',
                    'ngay_vao' => '202609010800', 'ngay_ra' => '202609300900',
                    'ma_cskcb' => $maCskcb, 'nam_qt' => $ky['nam'], 'thang_qt' => $ky['thang'],
                    'ma_loai_kcb' => '01', 't_tongchi_bv' => 1, 't_tongchi_bh' => 1, 't_bhtt' => 1,
                    't_bncct' => 0, 't_bntt' => 0, 't_nguonkhac' => 0,
                ));

                return parent::soBanKeTiep($maCskcb, $ky);
            }
        };

        try {
            (new Bh01TaoBan($s))->tao('01929', $this->ky(), 'tester');
            $this->fail('Phai nem Bh01KhongTaoDuocException');
        } catch (Bh01KhongTaoDuocException $e) {
            $this->assertContains('Z', $e->getMessage());
        }

        $this->assertSame(0, Bh01Ban::count());
        $this->assertSame(0, Bh01Dong::count());
    }

    /** @test */
    public function xem_truoc_dem_chan_canh_bao_va_mau_loi()
    {
        $this->dong(array('ma_lk' => 'A'));
        $this->dong(array('ma_lk' => 'W', 'ma_the_bhyt' => 'DN401000000000123'));
        $this->dong(array('ma_lk' => 'X', 'ho_ten' => ''));

        $r = (new Bh01TaoBan())->xemTruoc('01929', $this->ky());

        $this->assertSame(3, $r['so_ho_so']);
        $this->assertSame(1, $r['so_dong_chan']);
        $this->assertSame(1, $r['so_dong_canh_bao']);
        $this->assertCount(1, $r['loi_chan_mau']);
        $this->assertStringStartsWith('MA_LK X: ', $r['loi_chan_mau'][0]);
        $this->assertSame(0, Bh01Ban::count());
    }

    /** @test Xem chu thich Bh01NguonTest::doc_theo_lo_khong_dung_offset. */
    public function tao_ban_va_xem_truoc_khong_dung_offset()
    {
        $this->bayDongHopLe();

        DB::enableQueryLog();
        (new Bh01TaoBan())->xemTruoc('01929', $this->ky());
        (new Bh01TaoBan())->tao('01929', $this->ky(), 'tester');

        foreach (DB::getQueryLog() as $q) {
            $this->assertNotContains('offset', strtolower($q['query']), $q['query']);
        }
    }
}

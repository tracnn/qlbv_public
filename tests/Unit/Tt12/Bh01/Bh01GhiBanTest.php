<?php

namespace Tests\Unit\Tt12\Bh01;

use App\Models\BHYT\Tt12\Bh01Ban;
use App\Models\BHYT\Tt12\Bh01Dong;
use App\Services\Tt12\Bh01\Bh01ChuanHoa;
use App\Services\Tt12\Bh01\Bh01GhiBan;
use App\Services\Tt12\Bh01\Bh01KhongTaoDuocException;
use App\Services\Tt12\Bh01\Bh01Ky;
use Tests\Support\DungBangBh01Sqlite;
use Tests\TestCase;

class Bh01GhiBanTest extends TestCase
{
    use DungBangBh01Sqlite;

    public function setUp()
    {
        parent::setUp();
        $this->dungBangBh01();
    }

    /** Dong da chuan hoa hop le, tien theo ghiDe. */
    private function duLieu(array $ghiDe = array())
    {
        return array_merge(Bh01ChuanHoa::dong((object) array(
            'ho_ten' => 'NGUYEN VAN A', 'ngay_sinh' => '198001010000', 'gioi_tinh' => '1',
            'ma_the_bhyt' => 'DN4010000000001', 'ma_benh_chinh' => 'E11',
            'ngay_vao' => '202609010800', 'ngay_ra' => '202609010900',
            'ma_cskcb' => '01929', 'nam_qt' => '2026', 'thang_qt' => '09', 'ma_loai_kcb' => '01',
            't_tongchi_bv' => 100, 't_tongchi_bh' => 80, 't_bhtt' => 70,
            't_bncct' => 10, 't_bntt' => 20, 't_nguonkhac' => 0,
        )), $ghiDe);
    }

    private function dongNguon(array $duLieu, $viTri, $maLk = null)
    {
        return array('du_lieu' => $duLieu, 'ma_lk' => $maLk, 'vi_tri' => $viTri);
    }

    /** Nguon gia lap: moi phan tu la mot lo. Re-iterable vi nguon co the bi goi lai khi thu lai. */
    private function nguon(array $cacLo)
    {
        return function ($nhanLo) use ($cacLo) {
            foreach ($cacLo as $lo) {
                $nhanLo($lo);
            }
        };
    }

    private function ky()
    {
        return Bh01Ky::tu(2026, 9);
    }

    /** @test */
    public function ghi_ban_tu_lo_dong_va_tinh_tong()
    {
        $nguon = $this->nguon(array(
            array($this->dongNguon($this->duLieu(), 'Dòng 2'), $this->dongNguon($this->duLieu(), 'Dòng 3')),
            array($this->dongNguon($this->duLieu(array('t_bhtt' => '30')), 'Dòng 4')),
        ));

        $ban = (new Bh01GhiBan())->ghi('01929', $this->ky(), 'tester', $nguon);

        $this->assertSame(3, $ban->so_dong);
        $this->assertSame(1, $ban->so_ban);
        $this->assertSame('BH01_01929_202609_01', $ban->ma_ban);
        $this->assertSame(Bh01Ban::KY_CHO, $ban->trang_thai_ky);
        $this->assertSame('xml3176', Bh01Ban::find($ban->id)->nguon);
        $this->assertSame(170.0, (float) Bh01Ban::find($ban->id)->t_bhtt);
        $this->assertEquals(array(1, 2, 3), Bh01Dong::where('ban_id', $ban->id)->orderBy('stt')->pluck('stt')->all());
    }

    /** @test */
    public function thuoc_tinh_ban_duoc_ghi()
    {
        $nguon = $this->nguon(array(array($this->dongNguon($this->duLieu(), 'Dòng 2'))));

        $ban = (new Bh01GhiBan())->ghi(
            '01929', $this->ky(), 'tester', $nguon,
            array('nguon' => 'excel', 'ten_tep_nguon' => 'a.xlsx')
        );

        $luu = Bh01Ban::find($ban->id);
        $this->assertSame('excel', $luu->nguon);
        $this->assertSame('a.xlsx', $luu->ten_tep_nguon);
        $this->assertNull(Bh01Dong::where('ban_id', $ban->id)->first()->ma_lk);
    }

    /** @test */
    public function thuoc_tinh_ban_khong_ghi_de_truong_he_thong()
    {
        $nguon = $this->nguon(array(array($this->dongNguon($this->duLieu(), 'Dòng 2'))));

        $ban = (new Bh01GhiBan())->ghi(
            '01929', $this->ky(), 'tester', $nguon,
            array('so_ban' => 99, 'trang_thai_ky' => 'da_ky', 'nguon' => 'excel')
        );

        $luu = Bh01Ban::find($ban->id);
        $this->assertSame(1, $luu->so_ban);
        $this->assertSame(Bh01Ban::KY_CHO, $luu->trang_thai_ky);
        $this->assertSame('excel', $luu->nguon);
    }

    /** @test */
    public function dong_chan_thi_rollback_va_bao_vi_tri()
    {
        $nguon = $this->nguon(array(array(
            $this->dongNguon($this->duLieu(), 'Dòng 2'),
            $this->dongNguon($this->duLieu(array('ho_ten' => '')), 'Dòng 3'),
        )));

        try {
            (new Bh01GhiBan())->ghi('01929', $this->ky(), 'tester', $nguon);
            $this->fail('Phai nem Bh01KhongTaoDuocException');
        } catch (Bh01KhongTaoDuocException $e) {
            $this->assertContains('Dòng 3', $e->getMessage());
        }

        $this->assertSame(0, Bh01Ban::count());
        $this->assertSame(0, Bh01Dong::count());
    }

    /** @test */
    public function nguon_khong_co_dong_nao_thi_khong_tao_ban()
    {
        try {
            (new Bh01GhiBan())->ghi('01929', $this->ky(), 'tester', $this->nguon(array()));
            $this->fail('Phai nem Bh01KhongTaoDuocException');
        } catch (Bh01KhongTaoDuocException $e) {
            $this->assertNotSame('', $e->getMessage());
        }

        $this->assertSame(0, Bh01Ban::count());
    }
}

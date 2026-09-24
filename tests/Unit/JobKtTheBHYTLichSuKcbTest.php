<?php

namespace Tests\Unit;

use App\Jobs\jobKtTheBHYT;
use App\Services\BHYT\NhanMaThe;
use Tests\TestCase;

/**
 * Ma kiem tra do job tu tinh sau khi tra cong: 09 = noi DKBD tren HIS khac tren the.
 * Tren CSDL that co 26 dong 09; 2 dong la the vua DOI noi DKBD va HIS dang dung dung noi
 * MOI (vd 000007294309: HIS 01016, cong tra noi cu 01829, noi moi 01016) - bao nham.
 */
class JobKtTheBHYTLichSuKcbTest extends TestCase
{
    private function kiem($dkbdHis, $dkbdCong, $dkbdMoi = null, $gioiTinhHis = 1, $gioiTinhCong = 'Nam')
    {
        $job = new jobKtTheBHYT(['maDkbd' => $dkbdHis, 'gioiTinh' => $gioiTinhHis]);
        $m = new \ReflectionMethod($job, 'lichSuKCB');
        $m->setAccessible(true);

        return $m->invoke($job, ['maDkbd' => $dkbdHis, 'gioiTinh' => $gioiTinhHis],
            $dkbdCong, $gioiTinhCong, '01/01/2026', '31/12/2026', $dkbdMoi);
    }

    /** @test */
    public function khop_noi_dkbd_tren_the_la_00()
    {
        $this->assertSame('00', $this->kiem('42254', '42254', '42254'));
    }

    /** @test */
    public function khop_noi_dkbd_moi_khi_the_vua_doi_noi_la_00()
    {
        $this->assertSame('00', $this->kiem('01016', '01829', '01016'));
    }

    /** @test */
    public function khac_ca_noi_cu_lan_noi_moi_la_09()
    {
        $this->assertSame('09', $this->kiem('42258', '42254', '42254'));
        $this->assertSame('09', $this->kiem('35168', '35064', null));
        $this->assertSame('09', $this->kiem('35168', '35064', ''));
    }

    /** @test */
    public function khop_dkbd_nhung_sai_gioi_tinh_van_la_08()
    {
        $this->assertSame('08', $this->kiem('01016', '01829', '01016', 1, 'Nữ'));
    }

    /** @test */
    public function nhan_ma_09_va_11_sat_ngu_canh()
    {
        $this->assertSame('Sai nơi đăng ký KCB ban đầu', NhanMaThe::kiemTra('09'));
        $this->assertSame('Không lấy được thông tin thẻ từ cổng BHXH', NhanMaThe::kiemTra('11'));
    }
}

<?php

namespace Tests\Unit\Tt12;

use Tests\TestCase;
use Tests\Support\DungBangTt12Sqlite;
use App\Models\BHYT\Tt12\Tt12HoSo;
use App\Services\Tt12\Tt12DanhSach;

class Tt12DanhSachTest extends TestCase
{
    use DungBangTt12Sqlite;

    protected function setUp()
    {
        parent::setUp();
        $this->dungBangTt12();
    }

    private function tao($ma, array $ghiDe = array())
    {
        return Tt12HoSo::create(array_merge(array(
            'ma_ho_so' => $ma, 'mau' => 'MAU_01', 'loai_hs' => '70',
            'ma_cskcb' => '01929', 'ten_tep' => $ma . '.xlsx', 'so_dong' => 1,
            'id_danh_sach' => 'Id-' . $ma,
        ), $ghiDe));
    }

    private function maCua($loc)
    {
        return Tt12DanhSach::truyVan($loc)->pluck('ma_ho_so')->all();
    }

    /** @test */
    public function khong_loc_thi_tra_ve_tat_ca()
    {
        $this->tao('A');
        $this->tao('B');

        $this->assertCount(2, $this->maCua(array()));
    }

    /** @test */
    public function loc_theo_mau()
    {
        $this->tao('A');
        $this->tao('B', array('mau' => 'MAU_03', 'loai_hs' => '10'));

        $this->assertSame(array('B'), $this->maCua(array('mau' => 'MAU_03')));
    }

    /** @test */
    public function loc_trang_thai_chua_kiem()
    {
        $this->tao('A');
        $this->tao('B', array('checked_at' => '2026-08-25 10:00:00'));

        $this->assertSame(array('A'), $this->maCua(array('trang_thai' => 'chua_kiem')));
    }

    /** @test */
    public function loc_trang_thai_con_loi()
    {
        $this->tao('A', array('checked_at' => '2026-08-25 10:00:00', 'so_loi' => 3));
        $this->tao('B', array('checked_at' => '2026-08-25 10:00:00', 'so_loi' => 0));

        $this->assertSame(array('A'), $this->maCua(array('trang_thai' => 'con_loi')));
    }

    /** @test */
    public function loc_trang_thai_san_sang_la_da_kiem_sach_va_chua_ky()
    {
        $this->tao('A', array('checked_at' => '2026-08-25 10:00:00', 'so_loi' => 0));
        $this->tao('B', array('checked_at' => '2026-08-25 10:00:00', 'so_loi' => 0, 'is_signed' => true));
        $this->tao('C', array('checked_at' => null));

        $this->assertSame(array('A'), $this->maCua(array('trang_thai' => 'san_sang')));
    }

    /** @test */
    public function loc_trang_thai_da_gui_chi_lay_ma_200()
    {
        $this->tao('A', array('is_signed' => true, 'ma_ket_qua' => '200'));
        $this->tao('B', array('is_signed' => true, 'ma_ket_qua' => '500'));

        $this->assertSame(array('A'), $this->maCua(array('trang_thai' => 'da_gui')));
        $this->assertSame(array('B'), $this->maCua(array('trang_thai' => 'loi_gui')));
    }

    /** @test */
    public function tim_theo_ma_ho_so_ten_tep_va_ma_giao_dich()
    {
        $this->tao('TT12_MAU_01_01929_20260825_001', array('ten_tep' => 'khoa-phong.xlsx', 'ma_gd' => 'GD_ABC'));
        $this->tao('TT12_MAU_01_01929_20260825_002', array('ten_tep' => 'khac.xlsx'));

        $this->assertCount(1, $this->maCua(array('tim' => 'khoa-phong')));
        $this->assertCount(1, $this->maCua(array('tim' => 'GD_ABC')));
        $this->assertCount(1, $this->maCua(array('tim' => '_001')));
    }

    /** @test */
    public function loc_theo_khoang_ngay_nap()
    {
        $this->tao('A', array('imported_at' => '2026-08-20 08:00:00'));
        $this->tao('B', array('imported_at' => '2026-08-25 08:00:00'));

        $this->assertSame(
            array('B'),
            $this->maCua(array('tu_ngay' => '2026-08-22', 'den_ngay' => '2026-08-26'))
        );
    }
}

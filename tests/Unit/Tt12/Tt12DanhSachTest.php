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
    public function loc_theo_nguoi_nap()
    {
        // CTDT va XML3176 deu co bo loc nay; TT12 thieu la lech. Cot imported_by da co
        // san va da danh index tu Task 2.
        $this->tao('A', array('imported_by' => 'nvyt01'));
        $this->tao('B', array('imported_by' => 'nvyt02'));

        $this->assertSame(array('A'), $this->maCua(array('imported_by' => 'nvyt01')));
    }

    /** @test */
    public function khong_chon_nguoi_nap_thi_tra_ve_tat_ca()
    {
        // Chuoi rong phai duoc coi la "khong loc", khong phai "loc theo nguoi ten rong" -
        // o chon co muc "Tat ca" mang value rong.
        $this->tao('A', array('imported_by' => 'nvyt01'));
        $this->tao('B', array('imported_by' => null));

        $this->assertCount(2, $this->maCua(array('imported_by' => '')));
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

    /** @test */
    public function khoang_ngay_da_mang_gio_khong_bi_noi_them_hau_to()
    {
        // partials.date_range gui dang 'YYYY-MM-DD HH:mm:ss'. Neu truyVan noi them
        // ' 00:00:00'/' 23:59:59' vo dieu kien vao chuoi da co gio thi tham so gui xuong
        // CSDL thanh '...00:00:00 00:00:00' - MySQL doc khong ra va tra ve RONG. Kiem
        // THANG tham so gui xuong CSDL, khong kiem qua ket qua truy van: SQLite so sanh
        // chuoi kieu lexical nen mot moc hong van co the "tinh co" cho ra dung dong,
        // che mat loi ma MySQL that se lo ra.
        $bindings = Tt12DanhSach::truyVan(array(
            'tu_ngay'  => '2026-08-22 10:30:00',
            'den_ngay' => '2026-08-26 10:30:00',
        ))->getQuery()->getBindings();

        foreach ($bindings as $moc) {
            $this->assertRegExp(
                '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/',
                (string) $moc,
                'Moc thoi gian gui xuong CSDL phai la datetime hop le, khong duoc noi doi hau to'
            );
        }
    }

    /** @test */
    public function mocDau_khong_gio_thi_them_00_00_00()
    {
        $this->assertSame('2026-08-22 00:00:00', Tt12DanhSach::mocDau('2026-08-22'));
    }

    /** @test */
    public function mocDau_da_co_gio_thi_giu_nguyen()
    {
        $this->assertSame('2026-08-22 10:30:00', Tt12DanhSach::mocDau('2026-08-22 10:30:00'));
    }

    /** @test */
    public function mocCuoi_khong_gio_thi_them_23_59_59()
    {
        $this->assertSame('2026-08-22 23:59:59', Tt12DanhSach::mocCuoi('2026-08-22'));
    }

    /** @test */
    public function mocCuoi_da_co_gio_thi_giu_nguyen()
    {
        $this->assertSame('2026-08-22 10:30:00', Tt12DanhSach::mocCuoi('2026-08-22 10:30:00'));
    }

    /** @test */
    public function khoangMacDinh_bu_ca_hai_moc_khi_thieu()
    {
        // xuatNhatKy() co the bi goi thang khong qua man hinh (link dan tay, bookmark,
        // bam nut truoc khi DataTable kip gan tt12LocDaTai - bien do khoi tao null). Thieu
        // buoc bu nay thi tu_ngay/den_ngay rong lot xuong toi Tt12DanhSach::mocDau/mocCuoi,
        // Carbon::parse(' 00:00:00') tu suy ra HOM NAY chu khong nem loi.
        $daBu = Tt12DanhSach::khoangMacDinh(array('tu_ngay' => null, 'den_ngay' => ''));

        $this->assertNotEmpty($daBu['tu_ngay']);
        $this->assertNotEmpty($daBu['den_ngay']);
        $this->assertSame(
            30,
            \Carbon\Carbon::parse($daBu['tu_ngay'])->diffInDays(\Carbon\Carbon::parse($daBu['den_ngay'])),
            'Mac dinh phai lui dung 30 ngay'
        );
    }

    /** @test */
    public function khoangMacDinh_khong_de_len_lua_chon_cua_nguoi_dung()
    {
        $daBu = Tt12DanhSach::khoangMacDinh(array(
            'tu_ngay' => '2026-08-01', 'den_ngay' => '2026-08-05',
        ));

        $this->assertSame('2026-08-01', $daBu['tu_ngay']);
        $this->assertSame('2026-08-05', $daBu['den_ngay']);
    }
}

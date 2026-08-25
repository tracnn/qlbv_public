<?php

namespace Tests\Unit\Tt12;

use Tests\TestCase;
use Tests\Support\DungBangTt12Sqlite;
use Illuminate\Support\Facades\Storage;
use App\Models\BHYT\Tt12\Tt12HoSo;
use App\Models\BHYT\Tt12\Tt12Dong;
use App\Models\BHYT\Tt12\Tt12DongThuocPx;
use App\Models\BHYT\Tt12\Tt12Loi;
use App\Models\BHYT\Tt12\Tt12LichSuGui;
use App\Services\Tt12\Tt12XoaHoSo;

/**
 * Lop xoa DUNG CHUNG cho ca hai duong xoa ho so (man hinh va don dep khi nap hong).
 *
 * VI SAO CO TEP TEST RIENG: hai ban cai dat song song chinh la cach loi "bo sot bang con"
 * sinh ra lan thu hai - sua o Tt12Importer::doSach() roi van con nguyen o
 * BHYTTt12Controller::delete(). Kiem BAN THAN lop dung chung o day, roi kiem rieng viec
 * hai duong goi deu di qua no.
 */
class Tt12XoaHoSoTest extends TestCase
{
    use DungBangTt12Sqlite;

    protected function setUp()
    {
        parent::setUp();
        $this->dungBangTt12();

        Storage::fake('exportTt12');
    }

    private function hoSoDayDu($duongDanDaKy = null)
    {
        $hoSo = Tt12HoSo::create(array(
            'ma_ho_so' => 'TT12_MAU_05_01929_20260825_001',
            'mau' => 'MAU_05', 'loai_hs' => '12', 'ma_cskcb' => '01929',
            'ten_tep' => 'x.xlsx', 'so_dong' => 2, 'id_danh_sach' => 'Id-abc',
            'duong_dan_da_ky' => $duongDanDaKy,
        ));

        foreach (array(1, 2) as $stt) {
            $dong = Tt12Dong::create(array(
                'ho_so_id' => $hoSo->id, 'stt' => $stt,
                'du_lieu' => array('MA_DICH_VU' => 'DV' . $stt),
            ));

            Tt12DongThuocPx::create(array(
                'dong_id' => $dong->id, 'stt' => 1, 'ma_thuoc' => 'TPX' . $stt,
            ));
        }

        Tt12Loi::create(array(
            'ho_so_id' => $hoSo->id, 'ma_loi' => 'THIEU_BAT_BUOC',
            'muc_do' => 'loi', 'mo_ta' => 'Thiếu giá trị',
        ));

        Tt12LichSuGui::create(array(
            'ho_so_id' => $hoSo->id, 'ma_ho_so' => $hoSo->ma_ho_so,
            'gui_luc' => '2026-08-25 10:00:00', 'ma_ket_qua' => '205',
            'thong_diep' => 'Lỗi nội dung file XML',
        ));

        return $hoSo;
    }

    /** @test */
    public function xoa_het_ca_nam_bang_khong_de_lai_ban_ghi_mo_coi()
    {
        $hoSo = $this->hoSoDayDu();

        (new Tt12XoaHoSo())->xoa($hoSo);

        $this->assertSame(0, Tt12HoSo::count());
        $this->assertSame(0, Tt12Dong::count());
        $this->assertSame(0, Tt12DongThuocPx::count(),
            'Dong con thuoc phong xa khong duoc bo lai mo coi');
        $this->assertSame(0, Tt12Loi::count());
        $this->assertSame(0, Tt12LichSuGui::count());
    }

    /** @test */
    public function xoa_ca_tep_xml_da_ky_tren_dia()
    {
        // Tt12MaHoSo::keTiep() CO Y tai dung khoang trong so thu tu trong ngay, nen mot ho
        // so moi cung ngay cung co so co the mang dung ma vua bi xoa - va SignTt12Job dat
        // ten tep theo ma ho so. Bo lai tep cu la de ban ky cu duoc gui di duoi ten ho so
        // moi.
        $duongDan = 'MAU_05/202608/TT12_MAU_05_01929_20260825_001.xml';
        Storage::disk('exportTt12')->put($duongDan, '<HSDANHMUC/>');

        $hoSo = $this->hoSoDayDu($duongDan);

        (new Tt12XoaHoSo())->xoa($hoSo);

        $this->assertFalse(Storage::disk('exportTt12')->exists($duongDan));
    }

    /** @test */
    public function ho_so_chua_ky_thi_khong_nem_du_khong_co_tep()
    {
        $hoSo = $this->hoSoDayDu('MAU_05/202608/khong-ton-tai.xml');

        (new Tt12XoaHoSo())->xoa($hoSo);

        $this->assertSame(0, Tt12HoSo::count());
    }

    /** @test */
    public function goi_voi_null_thi_khong_lam_gi()
    {
        $this->hoSoDayDu();

        (new Tt12XoaHoSo())->xoa(null);

        $this->assertSame(1, Tt12HoSo::count());
    }
}

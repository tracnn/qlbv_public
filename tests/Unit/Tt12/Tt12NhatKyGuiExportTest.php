<?php

namespace Tests\Unit\Tt12;

use Tests\TestCase;
use Tests\Support\DungBangTt12Sqlite;
use App\Exports\Tt12NhatKyGuiExport;
use App\Models\BHYT\Tt12\Tt12HoSo;
use App\Models\BHYT\Tt12\Tt12LichSuGui;

/**
 * Mot dong = mot lan goi cong BHXH cho mot ho so TT12. Bang chi tang, khong bao gio giam,
 * nen phai co tran khoang ngay - khuon dung CtdtNhatKyGuiExport, doc ky khoi chu thich
 * dau tep do truoc khi sua o day.
 */
class Tt12NhatKyGuiExportTest extends TestCase
{
    use DungBangTt12Sqlite;

    protected function setUp()
    {
        parent::setUp();
        $this->dungBangTt12();
    }

    private function hoSo($ma)
    {
        return Tt12HoSo::create(array(
            'ma_ho_so' => $ma, 'mau' => 'MAU_01', 'loai_hs' => '70',
            'ma_cskcb' => '01929', 'ten_tep' => $ma . '.xlsx', 'so_dong' => 1,
            'id_danh_sach' => 'Id-' . $ma,
        ));
    }

    private function dongLichSu($maHoSo, $guiLuc, array $ghiDe = array())
    {
        $hoSo = $this->hoSo($maHoSo);

        return Tt12LichSuGui::create(array_merge(array(
            'ho_so_id' => $hoSo->id, 'ma_ho_so' => $maHoSo, 'gui_luc' => $guiLuc,
            'gui_boi' => 'tracnn', 'ma_ket_qua' => '200', 'ma_gd' => 'GD-' . $maHoSo,
            'thoi_gian_tiep_nhan' => '20260810080000', 'thong_diep' => 'OK',
        ), $ghiDe));
    }

    private function maTraVe($query)
    {
        return $query->get()->pluck('ma_ho_so')->all();
    }

    // -- tran khoang ngay --------------------------------------------------------------

    /** @test */
    public function khoang_91_ngay_bi_nem()
    {
        $this->expectException(\InvalidArgumentException::class);

        new Tt12NhatKyGuiExport('2026-01-01', '2026-04-02'); // 91 ngay
    }

    /** @test */
    public function khoang_90_ngay_qua_duoc()
    {
        $xuat = new Tt12NhatKyGuiExport('2026-01-01', '2026-04-01'); // dung 90 ngay

        $this->assertInstanceOf(Tt12NhatKyGuiExport::class, $xuat);
    }

    /** @test */
    public function thong_diep_loi_neu_ro_khoang_toi_da()
    {
        try {
            new Tt12NhatKyGuiExport('2026-01-01', '2026-12-31');
            $this->fail('Phai nem InvalidArgumentException');
        } catch (\InvalidArgumentException $e) {
            $this->assertContains('90', $e->getMessage());
        }
    }

    // -- truy van loc dung khoang ------------------------------------------------------

    /** @test */
    public function truy_van_chi_lay_dong_trong_khoang_ngay()
    {
        $this->dongLichSu('TT_DAU', '2026-08-01 00:00:00');
        $this->dongLichSu('TT_GIUA', '2026-08-15 12:00:00');
        $this->dongLichSu('TT_TRUOC', '2026-07-31 23:59:59');
        $this->dongLichSu('TT_SAU', '2026-09-01 00:00:01');

        $xuat = new Tt12NhatKyGuiExport('2026-08-01', '2026-08-31');

        $this->assertSame(
            array('TT_DAU', 'TT_GIUA'),
            $this->maTraVe($xuat->query())
        );
    }

    /** @test */
    public function truy_van_khong_gui_moc_thoi_gian_hong_xuong_csdl()
    {
        // Kiem thang tham so nem xuong CSDL: SQLite so sanh chuoi nen co the "tinh co" tra
        // ve dong dung du moc hong; MySQL doc khong ra va tra ve RONG. Phai kiem chinh
        // dinh dang moc, khong chi kiem so dong tra ve.
        $xuat = new Tt12NhatKyGuiExport('2026-08-01 00:00:00', '2026-08-31 23:59:59');

        foreach ($xuat->query()->getQuery()->getBindings() as $moc) {
            $this->assertRegExp(
                '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/',
                (string) $moc,
                'Moc thoi gian gui xuong CSDL phai la datetime hop le, khong duoc noi doi hau to'
            );
        }
    }

    /** @test */
    public function truy_van_van_nhan_khoang_ngay_dang_chi_co_ngay()
    {
        $this->dongLichSu('TT_DAU', '2026-08-01 00:00:00');
        $this->dongLichSu('TT_CUOI', '2026-08-31 23:59:59');
        $this->dongLichSu('TT_SAU', '2026-09-01 00:00:00');

        $xuat = new Tt12NhatKyGuiExport('2026-08-01', '2026-08-31');

        $this->assertSame(
            array('TT_DAU', 'TT_CUOI'),
            $this->maTraVe($xuat->query())
        );
    }

    /** @test */
    public function truy_van_co_khoa_pha_hoa_khi_sap_xep()
    {
        // Sheet::fromQuery() duyet bang chunk() tuc LIMIT/OFFSET. Sap xep chi theo gui_luc
        // (khong duy nhat - nhieu lan gui cung giay) thi thu tu cac dong trung giua hai
        // trang khong duoc bao dam.
        $xuat = new Tt12NhatKyGuiExport('2026-08-01', '2026-08-31');

        $cot = array_column($xuat->query()->getQuery()->orders, 'column');

        $this->assertContains('id', $cot, 'Phai sap xep kem cot id lam khoa pha hoa');
    }

    // -- anh xa cot ----------------------------------------------------------------

    /** @test */
    public function headings_co_du_cac_cot_yeu_cau()
    {
        $xuat = new Tt12NhatKyGuiExport('2026-08-01', '2026-08-31');
        $tieuDe = $xuat->headings();

        foreach (array('Thời điểm gửi', 'Người gửi', 'Mã kết quả', 'Mã giao dịch',
                       'Thời gian tiếp nhận', 'Thông điệp') as $can) {
            $this->assertContains($can, $tieuDe, 'Thieu cot: ' . $can);
        }
    }

    /** @test */
    public function map_tra_dung_so_o_khop_headings()
    {
        $dong = $this->dongLichSu('TT001', '2026-08-10 08:00:00');

        $xuat = new Tt12NhatKyGuiExport('2026-08-01', '2026-08-31');

        $this->assertCount(count($xuat->headings()), $xuat->map($dong));
    }

    /** @test */
    public function map_dua_dung_du_lieu_vao_dung_cot()
    {
        $dong = $this->dongLichSu('TT002', '2026-08-10 08:00:00', array(
            'gui_boi' => 'nvyt01', 'ma_ket_qua' => '205', 'ma_gd' => 'GD-XYZ',
            'thoi_gian_tiep_nhan' => '20260810081500', 'thong_diep' => 'Cổng từ chối',
            'loi' => 'Sai dinh dang so 5',
        ));

        $xuat = new Tt12NhatKyGuiExport('2026-08-01', '2026-08-31');
        $hang = $xuat->map($dong);

        $this->assertContains('nvyt01', $hang);
        $this->assertContains('205', $hang);
        $this->assertContains('GD-XYZ', $hang);
        $this->assertContains('20260810081500', $hang);
        $this->assertContains('Cổng từ chối', $hang);
        $this->assertContains('Sai dinh dang so 5', $hang);
    }
}

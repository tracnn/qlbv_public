<?php

namespace Tests\Unit\Xml3176;

use App\Exports\HeinCardErrorExport;
use Tests\TestCase;

/**
 * HeinCardErrorExport dung chung cho man QD130 (Qd130ErrorMultiSheetExport) va XML3176.
 * Cot Ma Khoa chi bat cho XML3176; goi mac dinh phai giu nguyen y het truoc khi sua.
 */
class HeinCardErrorExportMaKhoaTest extends TestCase
{
    private function mo($coMaKhoa)
    {
        return new HeinCardErrorExport('2026-09-01 00:00:00', '2026-09-30 23:59:59', null, 'xml3176', $coMaKhoa);
    }

    /** @test */
    public function goi_mac_dinh_giu_nguyen_tieu_de_va_truy_van()
    {
        $cu = new HeinCardErrorExport('2026-09-01 00:00:00', '2026-09-30 23:59:59');

        $this->assertSame(['STT', 'Mã điều trị', 'Mã kiểm tra', 'Mã kết quả', 'Ghi chú', 'Mã thẻ'], $cu->headings());

        $sql = str_replace(['`', '"'], '', $cu->query()->toSql());
        $this->assertNotContains('xml3176_xml1s', $sql);
        $this->assertNotContains('ma_khoa_xuat', $sql);
        $this->assertSame('Lỗi thẻ BHYT', $cu->title());
    }

    /** @test */
    public function bat_ma_khoa_thi_them_cot_sau_ma_dieu_tri()
    {
        $h = $this->mo(true)->headings();

        $this->assertCount(7, $h);
        $this->assertSame('Mã điều trị', $h[1]);
        $this->assertSame('Mã Khoa', $h[2]);
        $this->assertSame('Mã kiểm tra', $h[3]);
    }

    /** @test */
    public function bat_ma_khoa_thi_left_join_xml1_lay_khoa_ho_so()
    {
        $q = $this->mo(true)->query();
        $sql = str_replace(['`', '"'], '', $q->toSql());

        $this->assertContains('left join xml3176_xml1s', $sql);
        $this->assertContains('xml3176_xml1s.ma_khoa as ma_khoa_xuat', $sql);

        // Cot cua check_hein_card phai ghi ro bang de khong nhap nhang voi xml3176_xml1s.
        $this->assertContains('check_hein_cards.updated_at between', $sql);
    }

    /** @test */
    public function map_theo_dung_so_cot()
    {
        $dong = (object) [
            'ma_lk' => 'LK1', 'ma_khoa_xuat' => 'K01', 'ma_kiemtra' => '00',
            'ma_ketqua' => '000', 'ghi_chu' => 'g', 'ma_the' => 'T1',
        ];

        $coKhoa = $this->mo(true)->map($dong);
        $this->assertCount(7, $coKhoa);
        $this->assertSame('K01', $coKhoa[2]);

        $khong = $this->mo(false)->map($dong);
        $this->assertCount(6, $khong);
        $this->assertSame('LK1', $khong[1]);
    }
}

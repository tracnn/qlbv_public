<?php

namespace Tests\Unit\Xml3176;

use App\Exports\DmKhoaGiuongSheetExport;
use App\Exports\DmNvytSheetExport;
use App\Exports\HeinCardErrorExport;
use App\Exports\Xml3176ErrorMultiSheetExport;
use App\Exports\Xml3176ErrorSheetExport;
use App\Services\BHYT\Xml3176LocDanhSach;
use Tests\TestCase;

class Xml3176ErrorMultiSheetExportTest extends TestCase
{
    private function sheets(array $ghiDe = [])
    {
        $loc = array_merge(
            array_fill_keys(Xml3176LocDanhSach::KHOA, null),
            ['date_from' => '2026-09-01 00:00:00', 'date_to' => '2026-09-30 23:59:59', 'date_type' => 'date_in'],
            $ghiDe
        );

        return (new Xml3176ErrorMultiSheetExport($loc, ['01929' => 'A']))->sheets();
    }

    /** @test */
    public function du_19_sheet_dung_thu_tu_va_ten()
    {
        $ten = array_map(function ($s) { return $s->title(); }, $this->sheets());

        $this->assertSame([
            'XML1', 'XML2', 'XML3', 'XML4', 'XML5', 'XML6', 'XML7', 'XML8',
            'XML9', 'XML10', 'XML11', 'XML12', 'XML13', 'XML14', 'XML15',
            'XMLComplete', 'Lỗi thẻ BHYT', 'DM khoa-giường', 'DM NVYT',
        ], $ten);
    }

    /** @test */
    public function dung_lop_dung_cho_tung_vi_tri()
    {
        $s = $this->sheets();

        for ($i = 0; $i < 16; $i++) {
            $this->assertInstanceOf(Xml3176ErrorSheetExport::class, $s[$i], "sheet $i");
        }

        $this->assertInstanceOf(HeinCardErrorExport::class, $s[16]);
        $this->assertInstanceOf(DmKhoaGiuongSheetExport::class, $s[17]);
        $this->assertInstanceOf(DmNvytSheetExport::class, $s[18]);
    }

    /** @test */
    public function ten_sheet_hop_le_voi_excel()
    {
        foreach ($this->sheets() as $s) {
            $t = $s->title();
            $this->assertLessThanOrEqual(31, mb_strlen($t), "$t qua 31 ky tu");
            $this->assertSame(0, preg_match('#[:\\\\/?*\[\]]#', $t), "$t chua ky tu cam");
        }
    }

    /** @test */
    public function sheet_loi_the_bat_cot_ma_khoa_va_cat_theo_tap_ho_so()
    {
        $the = $this->sheets(['ma_khoa' => 'K01'])[16];

        $this->assertContains('Mã Khoa', $the->headings());
        $this->assertContains('K01', $the->query()->getBindings());
    }

    /** @test */
    public function sheet_danh_muc_nhan_ma_co_so_dang_loc()
    {
        $s = $this->sheets(['ma_cskcb' => '01929']);

        $this->assertContains('01929', $s[17]->query()->getBindings());
        $this->assertContains('01929', $s[18]->query()->getBindings());
    }
}

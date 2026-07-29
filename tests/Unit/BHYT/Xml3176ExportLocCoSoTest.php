<?php

namespace Tests\Unit\BHYT;

use Tests\TestCase;
use Illuminate\Http\Request;
use App\Exports\Xml3176ErrorExport;
use App\Exports\Xml3176XmlExport;
use App\Exports\Xml3176Xml7980aExport;

/**
 * Ba nut xuat tren man XML3176 (79/80a, loi XML, xlsx) tung bo sot bo loc theo
 * co so: URL gui du moi tham so TRU ma_cskcb, nen file xuat ra tron ca cac co
 * so khac du bang tren man da loc dung mot co so - im lang, khong bao loi.
 *
 * Test kiem tra SQL sinh ra qua toSql()/getBindings(), KHONG thuc thi truy van
 * (khong dong Oracle/MySQL that), giong cach lam cua LocCoSoTest.
 */
class Xml3176ExportLocCoSoTest extends TestCase
{
    protected function danhSach()
    {
        return ['01929' => 'Co so A', '37470' => 'Co so B'];
    }

    /** @test */
    public function xml3176_error_export_ap_bo_loc_co_so_khi_ma_hop_le()
    {
        $export = new Xml3176ErrorExport(
            '2026-01-01 00:00:00', '2026-01-31 23:59:59', null,
            'date_payment', null, null,
            'admin', null, null,
            '01929', $this->danhSach()
        );

        $sql = $export->query()->toSql();
        $bindings = $export->query()->getBindings();

        $this->assertContains('ma_cskcb', $sql);
        $this->assertContains('01929', $bindings);
    }

    /** @test */
    public function xml3176_error_export_khong_loc_khi_ma_khong_hop_le()
    {
        $export = new Xml3176ErrorExport(
            '2026-01-01 00:00:00', '2026-01-31 23:59:59', null,
            'date_payment', null, null,
            'admin', null, null,
            '99999', $this->danhSach()
        );

        $sql = $export->query()->toSql();

        $this->assertNotContains('ma_cskcb', $sql);
    }

    /** @test */
    public function xml3176_xml_export_ap_bo_loc_co_so_khi_ma_hop_le()
    {
        $export = new Xml3176XmlExport(
            '2026-01-01 00:00:00', '2026-01-31 23:59:59', null,
            'date_payment', null, null, null,
            'admin', null, null,
            '01929', $this->danhSach()
        );

        $sql = $export->query()->toSql();
        $bindings = $export->query()->getBindings();

        $this->assertContains('ma_cskcb', $sql);
        $this->assertContains('01929', $bindings);
    }

    /** @test */
    public function xml3176_xml_export_khong_loc_khi_ma_khong_hop_le()
    {
        $export = new Xml3176XmlExport(
            '2026-01-01 00:00:00', '2026-01-31 23:59:59', null,
            'date_payment', null, null, null,
            'admin', null, null,
            '', $this->danhSach()
        );

        $sql = $export->query()->toSql();

        $this->assertNotContains('ma_cskcb', $sql);
    }

    /** @test */
    public function xml7980a_export_ap_bo_loc_co_so_nhanh_khong_co_treatment_code()
    {
        $request = Request::create('/x', 'GET', [
            'date_from' => '2026-01-01 00:00:00',
            'date_to' => '2026-01-31 23:59:59',
            'date_type' => 'date_payment',
            'ma_cskcb' => '01929',
        ]);
        $export = new Xml3176Xml7980aExport($request);

        $sql = $export->query()->toSql();
        $bindings = $export->query()->getBindings();

        $this->assertContains('ma_cskcb', $sql);
        $this->assertContains('01929', $bindings);
    }

    /** @test */
    public function xml7980a_export_ap_bo_loc_co_so_nhanh_co_treatment_code()
    {
        $request = Request::create('/x', 'GET', [
            'treatment_code' => 'TC001',
            'ma_cskcb' => '01929',
        ]);
        $export = new Xml3176Xml7980aExport($request);

        $sql = $export->query()->toSql();
        $bindings = $export->query()->getBindings();

        $this->assertContains('ma_cskcb', $sql);
        $this->assertContains('01929', $bindings);
    }
}

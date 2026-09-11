<?php

namespace Tests\Unit\Xml3176;

use App\Services\BHYT\Xml3176LocDanhSach;
use Tests\TestCase;

/**
 * Chot lai cau noi giua ba lop: o loc tren giao dien -> ham dung tham so JavaScript ->
 * hang so KHOA phia may chu.
 *
 * Ban cu cua test nay chi soi RIENG nut 79/80a va chi kiem mot chieu (Export doc tham so
 * ma nut khong gui). No khong bat duoc hai lo thung lon nhat: nut "danh sach ho so" gui
 * xml_filter_status + xml3176_error_catalog nhung lop Export doc vao bien cuc bo roi
 * khong dung, va nam bo loc khac thi JavaScript khong gui.
 */
class Xml3176ExportParamsTest extends TestCase
{
    private function blade(): string
    {
        return file_get_contents(resource_path('views/bhyt/xml3176/index.blade.php'));
    }

    /** Cac khoa nam trong ham dung tham so dung chung cua ba nut xuat. */
    private function thamSoJs(): array
    {
        $blade = $this->blade();

        $start = strpos($blade, 'function xml3176ThamSoLoc()');
        $this->assertNotFalse($start, 'Khong tim thay ham dung tham so dung chung');

        $end = strpos($blade, "\n        }", $start);
        $this->assertNotFalse($end, 'Khong tim thay diem ket thuc cua ham');

        preg_match_all("/'([a-z0-9_]+)'\s*:/", substr($blade, $start, $end - $start), $m);
        $khoa = array_values(array_unique($m[1]));

        $this->assertNotEmpty($khoa, 'Khong doc duoc tham so nao - regex hong?');

        return $khoa;
    }

    /** @test */
    public function ham_dung_tham_so_js_khop_chinh_xac_voi_hang_so_KHOA()
    {
        $js  = $this->thamSoJs();
        $php = Xml3176LocDanhSach::KHOA;

        sort($js);
        sort($php);

        $this->assertSame($php, $js,
            'Ham xml3176ThamSoLoc() trong blade da lech voi Xml3176LocDanhSach::KHOA. '
            . 'Them bo loc moi phai sua CA HAI, neu khong bo loc se bi bo qua im lang.');
    }

    /** @test */
    public function ca_ba_nut_xuat_deu_dung_ham_dung_chung()
    {
        $blade = $this->blade();

        foreach (['export-7980a-data', 'export-xml3176-xml-errors', 'export-xml3176-xml-xlsx'] as $route) {
            $vt = strpos($blade, $route);
            $this->assertNotFalse($vt, "Khong tim thay nut $route");

            // Doan ngay sau ten route phai goi ham dung chung, khong tu liet ke tham so.
            $doan = substr($blade, $vt, 200);
            $this->assertContains('xml3176ThamSoLoc()', $doan,
                "Nut $route khong dung ham dung chung -> se lai sot bo loc");
        }
    }

    /** @test */
    public function moi_o_loc_tren_giao_dien_deu_co_trong_hang_so_KHOA()
    {
        $blade = $this->blade();
        $khoa  = Xml3176LocDanhSach::KHOA;

        // Bang DataTables doc thang gia tri cac o loc khi gui request; day la danh sach
        // day du nhat ve nhung gi nguoi dung CO THE loc tren man.
        preg_match_all("/d\.([a-z0-9_]+)\s*=\s*\\\$\('#/", $blade, $m);
        $oLoc = array_values(array_unique($m[1]));

        $this->assertNotEmpty($oLoc, 'Khong doc duoc o loc nao tu bang DataTables');

        $thieu = array_diff($oLoc, $khoa);
        $this->assertEmpty($thieu,
            'Bang danh sach loc theo tham so ma Xml3176LocDanhSach::KHOA khong co, nen '
            . 'file xuat se rong hon bang dang hien thi: ' . implode(', ', $thieu));
    }

    /** @test */
    public function lop_export_khong_con_tu_doc_request()
    {
        // Hai lop nay gio nhan mang bo loc da doc san; con tu doc request nghia la co
        // ai do chep lai mot ban rieng - dung cai da gay ra bug nay.
        foreach (['Xml3176XmlExport', 'Xml3176ErrorExport', 'Xml3176ErrorMultiSheetExport'] as $lop) {
            $src = file_get_contents(app_path("Exports/$lop.php"));
            $this->assertNotContains('request->input(', $src, "$lop khong duoc tu doc request");
        }
    }
}

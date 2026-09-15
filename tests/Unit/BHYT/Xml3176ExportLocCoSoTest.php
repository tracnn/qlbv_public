<?php

namespace Tests\Unit\BHYT;

use Cache;
use Tests\TestCase;
use Illuminate\Http\Request;
use App\Exports\Xml3176ErrorSheetExport;
use App\Exports\Xml3176XmlExport;
use App\Exports\Xml3176Xml7980aExport;
use App\Services\BHYT\DanhSachCoSo;

/**
 * Ba nut xuat tren man XML3176 (79/80a, loi XML, xlsx) tung bo sot bo loc theo
 * co so: URL gui du moi tham so TRU ma_cskcb, nen file xuat ra tron ca cac co
 * so khac du bang tren man da loc dung mot co so - im lang, khong bao loi.
 *
 * Test kiem tra truy van sinh ra qua toSql()/getBindings()/wheres, KHONG thuc thi truy van.
 */
class Xml3176ExportLocCoSoTest extends TestCase
{
    protected function setUp()
    {
        parent::setUp();

        // Hai ca 7980a KHONG nhan danh sach co so tu ngoai vao: Xml3176Xml7980aExport tu goi
        // DanhSachCoSo::danhSach(), ma ham do doc bang his_branch tren Oracle HIS. Tuc la ket
        // qua phu thuoc vao BENH VIEN dang chay test: may nay tra ve 01013, nen ma 01929
        // (Bach Mai, lay tu vi du trong tai lieu) bi coi la khong hop le va bo loc khong duoc
        // ap - test do vi mot ly do chang lien quan gi den thu no dinh kiem.
        //
        // danhSach() boc trong Cache::remember nen nap san cache la cat dut phu thuoc do.
        // Driver cache trong test la 'array' (phpunit.xml) nen moi test mot ban sach.
        Cache::put(DanhSachCoSo::KHOA_CACHE, $this->danhSach(), 60);
    }

    protected function danhSach()
    {
        return ['01929' => 'Co so A', '37470' => 'Co so B'];
    }

    /**
     * Cac cot that su bi rang buoc trong menh de WHERE.
     *
     * KHONG dung assertContains('ma_cskcb', $sql): chuoi 'ma_cskcb' nam san trong danh sach
     * cot select/group by cua truy van 79/80a, nen khang dinh do XANH KE CA KHI bo loc bi bo
     * han - dung cai bay ma test nay sinh ra de canh.
     *
     * @param mixed $query Eloquent Builder hoac Query Builder
     * @return array ten cot
     */
    protected function cotTrongWhere($query)
    {
        $builder = method_exists($query, 'getQuery') ? $query->getQuery() : $query;
        $cot = [];

        foreach ((array) $builder->wheres as $w) {
            if (isset($w['column'])) {
                $cot[] = $w['column'];
            }
        }

        return $cot;
    }

    /** Khang dinh truy van CO loc theo dung ma co so. */
    protected function khangDinhCoLoc($query, $ma)
    {
        $cot = $this->cotTrongWhere($query);
        $coCot = false;

        foreach ($cot as $c) {
            if ($c === 'ma_cskcb' || substr($c, -9) === '.ma_cskcb') {
                $coCot = true;
            }
        }

        $this->assertTrue($coCot, 'Truy van khong co dieu kien WHERE tren ma_cskcb. Cac cot: ' . implode(', ', $cot));
        $this->assertContains($ma, $query->getBindings(), 'Khong co gia tri ' . $ma . ' trong bindings');
    }

    /** Bo loc day du, chi ghi de nhung khoa can thiet cho tung ca. */
    protected function loc(array $ghiDe = [])
    {
        return array_merge(
            array_fill_keys(\App\Services\BHYT\Xml3176LocDanhSach::KHOA, null),
            [
                'date_from' => '2026-01-01 00:00:00',
                'date_to'   => '2026-01-31 23:59:59',
                'date_type' => 'date_payment',
                'imported_by' => 'admin',
            ],
            $ghiDe
        );
    }

    /**
     * Voi Xml3176ErrorSheetExport, dieu kien ma_cskcb nam trong TRUY VAN CON cat theo ma_lk
     * (xem Xml3176LocDanhSach::truyVanMaLk) chu khong o WHERE muc ngoai, nen phai soi
     * chuoi SQL. Van khong dung assertContains tren toan bo SQL mot cach mu quang: bang
     * xml3176_error_results khong co cot ma_cskcb nao trong select, nen chuoi nay chi co
     * the den tu dieu kien loc.
     */
    protected function khangDinhCoLocTrongTruyVanCon($query, $ma)
    {
        $this->assertContains('ma_cskcb', $query->toSql(),
            'Truy van con khong co dieu kien tren ma_cskcb');
        $this->assertContains($ma, $query->getBindings(),
            'Khong co gia tri ' . $ma . ' trong bindings');
    }

    /** @test */
    public function xml3176_error_export_ap_bo_loc_co_so_khi_ma_hop_le()
    {
        $export = new Xml3176ErrorSheetExport('XML3', $this->loc(['ma_cskcb' => '01929']), $this->danhSach());

        $this->khangDinhCoLocTrongTruyVanCon($export->query(), '01929');
    }

    /** @test */
    public function xml3176_error_export_khong_loc_khi_ma_khong_hop_le()
    {
        $export = new Xml3176ErrorSheetExport('XML3', $this->loc(['ma_cskcb' => '99999']), $this->danhSach());

        $this->assertNotContains('ma_cskcb', $export->query()->toSql());
        $this->assertNotContains('99999', $export->query()->getBindings());
    }

    /** @test */
    public function xml3176_xml_export_ap_bo_loc_co_so_khi_ma_hop_le()
    {
        $export = new Xml3176XmlExport($this->loc(['ma_cskcb' => '01929']), $this->danhSach());

        $this->khangDinhCoLoc($export->query(), '01929');
    }

    /** @test */
    public function xml3176_xml_export_khong_loc_khi_ma_khong_hop_le()
    {
        $export = new Xml3176XmlExport($this->loc(['ma_cskcb' => '']), $this->danhSach());

        $this->assertNotContains('ma_cskcb', $this->cotTrongWhere($export->query()));
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

        $this->khangDinhCoLoc($export->query(), '01929');
    }

    /** @test */
    public function xml7980a_export_ap_bo_loc_co_so_nhanh_co_treatment_code()
    {
        $request = Request::create('/x', 'GET', [
            'treatment_code' => 'TC001',
            'ma_cskcb' => '01929',
        ]);
        $export = new Xml3176Xml7980aExport($request);

        $this->khangDinhCoLoc($export->query(), '01929');
    }

    /** @test */
    public function xml7980a_khong_loc_khi_ma_khong_thuoc_danh_sach_co_so()
    {
        // Nhanh con lai cua LocCoSo::maHopLe - ma khong co trong danh sach thi BO QUA bo loc
        // chu khong nem loi, vi day la man danh sach chu khong phai thao tac ghi.
        $request = Request::create('/x', 'GET', [
            'treatment_code' => 'TC001',
            'ma_cskcb' => '99999',
        ]);
        $export = new Xml3176Xml7980aExport($request);

        $this->assertNotContains('xml3176_xml1s.ma_cskcb', $this->cotTrongWhere($export->query()));
        $this->assertNotContains('99999', $export->query()->getBindings());
    }
}

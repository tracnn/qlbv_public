<?php

namespace Tests\Unit\Xml3176\Chuoi;

use App\Services\Xml3176Service;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\Support\FakeXMLSignService;
use Tests\Support\Xml3176RuleTestSupport;
use Tests\TestCase;

/**
 * Buoc XUAT de lai tep cho ky; buoc KY doc tep do, ky, ghi tep dung cho va dung ten nhu
 * truoc day, copy sang cong ngoai, ghi duong dan cho buoc GUI.
 */
class Xml3176ServiceTepChoKyTest extends TestCase
{
    use Xml3176RuleTestSupport;

    protected function setUp()
    {
        parent::setUp();
        $this->bootXml3176Sqlite([
            '2026_01_09_162044_create_xml3176_informations_table.php',
            '2026_03_28_201021_add_sign_method_to_xml3176_informations.php',
            '2026_09_28_100000_them_checked_at_vao_xml3176_informations.php',
            '2026_09_29_120000_them_chain_token_va_signed_file_path_vao_xml3176_informations.php',
        ]);
        DB::table('xml3176_informations')->insert(['ma_lk' => 'LK1', 'macskcb' => '01929']);

        Storage::fake('local');
        Storage::fake('exportXml3176');
        config([
            'xml3176.export_to_directory_by_day' => false,
            'organization.truc_du_lieu_y_te.enabled' => false,
            'organization.cong_du_lieu_y_te_dien_bien.enabled' => false,
        ]);
    }

    /** Service that, voi lop ky gia. */
    private function service(FakeXMLSignService $ky)
    {
        $s = new Xml3176Service();
        $p = new \ReflectionProperty(Xml3176Service::class, 'xmlSignService');
        $p->setAccessible(true);
        $p->setValue($s, $ky);

        return $s;
    }

    /** @test */
    public function duong_dan_cho_ky_co_dinh_theo_ho_so_va_khong_thoat_ra_ngoai()
    {
        $this->assertSame('xml3176-cho-ky/LK1.xml', Xml3176Service::duongDanChoKy('LK1'));
        // ma_lk den tu tep XML ben ngoai: '../' ghep thang vao duong dan la mo loi thoat.
        $this->assertNotContains('..', Xml3176Service::duongDanChoKy('../../etc/x'));
    }

    /** @test */
    public function xuat_ghi_tep_cho_ky()
    {
        $s = new class extends Xml3176Service {
            public function getDataForXmlExport($ma) { return '<GIAMDINHHS/>'; }
        };

        $this->assertTrue($s->xuatTepChoKy('LK1'));
        $this->assertSame('<GIAMDINHHS/>', Storage::disk('local')->get('xml3176-cho-ky/LK1.xml'));
    }

    /** @test */
    public function xuat_khong_dung_duoc_du_lieu_thi_tra_false_va_khong_ghi()
    {
        $s = new class extends Xml3176Service {
            public function getDataForXmlExport($ma) { return false; }
        };

        $this->assertFalse($s->xuatTepChoKy('LK1'));
        $this->assertFalse(Storage::disk('local')->exists('xml3176-cho-ky/LK1.xml'));
    }

    /** @test */
    public function ky_thanh_cong_ghi_tep_da_ky_va_duong_dan_roi_xoa_tep_cho_ky()
    {
        Storage::disk('local')->put('xml3176-cho-ky/LK1.xml', '<CHUAKY/>');
        $ky = new FakeXMLSignService();

        $kq = $this->service($ky)->kyVaGhiTep('LK1');

        $this->assertSame(['isSigned' => true, 'macskcb' => '01929'], $kq);
        $this->assertSame('<CHUAKY/>', $ky->xmlNhanDuoc, 'Phai ky dung noi dung tep cho ky');

        $r = DB::table('xml3176_informations')->where('ma_lk', 'LK1')->first();
        $this->assertEquals(1, $r->is_signed);
        $this->assertSame('USB Token', $r->sign_method);
        $this->assertRegExp('#^01929/\d{4}\.\d{2}\.\d{2}_\d{2}\.\d{2}\.\d{2}_LK1\.xml$#', $r->signed_file_path,
            'Ten tep va thu muc phai giu nhu truoc day - co the co phan mem khac doc thu muc nay');
        $this->assertSame('<DAKY/>', Storage::disk('exportXml3176')->get($r->signed_file_path));
        $this->assertFalse(Storage::disk('local')->exists('xml3176-cho-ky/LK1.xml'));
    }

    /** @test */
    public function ky_khong_duoc_van_ghi_tep_chua_ky_nhu_truoc_day()
    {
        // Giu nguyen hanh vi: ky khong duoc van ghi tep va van copy sang cong ngoai, chi
        // khong gui cong BHXH (buoc ky quyet dinh viec do).
        Storage::disk('local')->put('xml3176-cho-ky/LK1.xml', '<CHUAKY/>');
        $ky = new FakeXMLSignService();
        $ky->ketQua = ['isSigned' => false, 'data' => '<CHUAKY/>', 'method' => null, 'error' => 'HSM tat'];

        $kq = $this->service($ky)->kyVaGhiTep('LK1');

        $this->assertSame(['isSigned' => false, 'macskcb' => '01929'], $kq);
        $r = DB::table('xml3176_informations')->where('ma_lk', 'LK1')->first();
        $this->assertEquals(0, $r->is_signed);
        $this->assertSame('HSM tat', $r->signed_error);
        $this->assertSame('<CHUAKY/>', Storage::disk('exportXml3176')->get($r->signed_file_path));
    }

    /** @test */
    public function khong_co_tep_cho_ky_thi_tra_false_va_khong_ky()
    {
        $ky = new FakeXMLSignService();

        $this->assertFalse($this->service($ky)->kyVaGhiTep('LK1'));
        $this->assertSame(0, $ky->soLanGoi);
    }
}

<?php

namespace Tests\Unit\Xml3176;

use App\Jobs\ExportXml3176Job;
use App\Services\Xml3176Service;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Tests\Support\Xml3176RuleTestSupport;
use Tests\TestCase;

/**
 * Buoc xuat phai CHO kiem loi xong moi duoc hoi "co loi nghiem trong khong".
 *
 * Do tren CSDL that ngay 28/09/2026: ca 50 ho so nap luc 11:17:38 deu duoc XUAT luc
 * 11:17:38, con dong loi nghiem trong dau tien mai 11:17:39 den 11:17:42 moi duoc ghi.
 * Kiem loi chay tren hang doi JobXml3176, xuat chay tren JobExportXml3176 - hai worker
 * khac nhau chay song song, va worker xuat thang cuoc dua. Luc ExportXml3176Job hoi
 * "ho so nay co loi nghiem trong khong" thi bang loi con TRONG, nen no tra loi khong va
 * xuat; xuat xong la ky so va day sang hang doi gui cong.
 *
 * Tuc la co export_xml_not_check khong hong - no duoc hoi vao dung luc chua co gi de thay.
 */
class XuatChoKiemXongTest extends TestCase
{
    use Xml3176RuleTestSupport;

    protected function setUp()
    {
        parent::setUp();
        $this->bootXml3176Sqlite([
            '2026_01_09_152817_create_xml3176_xml1s_table.php',
            '2026_01_09_161902_create_xml3176_error_results_table.php',
            '2026_01_09_162044_create_xml3176_informations_table.php',
            '2026_09_28_100000_them_checked_at_vao_xml3176_informations.php',
        ]);

        DB::table('xml3176_xml1s')->insert(['ma_lk' => 'LK1', 'stt' => 1]);
    }

    private function thongTin(array $gia = [])
    {
        DB::table('xml3176_informations')->insert(
            array_merge(['ma_lk' => 'LK1', 'macskcb' => '01929'], $gia));
    }

    private function loiNghiemTrong()
    {
        DB::table('xml3176_error_results')->insert([
            'ma_lk' => 'LK1', 'xml' => 'XML1', 'stt' => 1,
            'error_code' => 'X', 'description' => 'x', 'critical_error' => 1,
        ]);
    }

    /** Chay job va tra ve so lan processExportXml() duoc goi. */
    private function chay($soLanCho = 0)
    {
        $goi = 0;
        $this->app->instance(Xml3176Service::class, new class($goi) extends Xml3176Service {
            public $dem;
            public function __construct(&$dem) { $this->dem = &$dem; }
            public function processExportXml($ma_lk) { $this->dem++; }
        });

        (new ExportXml3176Job('LK1', $soLanCho))->handle(app(Xml3176Service::class));

        return $goi;
    }

    /** @test */
    public function bo_kiem_loi_thi_xuat_ngay_khong_cho()
    {
        // Co so co y bo kiem loi thi khong duoc lam cham ho: giu nguyen hanh vi cu.
        config(['organization.export_xml_not_check' => true]);
        $this->thongTin();
        Queue::fake();

        $this->assertSame(1, $this->chay());
        Queue::assertNothingPushed();
    }

    /** @test */
    public function bat_kiem_loi_ma_chua_kiem_xong_thi_hoan_lai_chu_khong_xuat()
    {
        config(['organization.export_xml_not_check' => false]);
        $this->thongTin(['checked_at' => null]);
        Queue::fake();

        $this->assertSame(0, $this->chay(), 'Chua kiem xong ma da xuat la dung cai bug can sua');
        Queue::assertPushed(ExportXml3176Job::class);
    }

    /** @test */
    public function kiem_xong_va_sach_thi_xuat()
    {
        config(['organization.export_xml_not_check' => false]);
        $this->thongTin(['checked_at' => '2026-09-28 11:17:42']);
        Queue::fake();

        $this->assertSame(1, $this->chay());
        Queue::assertNothingPushed();
    }

    /** @test */
    public function kiem_xong_ma_co_loi_nghiem_trong_thi_khong_xuat()
    {
        config(['organization.export_xml_not_check' => false]);
        $this->thongTin(['checked_at' => '2026-09-28 11:17:42']);
        $this->loiNghiemTrong();
        Queue::fake();

        $this->assertSame(0, $this->chay());
        Queue::assertNothingPushed();
    }

    /** @test */
    public function het_luot_cho_thi_khong_xuat_va_ghi_ro_ly_do()
    {
        // Chon huong AN TOAN: khong chac la khong gui. Yeu cau cua nguoi dung la moi ho so
        // phai duoc kiem truoc khi len cong.
        config(['organization.export_xml_not_check' => false]);
        $this->thongTin(['checked_at' => null]);
        Queue::fake();

        $this->assertSame(0, $this->chay(ExportXml3176Job::SO_LAN_CHO_TOI_DA));
        Queue::assertNothingPushed();

        $loi = DB::table('xml3176_informations')->where('ma_lk', 'LK1')->value('export_error');
        $this->assertNotEmpty($loi);
        $this->assertContains('kiểm lỗi', $loi);
    }

    /** @test */
    public function tat_kiem_tong_the_thi_khong_cho_vi_khong_ai_dat_dau()
    {
        // xml_3176_not_check = true nghia la CheckCompleteXml3176RecordJob khong bao gio
        // chay, tuc khong ai dat checked_at. Neu van cho thi moi ho so treo vinh vien.
        config([
            'organization.export_xml_not_check' => false,
            'organization.xml_3176_not_check'   => true,
        ]);
        $this->thongTin(['checked_at' => null]);
        Queue::fake();

        $this->assertSame(1, $this->chay());
        Queue::assertNothingPushed();
    }

    /** @test */
    public function nap_lai_ho_so_thi_xoa_dau_da_kiem()
    {
        // Khong xoa thi lan nap thu hai se xuat ngay bang dau cu, dung lai cuoc dua nay.
        $src = file_get_contents(app_path('Services/Xml3176Service.php'));
        $viTri = strpos($src, "if (\$operationType === 'import')");

        $this->assertNotFalse($viTri);
        $this->assertContains("\$values['checked_at'] = null;", substr($src, $viTri, 900));
    }

    /** @test */
    public function job_kiem_tong_the_ghi_dau_da_kiem()
    {
        $src = file_get_contents(app_path('Jobs/CheckCompleteXml3176RecordJob.php'));

        $this->assertContains('checked_at', $src,
            'CheckCompleteXml3176RecordJob la job kiem CUOI CUNG cua mot ho so, no phai dat dau da kiem xong');
    }
}

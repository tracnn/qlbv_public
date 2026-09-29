<?php

namespace Tests\Unit\Xml3176\Chuoi;

use App\Jobs\CheckCompleteXml3176RecordJob;
use App\Jobs\CheckXml3176TypeJob;
use App\Services\Xml3176CompleteChecker;
use Illuminate\Support\Facades\DB;
use Tests\Support\Xml3176RuleTestSupport;
use Tests\TestCase;

class JobKiemTrongChuoiTest extends TestCase
{
    use Xml3176RuleTestSupport;

    protected function setUp()
    {
        parent::setUp();
        $this->bootXml3176Sqlite([
            '2026_01_09_152817_create_xml3176_xml1s_table.php',
            '2026_01_09_161902_create_xml3176_error_results_table.php',
            '2026_01_09_162044_create_xml3176_informations_table.php',
            '2026_03_28_201021_add_sign_method_to_xml3176_informations.php',
            '2026_09_28_100000_them_checked_at_vao_xml3176_informations.php',
            '2026_09_29_120000_them_chain_token_va_signed_file_path_vao_xml3176_informations.php',
        ]);
        DB::table('xml3176_informations')->insert(['ma_lk' => 'LK1', 'macskcb' => '01929', 'chain_token' => 'MA_DUNG']);
    }

    private function loiXml1Cu()
    {
        DB::table('xml3176_error_results')->insert([
            'ma_lk' => 'LK1', 'xml' => 'XML1', 'stt' => 1,
            'error_code' => 'CU', 'description' => 'loi cu', 'critical_error' => 0,
        ]);
    }

    private function soLoiXml1()
    {
        return DB::table('xml3176_error_results')->where('ma_lk', 'LK1')->where('xml', 'XML1')->count();
    }

    /** Checker tong the gia: dem so lan goi, khong cham CSDL. */
    private function checkerTongThe()
    {
        return new class extends Xml3176CompleteChecker {
            public $soLanGoi = 0;
            public function __construct() {}
            public function checkErrors($ma_lk): void { $this->soLanGoi++; }
        };
    }

    // ─── Kiem tung loai ───────────────────────────────────────────────────

    /** @test */
    public function kiem_loai_ma_khop_thi_chay_va_giu_chuoi()
    {
        $this->loiXml1Cu();
        $job = new CheckXml3176TypeJob('LK1', 'XML1', 'MA_DUNG');
        $job->chained = ['buoc sau'];

        $job->handle();

        $this->assertSame(0, $this->soLoiXml1(), 'Job phai chay: tu xoa loi cu cua loai minh');
        $this->assertSame(['buoc sau'], $job->chained);
    }

    /** @test */
    public function kiem_loai_ma_lech_thi_khong_lam_gi_va_cat_chuoi()
    {
        $this->loiXml1Cu();
        $job = new CheckXml3176TypeJob('LK1', 'XML1', 'MA_CU');
        $job->chained = ['buoc sau'];

        $job->handle();

        $this->assertSame(1, $this->soLoiXml1(), 'Chuoi cu khong duoc dung vao ket qua kiem cua chuoi moi');
        $this->assertSame([], $job->chained);
    }

    /** @test */
    public function kiem_loai_job_cu_van_kiem_nhu_thuong()
    {
        $this->loiXml1Cu();

        (new CheckXml3176TypeJob('LK1', 'XML1'))->handle();

        $this->assertSame(0, $this->soLoiXml1());
    }

    /** @test */
    public function kiem_loai_hong_het_luot_thi_ghi_ly_do_vao_cot_xuat()
    {
        (new CheckXml3176TypeJob('LK1', 'XML2', 'MA_DUNG'))->failed(new \RuntimeException('mat ket noi'));

        $loi = DB::table('xml3176_informations')->value('export_error');
        $this->assertContains('Không xuất', $loi);
        $this->assertContains('XML2', $loi);
        $this->assertContains('mat ket noi', $loi);
    }

    /** @test */
    public function kiem_loai_hong_cua_chuoi_cu_khong_ghi()
    {
        (new CheckXml3176TypeJob('LK1', 'XML2', 'MA_CU'))->failed(new \RuntimeException('x'));

        $this->assertNull(DB::table('xml3176_informations')->value('export_error'));
    }

    // ─── Kiem tong the ────────────────────────────────────────────────────

    /** @test */
    public function kiem_tong_the_chay_va_dong_dau()
    {
        $checker = $this->checkerTongThe();

        (new CheckCompleteXml3176RecordJob('LK1', 'MA_DUNG'))->handle($checker);

        $this->assertSame(1, $checker->soLanGoi);
        $this->assertNotNull(DB::table('xml3176_informations')->value('checked_at'));
    }

    /** @test */
    public function tat_kiem_tong_the_van_dong_dau_de_chuoi_di_tiep()
    {
        // xml_3176_not_check chi tat Xml3176CompleteChecker. Job van la moc "buoc kiem da
        // xong" cua chuoi - khong co no thi khong co gi dung giua buoc kiem va buoc xuat.
        config(['organization.xml_3176_not_check' => true]);
        $checker = $this->checkerTongThe();
        $job = new CheckCompleteXml3176RecordJob('LK1', 'MA_DUNG');
        $job->chained = ['xuat'];

        $job->handle($checker);

        $this->assertSame(0, $checker->soLanGoi);
        $this->assertNotNull(DB::table('xml3176_informations')->value('checked_at'));
        $this->assertSame(['xuat'], $job->chained);
    }

    /** @test */
    public function kiem_tong_the_ma_lech_thi_khong_dong_dau()
    {
        $checker = $this->checkerTongThe();
        $job = new CheckCompleteXml3176RecordJob('LK1', 'MA_CU');
        $job->chained = ['xuat'];

        $job->handle($checker);

        $this->assertSame(0, $checker->soLanGoi);
        $this->assertNull(DB::table('xml3176_informations')->value('checked_at'),
            'Dau cua chuoi cu se cho chuoi moi xuat khi chua kiem');
        $this->assertSame([], $job->chained);
    }

    /** @test */
    public function kiem_tong_the_job_cu_van_kiem_va_dong_dau()
    {
        $checker = $this->checkerTongThe();

        (new CheckCompleteXml3176RecordJob('LK1'))->handle($checker);

        $this->assertSame(1, $checker->soLanGoi);
        $this->assertNotNull(DB::table('xml3176_informations')->value('checked_at'));
    }

    /** @test */
    public function kiem_tong_the_hong_thi_ghi_ly_do()
    {
        (new CheckCompleteXml3176RecordJob('LK1', 'MA_DUNG'))->failed(new \RuntimeException('het bo nho'));

        $loi = DB::table('xml3176_informations')->value('export_error');
        $this->assertContains('kiểm tổng thể', $loi);
        $this->assertContains('het bo nho', $loi);
    }
}

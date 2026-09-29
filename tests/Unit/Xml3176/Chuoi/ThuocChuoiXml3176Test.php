<?php

namespace Tests\Unit\Xml3176\Chuoi;

use App\Jobs\Concerns\ThuocChuoiXml3176;
use Illuminate\Bus\Queueable;
use Illuminate\Support\Facades\DB;
use Tests\Support\Xml3176RuleTestSupport;
use Tests\TestCase;

class ThuocChuoiXml3176Test extends TestCase
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
        DB::table('xml3176_informations')->insert(['ma_lk' => 'LK1', 'macskcb' => '01929', 'chain_token' => 'MA_DUNG']);
    }

    private function job($ma)
    {
        return new class($ma) {
            use Queueable, ThuocChuoiXml3176;

            public function __construct($ma) { $this->chainToken = $ma; }

            public function goi($ham, ...$thamSo) { return $this->{$ham}(...$thamSo); }
        };
    }

    /** @test */
    public function ma_khop_la_con_hieu_luc()
    {
        $this->assertTrue($this->job('MA_DUNG')->goi('conHieuLuc', 'LK1'));
    }

    /** @test */
    public function ma_lech_la_het_hieu_luc()
    {
        // Ho so da duoc nap lai (hoac lenh cuu da dung chuoi moi): chuoi nay phai thoi.
        $this->assertFalse($this->job('MA_CU')->goi('conHieuLuc', 'LK1'));
    }

    /** @test */
    public function job_khong_mang_ma_la_job_cu_va_khong_bao_gio_con_hieu_luc()
    {
        $job = $this->job(null);

        $this->assertTrue($job->goi('laJobCu'));
        $this->assertFalse($job->goi('conHieuLuc', 'LK1'));
        $this->assertFalse($this->job('MA_DUNG')->goi('laJobCu'));
    }

    /** @test */
    public function ho_so_khong_ton_tai_la_het_hieu_luc()
    {
        $this->assertFalse($this->job('MA_DUNG')->goi('conHieuLuc', 'KHONG_CO'));
    }

    /** @test */
    public function cat_chuoi_xoa_cac_buoc_phia_sau()
    {
        $job = $this->job('MA_DUNG');
        $job->chained = ['buoc sau 1', 'buoc sau 2'];

        $job->goi('catChuoi');

        $this->assertSame([], $job->chained);
    }

    /** @test */
    public function chi_ghi_khi_ma_con_khop()
    {
        $this->job('MA_CU')->goi('ghiNeuConHieuLuc', 'LK1', ['export_error' => 'loi chuoi cu']);
        $this->assertNull(DB::table('xml3176_informations')->value('export_error'),
            'Chuoi cu khong duoc ghi de trang thai cua chuoi moi');

        $this->job('MA_DUNG')->goi('ghiNeuConHieuLuc', 'LK1', ['export_error' => 'loi that']);
        $this->assertSame('loi that', DB::table('xml3176_informations')->value('export_error'));
    }
}

<?php

namespace Tests\Unit\Xml3176\Chuoi;

use App\Jobs\CheckCompleteXml3176RecordJob;
use App\Jobs\CheckXml3176TypeJob;
use App\Jobs\ExportXml3176Job;
use App\Jobs\SignXml3176Job;
use App\Jobs\SubmitXml3176Job;
use App\Services\Xml3176\Xml3176ChuoiXuLy;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Tests\Support\Xml3176RuleTestSupport;
use Tests\TestCase;

/**
 * Thu tu chuoi do FRAMEWORK bao dam, khong con dua vao viec moi hang doi chi co mot worker.
 * Laravel 5.5 khong co Queue::assertPushedWithChain(): doc $job->chained roi unserialize.
 */
class Xml3176ChuoiXuLyTest extends TestCase
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
        config(['xml3176.export_xml3176_enabled' => true]);
        Queue::fake();
    }

    private function thuocTinh($doiTuong, $ten)
    {
        $p = new \ReflectionProperty($doiTuong, $ten);
        $p->setAccessible(true);

        return $p->getValue($doiTuong);
    }

    /** Job dau tien da day + cac job trong chuoi cua no, theo thu tu. */
    private function chuoiDaDay($lopDau)
    {
        $dau = Queue::pushed($lopDau)->first();
        $this->assertNotNull($dau, 'Khong co job ' . $lopDau . ' nao duoc day');

        return array_merge([$dau], array_map('unserialize', $dau->chained));
    }

    /** Mo ta ngan gon moi job: lop|loai|hang doi|ma phien. */
    private function moTa(array $chuoi)
    {
        return array_map(function ($job) {
            $loai = $job instanceof CheckXml3176TypeJob ? $this->thuocTinh($job, 'xmlType') : '-';

            return class_basename($job) . '|' . $loai . '|' . $job->queue . '|' . $this->thuocTinh($job, 'chainToken');
        }, $chuoi);
    }

    /** @test */
    public function sinh_ma_32_ky_tu_hex_va_moi_lan_mot_khac()
    {
        $a = Xml3176ChuoiXuLy::sinhMa();

        $this->assertRegExp('/^[0-9a-f]{32}$/', $a);
        $this->assertNotSame($a, Xml3176ChuoiXuLy::sinhMa());
    }

    /** @test */
    public function sau_nap_du_chuoi_dung_thu_tu_dung_hang_doi_cung_mot_ma()
    {
        Xml3176ChuoiXuLy::xepSauNap('LK1', 'M1', ['XML1', 'XML2', 'XML1', 'KHONG_CO_CHECKER'], true);

        $this->assertSame([
            'CheckXml3176TypeJob|XML1|JobXml3176|M1',
            'CheckXml3176TypeJob|XML2|JobXml3176|M1',
            'CheckCompleteXml3176RecordJob|-|JobXml3176|M1',
            'ExportXml3176Job|-|JobExportXml3176|M1',
            'SignXml3176Job|-|JobSignXml3176|M1',
            'SubmitXml3176Job|-|JobSubmitXml3176|M1',
        ], $this->moTa($this->chuoiDaDay(CheckXml3176TypeJob::class)));
    }

    /** @test */
    public function khong_cho_xuat_thi_chuoi_dung_o_kiem_tong_the()
    {
        Xml3176ChuoiXuLy::xepSauNap('LK1', 'M1', ['XML1'], false);

        $this->assertSame([
            'CheckXml3176TypeJob|XML1|JobXml3176|M1',
            'CheckCompleteXml3176RecordJob|-|JobXml3176|M1',
        ], $this->moTa($this->chuoiDaDay(CheckXml3176TypeJob::class)));
    }

    /** @test */
    public function tat_tu_dong_xuat_thi_chuoi_dung_o_kiem_tong_the()
    {
        config(['xml3176.export_xml3176_enabled' => false]);

        Xml3176ChuoiXuLy::xepSauNap('LK1', 'M1', ['XML1'], true);

        $this->assertCount(2, $this->chuoiDaDay(CheckXml3176TypeJob::class));
    }

    /** @test */
    public function tat_kiem_tong_the_van_co_job_kiem_tong_the_trong_chuoi()
    {
        // Job do la moc "kiem xong"; co xml_3176_not_check chi tat phan kiem ben trong no.
        config(['organization.xml_3176_not_check' => true]);

        Xml3176ChuoiXuLy::xepSauNap('LK1', 'M1', ['XML1'], true);

        $this->assertContains('CheckCompleteXml3176RecordJob|-|JobXml3176|M1',
            $this->moTa($this->chuoiDaDay(CheckXml3176TypeJob::class)));
    }

    /** @test */
    public function khong_loai_nao_co_checker_thi_chuoi_bat_dau_tu_kiem_tong_the()
    {
        Xml3176ChuoiXuLy::xepSauNap('LK1', 'M1', ['KHONG_CO_CHECKER'], true);

        $this->assertSame('CheckCompleteXml3176RecordJob|-|JobXml3176|M1',
            $this->moTa($this->chuoiDaDay(CheckCompleteXml3176RecordJob::class))[0]);
        Queue::assertNotPushed(CheckXml3176TypeJob::class);
    }

    /** @test */
    public function tu_xuat_sinh_ma_moi_ghi_len_ho_so_va_day_xuat_ky_gui()
    {
        DB::table('xml3176_informations')->insert(['ma_lk' => 'LK1', 'macskcb' => '01929', 'chain_token' => 'MA_CU']);

        $ma = Xml3176ChuoiXuLy::xepTuXuat('LK1');

        $this->assertNotSame('MA_CU', $ma);
        $this->assertSame($ma, DB::table('xml3176_informations')->value('chain_token'),
            'Ma moi phai ghi len ho so de chuoi cu con song tu thoi - thieu thi co the gui trung');
        $this->assertSame([
            'ExportXml3176Job|-|JobExportXml3176|' . $ma,
            'SignXml3176Job|-|JobSignXml3176|' . $ma,
            'SubmitXml3176Job|-|JobSubmitXml3176|' . $ma,
        ], $this->moTa($this->chuoiDaDay(ExportXml3176Job::class)));
    }
}

<?php

namespace Tests\Unit\Xml3176\Nvyt;

use App\Services\Xml3176\NvytLookup;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\Support\Xml3176RuleTestSupport;
use Tests\TestCase;

class NvytKiemLaiLenhTest extends TestCase
{
    use Xml3176RuleTestSupport;

    protected function setUp()
    {
        parent::setUp();
        $files = ['2024_06_13_094552_create_nhan_vien_y_te_table.php', '2026_04_09_100000_add_so_dinh_danh_to_medical_staffs_table.php'];
        foreach ([1, 2, 3, 4, 5, 7, 8, 9, 10, 11, 13, 14] as $n) {
            $files[] = basename(glob(database_path("migrations/2026_01_09_*_create_xml3176_xml{$n}s_table.php"))[0]);
        }
        $files[] = '2026_01_09_161708_create_xml3176_error_catalogs_table.php';
        $files[] = '2026_01_09_161902_create_xml3176_error_results_table.php';
        $files[] = '2026_10_15_100000_gop_ma_loi_nvyt_xml3176.php';
        $this->bootXml3176Sqlite($files);
        NvytLookup::xoaBoNho();
        config(['xml3176.group_code_with_executor' => ['3', '8']]);
        DB::table('medical_staffs')->insert([
            'ma_khoa' => 'K', 'ten_khoa' => 'K', 'ma_bhxh' => 'B1', 'ho_ten' => 'A', 'gioi_tinh' => 1,
            'chucdanh_nn' => 1, 'macchn' => '000001/HNO-CCHN', 'ngaycap_cchn' => '', 'noicap_cchn' => '',
            'thoigian_dk' => 1, 'thoigian_ngay' => '', 'thoigian_tuan' => '', 'tu_ngay' => '20200101',
        ]);
    }

    protected function tearDown()
    {
        NvytLookup::xoaBoNho();
        foreach (glob(storage_path('logs/kiem-lai-nvyt-*.txt')) as $f) {
            @unlink($f);
        }
        parent::tearDown();
    }

    private function hs(string $maLk, array $xml1 = [], string $tao = '2026-10-01 10:00:00'): void
    {
        DB::table('xml3176_xml1s')->insert(array_merge(
            ['ma_lk' => $maLk, 'stt' => 1, 'ma_ttdv' => '000001/HNO-CCHN', 'created_at' => $tao], $xml1
        ));
    }

    private function loi(string $maLk, string $code, bool $chan = false, int $n = 1): void
    {
        for ($i = 0; $i < $n; $i++) {
            DB::table('xml3176_error_results')->insert([
                'xml' => 'XMLComplete', 'ma_lk' => $maLk, 'stt' => 1, 'error_code' => $code,
                'description' => 'x', 'critical_error' => $chan,
            ]);
        }
    }

    private function dem(): int
    {
        return DB::table('xml3176_error_results')->count();
    }

    private function chay(array $opt = []): string
    {
        Artisan::call('xml3176:kiem-lai-nvyt', $opt);
        return Artisan::output();
    }

    private function hsXml4Sai(string $maLk): void
    {
        $this->hs($maLk);
        DB::table('xml3176_xml4s')->insert(['ma_lk' => $maLk, 'stt' => 1, 'ma_bs_doc_kq' => 'abc']);
    }

    /** @test */
    public function thu_khong_ghi()
    {
        $this->hsXml4Sai('LK1');
        $this->loi('LK1', 'XML4_INFO_ERROR_MA_BS_DOC_KQ_NOT_FOUND', false, 3);
        $out = $this->chay(['--thu' => true]);
        $this->assertSame(3, $this->dem());
        $this->assertContains('3', $out);
    }

    /** @test */
    public function chay_that_thay_ma_cu_bang_ma_moi_giu_loi_khac()
    {
        $this->hsXml4Sai('LK1');
        $this->loi('LK1', 'XML4_INFO_ERROR_MA_BS_DOC_KQ_NOT_FOUND', false, 3);
        $this->loi('LK1', 'XML3_NGAY_TH_YL_EQUALS_NGAY_KQ');
        $this->chay();
        $this->assertSame(0, DB::table('xml3176_error_results')->where('error_code', 'like', 'XML4_INFO%')->count());
        $this->assertSame(1, DB::table('xml3176_error_results')->where('error_code', 'XMLComplete_NVYT_SAI_DINH_DANG')->count());
        $this->assertSame(1, DB::table('xml3176_error_results')->where('error_code', 'XML3_NGAY_TH_YL_EQUALS_NGAY_KQ')->count());
    }

    /** @test */
    public function chay_lai_cung_ket_qua()
    {
        $this->hsXml4Sai('LK1');
        $this->loi('LK1', 'XML4_INFO_ERROR_MA_BS_DOC_KQ_NOT_FOUND', false, 3);
        $this->chay();
        $lan1 = $this->dem();
        $this->chay();
        $this->assertSame($lan1, $this->dem());
    }

    /** @test */
    public function het_chan_ghi_tep()
    {
        $this->hs('LK1');
        $this->loi('LK1', 'XML1_INFO_ERROR_MA_TTDV', true);
        $this->chay();
        $this->assertSame(0, DB::table('xml3176_error_results')->where('critical_error', true)->count());
        $tep = glob(storage_path('logs/kiem-lai-nvyt-*.txt'));
        $this->assertNotEmpty($tep);
        $this->assertContains('LK1', file_get_contents($tep[0]));
    }

    /** @test */
    public function loc_theo_ngay()
    {
        $this->hs('LK1', [], '2026-09-01 10:00:00');
        $this->hs('LK2', [], '2026-10-10 10:00:00');
        $this->loi('LK1', 'XML1_INFO_ERROR_MA_TTDV');
        $this->loi('LK2', 'XML1_INFO_ERROR_MA_TTDV');
        $this->chay(['--tu' => '2026-10-01']);
        $this->assertSame(1, DB::table('xml3176_error_results')->where('ma_lk', 'LK1')->count());
        $this->assertSame(0, DB::table('xml3176_error_results')->where('ma_lk', 'LK2')->count());
    }
}

<?php

namespace Tests\Unit\Xml3176\TangToc;

use App\Models\BHYT\Xml3176Xml1;
use Illuminate\Support\Facades\DB;
use Tests\Support\Xml3176RuleTestSupport;
use Tests\TestCase;

class DoKiemLenhTest extends TestCase
{
    use Xml3176RuleTestSupport;

    private $thuMuc;

    protected function setUp()
    {
        parent::setUp();
        $this->bootXml3176Sqlite([
            '2026_01_09_152817_create_xml3176_xml1s_table.php',
            '2026_01_09_152826_create_xml3176_xml2s_table.php',
            '2026_01_09_152832_create_xml3176_xml3s_table.php',
            '2026_01_09_152838_create_xml3176_xml4s_table.php',
            '2026_01_09_152843_create_xml3176_xml5s_table.php',
            '2026_01_09_152854_create_xml3176_xml7s_table.php',
            '2026_01_09_152900_create_xml3176_xml8s_table.php',
            '2026_01_09_152906_create_xml3176_xml9s_table.php',
            '2026_01_09_152912_create_xml3176_xml10s_table.php',
            '2026_01_09_152918_create_xml3176_xml11s_table.php',
            '2026_01_09_152930_create_xml3176_xml13s_table.php',
            '2026_01_09_152936_create_xml3176_xml14s_table.php',
            '2024_07_04_072537_create_icd10_categories_table.php',
            '2024_07_04_092500_create_icdyhct_categories_table.php',
            '2026_01_09_161708_create_xml3176_error_catalogs_table.php',
            '2026_01_09_161902_create_xml3176_error_results_table.php',
        ]);

        $this->thuMuc = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'dokiem_' . uniqid();
        mkdir($this->thuMuc);

        Xml3176Xml1::create(['ma_lk' => 'B2', 'stt' => 2, 'ma_loai_kcb' => '03', 'ngay_tai_kham' => '2026091', 'ngay_vao' => '202610010800', 'ngay_ra' => '202610011000', 'ngay_ttoan' => '202610011000']);
        Xml3176Xml1::create(['ma_lk' => 'A1', 'stt' => 1, 'ma_loai_kcb' => '01', 'ngay_tai_kham' => '2026091', 'ngay_vao' => '202610010800', 'ngay_ra' => '202610011000', 'ngay_ttoan' => '202610011000']);
    }

    protected function tearDown()
    {
        foreach (glob($this->thuMuc . DIRECTORY_SEPARATOR . '*') as $f) {
            @unlink($f);
        }
        @rmdir($this->thuMuc);
        parent::tearDown();
    }

    private function tep($ten)
    {
        return $this->thuMuc . DIRECTORY_SEPARATOR . $ten;
    }

    private function chay(array $tuyChon)
    {
        file_put_contents($this->tep('ds.txt'), "B2\nA1\n");
        return $this->artisan('xml3176:do-kiem', array_merge(['--ma-lk-tep' => $this->tep('ds.txt')], $tuyChon));
    }

    private function docLoi($ten)
    {
        $dong = array_filter(explode("\n", file_get_contents($this->tep($ten))), 'strlen');
        return array_map(function ($d) {
            return json_decode($d, true);
        }, array_values($dong));
    }

    /** @test */
    public function xuat_loi_sap_xep_on_dinh()
    {
        $this->assertSame(0, $this->chay(['--xuat-loi' => $this->tep('l1.jsonl'), '--xuat-do' => $this->tep('do.txt')]));
        $this->assertSame(0, $this->chay(['--xuat-loi' => $this->tep('l2.jsonl')]));

        $loi = $this->docLoi('l1.jsonl');
        $this->assertNotEmpty($loi);
        $this->assertSame(
            ['xml', 'ma_lk', 'stt', 'error_code', 'description', 'critical_error'],
            array_keys($loi[0])
        );

        $khoa = function ($l) {
            return [$l['ma_lk'], $l['xml'], $l['stt'], $l['error_code'], (string) $l['description']];
        };
        $daSap = $loi;
        usort($daSap, function ($a, $b) use ($khoa) {
            return $khoa($a) <=> $khoa($b);
        });
        $this->assertSame($daSap, $loi);
        $this->assertSame('A1', $loi[0]['ma_lk']);

        $this->assertSame(file_get_contents($this->tep('l1.jsonl')), file_get_contents($this->tep('l2.jsonl')));
        $this->assertContains('ms', file_get_contents($this->tep('do.txt')));
    }

    /** @test */
    public function khong_ghi_csdl()
    {
        $truoc = [DB::table('xml3176_error_results')->count(), DB::table('xml3176_error_catalogs')->count()];

        $this->chay(['--xuat-loi' => $this->tep('l.jsonl')]);

        $this->assertNotEmpty($this->docLoi('l.jsonl'));
        $this->assertSame($truoc, [DB::table('xml3176_error_results')->count(), DB::table('xml3176_error_catalogs')->count()]);
    }

    /** @test */
    public function ghi_ma_lk_dung_lai_duoc()
    {
        Xml3176Xml1::query()->update(['created_at' => '2026-10-05 00:00:00']);

        $this->assertSame(0, $this->artisan('xml3176:do-kiem', [
            '--so' => 2, '--tu' => '2026-10-01', '--ghi-ma-lk' => $this->tep('mau.txt'),
            '--xuat-loi' => $this->tep('a.jsonl'),
        ]));
        $ds = array_filter(array_map('trim', explode("\n", file_get_contents($this->tep('mau.txt')))), 'strlen');
        $this->assertCount(2, $ds);

        $this->assertSame(0, $this->artisan('xml3176:do-kiem', [
            '--ma-lk-tep' => $this->tep('mau.txt'), '--ghi-ma-lk' => $this->tep('mau2.txt'),
            '--xuat-loi' => $this->tep('b.jsonl'),
        ]));

        $this->assertSame(file_get_contents($this->tep('mau.txt')), file_get_contents($this->tep('mau2.txt')));
        $this->assertSame(file_get_contents($this->tep('a.jsonl')), file_get_contents($this->tep('b.jsonl')));
    }

    /** @test */
    public function uu_tien_noi_tru_lay_ho_so_noi_tru_truoc()
    {
        Xml3176Xml1::query()->update(['created_at' => '2026-10-05 00:00:00']);

        $this->assertSame(0, $this->artisan('xml3176:do-kiem', [
            '--so' => 1, '--uu-tien-noi-tru' => true, '--ghi-ma-lk' => $this->tep('m.txt'),
        ]));

        $this->assertSame("B2\n", file_get_contents($this->tep('m.txt')));
    }

    /** @test */
    public function ma_loi_bi_tat_is_check_false_khong_co_trong_tep()
    {
        $this->chay(['--xuat-loi' => $this->tep('x.jsonl')]);
        $truoc = $this->docLoi('x.jsonl');
        $this->assertNotEmpty($truoc);
        $ma = $truoc[0]['error_code'];

        DB::table('xml3176_error_catalogs')->insert([
            'xml' => $truoc[0]['xml'], 'error_code' => $ma, 'critical_error' => 0, 'is_check' => 0,
        ]);

        $this->chay(['--xuat-loi' => $this->tep('y.jsonl')]);
        $sau = $this->docLoi('y.jsonl');

        $this->assertNotContains($ma, array_column($sau, 'error_code'));
        $this->assertLessThan(count($truoc), count($sau));
    }

    /** @test */
    public function co_ngoai_le_thi_tra_ma_thoat_2()
    {
        // Bang xml3176_xml12s khong lien quan; bo bang XML5 de checker XML5 nem ngoai le.
        DB::statement('DROP TABLE xml3176_xml5s');

        $this->assertSame(2, $this->chay(['--xuat-loi' => $this->tep('z.jsonl')]));
    }

    /** @test */
    public function xuat_loi_khong_doi_khi_xa_theo_ho_so()
    {
        $this->assertSame(0, $this->chay(['--xuat-loi' => $this->tep('l.jsonl')]));
        $dong = array_values(array_filter(explode("\n", file_get_contents($this->tep('l.jsonl'))), 'strlen'));
        $this->assertNotEmpty($dong);

        // Chuoi ky vong: chinh cac dong do, sap theo khoa cu, noi bang "\n" tuong minh.
        $mang = array_map(function ($d) {
            return json_decode($d, true);
        }, $dong);
        usort($mang, function ($a, $b) {
            $k = function ($d) {
                return [(string) $d['ma_lk'], (string) $d['xml'], (int) $d['stt'], (string) $d['error_code'],
                    (string) $d['description'], (int) $d['critical_error']];
            };
            return $k($a) <=> $k($b);
        });
        $kyVong = '';
        foreach ($mang as $d) {
            $kyVong .= json_encode($d, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
        }
        $this->assertSame($kyVong, file_get_contents($this->tep('l.jsonl')));

        // Ca hai ho so deu co loi va ma_lk A1 dung truoc B2.
        $maLk = array_values(array_unique(array_column($mang, 'ma_lk')));
        $this->assertSame(['A1', 'B2'], $maLk);
    }

    /** @test */
    public function bo_dem_gom_khong_tich_luy()
    {
        $this->assertSame(0, $this->chay(['--xuat-loi' => $this->tep('l.jsonl'), '--xuat-do' => $this->tep('do.txt')]));

        $this->assertSame([], app(\App\Services\Xml3176ErrorService::class)->boDemGom());

        $tong = count($this->docLoi('l.jsonl'));
        $theoHoSo = array_count_values(array_column($this->docLoi('l.jsonl'), 'ma_lk'));
        $lonNhat = max($theoHoSo);
        $this->assertLessThan($tong, $lonNhat);

        $this->assertRegExp('/bo dem toi da: (\d+)/', file_get_contents($this->tep('do.txt')));
        preg_match('/bo dem toi da: (\d+)/', file_get_contents($this->tep('do.txt')), $m);
        // Bo dem tho chua ca loi bi tat, nhung o day khong co ma bi tat nen khong lon hon ho so lon nhat.
        $this->assertLessThanOrEqual($lonNhat, (int) $m[1]);
        $this->assertGreaterThan(0, (int) $m[1]);
    }

    /** @test */
    public function xuat_do_co_thoi_gian_ho_so()
    {
        $this->assertSame(0, $this->chay(['--xuat-do' => $this->tep('do.txt')]));
        $this->assertRegExp('/wall\/ho so: tb [\d.]+ ms, p90 [\d.]+ ms, max [\d.]+ ms/', file_get_contents($this->tep('do.txt')));
    }
}

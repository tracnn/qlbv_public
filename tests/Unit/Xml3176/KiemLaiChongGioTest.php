<?php

namespace Tests\Unit\Xml3176;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\Support\Xml3176RuleTestSupport;
use Tests\TestCase;

/**
 * xml3176:kiem-lai-chong-gio thay ~1 trieu dong loi chong gio theo CAP (truoc 15/10/2026) bang
 * loi theo DONG + loi thoi gian dai bat thuong, khong dung toi loi khac.
 */
class KiemLaiChongGioTest extends TestCase
{
    use Xml3176RuleTestSupport;

    const CHONG = 'XML3_OVERLAPPING_SERVICE_EXECUTION';
    const DAI = 'XML3_EXECUTION_TIME_TOO_LONG';

    protected function setUp()
    {
        parent::setUp();
        $this->bootXml3176Sqlite([
            '2026_01_09_152817_create_xml3176_xml1s_table.php',
            '2026_01_09_152832_create_xml3176_xml3s_table.php',
            '2026_01_09_161708_create_xml3176_error_catalogs_table.php',
            '2026_01_09_161902_create_xml3176_error_results_table.php',
            '2026_10_15_120000_nap_ma_loi_thoi_gian_pttt_dai.php',
        ]);
        DB::table('xml3176_error_catalogs')->insert([
            'xml' => 'XML3', 'error_code' => self::CHONG, 'error_name' => 'x', 'critical_error' => true, 'is_check' => true,
        ]);
    }

    private function hs(string $maLk): void
    {
        DB::table('xml3176_xml1s')->insert(['ma_lk' => $maLk, 'stt' => 1, 'created_at' => '2026-10-01 10:00:00']);
    }

    private function x3(string $maLk, int $stt, string $ma, string $tu, string $den, string $nhom = '8'): void
    {
        DB::table('xml3176_xml3s')->insert([
            'ma_lk' => $maLk, 'stt' => $stt, 'ma_dich_vu' => $ma, 'ma_nhom' => $nhom,
            'ngay_yl' => $tu, 'ngay_th_yl' => $tu, 'ngay_kq' => $den,
        ]);
    }

    private function loiCu(string $maLk, string $code, int $n): void
    {
        for ($i = 0; $i < $n; $i++) {
            DB::table('xml3176_error_results')->insert([
                'xml' => 'XML3', 'ma_lk' => $maLk, 'stt' => 1, 'error_code' => $code,
                'description' => 'cu', 'critical_error' => true,
            ]);
        }
    }

    private function hsMau(): void
    {
        $this->hs('LK1');
        $this->x3('LK1', 1, 'A', '202601010800', '202601010900');
        $this->x3('LK1', 2, 'B', '202601010830', '202601010930');
        $this->x3('LK1', 3, 'C', '202601010845', '202601010850');
        $this->x3('LK1', 4, '02.0150.0114', '202601010800', '202601100800');
        $this->loiCu('LK1', self::CHONG, 3);
        $this->loiCu('LK1', 'XML3_KHAC', 2);
    }

    private function chay(array $opt = []): array
    {
        $ma = Artisan::call('xml3176:kiem-lai-chong-gio', $opt);

        return [$ma, Artisan::output()];
    }

    private function dem(string $code, string $maLk = 'LK1'): int
    {
        return DB::table('xml3176_error_results')->where('ma_lk', $maLk)->where('error_code', $code)->count();
    }

    /** @test */
    public function thay_loi_cap_bang_loi_dong_giu_loi_khac()
    {
        $this->hsMau();

        list($ma, $out) = $this->chay();

        $this->assertSame(0, $ma, $out);
        $this->assertSame(3, $this->dem(self::CHONG));
        $this->assertSame(1, $this->dem(self::DAI));
        $this->assertSame(2, $this->dem('XML3_KHAC'));

        $dong = DB::table('xml3176_error_results')->where('error_code', self::CHONG)->orderBy('stt')->get();
        $this->assertSame([1, 2, 3], $dong->pluck('stt')->map(function ($v) { return (int) $v; })->all());
        $this->assertContains('chồng thời gian thực hiện với 2 dịch vụ khác', $dong[0]->description);
        $this->assertSame('202601010800', $dong[0]->ngay_yl);
        $this->assertContains('Số dòng mã cũ đã xoá: 3', $out);
        $this->assertContains('Lỗi mới ' . self::CHONG . ': 3', $out);
        $this->assertContains('Lỗi mới ' . self::DAI . ': 1', $out);
    }

    /** @test */
    public function chay_lai_khong_nhan_doi()
    {
        $this->hsMau();
        $this->chay();
        $this->chay();

        $this->assertSame(3, $this->dem(self::CHONG));
        $this->assertSame(1, $this->dem(self::DAI));
    }

    /** @test */
    public function thu_khong_ghi()
    {
        $this->hsMau();

        list($ma, $out) = $this->chay(['--thu' => true]);

        $this->assertSame(0, $ma);
        $this->assertSame(3, $this->dem(self::CHONG));
        $this->assertSame(0, $this->dem(self::DAI));
        $this->assertContains('Số dòng mã cũ sẽ xoá: 3', $out);
        $this->assertContains('Lỗi mới ' . self::CHONG . ': 3', $out);
    }

    /** @test */
    public function ho_so_het_dong_pttt_van_xoa_loi_cu()
    {
        $this->hs('LK2');
        $this->x3('LK2', 1, 'X', '202601010800', '202601010900', '3');
        $this->loiCu('LK2', self::CHONG, 4);

        $this->chay();

        $this->assertSame(0, $this->dem(self::CHONG, 'LK2'));
    }

    /** @test */
    public function thieu_ma_moi_trong_danh_muc_thi_dung()
    {
        $this->hsMau();
        DB::table('xml3176_error_catalogs')->where('error_code', self::DAI)->delete();

        list($ma, $out) = $this->chay();

        $this->assertSame(1, $ma);
        $this->assertSame(3, $this->dem(self::CHONG), 'chua dung toi ho so nao');
    }
}

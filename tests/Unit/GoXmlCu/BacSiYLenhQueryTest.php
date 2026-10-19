<?php

namespace Tests\Unit\GoXmlCu;

use App\Services\BHYT\BacSiYLenhQuery;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

/**
 * Bao cao "Bac si y lenh" doc bang XML3176 (khong con qd130_*). Loc theo ngay vao/ra/thanh
 * toan phai di qua xml3176_xml1s - xml2/xml3 khong co cac cot do.
 */
class BacSiYLenhQueryTest extends TestCase
{
    private function sql(string $dateType): string
    {
        return str_replace(['`', '"'], '', BacSiYLenhQuery::build($dateType, '2026-09-01', '2026-09-30')->toSql());
    }

    /** @test */
    public function doc_bang_xml3176_xml2_va_xml3()
    {
        $sql = $this->sql('date_yl');

        $this->assertContains('xml3176_xml2s', $sql);
        $this->assertContains('xml3176_xml3s', $sql);
        $this->assertNotContains('qd130_', $sql);
    }

    /** @test */
    public function loc_ngay_ra_qua_xml1()
    {
        $sql = $this->sql('date_out');

        $this->assertContains('xml3176_xml1s', $sql);
        $this->assertContains('ngay_ra', $sql);
        $this->assertNotContains('q.ngay_ra', $sql);
    }

    /** @test */
    public function loc_ngay_yl_khong_cham_xml1()
    {
        $sql = $this->sql('date_yl');

        $this->assertNotContains('xml3176_xml1s', $sql);
        $this->assertContains('q.ngay_yl', $sql);
    }

    /** @test */
    public function ngay_yl_doi_sang_dang_YmdHi()
    {
        $b = BacSiYLenhQuery::build('date_yl', '2026-09-01', '2026-09-30')->getBindings();

        $this->assertSame(['202609010000', '202609302359', '202609010000', '202609302359'], $b);
    }

    private function dungBang()
    {
        config([
            'database.default' => 'mysql',
            'database.connections.mysql' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => ''],
        ]);
        DB::purge('mysql');

        foreach (['xml3176_xml2s', 'xml3176_xml3s'] as $bang) {
            Schema::create($bang, function ($t) {
                $t->increments('id');
                $t->string('ma_lk');
                $t->string('ma_khoa')->nullable();
                $t->string('ma_bac_si')->nullable();
                $t->string('ngay_yl')->nullable();
                $t->timestamps();
            });
        }
        Schema::create('xml3176_xml1s', function ($t) {
            $t->increments('id');
            $t->string('ma_lk');
            $t->string('ngay_vao')->nullable();
            $t->string('ngay_ra')->nullable();
            $t->string('ngay_ttoan')->nullable();
        });
        Schema::create('medical_staffs', function ($t) {
            $t->increments('id');
            $t->string('macchn')->nullable();
            $t->string('ma_khoa')->nullable();
            $t->string('ten_khoa')->nullable();
            $t->string('ho_ten')->nullable();
        });
    }

    /**
     * medical_staffs co nhieu dong cung macchn (unique theo dinh danh + khoa + co so + tu
     * ngay). JOIN thang se nhan so y lenh len theo so dong do.
     */
    /** @test */
    public function bac_si_nhieu_dong_nvyt_khong_bi_dem_trung()
    {
        $this->dungBang();
        DB::table('medical_staffs')->insert([
            ['macchn' => 'BS1', 'ma_khoa' => 'K01', 'ten_khoa' => 'Noi', 'ho_ten' => 'A'],
            ['macchn' => 'BS1', 'ma_khoa' => 'K01', 'ten_khoa' => 'Noi', 'ho_ten' => 'A'],
        ]);
        DB::table('xml3176_xml2s')->insert([
            ['ma_lk' => 'L1', 'ma_bac_si' => 'BS1', 'ngay_yl' => '202609050800'],
            ['ma_lk' => 'L1', 'ma_bac_si' => 'BS1', 'ngay_yl' => '202609050900'],
        ]);
        DB::table('xml3176_xml3s')->insert([
            ['ma_lk' => 'L1', 'ma_bac_si' => 'BS1', 'ngay_yl' => '202609051000'],
            ['ma_lk' => 'L2', 'ma_bac_si' => 'BS1', 'ngay_yl' => '202610010000'],
        ]);

        $rows = BacSiYLenhQuery::build('date_yl', '2026-09-01', '2026-09-30')->get();

        $this->assertCount(1, $rows);
        $this->assertSame(3, (int) $rows[0]->tong_so);
        $this->assertSame('A', $rows[0]->ho_ten);
        $this->assertSame('K01', $rows[0]->ma_khoa);
    }

    /** @test */
    public function loc_ngay_ra_dem_dung_tren_du_lieu()
    {
        $this->dungBang();
        DB::table('xml3176_xml1s')->insert([
            ['ma_lk' => 'L1', 'ngay_ra' => '202609100000'],
            ['ma_lk' => 'L1', 'ngay_ra' => '202609100000'],
            ['ma_lk' => 'L2', 'ngay_ra' => '202610100000'],
        ]);
        DB::table('xml3176_xml3s')->insert([
            ['ma_lk' => 'L1', 'ma_bac_si' => 'BS9', 'ngay_yl' => '202608010000'],
            ['ma_lk' => 'L2', 'ma_bac_si' => 'BS9', 'ngay_yl' => '202609010000'],
        ]);

        $rows = BacSiYLenhQuery::build('date_out', '2026-09-01', '2026-09-30')->get();

        $this->assertCount(1, $rows);
        $this->assertSame(1, (int) $rows[0]->tong_so);
        $this->assertSame('', $rows[0]->ho_ten);
    }

    /** Excel gom theo ma khoa tren y lenh: cung bac si, hai khoa thi hai dong. */
    /** @test */
    public function excel_tach_dong_theo_khoa_tren_y_lenh()
    {
        $this->dungBang();
        DB::table('xml3176_xml2s')->insert([
            ['ma_lk' => 'L1', 'ma_khoa' => 'K01', 'ma_bac_si' => 'BS1', 'ngay_yl' => '202609050800'],
            ['ma_lk' => 'L1', 'ma_khoa' => 'K02', 'ma_bac_si' => 'BS1', 'ngay_yl' => '202609050900'],
        ]);

        $rows = BacSiYLenhQuery::build('date_yl', '2026-09-01', '2026-09-30', 'q.ma_khoa')->get()->sortBy('ma_khoa')->values();

        $this->assertSame(['K01', 'K02'], $rows->pluck('ma_khoa')->all());
    }

    /** @test */
    public function cot_ma_khoa_chon_duoc()
    {
        $sql = str_replace(['`', '"'], '', BacSiYLenhQuery::build('date_yl', '2026-09-01', '2026-09-30', 'q.ma_khoa')->toSql());

        $this->assertContains('q.ma_khoa', $sql);
    }
}

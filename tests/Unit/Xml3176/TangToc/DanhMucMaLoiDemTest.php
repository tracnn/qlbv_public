<?php

namespace Tests\Unit\Xml3176\TangToc;

use App\Models\BHYT\Xml3176ErrorCatalog;
use App\Services\Xml3176\Xml3176BoDem;
use App\Services\Xml3176ErrorService;
use Illuminate\Support\Facades\DB;
use Tests\Support\Xml3176RuleTestSupport;
use Tests\TestCase;

class DanhMucMaLoiDemTest extends TestCase
{
    use Xml3176RuleTestSupport;

    protected function setUp()
    {
        parent::setUp();
        $this->bootXml3176Sqlite([
            '2026_01_09_161708_create_xml3176_error_catalogs_table.php',
        ]);
    }

    protected function tearDown()
    {
        Xml3176BoDem::xoaTatCa();
        parent::tearDown();
    }

    /** @test */
    public function muc_chan_doc_tu_bo_dem_mot_truy_van()
    {
        foreach (['A', 'B', 'C', 'D', 'E'] as $i => $ma) {
            Xml3176ErrorCatalog::create(['xml' => 'XML1', 'error_code' => $ma, 'critical_error' => $i % 2 == 0, 'is_check' => true]);
        }
        $soTruyVan = 0;
        DB::listen(function () use (&$soTruyVan) {
            $soTruyVan++;
        });

        $dv = new Xml3176ErrorService();
        for ($i = 0; $i < 50; $i++) {
            $ma = ['A', 'B', 'C', 'D', 'E'][$i % 5];
            $this->assertSame($i % 5 % 2 == 0, $dv->getCriticalErrorStatus($ma));
        }
        $this->assertLessThanOrEqual(1, $soTruyVan);
    }

    /** @test */
    public function ma_chua_co_tra_true()
    {
        $this->assertTrue((new Xml3176ErrorService())->getCriticalErrorStatus('KHONG_CO'));
    }

    /** @test */
    public function create_or_update_khong_de_muc_chan_admin()
    {
        Xml3176ErrorCatalog::create(['xml' => 'XML1', 'error_code' => 'A', 'error_name' => 'cu', 'critical_error' => false]);

        Xml3176ErrorCatalog::createOrUpdate('XML1', 'A', 'moi', true);

        $dong = Xml3176ErrorCatalog::where('error_code', 'A')->first();
        $this->assertEquals(0, $dong->critical_error);
        $this->assertSame('moi', $dong->error_name);
        $this->assertSame('moi', $dong->description);
        $this->assertFalse((new Xml3176ErrorService())->getCriticalErrorStatus('A'));
    }

    /** @test */
    public function create_or_update_tao_moi_dat_muc_chan()
    {
        $dv = new Xml3176ErrorService();
        $this->assertTrue($dv->getCriticalErrorStatus('N')); // nap bang rong vao bo dem

        Xml3176ErrorCatalog::createOrUpdate('XML1', 'N', 'ten', false);
        $this->assertEquals(0, Xml3176ErrorCatalog::where('error_code', 'N')->first()->critical_error);
        $this->assertFalse($dv->getCriticalErrorStatus('N'));

        Xml3176ErrorCatalog::createOrUpdate('XML1', 'M', 'ten', true);
        $this->assertEquals(1, Xml3176ErrorCatalog::where('error_code', 'M')->first()->critical_error);
        $this->assertTrue($dv->getCriticalErrorStatus('M'));
    }

    /** @test */
    public function save_errors_khong_gom_ton_trong_is_check()
    {
        Xml3176ErrorCatalog::create(['xml' => 'XML1', 'error_code' => 'TAT', 'critical_error' => true, 'is_check' => false]);
        Xml3176ErrorCatalog::create(['xml' => 'XML1', 'error_code' => 'BAT', 'critical_error' => true, 'is_check' => true]);
        $dv = new Xml3176ErrorService();

        $loi = function ($ma) {
            return (object) ['error_code' => $ma, 'description' => 'd', 'error_name' => 'n', 'critical_error' => true];
        };

        // Bang ket qua khong co trong SQLite: ma bi tat phai bi bo qua TRUOC khi cham bang do.
        $dv->saveErrors('XML1', 'LK1', 1, collect([$loi('TAT')]));
        $this->assertNull(Xml3176ErrorCatalog::where('error_code', 'TAT')->where('error_name', 'n')->first());

        // Ma dang bat di tiep toi buoc ghi ket qua (loi vi thieu bang) - chung to khong bi bo qua.
        try {
            $dv->saveErrors('XML1', 'LK1', 1, collect([$loi('BAT')]));
            $this->fail('ma dang bat phai di toi buoc ghi ket qua');
        } catch (\Illuminate\Database\QueryException $e) {
            $this->assertContains('xml3176_error_results', $e->getMessage());
        }
    }
}

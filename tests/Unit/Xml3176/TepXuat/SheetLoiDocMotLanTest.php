<?php

namespace Tests\Unit\Xml3176\TepXuat;

use App\Exports\Xml3176ErrorMultiSheetExport;
use App\Exports\Xml3176ErrorSheetExport;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromGenerator;
use Maatwebsite\Excel\Concerns\FromQuery;
use Tests\Support\FakeKhoaDieuTriHis;
use Tests\Support\LocComment;
use Tests\Support\Xml3176RuleTestSupport;
use Tests\TestCase;

/**
 * FromQuery doc theo lo LIMIT/OFFSET: moi lo MySQL chay lai TOAN BO truy van nang (subquery +
 * 4 join + ORDER BY) - do 29/09/2026 moi lo ~7 giay, sheet XML3 ~102 lo. Doc mot lan bang
 * cursor(): 7,8 giay cho ca sheet. Noi dung, thu tu, dinh dang tep KHONG doi.
 */
class SheetLoiDocMotLanTest extends TestCase
{
    use Xml3176RuleTestSupport;
    use LocComment;

    protected function setUp()
    {
        parent::setUp();
        $this->bootXml3176Sqlite([
            '2026_01_09_152817_create_xml3176_xml1s_table.php',
            '2026_01_09_161708_create_xml3176_error_catalogs_table.php',
            '2026_01_09_161902_create_xml3176_error_results_table.php',
            '2026_01_09_162044_create_xml3176_informations_table.php',
        ]);

        foreach (['LK2' => 'Nguyen Van B', 'LK1' => 'Tran Thi A'] as $ma => $ten) {
            DB::table('xml3176_xml1s')->insert([
                'ma_lk' => $ma, 'stt' => 1, 'ho_ten' => $ten, 'ma_khoa' => 'K01',
                'ngay_ttoan' => '202609291000', 'ma_the_bhyt' => 'DN4010112345678',
            ]);
        }
        DB::table('xml3176_error_catalogs')->insert(['xml' => 'XML1', 'error_code' => 'E1', 'error_name' => 'Loi mot']);

        // Chen LECH thu tu de kiem ORDER BY ma_lk, stt, id.
        $dong = function ($ma, $stt, $ma_loi) {
            DB::table('xml3176_error_results')->insert([
                'xml' => 'XML1', 'ma_lk' => $ma, 'stt' => $stt, 'error_code' => $ma_loi,
                'description' => "$ma-$stt-$ma_loi", 'critical_error' => 0,
            ]);
        };
        $dong('LK2', 1, 'E1');
        $dong('LK1', 2, 'E1');
        $dong('LK1', 1, 'E2');
        // Dong cua loai XML khac khong duoc lot vao sheet XML1.
        DB::table('xml3176_error_results')->insert([
            'xml' => 'XML2', 'ma_lk' => 'LK1', 'stt' => 1, 'error_code' => 'X', 'description' => 'xml2', 'critical_error' => 0,
        ]);
    }

    private function loc()
    {
        return [
            'date_from' => '2026-09-29 00:00:00', 'date_to' => '2026-09-29 23:59:59',
            'date_type' => 'date_payment',
        ];
    }

    /** @test */
    public function sheet_la_from_generator_khong_con_from_query()
    {
        $s = new Xml3176ErrorSheetExport('XML1', $this->loc(), []);

        $this->assertInstanceOf(FromGenerator::class, $s);
        $this->assertNotInstanceOf(FromQuery::class, $s,
            'FromQuery doc phan trang OFFSET: ngay 29/09 mat 1.796 giay');
    }

    /** @test */
    public function doc_mot_lan_dung_dong_dung_thu_tu_dung_cot()
    {
        $s = new Xml3176ErrorSheetExport('XML1', $this->loc(), [], new FakeKhoaDieuTriHis());

        $dong = [];
        foreach ($s->generator() as $r) {
            $dong[] = $s->map($r);
        }

        $this->assertCount(3, $dong, 'Chi 3 dong loi cua XML1, khong lan XML2');
        // Thu tu ma_lk, stt, id: LK1/1, LK1/2, LK2/1 - STT chay 1..3.
        // PHP 7.4 + pdo_sqlite tra cot so dang CHUOI ('1') - dung assertEquals cho cot so.
        $this->assertEquals([1, 'XML1', 1, 'LK1', 'K01', null, 'Tran Thi A'], array_slice($dong[0], 0, 7));
        $this->assertSame('LK1-1-E2', $dong[0][15]);
        $this->assertSame('E2', $dong[0][14], 'Ma loi chua co trong danh muc thi hien ma');
        $this->assertEquals([2, 'LK1', 2], [$dong[1][0], $dong[1][3], $dong[1][2]]);
        $this->assertSame('Loi mot', $dong[1][14], 'Co trong danh muc thi hien ten loi');
        $this->assertEquals([3, 'LK2'], [$dong[2][0], $dong[2][3]]);
        $this->assertSame('Cảnh báo', $dong[2][16]);
    }

    /** @test */
    public function bo_xuat_khong_con_tu_dat_gioi_han_thoi_gian()
    {
        // sheets() chay BEN TRONG Excel::store, sau khi job da dat set_time_limit(0). De
        // set_time_limit(1800) o day thi no ghi de lai 30 phut - tren Windows do theo gio
        // thuc, lan xuat ngay lon co the sat 30 phut va bi PHP giet giua chung.
        $ma = $this->maKhongComment(app_path('Exports/Xml3176ErrorMultiSheetExport.php'));

        $this->assertNotContains('set_time_limit', $ma);
        $this->assertNotContains('memory_limit', $ma);
        $this->assertCount(19, (new Xml3176ErrorMultiSheetExport($this->loc(), []))->sheets());
    }
}

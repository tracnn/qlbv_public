<?php

namespace Tests\Unit\Xml3176\TepXuat;

use App\Exports\HeinCardErrorExport;
use App\Exports\Xml3176ErrorMultiSheetExport;
use App\Exports\Xml3176ErrorSheetExport;
use App\Models\CheckBHYT\check_hein_card;
use Illuminate\Support\Facades\DB;
use Tests\Support\FakeKhoaDieuTriHis;
use Tests\Support\Xml3176RuleTestSupport;
use Tests\TestCase;

/**
 * File loi XML3176 gan ho so voi khoa dieu tri cuoi trong HIS (his_treatment.last_department_id):
 * hai cot cuoi 'Ma khoa (HIS)', 'Khoa dieu tri (HIS)'. Cot E 'Ma Khoa' (ma khoa trong XML)
 * giu nguyen.
 */
class SheetLoiKhoaHisTest extends TestCase
{
    use Xml3176RuleTestSupport;

    const K01 = ['ma_khoa' => 'KHIS01', 'ten_khoa' => 'Khoa Noi HIS'];

    protected function setUp()
    {
        parent::setUp();
        $this->bootXml3176Sqlite([
            '2026_01_09_152817_create_xml3176_xml1s_table.php',
            '2026_01_09_161708_create_xml3176_error_catalogs_table.php',
            '2026_01_09_161902_create_xml3176_error_results_table.php',
            '2026_01_09_162044_create_xml3176_informations_table.php',
        ]);
    }

    private function loc()
    {
        return [
            'date_from' => '2026-09-29 00:00:00', 'date_to' => '2026-09-29 23:59:59',
            'date_type' => 'date_payment',
        ];
    }

    /** Moi ho so mot dong xml1 + so dong loi XML1 cho truoc. */
    private function hoSo(array $soLoiTheoMaLk)
    {
        $xml1 = [];
        $loi = [];

        foreach ($soLoiTheoMaLk as $ma => $so) {
            $xml1[] = ['ma_lk' => $ma, 'stt' => 1, 'ho_ten' => 'BN ' . $ma, 'ma_khoa' => 'K01',
                       'ngay_ttoan' => '202609291000'];

            for ($i = 1; $i <= $so; $i++) {
                $loi[] = ['xml' => 'XML1', 'ma_lk' => $ma, 'stt' => $i, 'error_code' => 'E1',
                          'description' => "$ma-$i", 'critical_error' => 0];
            }
        }

        foreach (array_chunk($xml1, 100) as $lo) {
            DB::table('xml3176_xml1s')->insert($lo);
        }
        foreach (array_chunk($loi, 100) as $lo) {
            DB::table('xml3176_error_results')->insert($lo);
        }
    }

    private function docSheet(Xml3176ErrorSheetExport $s)
    {
        $ra = [];

        foreach ($s->generator() as $r) {
            $ra[] = $s->map($r);
        }

        return $ra;
    }

    /** @test */
    public function sheet_loi_them_hai_cot_khoa_his_o_cuoi()
    {
        $this->hoSo(['LK1' => 2, 'LK2' => 1]);
        $s = new Xml3176ErrorSheetExport('XML1', $this->loc(), [], new FakeKhoaDieuTriHis(['LK1' => self::K01]));

        $h = $s->headings();
        $this->assertSame(['Mã khoa (HIS)', 'Khoa điều trị (HIS)'], array_slice($h, -2));
        $this->assertSame('Mã Khoa', $h[4], 'Cot E ma khoa XML giu nguyen');

        $dong = $this->docSheet($s);

        $this->assertCount(3, $dong);
        $this->assertSame(count($h), count($dong[0]));
        $this->assertSame(['KHIS01', 'Khoa Noi HIS'], array_slice($dong[0], -2));
        $this->assertSame(['KHIS01', 'Khoa Noi HIS'], array_slice($dong[1], -2));
        $this->assertSame([null, null], array_slice($dong[2], -2), 'Khong co trong HIS thi de trong');
        $this->assertSame('K01', $dong[0][4]);
    }

    /**
     * Gom 1000 dong mot lan tra HIS: bo nho chi giu mot lo, va khong moi dong mot truy van.
     */
    /** @test */
    public function sheet_loi_tra_his_theo_lo_1000_dong()
    {
        $ma = [];
        for ($i = 1; $i <= 1001; $i++) {
            $ma[sprintf('LK%04d', $i)] = 1;
        }
        $this->hoSo($ma);

        $his = new FakeKhoaDieuTriHis();
        $dong = $this->docSheet(new Xml3176ErrorSheetExport('XML1', $this->loc(), [], $his));

        $this->assertCount(1001, $dong);
        $this->assertSame([1000, 1], array_map('count', $his->cacLo));
    }

    /**
     * Laravel Excel doc generator bang new Collection(...) = iterator_to_array GIU KHOA. Neu
     * moi lo yield lai khoa 0..999 thi lo sau GHI DE lo truoc: do that 05/10/2026, sheet XML3
     * 64.392 dong ra tep chi con 1.000 dong - khong bao loi gi.
     */
    /** @test */
    public function generator_khong_lap_khoa_giua_cac_lo()
    {
        $ma = [];
        for ($i = 1; $i <= 1001; $i++) {
            $ma[sprintf('LK%04d', $i)] = 1;
        }
        $this->hoSo($ma);

        $s = new Xml3176ErrorSheetExport('XML1', $this->loc(), [], new FakeKhoaDieuTriHis());

        $this->assertCount(1001, iterator_to_array($s->generator(), true));
    }

    /** @test */
    public function sheet_loi_mat_ket_noi_his_van_xuat_va_ghi_ro_loi()
    {
        $this->hoSo(['LK1' => 1]);
        $his = new FakeKhoaDieuTriHis([], new \RuntimeException('ORA-12541'));

        $dong = $this->docSheet(new Xml3176ErrorSheetExport('XML1', $this->loc(), [], $his));

        $this->assertCount(1, $dong);
        $this->assertSame(['Lỗi tra HIS', 'Lỗi tra HIS'], array_slice($dong[0], -2));
    }

    /** @test */
    public function sheet_the_xml3176_them_hai_cot_khoa_his_o_cuoi()
    {
        $his = new FakeKhoaDieuTriHis(['LK1' => self::K01]);
        $x = new HeinCardErrorExport('2026-09-01 00:00:00', '2026-09-30 23:59:59', null, 'xml3176', true, $his);

        $h = $x->headings();
        $this->assertSame(['Mã khoa (HIS)', 'Khoa điều trị (HIS)'], array_slice($h, -2));
        $this->assertSame('Mã Khoa', $h[2]);

        $rows = collect([
            (new check_hein_card())->forceFill(['ma_lk' => 'LK1']),
            (new check_hein_card())->forceFill(['ma_lk' => 'LK2']),
        ]);
        $this->assertSame($rows, $x->prepareRows($rows));

        $d1 = $x->map($rows[0]);
        $this->assertSame(count($h), count($d1));
        $this->assertSame(['KHIS01', 'Khoa Noi HIS'], array_slice($d1, -2));
        $this->assertSame([null, null], array_slice($x->map($rows[1]), -2));
        $this->assertCount(1, $his->cacLo);
    }

    /** File cua man QD130 dung chung lop nay - khong duoc doi, va khong duoc cham HIS. */
    /** @test */
    public function sheet_the_che_do_qd130_khong_doi_va_khong_tra_his()
    {
        $his = new FakeKhoaDieuTriHis(['LK1' => self::K01]);
        $x = new HeinCardErrorExport('2026-09-01 00:00:00', '2026-09-30 23:59:59', null, 'qd130xml', false, $his);

        $this->assertSame(['STT', 'Mã điều trị', 'Mã kiểm tra', 'Mã kết quả', 'Ghi chú', 'Mã thẻ'], $x->headings());

        $rows = collect([(new check_hein_card())->forceFill(['ma_lk' => 'LK1'])]);
        $x->prepareRows($rows);

        $this->assertCount(6, $x->map($rows[0]));
        $this->assertSame([], $his->cacLo);
    }

    /** 17 sheet dung chung MOT bo tra HIS: ho so loi o nhieu sheet chi tra mot lan. */
    /** @test */
    public function bo_xuat_dung_chung_mot_bo_tra_his_cho_17_sheet()
    {
        $sheets = (new Xml3176ErrorMultiSheetExport($this->loc(), []))->sheets();

        $doc = function ($sheet) {
            $p = new \ReflectionProperty($sheet, 'khoaHis');
            $p->setAccessible(true);

            return $p->getValue($sheet);
        };

        $dau = $doc($sheets[0]);
        $this->assertNotNull($dau);

        for ($i = 0; $i <= 16; $i++) {
            $this->assertSame($dau, $doc($sheets[$i]), "sheet $i");
        }
    }
}

<?php

namespace Tests\Unit\ExcelLuong;

use App\Models\BHYT\Xml3176ErrorResult;
use App\Services\ExcelLuong\NguonSheet;
use Illuminate\Support\Facades\DB;
use Tests\Support\SheetGia;
use Tests\Support\Xml3176RuleTestSupport;
use Tests\TestCase;

class NguonSheetTest extends TestCase
{
    use Xml3176RuleTestSupport;

    /** Lop export FromQuery gia: query() cho truoc, ghi lai cac lo prepareRows. */
    private function xuatTruyVan($query)
    {
        return new class($query) {
            public $q;
            public $cacLo = [];

            public function __construct($q)
            {
                $this->q = $q;
            }

            public function query()
            {
                return $this->q;
            }

            public function prepareRows($rows)
            {
                $this->cacLo[] = count($rows);

                return $rows;
            }

            public function map($r): array
            {
                return [(int) $r->id, $r->ma_lk];
            }

            public function headings(): array
            {
                return ['ID', 'Ma LK'];
            }

            public function title(): string
            {
                return 'Gia';
            }
        };
    }

    private function bangLoi(array $ids)
    {
        $this->bootXml3176Sqlite(['2026_01_09_161902_create_xml3176_error_results_table.php']);

        foreach (array_chunk($ids, 100) as $lo) {
            DB::table('xml3176_error_results')->insert(array_map(function ($id) {
                return ['id' => $id, 'xml' => 'XML1', 'ma_lk' => 'LK' . $id, 'stt' => 1,
                        'error_code' => 'E', 'description' => 'd', 'critical_error' => 0];
            }, $lo));
        }
    }

    /** @test */
    public function generator_di_qua_map_va_khoa_tang_dan()
    {
        $n = new NguonSheet(new SheetGia('S', ['A'], [[1], [2], [3]]));

        $this->assertSame([[1], [2], [3]], iterator_to_array($n->dong(), true));
        $this->assertSame('S', $n->ten());
        $this->assertSame(['A'], $n->tieuDe());
    }

    /**
     * Duong cu FromQuery dung chunk(): tu orderBy khoa chinh khi truy van chua co thu tu.
     * cursor() thi khong - phai tu them, khong thi thu tu dong khac tep cu.
     */
    /** @test */
    public function truy_van_chua_co_thu_tu_thi_sap_theo_khoa_chinh()
    {
        $this->bangLoi([3, 1, 2]);
        $x = $this->xuatTruyVan(Xml3176ErrorResult::query()->select('id', 'ma_lk'));

        $ra = iterator_to_array((new NguonSheet($x))->dong(), true);

        $this->assertSame([[1, 'LK1'], [2, 'LK2'], [3, 'LK3']], $ra);
    }

    /** @test */
    public function truy_van_da_co_thu_tu_thi_giu()
    {
        $this->bangLoi([3, 1, 2]);
        $x = $this->xuatTruyVan(Xml3176ErrorResult::query()->select('id', 'ma_lk')->orderByDesc('id'));

        $this->assertSame([3, 2, 1], array_column(iterator_to_array((new NguonSheet($x))->dong(), true), 0));
    }

    /** @test */
    public function prepare_rows_mot_lan_moi_lo_1000()
    {
        $this->bangLoi(range(1, 2500));
        $x = $this->xuatTruyVan(Xml3176ErrorResult::query()->select('id', 'ma_lk'));

        $ra = iterator_to_array((new NguonSheet($x))->dong(), true);

        $this->assertCount(2500, $ra, 'Khong duoc mat dong giua cac lo');
        $this->assertSame([1000, 1000, 500], $x->cacLo);
    }

    /** @test */
    public function dinh_dang_gop_mac_dinh()
    {
        $n = new NguonSheet(new SheetGia('S', ['A'], [], ['xuong_dong' => true]));

        $this->assertSame([
            'do_rong' => [], 'cot_so' => [], 'kieu_o' => 'tu_dong',
            'xuong_dong' => true, 'tieu_de_can_giua' => false,
        ], $n->dinhDang());
    }
}

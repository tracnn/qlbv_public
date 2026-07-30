<?php

namespace Tests\Unit\Import;

use Tests\TestCase;
use Tests\Support\LocComment;
use App\Imports\CatalogChunkImport;

class DocTheoLoTest extends TestCase
{
    use LocComment;

    /** @test */
    public function lop_nhap_cai_dat_doc_theo_lo()
    {
        $this->assertInstanceOf(
            \Maatwebsite\Excel\Concerns\WithChunkReading::class,
            new CatalogChunkImport()
        );
    }

    /**
     * @test
     *
     * Co lo phai du LON de khong doc lai tep theo binh phuong, va du NHO de khong vo bo nho.
     *
     * Maatwebsite mo lai tep cho MOI lo: lo cang nho thi cang nhieu lan phan tich lai ca
     * sheet + sharedStrings. Do tren tep ICD that (52.551 dong x 15 cot, 29 MB XML + 5,8 MB
     * sharedStrings), chi tinh phan doc:
     *
     *   co lo  1.000 -> 346 giay, dinh 128 MB
     *   co lo  5.000 ->  67 giay, dinh 102 MB
     *   co lo 20.000 ->  26 giay, dinh 226 MB
     *
     * May chu dat PHP 128 MB / 120 giay: lo 1.000 chet ca hai dau, lo 20.000 chet vi bo nho.
     */
    public function co_lo_can_bang_giua_bo_nho_va_so_lan_doc_lai_tep()
    {
        $co = (new CatalogChunkImport())->chunkSize();

        $this->assertGreaterThanOrEqual(5000, $co,
            'Lo qua nho: tep ICD 52.551 dong mat 346 giay chi de doc, vuot gioi han 120 giay');
        $this->assertLessThanOrEqual(5000, $co,
            'Lo qua lon: dinh bo nho 226 MB voi lo 20.000, vuot gioi han 128 MB');
    }

    /** @test */
    public function bao_dung_so_dong_excel_va_lo_dau()
    {
        $goi = [];

        $imp = new CatalogChunkImport(function ($rows, $dongDau, $laLoDau) use (&$goi) {
            $goi[] = ['so' => $rows->count(), 'dong_dau' => $dongDau, 'lo_dau' => $laLoDau];
        });

        $imp->collection(collect(array_fill(0, 1000, ['x'])));
        $imp->collection(collect(array_fill(0, 250, ['x'])));

        $this->assertSame(
            [
                ['so' => 1000, 'dong_dau' => 1, 'lo_dau' => true],
                ['so' => 250, 'dong_dau' => 1001, 'lo_dau' => false],
            ],
            $goi
        );

        $this->assertSame(1250, $imp->soDongDaDoc());
    }

    /** @test */
    public function catalog_import_service_khong_con_doc_ca_tep_mot_lan()
    {
        // Phai bo chu thich truoc khi tim: ten ham van duoc nhac trong chu thich
        // giai thich VI SAO khong dung no nua.
        $ma = $this->maKhongComment(app_path('Services/CatalogImportService.php'));

        $this->assertNotContains('Excel::toCollection', $ma,
            'Van con doc ca tep mot lan - dinh bo nho 208 MB voi tep 1,3 MB');
    }
}

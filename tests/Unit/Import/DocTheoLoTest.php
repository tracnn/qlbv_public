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

    /** @test */
    public function co_lo_du_nho_de_khong_vo_bo_nho()
    {
        // Tep 10.000 dong x 23 cot lam dinh bo nho 208 MB khi doc mot lan.
        $co = (new CatalogChunkImport())->chunkSize();

        $this->assertGreaterThan(0, $co);
        $this->assertLessThanOrEqual(2000, $co);
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

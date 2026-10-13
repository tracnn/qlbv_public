<?php

namespace Tests\Unit\Xml3176\VatPhamKemDvkt;

use App\Services\Xml3176\VatPhamKemDvktRule;
use Tests\TestCase;

class VatPhamKemDvktSeederTest extends TestCase
{
    /** @test */
    public function seeder_khai_du_4_ma_loi_dung_ten_cua_quy_tac()
    {
        $nguon = file_get_contents(database_path('seeds/Xml3176ErrorCatalogVatPhamKemDvktSeeder.php'));

        foreach (VatPhamKemDvktRule::TEN as $key => $ten) {
            $this->assertContains("'XMLComplete_{$key}'", $nguon);
            $this->assertContains($ten, $nguon);
        }
        $this->assertContains("'XMLComplete'", $nguon);
        $this->assertContains("'critical_error' => true", $nguon);
    }
}

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
        // Muc critical theo tung ma: 4 ma cu nghiem trong, R3 chi canh bao
        $mucCritical = [
            'VTYT_KHONG_CO_DVKT_ME' => 'true',
            'THUOC_KHONG_CO_DVKT_ME' => 'true',
            'GOI_VTYT_KHONG_KHOP' => 'true',
            'THUOC_DVKT_ME_KHONG_KHOP' => 'true',
            'DVKT_THIEU_VAT_PHAM_KEM' => 'false',
        ];
        $this->assertSame(array_keys($mucCritical), array_keys(VatPhamKemDvktRule::TEN));
        foreach ($mucCritical as $key => $muc) {
            $this->assertRegExp("/'XMLComplete_{$key}'[^\]]*,\s*{$muc}\s*\]/s", $nguon, "{$key} phai co critical = {$muc}");
        }
        $this->assertContains("'critical_error' => \$critical", $nguon);
    }
}

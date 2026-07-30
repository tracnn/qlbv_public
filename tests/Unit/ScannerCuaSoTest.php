<?php

namespace Tests\Unit;

use Tests\TestCase;
use Tests\Support\LocComment;

/**
 * Chan hai kieu thut lui:
 *  - scanner khong con truyen cua so xuong truy van (mat het loi ich hieu nang);
 *  - scanner tu day moc bang tay thay vi dung CuaSoQuet (de tai lap cai bay cua so rong
 *    khien bo quet dung im vinh vien).
 */
class ScannerCuaSoTest extends TestCase
{
    use LocComment;

    protected function ma($ten)
    {
        // Bo comment truoc khi quet: mot chuoi nam trong comment se lam test xanh gia.
        return $this->maKhongComment(
            base_path('app/Services/OrderCheck/Scanners/' . $ten . '.php')
        );
    }

    /** @test */
    public function ba_scanner_deu_dung_cua_so_quet()
    {
        foreach (['ServiceRestrictionScanner', 'MedicineScanner', 'InteractionLogScanner'] as $s) {
            $ma = $this->ma($s);

            $this->assertContains('CuaSoQuet::ketThuc', $ma, "$s khong tinh cuoi cua so");
            $this->assertContains('CuaSoQuet::mocMoi', $ma, "$s khong dung CuaSoQuet de day moc");
        }
    }

    /** @test */
    public function scanner_gioi_han_dv_bo_qua_khi_danh_muc_rong()
    {
        $ma = $this->ma('ServiceRestrictionScanner');

        // exists() chu khong count(): chi can biet co hay khong.
        $this->assertContains('exists()', $ma,
            'Khong thay phep kiem danh muc rong bang exists()');
    }
}

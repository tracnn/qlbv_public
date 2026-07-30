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

    /**
     * Canh gac chinh cai bay ma task nay sinh ra de chua: neu saveWatermark() bi dua
     * TRO LAI vao trong khoi if ($scanned > 0), cua so rong se khong duoc day moc va
     * bo quet dung im vinh vien - im lang, khong loi nao bao ra. Hai test o tren van
     * xanh trong tinh huong do vi chung chi kiem CHUOI CO MAT, khong kiem VI TRI.
     *
     * @test
     */
    public function saveWatermark_nam_ngoai_khoi_if_scanned()
    {
        foreach (['ServiceRestrictionScanner', 'MedicineScanner', 'InteractionLogScanner'] as $s) {
            $ma = $this->ma($s);

            $posDongKhoiIf = $this->viTriDongKhoiIfScanned($ma, $s);

            // Tim tu VI TRI DONG KHOI TRO DI: ServiceRestrictionScanner co them mot
            // saveWatermark KHAC o nhanh bo qua danh muc rong (nam TRUOC khoi if nay), nen
            // khong the lay occurrence dau tien trong ca file - phai loai no ra bang offset.
            $posSaveWatermarkSauKhoi = strpos($ma, 'saveWatermark', $posDongKhoiIf);

            $this->assertNotFalse(
                $posSaveWatermarkSauKhoi,
                "$s: khong co saveWatermark nao sau khi khoi if (\$scanned > 0) dong lai - day chinh la bay cua so rong (khong day moc khi cua so rong -> dung im vinh vien)."
            );
        }
    }

    /**
     * Tra ve vi tri (offset) cua dau '}' dong khoi "if ($scanned > 0) {" trong $ma, bang
     * cach dem do sau ngoac nhon tu dau khoi. Ben trong khoi nay co the co nhieu ngoac
     * nhon long nhau (foreach, if long...) nen khong the lay dau '}' DAU TIEN gap phai.
     */
    protected function viTriDongKhoiIfScanned($ma, $ten)
    {
        $needle = 'if ($scanned > 0) {';
        $posIf = strpos($ma, $needle);
        $this->assertNotFalse($posIf, "$ten khong tim thay khoi if (\$scanned > 0)");

        $posMoNgoac = $posIf + strlen($needle) - 1; // vi tri dau '{' mo khoi
        $doSau = 0;
        $doDai = strlen($ma);

        for ($i = $posMoNgoac; $i < $doDai; $i++) {
            if ($ma[$i] === '{') {
                $doSau++;
            } elseif ($ma[$i] === '}') {
                $doSau--;
                if ($doSau === 0) {
                    return $i;
                }
            }
        }

        $this->fail("$ten: khong tim thay dau dong ngoac tuong ung cua khoi if (\$scanned > 0)");
    }
}

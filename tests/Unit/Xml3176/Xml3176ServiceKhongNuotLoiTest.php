<?php

namespace Tests\Unit\Xml3176;

use Tests\TestCase;

class Xml3176ServiceKhongNuotLoiTest extends TestCase
{
    /** @test */
    public function moi_khoi_catch_trong_cac_ham_luu_deu_nem_lai_loi()
    {
        // Nuot loi o day nghia la: mot dong hong bi bo, cac dong khac van ghi, va nguoi
        // dung nhan "processed successfully" trong khi ho so thieu du lieu. Voi so lieu
        // thanh toan BHYT thi ho so thieu dong duoc xuat len BHXH la sai quyet toan.
        $lines = file(app_path('Services/Xml3176Service.php'));

        $fn = '';
        $viPham = [];

        foreach ($lines as $i => $l) {
            if (preg_match('/^    (public|protected|private) function (\w+)/', $l, $m)) {
                $fn = $m[2];
            }

            if (strpos($l, 'catch (\Exception $e) {') === false) {
                continue;
            }

            if (strpos($fn, 'storeXml3176') !== 0) {
                continue;   // ngoai luong import - khong thuoc pham vi
            }

            $than = '';
            for ($j = $i + 1; $j < min($i + 8, count($lines)); $j++) {
                if (trim($lines[$j]) === '}') {
                    break;
                }
                $than .= $lines[$j];
            }

            if (strpos($than, 'throw') === false) {
                $viPham[] = 'dong ' . ($i + 1) . ' trong ' . $fn;
            }
        }

        $this->assertEmpty(
            $viPham,
            "Cac khoi catch sau van nuot loi:\n" . implode("\n", $viPham)
        );
    }

    /** @test */
    public function hang_rao_that_su_dem_duoc_cac_khoi_catch()
    {
        // Chung minh phep kiem tren khong rong: neu regex hong thi no se khong tim thay
        // khoi nao va tu dong xanh ma khong kiem gi.
        $src = file_get_contents(app_path('Services/Xml3176Service.php'));

        $this->assertGreaterThanOrEqual(
            15,
            substr_count($src, 'catch (\Exception $e) {'),
            'Khong con du 15 khoi catch trong luong import - hang rao co the dang vo dung'
        );
    }
}

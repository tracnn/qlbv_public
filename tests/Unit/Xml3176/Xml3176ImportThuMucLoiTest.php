<?php

namespace Tests\Unit\Xml3176;

use Tests\TestCase;

class Xml3176ImportThuMucLoiTest extends TestCase
{
    const THU_MUC_LOI = 'loi/';

    /** @test */
    public function file_hong_duoc_chuyen_sang_thu_muc_loi()
    {
        $src = file_get_contents(app_path('Console/Commands/XML3176Import.php'));

        $this->assertContains(self::THU_MUC_LOI, $src,
            'File hong van nam lai thu muc quet, se duoc thu lai moi 3 giay');
        $this->assertContains('->move(', $src);
    }

    /** @test */
    public function thu_muc_loi_bi_loai_khoi_luot_quet()
    {
        // Storage::allFiles() quet DE QUY. Khong bo qua loi/ thi file hong lai duoc
        // nhat len lan nua - dung cai vong lap ma task nay muon cat.
        $src = file_get_contents(app_path('Console/Commands/XML3176Import.php'));

        $this->assertContains('starts_with', $src,
            'Khong thay phep loai thu muc loi khoi luot quet');
    }
}

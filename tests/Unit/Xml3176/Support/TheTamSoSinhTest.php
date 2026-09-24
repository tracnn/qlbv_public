<?php

namespace Tests\Unit\Xml3176\Support;

use App\Services\Xml3176\Support\TheTamSoSinh;
use Tests\TestCase;

/**
 * The tam cap cho tre so sinh: HIS dien noi DKBD dang XX000 (ma tinh + 000) - khong phai
 * CSKCB that. Tra cong BHXH ra "The khong ton tai", tra danh muc CSKCB ra "khong co" -
 * ca hai deu la loi gia. Tren HIS tu 07/2026: 1.817 luot XX000, 1.815 the TE1.
 */
class TheTamSoSinhTest extends TestCase
{
    /** @test */
    public function the_te1_co_dkbd_xx000_la_the_tam()
    {
        $this->assertTrue(TheTamSoSinh::la('TE1010000012345', '01000'));
        $this->assertTrue(TheTamSoSinh::la('TE1373700012345', '37000'));
        $this->assertTrue(TheTamSoSinh::la(' TE1010000012345 ', ' 01000 '));
    }

    /** @test */
    public function the_khac_te1_du_dkbd_xx000_khong_phai_the_tam()
    {
        // 2 luot tren HIS co XX000 nhung the TR1, HT3 - van phai tra cong.
        $this->assertFalse(TheTamSoSinh::la('TR1010000012345', '01000'));
        $this->assertFalse(TheTamSoSinh::la('HT3010000012345', '01000'));
    }

    /** @test */
    public function dkbd_khong_dang_xx000_khong_phai_the_tam()
    {
        $this->assertFalse(TheTamSoSinh::la('TE1010000012345', '01001'));
        $this->assertFalse(TheTamSoSinh::la('TE1010000012345', '010000'));
        $this->assertFalse(TheTamSoSinh::la('TE1010000012345', '1000'));
        $this->assertFalse(TheTamSoSinh::la('TE1010000012345', ''));
        $this->assertFalse(TheTamSoSinh::la('TE1010000012345', null));
    }

    /** @test */
    public function thieu_ma_the_khong_phai_the_tam()
    {
        $this->assertFalse(TheTamSoSinh::la(null, '01000'));
        $this->assertFalse(TheTamSoSinh::la('', '01000'));
    }

    /** @test */
    public function doc_mau_tu_cau_hinh()
    {
        config(['xml3176.the_tam_so_sinh' => ['dkbd_pattern' => '/^\d{2}000$/', 'tien_to_the' => ['TE1', 'TR1']]]);
        $this->assertTrue(TheTamSoSinh::la('TR1010000012345', '01000'));
    }
}

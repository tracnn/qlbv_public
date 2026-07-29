<?php

namespace Tests\Unit;

use App\Services\OrderCheck\Support\MaBhytDong;
use Tests\TestCase;

class MaBhytDongTest extends TestCase
{
    /** @test */
    public function co_ma_hoat_chat_thi_uu_tien_ma_hoat_chat()
    {
        // Dong THUOC: ma BHYT nam o his_medicine_type.active_ingr_bhyt_code, khong phai
        // o his_service.hein_service_bhyt_code.
        $this->assertSame('40.12', MaBhytDong::cua('40.12', 'DV001'));
    }

    /** @test */
    public function khong_co_ma_hoat_chat_thi_lay_ma_dich_vu()
    {
        // Dong VAT TU va DVKT khong join ra duoc danh muc thuoc nen roi ve ma dich vu.
        $this->assertSame('DV001', MaBhytDong::cua(null, 'DV001'));
        $this->assertSame('DV001', MaBhytDong::cua('', 'DV001'));
    }

    /** @test */
    public function ca_hai_rong_thi_tra_chuoi_rong()
    {
        // Tra CHUOI RONG chu khong phai null: BhytCodeMissingRule kiem
        // trim((string) $s->bhytCode) !== '' nen hai dang phai cho cung ket qua.
        $this->assertSame('', MaBhytDong::cua(null, null));
        $this->assertSame('', MaBhytDong::cua('', ''));
    }

    /** @test */
    public function cat_khoang_trang_hai_dau()
    {
        $this->assertSame('40.12', MaBhytDong::cua('  40.12  ', null));
        $this->assertSame('DV001', MaBhytDong::cua(null, "\tDV001\n"));
    }

    /** @test */
    public function ma_hoat_chat_chi_gom_khoang_trang_thi_coi_nhu_rong()
    {
        $this->assertSame('DV001', MaBhytDong::cua('   ', 'DV001'));
    }
}

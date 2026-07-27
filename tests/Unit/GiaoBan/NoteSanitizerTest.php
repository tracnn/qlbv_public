<?php

namespace Tests\Unit\GiaoBan;

use Tests\TestCase;
use App\Services\GiaoBan\NoteSanitizer;

class NoteSanitizerTest extends TestCase
{
    /** @test */
    public function keeps_basic_formatting()
    {
        $out = NoteSanitizer::clean('<b>đậm</b> <span style="color:#ff0000">đỏ</span><ul><li>a</li></ul>');
        $this->assertContains('<b>đậm</b>', $out);
        $this->assertContains('color', $out);
        $this->assertContains('<li>a</li>', $out);
    }

    /** @test */
    public function strips_script_and_event_handlers_and_iframe()
    {
        $out = NoteSanitizer::clean('<b>ok</b><script>alert(1)</script><img src=x onerror=alert(1)><iframe src="x"></iframe>');
        $this->assertNotContains('<script', $out);
        $this->assertNotContains('onerror', $out);
        $this->assertNotContains('<iframe', $out);
        $this->assertContains('<b>ok</b>', $out);
    }

    /** @test */
    public function null_or_empty_returns_empty_string()
    {
        $this->assertSame('', NoteSanitizer::clean(null));
        $this->assertSame('', NoteSanitizer::clean('   '));
    }

    // ===== cleanPlain: van ban thuan cho chi tieu nhap tay kieu chuoi =====

    /** Giai ma nguoc lai giong het textarea cua trinh duyet lam khi nap gia tri. */
    protected function giaiMa($s)
    {
        return htmlspecialchars_decode($s, ENT_QUOTES);
    }

    /** @test */
    public function cleanPlain_vo_hieu_hoa_the_chay_duoc()
    {
        $out = NoteSanitizer::cleanPlain('<script>alert(1)</script><img src=x onerror=alert(1)>');

        $this->assertNotContains('<script', $out);
        $this->assertNotContains('<img', $out);
        // noi dung van con, chi la khong con la the
        $this->assertContains('alert(1)', $out);
    }

    /** @test */
    public function cleanPlain_giu_nguyen_nghia_chi_so_lam_sang_sau_khi_giai_ma()
    {
        $goc = 'Hb < 8 g/dL, HA > 140/90';
        $luu = NoteSanitizer::cleanPlain($goc);

        $this->assertNotContains('<', $luu);           // trong DB khong con dau nhon
        $this->assertEquals($goc, $this->giaiMa($luu)); // nhung nghia thi nguyen ven
    }

    /** @test */
    public function cleanPlain_chuan_hoa_xuong_dong_va_giu_so_dong()
    {
        $out = NoteSanitizer::cleanPlain("SP Tham\r\nSP Huyen\rSP Lan");

        $this->assertNotContains("\r", $out);
        $this->assertEquals(3, count(explode("\n", $out)));
    }

    /** @test */
    public function cleanPlain_bo_ky_tu_dieu_khien_nhung_giu_xuong_dong_va_tab()
    {
        $out = NoteSanitizer::cleanPlain("a\x00b\x07c\nd\te");

        $this->assertEquals("abc\nd\te", $out);
    }

    /** @test */
    public function cleanPlain_cat_o_5000_ky_tu()
    {
        $out = NoteSanitizer::cleanPlain(str_repeat('a', 6000));

        $this->assertEquals(5000, mb_strlen($out));
    }

    /** @test */
    public function cleanPlain_rong_hoac_null_tra_chuoi_rong()
    {
        $this->assertSame('', NoteSanitizer::cleanPlain(null));
        $this->assertSame('', NoteSanitizer::cleanPlain(''));
    }

    /**
     * Test quan trong nhat cua nhom nay: vong luu -> nap vao textarea -> luu lai
     * khong duoc lam hong dan van ban (`&lt;` thanh `&amp;lt;`).
     *
     * @test
     */
    public function cleanPlain_khong_ma_hoa_kep_qua_nhieu_lan_luu()
    {
        $goc = "SP Tham: Thai 39 tuan\nHb < 8 g/dL & HA > 140\n<b>Nang</b>";

        $lan1 = NoteSanitizer::cleanPlain($goc);
        $lan2 = NoteSanitizer::cleanPlain($this->giaiMa($lan1));
        $lan3 = NoteSanitizer::cleanPlain($this->giaiMa($lan2));

        $this->assertEquals($lan1, $lan2);
        $this->assertEquals($lan1, $lan3);
        $this->assertEquals($goc, $this->giaiMa($lan3));
    }
}

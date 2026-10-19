<?php

namespace Tests\Unit\GoXmlCu;

use Tests\TestCase;

/**
 * getFormattedSuggestion() la nguon goi y o man chi tiet loi XML3176. No phai doc
 * config xml3176_suggestions: xoa config qd130xml_suggestions ma helper con doc file do
 * thi moi goi y IM LANG roi ve 'general', khong ai thay loi.
 */
class GoiYLoiXml3176Test extends TestCase
{
    /** @test */
    public function doc_tu_config_xml3176()
    {
        config(['qd130xml_suggestions' => [], 'xml3176_suggestions.XMLComplete_NVYT_THIEU' => 'X {a}']);

        $this->assertSame('X 1', getFormattedSuggestion('XMLComplete_NVYT_THIEU', ['a' => '1']));
    }

    /** @test */
    public function roi_ve_general_cua_xml3176()
    {
        config(['qd130xml_suggestions.general' => 'cu', 'xml3176_suggestions.general' => 'moi']);

        $this->assertSame('moi', getFormattedSuggestion('KHOA_KHONG_TON_TAI'));
    }

    /** @test */
    public function xml3176_bao_trum_qd130()
    {
        $cu = require base_path('config/qd130xml_suggestions.php');
        $moi = require base_path('config/xml3176_suggestions.php');

        $this->assertSame([], array_values(array_diff(array_keys($cu), array_keys($moi))));
    }
}

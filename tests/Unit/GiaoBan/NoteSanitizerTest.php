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
}

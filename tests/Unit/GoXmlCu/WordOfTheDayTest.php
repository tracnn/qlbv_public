<?php

namespace Tests\Unit\GoXmlCu;

use App\Models\BHYT\Xml3176Xml1;
use App\Models\CheckBHYT\check_hein_card;
use Tests\Support\LocComment;
use Tests\TestCase;

/**
 * word:day chi con gui phan loi the. Phan loi XML 4210 (XmlErrorCheck) da bo; mail lay ma
 * the qua check_hein_card::xml1() nen quan he do phai tro ho so XML3176.
 */
class WordOfTheDayTest extends TestCase
{
    use LocComment;

    /** @test */
    public function quan_he_xml1_tro_xml3176()
    {
        $this->assertInstanceOf(Xml3176Xml1::class, (new check_hein_card())->xml1()->getRelated());
    }

    /** @test */
    public function word_day_khong_con_4210()
    {
        $ma = $this->maKhongComment(app_path('Console/Commands/WordOfTheDay.php'));

        $this->assertNotContains('XmlErrorCheck', $ma);
        $this->assertNotContains('getCheckXmlErrors', $ma);
        $this->assertNotContains('errorsXml', $ma);
    }

    /** @test */
    public function mail_import_render_khong_can_errorsXml()
    {
        $html = view('templates.mail-import', [
            'name' => 'A',
            'from_date' => '202610190000',
            'to_date' => '202610192359',
            'email' => 'a@example.test',
            'errorsHeinCard' => collect(),
        ])->render();

        $this->assertContains('Lỗi thẻ', $html);
        $this->assertNotContains('Lỗi XML', $html);
    }
}

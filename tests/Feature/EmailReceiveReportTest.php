<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\EmailReceiveReport;

/**
 * VI SAO CHI CON MOT TEST: sau test kiem tra luat validate, tep nay tung co 6 test nua
 * ghi/doc that vao co so du lieu qua trait RefreshDatabase.
 *
 * Du an KHONG co .env.testing, va phunit.xml khong ghi de bien DB_*. Nen test chay voi
 * DB_CONNECTION=mysql, DB_DATABASE=qlbv lay thang tu .env - co so du lieu phat trien
 * that. RefreshDatabase goi migrate:fresh, tuc DROP TOAN BO BANG cua qlbv. Chi can ai
 * do chay `vendor/bin/phpunit` mot lan la mat sach du lieu.
 *
 * Sau khi co moi truong test rieng (xem docs/superpowers/plans/2026-08-01-khoi-tao-superadmin.md),
 * co the khoi phuc cac test do tu lich su git. Neu can test cham DB truoc khi co moi
 * truong rieng: tro chinh ket noi ten 'mysql' sang SQLite bo nho roi chay dung migration
 * can thiet - dung ghi de database.default, vi nhieu model ghim cung $connection.
 */
class EmailReceiveReportTest extends TestCase
{
    /**
     * Test validation rules. Ham thuan, khong cham co so du lieu.
     */
    public function test_validation_rules()
    {
        $rules = EmailReceiveReport::getValidationRules();

        $this->assertArrayHasKey('name', $rules);
        $this->assertArrayHasKey('email', $rules);
        $this->assertArrayHasKey('period', $rules);
        $this->assertArrayHasKey('active', $rules);

        $this->assertContains('required', $rules['name']);
        $this->assertContains('required', $rules['email']);
        $this->assertContains('email', $rules['email']);
        $this->assertContains('unique', $rules['email']);
        $this->assertContains('boolean', $rules['period']);
    }
}

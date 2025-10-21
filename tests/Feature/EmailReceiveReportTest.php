<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\EmailReceiveReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;

class EmailReceiveReportTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    /**
     * Test tạo email nhận báo cáo mới
     */
    public function test_can_create_email_receive_report()
    {
        $data = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'active' => true,
            'bcaobhxh' => true,
            'bcaoqtri' => false,
            'qtri_tckt' => true,
            'qtri_hsdt' => false,
            'qtri_dvkt' => false,
            'qtri_canhbao' => true,
            'period' => true
        ];

        $emailReport = EmailReceiveReport::create($data);

        $this->assertDatabaseHas('email_receive_reports', [
            'email' => 'test@example.com',
            'name' => 'Test User'
        ]);

        $this->assertEquals('Test User', $emailReport->name);
        $this->assertEquals('test@example.com', $emailReport->email);
        $this->assertTrue($emailReport->active);
        $this->assertFalse($emailReport->period);
    }

    /**
     * Test validation rules
     */
    public function test_validation_rules()
    {
        $rules = EmailReceiveReport::getValidationRules();

        $this->assertArrayHasKey('name', $rules);
        $this->assertArrayHasKey('email', $rules);
        $this->assertArrayHasKey('period', $rules);
        $this->assertArrayHasKey('active', $rules);

        $this->assertStringContainsString('required', $rules['name']);
        $this->assertStringContainsString('required', $rules['email']);
        $this->assertStringContainsString('email', $rules['email']);
        $this->assertStringContainsString('unique', $rules['email']);
        $this->assertStringContainsString('boolean', $rules['period']);
    }

    /**
     * Test scope active
     */
    public function test_scope_active()
    {
        // Tạo email active
        EmailReceiveReport::create([
            'name' => 'Active User',
            'email' => 'active@example.com',
            'active' => true,
            'period' => true
        ]);

        // Tạo email inactive
        EmailReceiveReport::create([
            'name' => 'Inactive User',
            'email' => 'inactive@example.com',
            'active' => false,
            'period' => true
        ]);

        $activeEmails = EmailReceiveReport::active()->get();

        $this->assertCount(1, $activeEmails);
        $this->assertEquals('active@example.com', $activeEmails->first()->email);
    }

    /**
     * Test scope for report type
     */
    public function test_scope_for_report_type()
    {
        EmailReceiveReport::create([
            'name' => 'BHXH User',
            'email' => 'bhxh@example.com',
            'active' => true,
            'bcaobhxh' => true,
            'period' => true
        ]);

        EmailReceiveReport::create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'active' => true,
            'bcaoqtri' => true,
            'period' => true
        ]);

        $bhxhEmails = EmailReceiveReport::forReportType('bcaobhxh')->get();
        $adminEmails = EmailReceiveReport::forReportType('bcaoqtri')->get();

        $this->assertCount(1, $bhxhEmails);
        $this->assertCount(1, $adminEmails);
        $this->assertEquals('bhxh@example.com', $bhxhEmails->first()->email);
        $this->assertEquals('admin@example.com', $adminEmails->first()->email);
    }

    /**
     * Test scope for special report
     */
    public function test_scope_for_special_report()
    {
        EmailReceiveReport::create([
            'name' => 'Special User',
            'email' => 'special@example.com',
            'active' => true,
            'period' => true
        ]);

        EmailReceiveReport::create([
            'name' => 'Normal User',
            'email' => 'normal@example.com',
            'active' => true,
            'period' => false
        ]);

        $specialEmails = EmailReceiveReport::forSpecialReport()->get();

        $this->assertCount(1, $specialEmails);
        $this->assertEquals('special@example.com', $specialEmails->first()->email);
    }

    /**
     * Test get emails for BHXH report
     */
    public function test_get_emails_for_bhxh_report()
    {
        EmailReceiveReport::create([
            'name' => 'BHXH Normal',
            'email' => 'bhxh-normal@example.com',
            'active' => true,
            'bcaobhxh' => true,
            'period' => false
        ]);

        EmailReceiveReport::create([
            'name' => 'BHXH Special',
            'email' => 'bhxh-special@example.com',
            'active' => true,
            'bcaobhxh' => true,
            'period' => true
        ]);

        $normalEmails = EmailReceiveReport::getEmailsForBHXHReport(false);
        $withSpecialEmails = EmailReceiveReport::getEmailsForBHXHReport(true);

        $this->assertArrayHasKey('BHXH Normal', $normalEmails);
        $this->assertArrayHasKey('BHXH Normal', $withSpecialEmails);
        $this->assertArrayHasKey('BHXH Special', $withSpecialEmails);
        $this->assertEquals('bhxh-normal@example.com', $normalEmails['BHXH Normal']);
        $this->assertEquals('bhxh-normal@example.com', $withSpecialEmails['BHXH Normal']);
        $this->assertEquals('bhxh-special@example.com', $withSpecialEmails['BHXH Special']);
    }

    /**
     * Test soft delete
     */
    public function test_soft_delete()
    {
        $emailReport = EmailReceiveReport::create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'active' => true,
            'period' => false
        ]);

        $emailReport->delete();

        $this->assertSoftDeleted('email_receive_reports', [
            'id' => $emailReport->id
        ]);
    }
}

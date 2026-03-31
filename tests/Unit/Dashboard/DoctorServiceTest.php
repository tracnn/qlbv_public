<?php

namespace Tests\Unit\Dashboard;

use Tests\TestCase;
use App\Services\Dashboard\DoctorService;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class DoctorServiceTest extends TestCase
{
    protected $service;

    protected function setUp()
    {
        parent::setUp();
        $this->service = new DoctorService();
    }

    /** @test */
    public function it_converts_date_range_to_oracle_format()
    {
        $from = '2026-03-01';
        $to   = '2026-03-31';

        $result = $this->service->buildDateRange($from, $to);

        $this->assertEquals('20260301000000', $result['from']);
        $this->assertEquals('20260331235959', $result['to']);
    }

    /** @test */
    public function it_formats_examination_results()
    {
        $rows = [
            (object)['loginname' => 'vck', 'username' => 'VŨ CÔNG KHANH', 'total_exams' => 450, 'total_patients' => 400],
            (object)['loginname' => 'abc', 'username' => 'NGUYỄN VĂN A', 'total_exams' => 200, 'total_patients' => 180],
        ];

        $result = $this->service->formatExaminationRows($rows);

        $this->assertCount(2, $result);
        $this->assertEquals('vck', $result[0]['loginname']);
        $this->assertEquals(450, $result[0]['total_exams']);
    }

    /** @test */
    public function it_formats_revenue_results()
    {
        $rows = [
            (object)['loginname' => 'vck', 'username' => 'VŨ CÔNG KHANH', 'total_revenue' => 125000000, 'total_patients' => 320],
        ];

        $result = $this->service->formatRevenueRows($rows);

        $this->assertEquals(125000000, $result[0]['total_revenue']);
        $this->assertEquals(320, $result[0]['total_patients']);
    }

    /** @test */
    public function it_formats_surgery_results()
    {
        $rows = [
            (object)['loginname' => 'ptv1', 'username' => 'PTV CHÍNH', 'total_surgeries' => 30],
        ];

        $result = $this->service->formatSurgeryRows($rows);

        $this->assertEquals(30, $result[0]['total_surgeries']);
    }
}

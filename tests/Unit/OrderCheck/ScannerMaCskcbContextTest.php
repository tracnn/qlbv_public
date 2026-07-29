<?php

namespace Tests\Unit\OrderCheck;

use Tests\TestCase;
use App\Services\OrderCheck\Scanners\InteractionLogScanner;
use App\Services\OrderCheck\Scanners\MedicineScanner;
use App\Services\OrderCheck\Scanners\ServiceRestrictionScanner;

/**
 * Ba scanner nay dung ViolationContext::make() TRUC TIEP (khong qua
 * ViolationContext::fromOrderContext()), nen phai TU gan ma_cskcb - de quen la
 * vi pham moi im lang bien mat khoi bo loc theo co so.
 *
 * Goi thang ham context() rieng (private, qua Reflection) thay vi chay het scan()
 * vi scan() doc HIS qua Oracle - khong phu hop Unit test.
 */
class ScannerMaCskcbContextTest extends TestCase
{
    private function goiContext($scanner, array $args)
    {
        $ref = new \ReflectionMethod($scanner, 'context');
        $ref->setAccessible(true);
        return $ref->invokeArgs($scanner, $args);
    }

    public function test_medicine_scanner_gan_ma_cskcb_tu_treatment_info()
    {
        $scanner = new MedicineScanner();
        $info = (object) [
            'treatment_code' => 'TC001',
            'tdl_patient_code' => 'BN001',
            'tdl_patient_name' => 'Nguyen Van A',
            'last_department_id' => 5,
            'ma_cskcb' => '01929',
        ];

        $ctx = $this->goiContext($scanner, [123, $info]);

        $this->assertSame('01929', $ctx->maCskcb);
        $this->assertSame(123, $ctx->treatmentId);
    }

    public function test_medicine_scanner_khong_co_info_thi_ma_cskcb_null()
    {
        $scanner = new MedicineScanner();

        $ctx = $this->goiContext($scanner, [123, null]);

        $this->assertNull($ctx->maCskcb);
    }

    public function test_interaction_log_scanner_gan_ma_cskcb_tu_treatment_info()
    {
        $scanner = new InteractionLogScanner();
        $row = (object) [
            'treatment_id' => 456,
            'request_loginname' => 'bs.a',
            'request_department_id' => 7,
        ];
        $info = (object) ['ma_cskcb' => '02345'];

        $ctx = $this->goiContext($scanner, [$row, $info]);

        $this->assertSame('02345', $ctx->maCskcb);
        $this->assertSame(456, $ctx->treatmentId);
    }

    public function test_interaction_log_scanner_khong_co_info_thi_ma_cskcb_null()
    {
        $scanner = new InteractionLogScanner();
        $row = (object) [
            'treatment_id' => 456,
            'request_loginname' => 'bs.a',
            'request_department_id' => 7,
        ];

        $ctx = $this->goiContext($scanner, [$row, null]);

        $this->assertNull($ctx->maCskcb);
    }

    public function test_service_restriction_scanner_gan_ma_cskcb_tu_row_da_join()
    {
        $scanner = new ServiceRestrictionScanner();
        $row = (object) [
            'tdl_treatment_id' => 789,
            'treatment_code' => 'TC002',
            'tdl_patient_code' => 'BN002',
            'tdl_patient_name' => 'Tran Thi B',
            'tdl_service_code' => 'SV001',
            'tdl_service_name' => 'Dich vu X',
            'ma_cskcb' => '03456',
        ];
        $sr = (object) [
            'execute_department_id' => 9,
            'service_req_code' => 'PC001',
            'service_req_type_id' => 2,
        ];

        $ctx = $this->goiContext($scanner, [$row, $sr]);

        $this->assertSame('03456', $ctx->maCskcb);
        $this->assertSame(789, $ctx->treatmentId);
    }

    public function test_service_restriction_scanner_ma_cskcb_null_khi_row_khong_co()
    {
        $scanner = new ServiceRestrictionScanner();
        $row = (object) [
            'tdl_treatment_id' => 789,
            'treatment_code' => 'TC002',
            'tdl_patient_code' => 'BN002',
            'tdl_patient_name' => 'Tran Thi B',
            'tdl_service_code' => 'SV001',
            'tdl_service_name' => 'Dich vu X',
            'ma_cskcb' => null,
        ];

        $ctx = $this->goiContext($scanner, [$row, null]);

        $this->assertNull($ctx->maCskcb);
    }
}

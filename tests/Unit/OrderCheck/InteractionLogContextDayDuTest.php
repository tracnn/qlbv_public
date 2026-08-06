<?php

namespace Tests\Unit\OrderCheck;

use Tests\TestCase;
use App\Services\OrderCheck\Scanners\InteractionLogScanner;

/**
 * Vi pham tuong tac thuoc tung ghi vao DB thieu ma dot dieu tri, ma/ten benh nhan va
 * ten bac si - trong khi fetchTreatmentInfo() da tra ve san ba truong dau. Dashboard
 * loc theo tu khoa (ma BN, ten BN, ma dot) khong bao gio thay nhung dong nay.
 *
 * Goi thang ham context() rieng (qua Reflection) thay vi chay het scan() vi scan() doc
 * HIS qua Oracle - khong phu hop Unit test. Cung cach ScannerMaCskcbContextTest lam.
 */
class InteractionLogContextDayDuTest extends TestCase
{
    private function goiContext($scanner, array $args)
    {
        $ref = new \ReflectionMethod($scanner, 'context');
        $ref->setAccessible(true);

        return $ref->invokeArgs($scanner, $args);
    }

    private function dong()
    {
        return (object) [
            'treatment_id' => 456,
            'request_loginname' => 'bs.a',
            'request_username' => 'Nguyen Van Bac Si',
            'request_department_id' => 7,
        ];
    }

    private function thongTin()
    {
        return (object) [
            'treatment_code' => 'TC001',
            'tdl_patient_code' => 'BN001',
            'tdl_patient_name' => 'Nguyen Van A',
            'last_department_id' => 5,
            'ma_cskcb' => '01929',
        ];
    }

    public function test_gan_du_ma_dot_va_thong_tin_benh_nhan()
    {
        $ctx = $this->goiContext(new InteractionLogScanner(), [$this->dong(), $this->thongTin()]);

        $this->assertSame('TC001', $ctx->treatmentCode);
        $this->assertSame('BN001', $ctx->patientCode);
        $this->assertSame('Nguyen Van A', $ctx->patientName);
    }

    public function test_gan_ten_bac_si_tu_dong_da_join()
    {
        $ctx = $this->goiContext(new InteractionLogScanner(), [$this->dong(), $this->thongTin()]);

        $this->assertSame('bs.a', $ctx->doctorLoginname);
        $this->assertSame('Nguyen Van Bac Si', $ctx->doctorUsername);
    }

    /** Khong co thong tin dot dieu tri thi de trong, khong duoc nem loi. */
    public function test_khong_co_info_thi_cac_truong_do_null()
    {
        $ctx = $this->goiContext(new InteractionLogScanner(), [$this->dong(), null]);

        $this->assertNull($ctx->treatmentCode);
        $this->assertNull($ctx->patientCode);
        $this->assertNull($ctx->patientName);
        $this->assertSame(456, $ctx->treatmentId);
    }

    /**
     * Ban ghi tuong tac thuoc khong gan voi phieu chi dinh hay dich vu nao - de trong
     * o day la DUNG BAN CHAT, khong phai thieu sot.
     */
    public function test_khong_gan_thong_tin_phieu_chi_dinh()
    {
        $ctx = $this->goiContext(new InteractionLogScanner(), [$this->dong(), $this->thongTin()]);

        $this->assertNull($ctx->serviceReqCode);
        $this->assertNull($ctx->serviceCode);
    }

    /** Bang HIS thieu cot ten thi van chay, chi de trong ten bac si. */
    public function test_dong_thieu_request_username_thi_khong_vo()
    {
        $dong = (object) [
            'treatment_id' => 456,
            'request_loginname' => 'bs.a',
            'request_department_id' => 7,
        ];

        $ctx = $this->goiContext(new InteractionLogScanner(), [$dong, $this->thongTin()]);

        $this->assertNull($ctx->doctorUsername);
        $this->assertSame('bs.a', $ctx->doctorLoginname);
    }
}

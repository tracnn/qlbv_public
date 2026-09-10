<?php

namespace Tests\Unit;

use Illuminate\Database\Eloquent\Model;
use Tests\TestCase;

class DanhMucTraCuuSoDangKyTest extends TestCase
{
    private function so()
    {
        return config('danh_muc_tra_cuu');
    }

    /** @test */
    public function so_dang_ky_ton_tai_va_khong_rong()
    {
        $so = $this->so();

        $this->assertInternalType('array', $so);
        $this->assertNotEmpty($so, 'So dang ky danh muc tra cuu dang rong');
    }

    /** @test */
    public function moi_muc_khai_du_bon_khoa_bat_buoc()
    {
        foreach ($this->so() as $khoa => $dm) {
            foreach (['ten', 'model', 'cot'] as $bb) {
                $this->assertArrayHasKey($bb, $dm, "Muc '$khoa' thieu khoa '$bb'");
            }
            $this->assertNotEmpty($dm['cot'], "Muc '$khoa' khong khai cot nao");
        }
    }

    /** @test */
    public function model_khai_phai_ton_tai_va_la_eloquent()
    {
        foreach ($this->so() as $khoa => $dm) {
            $this->assertTrue(class_exists($dm['model']), "Muc '$khoa': khong co lop {$dm['model']}");
            $this->assertTrue(is_subclass_of($dm['model'], Model::class),
                "Muc '$khoa': {$dm['model']} khong phai Eloquent Model");
        }
    }


    /** @test */
    public function co_danh_muc_dvkt_can_ma_may()
    {
        $so = $this->so();

        $this->assertArrayHasKey('dvkt_can_ma_may', $so);
        $this->assertSame(\App\Models\BHYT\DvktCanMaMay::class, $so['dvkt_can_ma_may']['model']);
    }

    /** @test */
    public function model_dang_ky_khong_duoc_co_appends()
    {
        // Accessor trong $appends lot vao JSON bat ke select() -> lo cot khong khai.
        foreach ($this->so() as $khoa => $dm) {
            $m = new $dm['model']();
            // Laravel 5.5 chua co getAppends(); doc thang thuoc tinh protected.
            $ref = new \ReflectionProperty($dm['model'], 'appends');
            $ref->setAccessible(true);
            $this->assertSame([], $ref->getValue($m),
                "Muc '$khoa': model {$dm['model']} co \$appends nen se lo cot khong khai");
        }
    }
}

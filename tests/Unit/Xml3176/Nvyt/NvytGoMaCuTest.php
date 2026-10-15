<?php

namespace Tests\Unit\Xml3176\Nvyt;

use App\Services\Xml3176\NvytRule;
use Tests\TestCase;

class NvytGoMaCuTest extends TestCase
{
    const XML_SO = [1, 2, 3, 4, 5, 7, 8, 9, 10, 11, 13, 14];

    private function nguon(int $n): string
    {
        return file_get_contents(app_path("Services/Xml3176Xml{$n}Checker.php"));
    }

    /** @test */
    public function khong_checker_nao_con_sinh_ma_cu()
    {
        $con = [];
        foreach (self::XML_SO as $n) {
            $nguon = $this->nguon($n);
            foreach (NvytRule::MA_CU as $ma) {
                if (strpos($ma, "XML{$n}_") !== 0) {
                    continue;
                }
                $duoi = substr($ma, strlen("XML{$n}_"));
                if (strpos($nguon, "generateErrorCode('{$duoi}')") !== false) {
                    $con[] = $ma;
                }
            }
        }
        $this->assertSame([], $con, 'Con sinh ma cu: ' . implode(', ', $con));
    }

    /** @test */
    public function checker_khong_con_goi_isMedicalStaffValid()
    {
        foreach (self::XML_SO as $n) {
            $this->assertFalse(strpos($this->nguon($n), 'isMedicalStaffValid'), "XML{$n}");
        }
    }

    /** @test */
    public function same_doctor_van_con()
    {
        $this->assertNotFalse(strpos($this->nguon(3), 'SAME_DOCTOR_ORDER_AND_EXECUTE'));
    }
}

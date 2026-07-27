<?php

namespace Tests\Unit\GiaoBan;

use Tests\TestCase;
use App\Models\GiaoBan\GiaoBanMetricTemplate;
use App\Services\GiaoBan\MetricValidator;

class MetricTemplateSeedTest extends TestCase
{
    /** @test */
    public function co_du_5_mau_chuyen_tu_blade_sang()
    {
        $this->assertCount(5, GiaoBanMetricTemplate::SEED);

        $ten = array_column(GiaoBanMetricTemplate::SEED, 'name');
        foreach (['Điều trị (mặc định)', 'Khám (mặc định)', 'Tổng dịch vụ',
                  'CĐHA (XQ/CT/MRI/SA)', 'Xét nghiệm (HH/SH/VS...)'] as $t) {
            $this->assertContains($t, $ten, "Thieu mau: $t");
        }
    }

    /** @test */
    public function moi_mau_deu_dat_schema()
    {
        foreach (GiaoBanMetricTemplate::SEED as $mau) {
            $loi = MetricValidator::validate($mau['metrics'], $mau['block_type']);
            $this->assertSame([], $loi,
                "Mau '{$mau['name']}' khong dat schema: " . json_encode($loi, JSON_UNESCAPED_UNICODE));
        }
    }

    /** @test */
    public function mau_dieu_tri_giu_nguyen_8_chi_tieu_va_dung_thu_tu()
    {
        $mau = null;
        foreach (GiaoBanMetricTemplate::SEED as $m) {
            if ($m['name'] === 'Điều trị (mặc định)') $mau = $m;
        }

        $this->assertEquals(
            ['bn_cu', 'bn_vao', 'bn_chuyen_den', 'bn_ra_vien', 'bn_chuyen_vien',
             'bn_tu_vong', 'bn_chuyen_khoa', 'hien_co'],
            array_column($mau['metrics'], 'code')
        );
    }
}

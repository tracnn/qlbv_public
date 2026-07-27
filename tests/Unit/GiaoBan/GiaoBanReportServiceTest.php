<?php

namespace Tests\Unit\GiaoBan;

use Tests\TestCase;
use App\Services\GiaoBan\GiaoBanReportService;

class GiaoBanReportServiceTest extends TestCase
{
    /** @test */
    public function merge_preserves_manual_values_and_updates_auto()
    {
        $existing = [
            '1|bn_cu' => ['auto' => 10.0, 'manual' => 12.0],
            '1|bn_vao' => ['auto' => 5.0, 'manual' => null],
        ];
        $fresh = ['1|bn_cu' => 11.0, '1|bn_vao' => 6.0, '1|hien_co' => 9.0];

        $merged = GiaoBanReportService::mergeAutoValues($existing, $fresh);

        $this->assertEquals(['auto' => 11.0, 'manual' => 12.0], $merged['1|bn_cu']);
        $this->assertEquals(['auto' => 6.0, 'manual' => null], $merged['1|bn_vao']);
        $this->assertEquals(['auto' => 9.0, 'manual' => null], $merged['1|hien_co']);
    }

    /** @test */
    public function balance_check_flags_mismatched_departments_using_display_values()
    {
        $cells = [
            '1|bn_cu' => ['auto' => 10.0, 'manual' => null], '1|bn_vao' => ['auto' => 5.0, 'manual' => null],
            '1|bn_chuyen_den' => ['auto' => 1.0, 'manual' => null], '1|bn_ra_vien' => ['auto' => 3.0, 'manual' => null],
            '1|bn_chuyen_vien' => ['auto' => 1.0, 'manual' => null], '1|bn_tu_vong' => ['auto' => 0.0, 'manual' => null],
            '1|bn_chuyen_khoa' => ['auto' => 2.0, 'manual' => null], '1|hien_co' => ['auto' => 10.0, 'manual' => null],
            '2|bn_cu' => ['auto' => 7.0, 'manual' => null], '2|bn_vao' => ['auto' => 0.0, 'manual' => null],
            '2|bn_chuyen_den' => ['auto' => 0.0, 'manual' => null], '2|bn_ra_vien' => ['auto' => 0.0, 'manual' => null],
            '2|bn_chuyen_vien' => ['auto' => 0.0, 'manual' => null], '2|bn_tu_vong' => ['auto' => 0.0, 'manual' => null],
            '2|bn_chuyen_khoa' => ['auto' => 0.0, 'manual' => null], '2|hien_co' => ['auto' => 7.0, 'manual' => 8.0],
        ];

        $warnings = GiaoBanReportService::checkBalance($cells, [1, 2]);

        $this->assertArrayNotHasKey(1, $warnings);
        $this->assertEquals(1.0, $warnings[2]);
    }

    /** @test */
    public function totals_sum_display_values_across_departments()
    {
        $cells = [
            '1|hien_co' => ['auto' => 10.0, 'manual' => null],
            '2|hien_co' => ['auto' => 7.0, 'manual' => 9.0],
        ];
        $this->assertEquals(19.0, GiaoBanReportService::sumMetric($cells, 'hien_co', [1, 2]));
    }

    protected function metricsNhapTay()
    {
        return [
            ['code' => 'bn_cu', 'name' => 'BN cũ', 'type' => 'census_from'],
            ['code' => 'ke_thua', 'name' => 'Giường kế hoạch', 'type' => 'manual',
             'input' => ['carry_over' => true]],
            ['code' => 'mac_dinh', 'name' => 'Ca mổ', 'type' => 'manual',
             'input' => ['default' => 0]],
            ['code' => 'tron', 'name' => 'Ghi chú số', 'type' => 'manual'],
        ];
    }

    /** @test */
    public function ke_thua_gia_tri_ky_truoc_va_ap_gia_tri_mac_dinh()
    {
        $ra = GiaoBanReportService::initialManualValues(
            $this->metricsNhapTay(), [], ['ke_thua' => 12.0, 'tron' => 5.0]
        );

        $this->assertEquals(['value' => 12.0, 'carried' => true], $ra['ke_thua']);
        $this->assertEquals(['value' => 0.0, 'carried' => false], $ra['mac_dinh']);
        // 'tron' khong bat carry_over -> khong ke thua du ky truoc co so
        $this->assertArrayNotHasKey('tron', $ra);
        // chi tieu tu dong khong bao gio duoc gan manual
        $this->assertArrayNotHasKey('bn_cu', $ra);
    }

    /** @test */
    public function khong_dung_toi_o_da_ton_tai()
    {
        // day la test chong mat du lieu: fetchAndStore chay lai nhieu lan tren cung bao cao draft
        $ra = GiaoBanReportService::initialManualValues(
            $this->metricsNhapTay(), ['ke_thua', 'mac_dinh'], ['ke_thua' => 12.0]
        );

        $this->assertSame([], $ra);
    }

    /** @test */
    public function ky_truoc_khong_co_so_thi_khong_ke_thua()
    {
        $ra = GiaoBanReportService::initialManualValues($this->metricsNhapTay(), [], []);

        $this->assertArrayNotHasKey('ke_thua', $ra);
        $this->assertEquals(['value' => 0.0, 'carried' => false], $ra['mac_dinh']);
    }

    /** @test */
    public function carry_over_uu_tien_hon_default()
    {
        $metrics = [['code' => 'x', 'name' => 'X', 'type' => 'manual',
                     'input' => ['carry_over' => true, 'default' => 0]]];

        $ra = GiaoBanReportService::initialManualValues($metrics, [], ['x' => 7.0]);
        $this->assertEquals(['value' => 7.0, 'carried' => true], $ra['x']);
    }
}

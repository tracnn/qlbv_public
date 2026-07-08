<?php

namespace App\Services\GiaoBan;

use App\Models\GiaoBan\GiaoBanDeptConfig;
use App\Models\GiaoBan\GiaoBanReport;
use App\Models\GiaoBan\GiaoBanReportCell;

class GiaoBanReportService
{
    const BALANCE_PLUS = ['bn_cu', 'bn_vao', 'bn_chuyen_den'];
    const BALANCE_MINUS = ['bn_ra_vien', 'bn_chuyen_vien', 'bn_tu_vong', 'bn_chuyen_khoa'];
    const BALANCE_TARGET = 'hien_co';

    protected $metricService;

    public function __construct(GiaoBanMetricService $metricService)
    {
        $this->metricService = $metricService;
    }

    // ===== Phần thuần (unit test) =====

    /**
     * Trộn auto_value mới vào cells hiện có, giữ nguyên manual.
     * @param array $existing map "dept|metric" => ['auto' => ?, 'manual' => ?]
     * @param array $fresh    map "dept|metric" => float|null
     */
    public static function mergeAutoValues(array $existing, array $fresh)
    {
        $out = $existing;
        foreach ($fresh as $key => $auto) {
            if (isset($out[$key])) {
                $out[$key]['auto'] = $auto;
            } else {
                $out[$key] = ['auto' => $auto, 'manual' => null];
            }
        }
        return $out;
    }

    protected static function display(array $cells, $deptId, $metric)
    {
        $key = $deptId . '|' . $metric;
        if (!isset($cells[$key])) return 0.0;
        $c = $cells[$key];
        return $c['manual'] !== null ? (float) $c['manual'] : (float) ($c['auto'] !== null ? $c['auto'] : 0);
    }

    /**
     * Kiểm tra cân đối: cũ + vào + đến − ra − cv − tv − đi == hiện có.
     * @return array map dept_config_id => chênh lệch (chỉ các khoa lệch)
     */
    public static function checkBalance(array $cells, array $deptConfigIds)
    {
        $warnings = [];
        foreach ($deptConfigIds as $id) {
            if (!isset($cells[$id . '|' . self::BALANCE_TARGET])) continue;
            $expect = 0.0;
            foreach (self::BALANCE_PLUS as $m) $expect += self::display($cells, $id, $m);
            foreach (self::BALANCE_MINUS as $m) $expect -= self::display($cells, $id, $m);
            $actual = self::display($cells, $id, self::BALANCE_TARGET);
            if (abs($expect - $actual) > 0.001) {
                $warnings[$id] = round(abs($expect - $actual), 2);
            }
        }
        return $warnings;
    }

    /** Tổng một metric trên các khoa (ưu tiên manual). */
    public static function sumMetric(array $cells, $metric, array $deptConfigIds)
    {
        $sum = 0.0;
        foreach ($deptConfigIds as $id) $sum += self::display($cells, $id, $metric);
        return $sum;
    }

    // ===== Phần persistence =====

    /** Lấy (tạo nếu chưa có) report của ngày. */
    public function getOrCreateReport($date, $from, $to, $userId)
    {
        $report = GiaoBanReport::where('report_date', $date)->first();
        if (!$report) {
            $report = GiaoBanReport::create([
                'report_date' => $date, 'from_time' => $from, 'to_time' => $to,
                'status' => 'draft', 'created_by' => $userId,
            ]);
        }
        return $report;
    }

    /**
     * Lấy số liệu từ HIS và upsert vào giaoban_report_cells (giữ manual_value).
     * Chỉ gọi khi report ở trạng thái draft.
     */
    public function fetchAndStore(GiaoBanReport $report, $from, $to, $userId)
    {
        $configs = GiaoBanDeptConfig::where('is_active', true)->orderBy('sort_order')->get();
        $fresh = $this->metricService->computeAll($configs, $from, $to);

        foreach ($fresh as $key => $auto) {
            list($deptConfigId, $metricCode) = explode('|', $key, 2);
            $cell = GiaoBanReportCell::firstOrNew([
                'report_id' => $report->id,
                'dept_config_id' => (int) $deptConfigId,
                'metric_code' => $metricCode,
            ]);
            $cell->auto_value = $auto;
            $cell->updated_by = $userId;
            $cell->save();
        }

        $report->update(['from_time' => $from, 'to_time' => $to, 'data_fetched_at' => date('Y-m-d H:i:s')]);
        return $report;
    }

    /** Cells của report dạng map "dept|metric" => ['auto','manual'] cho các hàm thuần. */
    public function cellMap(GiaoBanReport $report)
    {
        $map = [];
        foreach ($report->cells as $c) {
            if ($c->metric_code === 'note') continue;
            $map[$c->dept_config_id . '|' . $c->metric_code] = [
                'auto' => $c->auto_value !== null ? (float) $c->auto_value : null,
                'manual' => $c->manual_value !== null ? (float) $c->manual_value : null,
            ];
        }
        return $map;
    }
}

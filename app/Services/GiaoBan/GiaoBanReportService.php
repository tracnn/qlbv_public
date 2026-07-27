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

    /**
     * Gia tri khoi tao cho cac o nhap tay CHUA TON TAI trong bao cao.
     * carry_over uu tien hon default. O da ton tai khong bao gio bi dung toi
     * — neu khong, chay lai fetchAndStore se ghi de so khoa vua sua tay.
     *
     * @param array $metrics    metricList() cua mot dept config
     * @param array $daCoCode   cac metric_code da co cell trong bao cao hien tai
     * @param array $prevManual map metric_code => manual_value cua bao cao lien truoc
     * @return array map metric_code => ['value' => float, 'carried' => bool]
     */
    public static function initialManualValues(array $metrics, array $daCoCode, array $prevManual)
    {
        $out = [];
        foreach ($metrics as $m) {
            if (!isset($m['type']) || $m['type'] !== 'manual') continue;
            $code = isset($m['code']) ? $m['code'] : null;
            if ($code === null || in_array($code, $daCoCode, true)) continue;

            $in = isset($m['input']) && is_array($m['input']) ? $m['input'] : [];

            if (!empty($in['carry_over']) && isset($prevManual[$code]) && $prevManual[$code] !== null) {
                $out[$code] = ['value' => (float) $prevManual[$code], 'carried' => true];
                continue;
            }
            if (isset($in['default']) && is_numeric($in['default'])) {
                $out[$code] = ['value' => (float) $in['default'], 'carried' => false];
            }
        }
        return $out;
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

        try {
            list($bedSql, $bedBinds) = $this->metricService->buildBedCapacitySql($to);
            $bedRows = $this->metricService->normalizeRows(
                \Illuminate\Support\Facades\DB::connection('HISPro')->select($bedSql, $bedBinds)
            );
            \App\Models\GiaoBan\GiaoBanReportBed::where('report_id', $report->id)->delete();
            foreach ($bedRows as $b) {
                \App\Models\GiaoBan\GiaoBanReportBed::create([
                    'report_id' => $report->id, 'department_id' => (int) $b->department_id,
                    'total_beds' => (int) $b->total_beds, 'used_beds' => (int) $b->used_beds,
                ]);
            }
        } catch (\Exception $e) { /* HIS loi -> bo qua, khong chan luu cells */ }

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

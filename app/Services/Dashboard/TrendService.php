<?php

namespace App\Services\Dashboard;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class TrendService
{
    /**
     * Format trend rows từ Oracle thành label + value
     */
    public function formatTrendRows($rows, string $mode): array
    {
        return collect($rows)->map(function ($row) use ($mode) {
            if ($mode === 'daily') {
                $dayStr = (string) $row->day_val; // e.g. '20260315'
                $label  = substr($dayStr, 6, 2) . '/' . substr($dayStr, 4, 2);
            } else {
                $monthStr = (string) $row->month_val; // e.g. '202603'
                $label    = substr($monthStr, 4, 2) . '/' . substr($monthStr, 0, 4);
            }
            return [
                'label' => $label,
                'value' => (int) ($row->total ?? 0),
            ];
        })->toArray();
    }

    /**
     * Tính kỳ trước (dùng để vẽ đường nét đứt so sánh)
     */
    public function buildPreviousPeriod(string $from, string $to, string $mode): array
    {
        if ($mode === 'daily') {
            // Shift 1 tháng về trước — cùng khoảng ngày
            $prevFrom = Carbon::parse($from)->subMonth()->format('Y-m-d');
            $prevTo   = Carbon::parse($to)->subMonth()->format('Y-m-d');
        } else {
            // Shift 1 năm về trước
            $prevFrom = Carbon::parse($from)->subYear()->format('Y-m-d');
            $prevTo   = Carbon::parse($to)->subYear()->format('Y-m-d');
        }

        return ['from' => $prevFrom, 'to' => $prevTo];
    }

    /**
     * Tính trạng thái quá tải
     */
    public function calculateOverloadStatus(int $todayCount, float $avg30d): array
    {
        $ratio = $avg30d > 0 ? round($todayCount / $avg30d, 2) : 0;

        if ($ratio > 1.2) {
            $status = 'overload';
        } elseif ($ratio < 0.8) {
            $status = 'underload';
        } else {
            $status = 'normal';
        }

        return [
            'today_count' => $todayCount,
            'average_30d' => round($avg30d, 1),
            'ratio'       => $ratio,
            'status'      => $status,
        ];
    }

    /**
     * Lấy dữ liệu xu hướng lượt khám hoặc doanh thu
     */
    public function getTrendChart(string $from, string $to, string $mode, string $metric): array
    {
        $fromDate   = Carbon::parse($from)->startOfDay()->format('YmdHis');
        $toDate     = Carbon::parse($to)->endOfDay()->format('YmdHis');
        $prevPeriod = $this->buildPreviousPeriod($from, $to, $mode);
        $prevFrom   = Carbon::parse($prevPeriod['from'])->startOfDay()->format('YmdHis');
        $prevTo     = Carbon::parse($prevPeriod['to'])->endOfDay()->format('YmdHis');

        $currentRows  = $this->formatTrendRows($this->queryTrendData($fromDate, $toDate, $mode, $metric), $mode);
        $previousRows = $this->formatTrendRows($this->queryTrendData($prevFrom, $prevTo, $mode, $metric), $mode);

        return [
            'labels'          => array_map(function ($r) { return $r['label']; }, $currentRows),
            'current'         => array_map(function ($r) { return $r['value']; }, $currentRows),
            'previous'        => array_map(function ($r) { return $r['value']; }, $previousRows),
            'previous_labels' => array_map(function ($r) { return $r['label']; }, $previousRows),
        ];
    }

    private function queryTrendData(string $fromDate, string $toDate, string $mode, string $metric)
    {
        $groupExpr = $mode === 'daily'
            ? 'TRUNC(sr.INTRUCTION_TIME / 1000000)'
            : 'TRUNC(sr.INTRUCTION_TIME / 100000000)';

        $alias = $mode === 'daily' ? 'day_val' : 'month_val';

        $query = DB::connection('HISPro')
            ->table('his_service_req as sr')
            ->where('sr.SERVICE_REQ_TYPE_ID', 1)
            ->where('sr.IS_DELETE', 0)
            ->where('sr.IS_ACTIVE', 1)
            ->whereBetween('sr.INTRUCTION_TIME', [$fromDate, $toDate]);

        if ($metric === 'revenue') {
            $query->join('his_sere_serv as ss', function ($join) {
                $join->on('ss.SERVICE_REQ_ID', '=', 'sr.ID')
                     ->where('ss.IS_DELETE', 0)
                     ->where('ss.IS_ACTIVE', 1);
            });
            $selectRaw = "$groupExpr as $alias, SUM(ss.VIR_TOTAL_PRICE) as total";
        } else {
            $selectRaw = "$groupExpr as $alias, COUNT(*) as total";
        }

        return $query->selectRaw($selectRaw)
                     ->groupBy(DB::raw($groupExpr))
                     ->orderBy(DB::raw($groupExpr))
                     ->get();
    }

    /**
     * Lấy số BN/giờ theo khung giờ trong ngày
     */
    public function getPatientsPerHour(string $from, string $to, ?int $departmentId = null): array
    {
        $fromDate = Carbon::parse($from)->startOfDay()->format('YmdHis');
        $toDate   = Carbon::parse($to)->endOfDay()->format('YmdHis');

        $query = DB::connection('HISPro')
            ->table('his_service_req as sr')
            ->selectRaw('FLOOR(MOD(sr.START_TIME, 1000000) / 10000) as hour_of_day, COUNT(*) as total_patients')
            ->where('sr.SERVICE_REQ_TYPE_ID', 1)
            ->where('sr.IS_DELETE', 0)
            ->where('sr.IS_ACTIVE', 1)
            ->whereNotNull('sr.START_TIME')
            ->whereBetween('sr.INTRUCTION_TIME', [$fromDate, $toDate]);

        if ($departmentId) {
            $query->where('sr.EXECUTE_ROOM_ID', $departmentId);
        }

        $rows = $query->groupBy(DB::raw('FLOOR(MOD(sr.START_TIME, 1000000) / 10000)'))
                      ->orderBy(DB::raw('FLOOR(MOD(sr.START_TIME, 1000000) / 10000)'))
                      ->get();

        $totalPatients = $rows->sum('total_patients');
        $workingDays   = max(1, Carbon::parse($from)->diffInWeekdays(Carbon::parse($to)) + 1);
        $avgPerHour    = round($totalPatients / ($workingDays * 8), 1);

        $byHour = $rows->map(function ($r) {
            return ['hour' => (int) $r->hour_of_day, 'count' => (int) $r->total_patients];
        })->toArray();

        return ['average_per_hour' => $avgPerHour, 'by_hour' => $byHour];
    }

    /**
     * Kiểm tra quá tải ngày hôm nay so với trung bình 30 ngày
     */
    public function getOverloadAlert(string $date): array
    {
        $dayVal     = Carbon::parse($date)->format('Ymd');
        $from30d    = Carbon::parse($date)->subDays(30)->startOfDay()->format('YmdHis');
        $to30d      = Carbon::parse($date)->subDay()->endOfDay()->format('YmdHis');
        $todayFrom  = Carbon::parse($date)->startOfDay()->format('YmdHis');
        $todayTo    = Carbon::parse($date)->endOfDay()->format('YmdHis');

        $todayCount = DB::connection('HISPro')
            ->table('his_service_req')
            ->where('SERVICE_REQ_TYPE_ID', 1)
            ->where('IS_DELETE', 0)
            ->where('IS_ACTIVE', 1)
            ->whereBetween('INTRUCTION_TIME', [$todayFrom, $todayTo])
            ->count();

        $avg30d = DB::connection('HISPro')
            ->table('his_service_req')
            ->where('SERVICE_REQ_TYPE_ID', 1)
            ->where('IS_DELETE', 0)
            ->where('IS_ACTIVE', 1)
            ->whereBetween('INTRUCTION_TIME', [$from30d, $to30d])
            ->count() / 30;

        return $this->calculateOverloadStatus($todayCount, $avg30d);
    }
}

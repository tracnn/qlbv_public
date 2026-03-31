<?php

namespace App\Services\Dashboard;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class OperatingRoomService
{
    const WORKING_MINUTES_PER_DAY = 480; // 8h × 60 phút

    /**
     * Tính thời gian (phút) giữa 2 mốc thời gian YmdHis dạng NUMBER
     */
    public function calcDurationMinutes($startRaw, $endRaw): int
    {
        if (!$startRaw || !$endRaw) {
            return 0;
        }
        try {
            $start = Carbon::createFromFormat('YmdHis', sprintf('%014d', (int) $startRaw));
            $end   = Carbon::createFromFormat('YmdHis', sprintf('%014d', (int) $endRaw));
            return max(0, (int) $start->diffInMinutes($end));
        } catch (\Exception $e) {
            return 0;
        }
    }

    /**
     * Tính % công suất sử dụng phòng mổ
     */
    public function calcUtilizationPct(int $totalMinutes, int $workingDays): float
    {
        if ($workingDays <= 0) return 0.0;
        return round($totalMinutes / ($workingDays * self::WORKING_MINUTES_PER_DAY) * 100, 2);
    }

    /**
     * Xác định trạng thái công suất
     */
    public function getUtilizationStatus(float $pct): string
    {
        if ($pct > 100) return 'overload';
        if ($pct >= 70) return 'optimal';
        return 'underload';
    }

    /**
     * Build dữ liệu heatmap từ rows Oracle
     */
    public function buildHeatmapData(array $rows): array
    {
        $rooms = array_values(array_unique(array_map(function ($r) { return $r->room_name; }, $rows)));
        $dates = array_values(array_unique(array_map(function ($r) { return $r->day_val; }, $rows)));
        sort($dates);

        // Format ngày hiển thị
        $dateLabels = array_map(function ($d) {
            $s = (string) $d;
            return substr($s, 6, 2) . '/' . substr($s, 4, 2);
        }, $dates);

        // Build lookup: room → date → count
        $lookup = [];
        foreach ($rows as $r) {
            $lookup[$r->room_name][$r->day_val] = (int) $r->total_cases;
        }

        $matrix = [];
        foreach ($rooms as $room) {
            $rowData = [];
            foreach ($dates as $d) {
                $rowData[] = $lookup[$room][$d] ?? 0;
            }
            $matrix[] = $rowData;
        }

        return [
            'rooms'  => $rooms,
            'dates'  => $dateLabels,
            'matrix' => $matrix,
        ];
    }

    /**
     * Số ca PT theo phòng theo ngày
     */
    public function getCasesPerRoom(string $from, string $to): array
    {
        $fromDate = Carbon::parse($from)->startOfDay()->format('YmdHis');
        $toDate   = Carbon::parse($to)->endOfDay()->format('YmdHis');

        $rows = DB::connection('HISPro')
            ->table('his_service_req as sr')
            ->join('his_execute_room as er', 'er.ROOM_ID', '=', 'sr.EXECUTE_ROOM_ID')
            ->selectRaw('er.EXECUTE_ROOM_NAME as room_name,
                         TRUNC(sr.START_TIME / 1000000) as day_val,
                         COUNT(*) as total_cases')
            ->where('sr.SERVICE_REQ_TYPE_ID', 10)
            ->where('sr.IS_DELETE', 0)
            ->where('sr.IS_ACTIVE', 1)
            ->whereNotNull('sr.START_TIME')
            ->whereBetween('sr.INTRUCTION_TIME', [$fromDate, $toDate])
            ->groupBy(DB::raw('er.EXECUTE_ROOM_NAME, TRUNC(sr.START_TIME / 1000000)'))
            ->orderBy(DB::raw('er.EXECUTE_ROOM_NAME'))
            ->orderBy(DB::raw('TRUNC(sr.START_TIME / 1000000)'))
            ->get()
            ->toArray();

        return $this->buildHeatmapData($rows);
    }

    /**
     * % Công suất sử dụng phòng mổ
     */
    public function getUtilization(string $from, string $to): array
    {
        $fromDate    = Carbon::parse($from)->startOfDay()->format('YmdHis');
        $toDate      = Carbon::parse($to)->endOfDay()->format('YmdHis');
        // Bệnh viện làm 6 ngày/tuần (Mon-Sat), chỉ nghỉ CN
        $startDate   = Carbon::parse($from);
        $endDate     = Carbon::parse($to);
        $workingDays = 0;
        for ($d = $startDate->copy(); $d->lte($endDate); $d->addDay()) {
            if ($d->dayOfWeek !== Carbon::SUNDAY) $workingDays++;
        }
        $workingDays = max(1, $workingDays);

        // Lấy raw records (không aggregate thời gian trong SQL)
        $rows = DB::connection('HISPro')
            ->table('his_service_req as sr')
            ->join('his_execute_room as er', 'er.ROOM_ID', '=', 'sr.EXECUTE_ROOM_ID')
            ->selectRaw('sr.EXECUTE_ROOM_ID as room_id, er.EXECUTE_ROOM_NAME as room_name,
                         sr.START_TIME, sr.FINISH_TIME')
            ->where('sr.SERVICE_REQ_TYPE_ID', 10)
            ->where('sr.IS_DELETE', 0)
            ->where('sr.IS_ACTIVE', 1)
            ->whereNotNull('sr.START_TIME')
            ->whereNotNull('sr.FINISH_TIME')
            ->whereBetween('sr.INTRUCTION_TIME', [$fromDate, $toDate])
            ->get();

        // Group và tính thời gian trong PHP
        $grouped = [];
        foreach ($rows as $row) {
            $key = $row->room_id;
            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'room_name'     => $row->room_name,
                    'total_cases'   => 0,
                    'total_minutes' => 0,
                ];
            }
            $grouped[$key]['total_cases']++;
            $grouped[$key]['total_minutes'] += $this->calcDurationMinutes(
                $row->start_time,
                $row->finish_time
            );
        }

        $result = [];
        foreach ($grouped as $data) {
            $pct = $this->calcUtilizationPct($data['total_minutes'], $workingDays);
            $result[] = [
                'room_name'       => $data['room_name'],
                'total_cases'     => $data['total_cases'],
                'total_minutes'   => $data['total_minutes'],
                'working_days'    => $workingDays,
                'utilization_pct' => $pct,
                'status'          => $this->getUtilizationStatus($pct),
            ];
        }

        usort($result, function ($a, $b) { return strcmp($a['room_name'], $b['room_name']); });

        return $result;
    }
}

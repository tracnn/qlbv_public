<?php

namespace App\Services\GiaoBan;

use App\Models\GiaoBan\GiaoBanReport;
use App\Models\GiaoBan\GiaoBanReportDuty;

class GiaoBanDutyService
{
    /** Thuần: chuyển kíp trực ngày trước -> mảng dòng chèn cho report mới (bỏ id/report_id cũ). */
    public static function copyRows($prevRows, $newReportId)
    {
        $out = [];
        foreach ($prevRows as $r) {
            $out[] = [
                'report_id' => (int) $newReportId,
                'position_id' => (int) $r->position_id,
                'user_id' => $r->user_id !== null ? (int) $r->user_id : null,
                'person_name' => $r->person_name,
                'phone' => $r->phone,
            ];
        }
        return $out;
    }

    /** Upsert 1 dòng kíp trực theo (report_id, position_id). */
    public function saveDuty($reportId, $positionId, $userId, $personName, $phone)
    {
        $duty = GiaoBanReportDuty::firstOrNew(['report_id' => (int) $reportId, 'position_id' => (int) $positionId]);
        $duty->user_id = $userId !== null && $userId !== '' ? (int) $userId : null;
        $duty->person_name = $personName;
        $duty->phone = $phone;
        $duty->save();
        return $duty;
    }

    /** Sao chép kíp trực từ report gần nhất TRƯỚC ngày của $report (có kíp). Trả số dòng đã copy. */
    public function copyFromPrevious(GiaoBanReport $report)
    {
        $prevReport = GiaoBanReport::where('report_date', '<', $report->report_date)
            ->whereIn('id', GiaoBanReportDuty::select('report_id'))
            ->orderBy('report_date', 'desc')->first();
        if (!$prevReport) return 0;

        $prevRows = GiaoBanReportDuty::where('report_id', $prevReport->id)->get();
        $rows = self::copyRows($prevRows, $report->id);
        foreach ($rows as $row) {
            $duty = GiaoBanReportDuty::firstOrNew(['report_id' => $row['report_id'], 'position_id' => $row['position_id']]);
            $duty->fill($row)->save();
        }
        return count($rows);
    }
}

<?php

namespace App\Services\GiaoBan;

use App\Models\GiaoBan\GiaoBanReport;
use App\Models\GiaoBan\GiaoBanReportDuty;
use App\Models\GiaoBan\GiaoBanDutyEditor;

class GiaoBanDutyService
{
    /** Thuần: chuyển kíp trực ngày trước -> mảng dòng chèn cho report mới. */
    public static function copyRows($prevRows, $newReportId)
    {
        $out = [];
        foreach ($prevRows as $r) {
            $out[] = [
                'report_id' => (int) $newReportId,
                'position_id' => (int) $r->position_id,
                'employee_id' => $r->employee_id !== null ? (int) $r->employee_id : null,
                'person_name' => $r->person_name,
                'phone' => $r->phone,
            ];
        }
        return $out;
    }

    /** Thuần: quyền sửa kíp trực (admin hoặc trong danh sách editor). */
    public static function canEdit($isAdmin, array $editorUserIds, $userId)
    {
        if ($isAdmin) return true;
        return in_array((int) $userId, array_map('intval', $editorUserIds), true);
    }

    /** Thêm 1 người vào chức danh (bỏ qua nếu employee đã có trong chức danh đó). */
    public function addDuty($reportId, $positionId, $employeeId, $personName, $phone)
    {
        $employeeId = $employeeId !== null && $employeeId !== '' ? (int) $employeeId : null;
        if ($employeeId !== null) {
            $exists = GiaoBanReportDuty::where('report_id', $reportId)->where('position_id', $positionId)
                ->where('employee_id', $employeeId)->first();
            if ($exists) return $exists;
        }
        return GiaoBanReportDuty::create([
            'report_id' => (int) $reportId, 'position_id' => (int) $positionId,
            'employee_id' => $employeeId, 'person_name' => $personName, 'phone' => $phone,
        ]);
    }

    public function removeDuty($dutyId)
    {
        return GiaoBanReportDuty::where('id', (int) $dutyId)->delete();
    }

    public function updatePhone($dutyId, $phone)
    {
        $d = GiaoBanReportDuty::find((int) $dutyId);
        if ($d) { $d->phone = $phone; $d->save(); }
        return $d;
    }

    /** Sao chép kíp trực từ report gần nhất TRƯỚC ngày (có kíp). Trả số dòng copy. */
    public function copyFromPrevious(GiaoBanReport $report)
    {
        $prevReport = GiaoBanReport::where('report_date', '<', $report->report_date)
            ->whereIn('id', GiaoBanReportDuty::select('report_id'))
            ->orderBy('report_date', 'desc')->first();
        if (!$prevReport) return 0;
        $rows = self::copyRows(GiaoBanReportDuty::where('report_id', $prevReport->id)->get(), $report->id);
        foreach ($rows as $row) GiaoBanReportDuty::create($row);
        return count($rows);
    }

    public function editorUserIds()
    {
        return GiaoBanDutyEditor::pluck('user_id')->map(function ($x) { return (int) $x; })->all();
    }
}

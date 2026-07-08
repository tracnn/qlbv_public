<?php

namespace App\Http\Controllers\KHTH;

use App\Http\Controllers\Controller;
use App\Models\GiaoBan\GiaoBanDeptConfig;
use App\Models\GiaoBan\GiaoBanReport;
use App\Models\GiaoBan\GiaoBanReportCell;
use App\Models\GiaoBan\GiaoBanUserDepartment;
use App\Services\GiaoBan\GiaoBanPermission;
use App\Services\GiaoBan\GiaoBanReportService;
use Illuminate\Http\Request;

class GiaoBanController extends Controller
{
    protected $service;

    public function __construct(GiaoBanReportService $service)
    {
        $this->service = $service;
    }

    protected function isAdmin()
    {
        return auth()->user()->can('giaoban-admin');
    }

    protected function assignedDeptIds()
    {
        return GiaoBanUserDepartment::where('user_id', auth()->id())->pluck('dept_config_id')->all();
    }

    public function index()
    {
        return view('khth.giaoban-index', [
            'isAdmin' => $this->isAdmin(),
            'assignedDeptIds' => $this->assignedDeptIds(),
        ]);
    }

    /** JSON toàn bộ report của 1 ngày: configs + cells + warnings. */
    public function show(Request $request)
    {
        $date = $request->input('date', date('Y-m-d'));
        $report = GiaoBanReport::with('cells')->where('report_date', $date)->first();
        $configs = GiaoBanDeptConfig::where('is_active', true)->orderBy('sort_order')->get()
            ->map(function ($c) {
                return [
                    'id' => $c->id, 'display_name' => $c->display_name,
                    'his_department_id' => $c->his_department_id, 'metrics' => $c->metricList(),
                ];
            });

        $cells = []; $warnings = [];
        if ($report) {
            foreach ($report->cells as $c) {
                $cells[] = [
                    'dept_config_id' => $c->dept_config_id, 'metric_code' => $c->metric_code,
                    'auto_value' => $c->auto_value, 'manual_value' => $c->manual_value, 'note' => $c->note,
                ];
            }
            $warnings = GiaoBanReportService::checkBalance(
                $this->service->cellMap($report),
                $configs->pluck('id')->all()
            );
        }

        return response()->json([
            'report' => $report, 'configs' => $configs, 'cells' => $cells,
            'balance_warnings' => $warnings,
            'is_admin' => $this->isAdmin(), 'assigned_dept_ids' => $this->assignedDeptIds(),
        ]);
    }

    /** Lấy/Lấy lại số liệu từ HIS (admin). */
    public function fetchData(Request $request)
    {
        if (!$this->isAdmin()) abort(403);
        $this->validate($request, [
            'date' => 'required|date_format:Y-m-d',
            'from_time' => 'required|date_format:Y-m-d H:i:s',
            'to_time' => 'required|date_format:Y-m-d H:i:s',
        ]);

        $report = $this->service->getOrCreateReport(
            $request->input('date'), $request->input('from_time'), $request->input('to_time'), auth()->id()
        );
        if ($report->isFinal()) {
            return response()->json(['message' => 'Báo cáo đã chốt, cần mở khóa trước.'], 422);
        }
        $this->service->fetchAndStore($report, $request->input('from_time'), $request->input('to_time'), auth()->id());
        return response()->json(['ok' => true]);
    }

    /** Sửa 1 ô (manual_value) hoặc ghi chú khoa. */
    public function saveCell(Request $request)
    {
        $this->validate($request, [
            'report_id' => 'required|integer',
            'dept_config_id' => 'required|integer',
            'metric_code' => 'required|string|max:50',
            'manual_value' => 'nullable|numeric',
            'note' => 'nullable|string',
        ]);
        $report = GiaoBanReport::findOrFail($request->input('report_id'));
        if (!GiaoBanPermission::canEditReport($report->status, $this->isAdmin())) {
            return response()->json(['message' => 'Báo cáo đã chốt.'], 422);
        }
        if (!GiaoBanPermission::canEditDept($this->isAdmin(), $this->assignedDeptIds(), $request->input('dept_config_id'))) {
            abort(403, 'Bạn không có quyền nhập số liệu khoa này.');
        }

        $cell = GiaoBanReportCell::firstOrNew([
            'report_id' => $report->id,
            'dept_config_id' => (int) $request->input('dept_config_id'),
            'metric_code' => $request->input('metric_code'),
        ]);
        if ($request->input('metric_code') === 'note') {
            $cell->note = $request->input('note');
        } else {
            $cell->manual_value = $request->filled('manual_value') ? $request->input('manual_value') : null;
        }
        $cell->updated_by = auth()->id();
        $cell->save();
        return response()->json(['ok' => true]);
    }

    /** Ghi chú chung (chỉ admin). */
    public function saveGeneralNote(Request $request)
    {
        if (!$this->isAdmin()) abort(403);
        $report = GiaoBanReport::findOrFail($request->input('report_id'));
        if ($report->isFinal()) return response()->json(['message' => 'Báo cáo đã chốt.'], 422);
        $report->update(['general_note' => $request->input('general_note')]);
        return response()->json(['ok' => true]);
    }

    public function finalize(Request $request)
    {
        if (!$this->isAdmin()) abort(403);
        $report = GiaoBanReport::findOrFail($request->input('report_id'));
        $report->update([
            'status' => 'final', 'finalized_by' => auth()->id(), 'finalized_at' => date('Y-m-d H:i:s'),
        ]);
        return response()->json(['ok' => true]);
    }

    public function unlock(Request $request)
    {
        if (!$this->isAdmin()) abort(403);
        $report = GiaoBanReport::findOrFail($request->input('report_id'));
        $report->update([
            'status' => 'draft', 'unlocked_by' => auth()->id(), 'unlocked_at' => date('Y-m-d H:i:s'),
        ]);
        return response()->json(['ok' => true]);
    }

    public function export(Request $request)
    {
        abort(501, 'Chưa triển khai');
    }
}

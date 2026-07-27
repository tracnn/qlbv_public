<?php

namespace App\Http\Controllers\KHTH;

use App\Http\Controllers\Controller;
use App\Models\GiaoBan\GiaoBanDeptConfig;
use App\Models\GiaoBan\GiaoBanReport;
use App\Models\GiaoBan\GiaoBanReportCell;
use App\Models\GiaoBan\GiaoBanReportBed;
use App\Models\GiaoBan\GiaoBanUserDepartment;
use App\Models\GiaoBan\GiaoBanDutyPosition;
use App\Models\GiaoBan\GiaoBanReportDuty;
use App\Models\GiaoBan\GiaoBanDutyEditor;
use App\Services\GiaoBan\GiaoBanDutyService;
use App\Services\GiaoBan\GiaoBanPermission;
use App\Services\GiaoBan\GiaoBanReportService;
use App\Services\GiaoBan\MetricSchema;
use App\Services\GiaoBan\NoteSanitizer;
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
        $isAdmin = $this->isAdmin();
        $assigned = $this->assignedDeptIds();
        $report = GiaoBanReport::with('cells')->where('report_date', $date)->first();

        $allConfigs = GiaoBanDeptConfig::where('is_active', true)->orderBy('sort_order')->get();
        // Quyen quyet dinh du lieu nao ROI KHOI server, khong phai CSS quyet dinh.
        $visibleIds = GiaoBanPermission::visibleDeptConfigIds($isAdmin, $assigned, $allConfigs->pluck('id')->all());

        $configs = $allConfigs
            ->filter(function ($c) use ($visibleIds) {
                return in_array((int) $c->id, $visibleIds, true);
            })
            ->map(function ($c) {
                return [
                    'id' => $c->id, 'display_name' => $c->display_name,
                    'his_department_id' => $c->his_department_id, 'metrics' => $c->metricList(),
                ];
            })
            ->values();

        $cells = []; $warnings = [];
        if ($report) {
            foreach ($report->cells as $c) {
                if (!in_array((int) $c->dept_config_id, $visibleIds, true)) continue;
                $cells[] = [
                    'dept_config_id' => $c->dept_config_id, 'metric_code' => $c->metric_code,
                    'auto_value' => $c->auto_value, 'manual_value' => $c->manual_value, 'note' => $c->note,
                    'carried_over' => (bool) $c->carried_over,
                ];
            }
            $warnings = GiaoBanReportService::checkBalance(
                $this->service->cellMap($report),
                $visibleIds
            );
        }

        $positions = GiaoBanDutyPosition::where('is_active', true)->orderBy('sort_order')->get(['id', 'name']);
        $duties = [];
        if ($report) {
            foreach (GiaoBanReportDuty::where('report_id', $report->id)->orderBy('position_id')->orderBy('id')->get() as $d) {
                $duties[] = ['id' => $d->id, 'position_id' => $d->position_id,
                    'employee_id' => $d->employee_id, 'person_name' => $d->person_name, 'phone' => $d->phone];
            }
        }

        // Luot kham theo tung phong trong ky — cho slide phong kham tren trinh chieu.
        // Toan vien nen chi admin; nguoi dung khoa khong phai ganh them mot truy van HIS.
        $roomStats = [];
        if ($report && $isAdmin) {
            $roomStats = app(\App\Services\GiaoBan\GiaoBanMetricService::class)
                ->examByRoom($report->from_time, $report->to_time);
        }

        // Cong suat giuong la du lieu toan vien (tong + chi tiet tung khoa) -> chi admin duoc xem.
        $bedTotal = 0; $bedUsed = 0; $bedByDept = [];
        if ($report && $isAdmin) {
            foreach (GiaoBanReportBed::where('report_id', $report->id)->get() as $b) {
                $bedTotal += (int) $b->total_beds; $bedUsed += (int) $b->used_beds;
                $bedByDept[(int) $b->department_id] = ['total' => (int) $b->total_beds, 'used' => (int) $b->used_beds];
            }
        }
        $bedByConfig = [];
        foreach (($isAdmin ? $allConfigs : []) as $cfgBed) {
            $t = 0; $u = 0; $has = false;
            foreach ($cfgBed->hisDepartmentIds() as $hid) {
                if (isset($bedByDept[$hid])) { $t += $bedByDept[$hid]['total']; $u += $bedByDept[$hid]['used']; $has = true; }
            }
            if ($has && $t > 0) $bedByConfig[] = ['dept_config_id' => $cfgBed->id, 'display_name' => $cfgBed->display_name, 'total' => $t, 'used' => $u];
        }

        // Công suất theo từng khoa HIS có giường (kèm tên khoa) — fallback khi không có khoa báo cáo điều trị.
        $bedByDepartment = [];
        if (!empty($bedByDept)) {
            $names = [];
            try {
                $ids = implode(',', array_map('intval', array_keys($bedByDept)));
                foreach (\Illuminate\Support\Facades\DB::connection('HISPro')
                    ->select("SELECT id, department_name FROM his_department WHERE id IN ($ids)") as $d) {
                    $row = (array) array_change_key_case((array) $d, CASE_LOWER);
                    $names[(int) $row['id']] = $row['department_name'];
                }
            } catch (\Exception $e) { /* HIS lỗi -> dùng id làm tên */ }
            foreach ($bedByDept as $hid => $bd) {
                if ($bd['total'] <= 0) continue;
                $bedByDepartment[] = [
                    'department_id' => $hid,
                    'display_name' => isset($names[$hid]) ? $names[$hid] : ('Khoa ' . $hid),
                    'total' => $bd['total'], 'used' => $bd['used'],
                ];
            }
        }

        // $report duoc nap kem quan he cells; tra nguyen doi tuong thi TOAN BO o so lieu di theo
        // trong JSON, vo hieu hoa viec loc $cells o tren. Nen liet ke tuong minh dung nhung truong
        // cac view thuc su doc — cot hay quan he them sau nay cung khong the di ke.
        $reportOut = null;
        if ($report) {
            $reportOut = [
                'id' => $report->id, 'status' => $report->status,
                'from_time' => $report->from_time, 'to_time' => $report->to_time,
                'general_note' => $report->general_note,
            ];
        }

        return response()->json([
            'report' => $reportOut, 'configs' => $configs, 'cells' => $cells,
            'balance_warnings' => $warnings,
            'is_admin' => $isAdmin, 'assigned_dept_ids' => $assigned,
            'no_assignment' => GiaoBanPermission::chuaPhanCongKhoa($isAdmin, $visibleIds),
            'duty_positions' => $positions, 'duties' => $duties,
            'can_edit_duty' => $this->canEditDuty(),
            'room_stats' => $roomStats,
            'bed_total' => $bedTotal, 'bed_used' => $bedUsed,
            'bed_by_config' => $bedByConfig, 'bed_by_department' => $bedByDepartment,
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

        $maChiTieu = $request->input('metric_code');
        $laGhiChuKhoa = $maChiTieu === 'note';

        $metric = null;
        if (!$laGhiChuKhoa) {
            $cfg = GiaoBanDeptConfig::find($request->input('dept_config_id'));
            $metric = $cfg ? $cfg->metricByCode($maChiTieu) : null;
            if (!$metric) {
                // Ma chi tieu khong co trong cau hinh khoa -> khong co khai bao de kiem.
                // Van cho luu (tranh khoa khoa dang nhap lieu khi cau hinh vua doi giua ca),
                // nhung ghi log de con lan ra khi so lieu bat thuong.
                \Log::warning('Giao ban saveCell: khong tim thay khai bao chi tieu', [
                    'dept_config_id' => $request->input('dept_config_id'),
                    'metric_code' => $maChiTieu,
                ]);
            }
        }

        $laChiTieuChuoi = $metric !== null && MetricSchema::laKieuChuoi($metric);

        // Kiem rang buoc theo khai bao truoc khi ghi. Chi tieu chuoi kiem tren o `note`,
        // chi tieu so kiem tren o `manual_value`.
        if ($metric) {
            $giaTri = $laChiTieuChuoi ? $request->input('note') : $request->input('manual_value');
            if ($giaTri !== null && $giaTri !== '') {
                $loi = MetricSchema::kiemGiaTriNhapTay($metric, $giaTri);
                if ($loi !== null) {
                    return response()->json(['message' => $loi], 422);
                }
            }
        }

        $cell = GiaoBanReportCell::firstOrNew([
            'report_id' => $report->id,
            'dept_config_id' => (int) $request->input('dept_config_id'),
            'metric_code' => $maChiTieu,
        ]);
        if ($laGhiChuKhoa) {
            // Ghi chu khoa la o giau dinh dang -> loc HTML theo whitelist.
            $cell->note = NoteSanitizer::clean($request->input('note'));
        } elseif ($laChiTieuChuoi) {
            // Chi tieu chuoi la van ban thuan -> ma hoa toan bo, khong giu the nao.
            // Nap vao textarea phai giai ma nguoc, xem giaiMaHtml() trong giaoban-index.blade.php.
            $cell->note = NoteSanitizer::cleanPlain($request->input('note'));
        } else {
            $cell->manual_value = $request->filled('manual_value') ? $request->input('manual_value') : null;
            $cell->carried_over = false;   // khoa da xac nhan -> khong con la so ke thua
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
        $report->update(['general_note' => NoteSanitizer::clean($request->input('general_note'))]);
        return response()->json(['ok' => true]);
    }

    protected function canEditDuty()
    {
        return GiaoBanDutyService::canEdit($this->isAdmin(),
            GiaoBanDutyEditor::pluck('user_id')->all(), auth()->id());
    }

    public function searchEmployees(Request $request)
    {
        $raw = trim((string) $request->input('q', ''));
        if (mb_strlen($raw) < 2) return response()->json([]);
        $q = \App\Services\GiaoBan\ViSearch::normalize($raw); // bỏ dấu + lowercase
        $nameExpr = \App\Services\GiaoBan\ViSearch::noDiacriticsSql('tdl_username');
        try {
            $rows = \Illuminate\Support\Facades\DB::connection('HISPro')->select(
                "SELECT * FROM (
                    SELECT id, loginname, tdl_username, tdl_mobile, title FROM his_employee
                    WHERE is_delete = 0 AND is_active = 1
                      AND ($nameExpr LIKE :q1 OR LOWER(loginname) LIKE :q2 OR LOWER(employee_code) LIKE :q3)
                    ORDER BY tdl_username
                 ) WHERE ROWNUM <= 30",
                ['q1' => '%' . $q . '%', 'q2' => '%' . $q . '%', 'q3' => '%' . $q . '%']
            );
        } catch (\Exception $e) { return response()->json([]); }
        $out = array_map(function ($r) {
            $u = (object) array_change_key_case((array) $r, CASE_LOWER);
            return ['id' => (int) $u->id, 'name' => $u->tdl_username, 'phone' => $u->tdl_mobile,
                'title' => $u->title, 'loginname' => $u->loginname];
        }, $rows);
        return response()->json($out);
    }

    protected function reportForDuty($date)
    {
        $from = date('Y-m-d 07:00:00', strtotime('-1 day', strtotime($date)));
        $to = date('Y-m-d 07:00:00', strtotime($date));
        return $this->service->getOrCreateReport($date, $from, $to, auth()->id());
    }

    public function addDuty(Request $request)
    {
        if (!$this->canEditDuty()) abort(403);
        $this->validate($request, [
            'date' => 'required|date_format:Y-m-d', 'position_id' => 'required|integer',
            'employee_id' => 'nullable|integer', 'person_name' => 'nullable|string|max:255', 'phone' => 'nullable|string|max:50',
        ]);
        $report = $this->reportForDuty($request->input('date'));
        if ($report->isFinal()) return response()->json(['message' => 'Báo cáo đã chốt.'], 422);
        $d = (new GiaoBanDutyService())->addDuty($report->id, $request->input('position_id'),
            $request->input('employee_id'), $request->input('person_name'), $request->input('phone'));
        return response()->json(['ok' => true, 'id' => $d->id]);
    }

    public function removeDuty(Request $request)
    {
        if (!$this->canEditDuty()) abort(403);
        $this->validate($request, ['duty_id' => 'required|integer']);
        $duty = GiaoBanReportDuty::find($request->input('duty_id'));
        if ($duty) {
            $report = GiaoBanReport::find($duty->report_id);
            if ($report && $report->isFinal()) return response()->json(['message' => 'Báo cáo đã chốt.'], 422);
            (new GiaoBanDutyService())->removeDuty($duty->id);
        }
        return response()->json(['ok' => true]);
    }

    public function updateDutyPhone(Request $request)
    {
        if (!$this->canEditDuty()) abort(403);
        $this->validate($request, ['duty_id' => 'required|integer', 'phone' => 'nullable|string|max:50']);
        $duty = GiaoBanReportDuty::find($request->input('duty_id'));
        if ($duty) {
            $report = GiaoBanReport::find($duty->report_id);
            if ($report && $report->isFinal()) return response()->json(['message' => 'Báo cáo đã chốt.'], 422);
            (new GiaoBanDutyService())->updatePhone($duty->id, $request->input('phone'));
        }
        return response()->json(['ok' => true]);
    }

    /** Sao chép kíp trực từ ngày gần nhất trước đó. */
    public function copyDuties(Request $request)
    {
        if (!$this->canEditDuty()) abort(403);
        $this->validate($request, ['date' => 'required|date_format:Y-m-d']);
        $from = date('Y-m-d 07:00:00', strtotime('-1 day', strtotime($request->input('date'))));
        $to = date('Y-m-d 07:00:00', strtotime($request->input('date')));
        $report = $this->service->getOrCreateReport($request->input('date'), $from, $to, auth()->id());
        if ($report->isFinal()) {
            return response()->json(['message' => 'Báo cáo đã chốt.'], 422);
        }
        $n = (new GiaoBanDutyService())->copyFromPrevious($report);
        if ($n === 0) return response()->json(['message' => 'Không có kíp trực ngày trước để sao chép.'], 422);
        return response()->json(['ok' => true, 'copied' => $n]);
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

    /** Trang trình chiếu toàn màn hình cho báo cáo ngày đang chọn. */
    /** Trinh chieu toan vien -> chi admin (nguoi khoa chi duoc xem khoa minh). */
    public function present(Request $request)
    {
        if (!$this->isAdmin()) abort(403);
        $date = $request->input('date', date('Y-m-d'));
        return view('khth.giaoban-present', [
            'date' => $date,
            'showUrl' => route('khth.giao-ban-show'),
        ]);
    }

    /** File Excel chua so lieu toan vien -> chi admin. Truoc day khong chan quyen gi. */
    public function export(Request $request)
    {
        if (!$this->isAdmin()) abort(403);
        $date = $request->input('date', date('Y-m-d'));
        $report = \App\Models\GiaoBan\GiaoBanReport::with('cells')->where('report_date', $date)->firstOrFail();
        $configs = \App\Models\GiaoBan\GiaoBanDeptConfig::where('is_active', true)->orderBy('sort_order')->get();
        $filename = 'bao-cao-giao-ban-' . $date . ($report->status === 'final' ? '' : '-nhap') . '.xlsx';
        return \Maatwebsite\Excel\Facades\Excel::download(new \App\Exports\GiaoBanExport($report, $configs), $filename);
    }
}

<?php

namespace App\Http\Controllers\KHTH;

use App\Http\Controllers\Controller;
use App\Models\OrderCheck\OrderCheckViolation;
use App\Models\OrderCheck\OrderCheckRule;
use App\Models\OrderCheck\OrderCheckRuleLog;
use App\Services\OrderCheck\TreatmentIssueService;
use App\Services\OrderCheck\ViolationQueryService;
use App\Exports\OrderCheckViolationExport;
use Illuminate\Http\Request;
use Yajra\Datatables\Datatables;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;

class OrderCheckController extends Controller
{
    protected $service;

    public function __construct(ViolationQueryService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        $rules = OrderCheckRule::orderBy('code')->get(['code', 'name']);
        $danhSachCoSo = \App\Services\BHYT\DanhSachCoSo::danhSach();

        return view('khth.order-check', compact('rules', 'danhSachCoSo'));
    }

    public function summary(Request $request)
    {
        $base = $this->service->filtered($request);

        $bySeverity = (clone $base)->selectRaw('severity, COUNT(*) c')->groupBy('severity')->pluck('c', 'severity');
        $byStatus = (clone $base)->selectRaw('status, COUNT(*) c')->groupBy('status')->pluck('c', 'status');

        return response()->json([
            'total' => (clone $base)->count(),
            'critical' => (int) ($bySeverity['critical'] ?? 0),
            'warning' => (int) ($bySeverity['warning'] ?? 0),
            'info' => (int) ($bySeverity['info'] ?? 0),
            'new' => (int) ($byStatus['new'] ?? 0),
            'processed' => (int) ($byStatus['processed'] ?? 0),
            'false_positive' => (int) ($byStatus['false_positive'] ?? 0),
        ]);
    }

    /** Thống kê quét: tổng đã quét + theo từng source_key (từ order_check_rule_logs). */
    public function scanStats(Request $request)
    {
        $labels = [
            'his_service_req' => 'Phiếu chỉ định (thời gian/CCHN/thiếu CĐ)',
            'his_medicine_interactive' => 'Tương tác thuốc',
            'his_exp_mest_medicine' => 'Thuốc (liều)',
            'his_sere_serv_restriction' => 'Dịch vụ (giới tính/tuổi)',
        ];

        $q = OrderCheckRuleLog::query();
        if ($request->filled('date_from')) {
            $q->where('started_at', '>=', $request->input('date_from') . ' 00:00:00');
        }
        if ($request->filled('date_to')) {
            $q->where('started_at', '<=', $request->input('date_to') . ' 23:59:59');
        }

        $rows = (clone $q)
            ->selectRaw("source_key,
                SUM(scanned_count) as scanned,
                SUM(violation_count) as violations,
                COUNT(*) as runs,
                SUM(CASE WHEN status = 'error' THEN 1 ELSE 0 END) as errors,
                SUM(TIMESTAMPDIFF(SECOND, started_at, finished_at)) as total_secs,
                SUM(CASE WHEN finished_at IS NOT NULL THEN 1 ELSE 0 END) as finished_runs,
                MAX(finished_at) as last_run")
            ->groupBy('source_key')
            ->get();

        $sources = [];
        $totalScanned = 0;
        $totalViolations = 0;
        $totalSecs = 0;
        $finishedRuns = 0;
        foreach ($rows as $r) {
            $totalScanned += (int) $r->scanned;
            $totalViolations += (int) $r->violations;
            $secs = (int) $r->total_secs;
            $fin = (int) $r->finished_runs;
            $totalSecs += $secs;
            $finishedRuns += $fin;
            $sources[] = [
                'source_key' => $r->source_key,
                'label' => $labels[$r->source_key] ?? $r->source_key,
                'scanned' => (int) $r->scanned,
                'violations' => (int) $r->violations,
                'runs' => (int) $r->runs,
                'errors' => (int) $r->errors,
                'total_secs' => $secs,
                'avg_secs' => $fin > 0 ? round($secs / $fin, 1) : 0,
                'last_run' => $r->last_run ? Carbon::parse($r->last_run)->format('d/m/Y H:i') : '',
            ];
        }

        return response()->json([
            'total_scanned' => $totalScanned,
            'total_violations' => $totalViolations,
            'total_runs' => (int) (clone $q)->count(),
            'total_secs' => $totalSecs,
            'avg_secs' => $finishedRuns > 0 ? round($totalSecs / $finishedRuns, 1) : 0,
            'sources' => $sources,
        ]);
    }

    public function fetch(Request $request)
    {
        $query = $this->service->filtered($request)->orderBy('detected_at', 'desc');

        return Datatables::of($query)
            ->editColumn('detected_at', function ($v) {
                return $v->detected_at ? Carbon::parse($v->detected_at)->format('d/m/Y H:i') : '';
            })
            ->addColumn('severity_badge', function ($v) {
                $map = [
                    'critical' => '<span class="label label-danger">Nghiêm trọng</span>',
                    'warning' => '<span class="label label-warning">Cảnh báo</span>',
                    'info' => '<span class="label label-info">Thông tin</span>',
                ];
                return $map[$v->severity] ?? $v->severity;
            })
            ->addColumn('status_badge', function ($v) {
                $map = [
                    'new' => '<span class="label label-default">Mới</span>',
                    'seen' => '<span class="label label-primary">Đã xem</span>',
                    'processed' => '<span class="label label-success">Đã xử lý</span>',
                    'false_positive' => '<span class="label label-warning">Bỏ qua</span>',
                ];
                return $map[$v->status] ?? $v->status;
            })
            ->addColumn('doctor', function ($v) {
                return $v->doctor_username ?: $v->doctor_loginname;
            })
            ->addColumn('department_label', function ($v) {
                if (!$v->department_name && !$v->department_code) {
                    return $v->department_id;
                }
                return trim(($v->department_code ? '[' . $v->department_code . '] ' : '') . $v->department_name);
            })
            ->addColumn('actions', function ($v) {
                return '<div class="btn-group">'
                    . '<button class="btn btn-xs btn-success oc-act" data-id="' . $v->id . '" data-status="processed">Đã xử lý</button> '
                    . '<button class="btn btn-xs btn-warning oc-act" data-id="' . $v->id . '" data-status="false_positive">Bỏ qua</button>'
                    . '</div>';
            })
            ->rawColumns(['severity_badge', 'status_badge', 'actions'])
            ->make(true);
    }

    public function updateStatus(Request $request)
    {
        $request->validate([
            'id' => 'required|integer',
            'status' => 'required|string',
            'note' => 'nullable|string|max:1000',
        ]);

        if (!$this->service->isValidUpdateStatus($request->input('status'))) {
            return response()->json(['ok' => false, 'message' => 'Trạng thái không hợp lệ'], 422);
        }

        $v = OrderCheckViolation::find($request->input('id'));
        if (!$v) {
            return response()->json(['ok' => false, 'message' => 'Không tìm thấy vi phạm'], 404);
        }

        $user = auth()->user();
        $v->status = $request->input('status');
        $v->processed_by = $user ? $user->name : null;
        $v->processed_at = Carbon::now();
        if ($request->filled('note')) {
            $v->note = $request->input('note');
        }
        $v->save();

        return response()->json(['ok' => true]);
    }

    public function export(Request $request)
    {
        $fileName = 'sai_sot_y_lenh_' . Carbon::now()->format('YmdHis') . '.xlsx';
        return Excel::download(new OrderCheckViolationExport($request->all()), $fileName);
    }

    /**
     * API JSON chỉ đọc: tra cứu TOÀN BỘ lỗi của một đợt điều trị - sai sót y lệnh, lỗi
     * tra thẻ BHYT, lỗi XML3176 - trong một lần gọi.
     */
    public function apiViolations(Request $request, TreatmentIssueService $issueService)
    {
        $treatmentCode = trim((string) $request->input('treatment_code'));
        $treatmentId = trim((string) $request->input('treatment_id'));

        // Kiểm tham số thủ công thay vì $request->validate(): validate() trả khuôn lỗi
        // mặc định của Laravel, khác hẳn khuôn {success,error,meta} của ApiAuthMiddleware,
        // buộc bên gọi phải xử lý hai định dạng.
        if ($treatmentCode === '' && $treatmentId === '') {
            return $this->loiApi(
                'VALIDATION_ERROR',
                'Thiếu tham số bắt buộc',
                'Cần truyền treatment_code',
                422
            );
        }

        try {
            $ketQua = $issueService->cua(
                $treatmentCode !== '' ? $treatmentCode : null,
                $treatmentId !== '' ? $treatmentId : null,
                ['status' => $request->input('status')]
            );
        } catch (\Exception $e) {
            \Log::error('Loi API tra cuu loi dot dieu tri', [
                'treatment_code' => $treatmentCode,
                'treatment_id' => $treatmentId,
                'loi' => $e->getMessage(),
            ]);

            return $this->loiApi(
                'INTERNAL_ERROR',
                'Lỗi hệ thống',
                'Vui lòng thử lại sau',
                500
            );
        }

        return response()->json([
            'success' => true,
            'data' => $ketQua['data'],
            'summary' => $ketQua['summary'],
            'meta' => $this->metaApi(),
        ]);
    }

    /** Khuôn lỗi thống nhất với ApiAuthMiddleware. Không lộ thông điệp ngoại lệ ra ngoài. */
    protected function loiApi($code, $message, $details, $status)
    {
        return response()->json([
            'success' => false,
            'error' => [
                'code' => $code,
                'message' => $message,
                'details' => $details,
            ],
            'meta' => $this->metaApi(),
        ], $status);
    }

    protected function metaApi()
    {
        return [
            'timestamp' => Carbon::now()->format('YmdHis'),
            'request_id' => uniqid('req_'),
        ];
    }
}

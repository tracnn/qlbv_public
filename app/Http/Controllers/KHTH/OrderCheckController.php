<?php

namespace App\Http\Controllers\KHTH;

use App\Http\Controllers\Controller;
use App\Models\OrderCheck\OrderCheckViolation;
use App\Models\OrderCheck\OrderCheckRule;
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
        return view('khth.order-check', compact('rules'));
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
}

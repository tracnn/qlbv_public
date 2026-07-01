<?php

namespace App\Http\Controllers\KHTH;

use App\Http\Controllers\Controller;
use App\Models\OrderCheck\OrderCheckRule;
use Illuminate\Http\Request;
use Yajra\Datatables\Datatables;

class OrderCheckRuleController extends Controller
{
    const SEVERITIES = ['info', 'warning', 'critical'];

    public function index()
    {
        return view('khth.order-check-rule');
    }

    public function fetch()
    {
        return Datatables::of(OrderCheckRule::query()->orderBy('family')->orderBy('code'))
            ->addColumn('severity_badge', function ($r) {
                $map = [
                    'critical' => '<span class="label label-danger">Nghiêm trọng</span>',
                    'warning' => '<span class="label label-warning">Cảnh báo</span>',
                    'info' => '<span class="label label-info">Thông tin</span>',
                ];
                return isset($map[$r->severity]) ? $map[$r->severity] : e($r->severity);
            })
            ->addColumn('active_text', function ($r) {
                return $r->is_active
                    ? '<span class="label label-success">Bật</span>'
                    : '<span class="label label-default">Tắt</span>';
            })
            ->editColumn('updated_at', function ($r) {
                return $r->updated_at ? $r->updated_at->format('d/m/Y H:i') : '';
            })
            ->addColumn('actions', function ($r) {
                $label = $r->is_active ? 'Tắt' : 'Bật';
                $cls = $r->is_active ? 'btn-default' : 'btn-success';
                return '<button class="btn btn-xs btn-primary rule-edit" data-id="' . $r->id . '">Sửa</button> '
                    . '<button class="btn btn-xs ' . $cls . ' rule-toggle" data-id="' . $r->id . '">' . $label . '</button>';
            })
            ->rawColumns(['severity_badge', 'active_text', 'actions'])
            ->make(true);
    }

    public function update(Request $request, $id)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'severity' => 'required|in:' . implode(',', self::SEVERITIES),
        ]);
        $rule = OrderCheckRule::findOrFail($id);
        $rule->name = $request->input('name');
        $rule->severity = $request->input('severity');
        $rule->is_active = $request->input('is_active') ? 1 : 0;
        $rule->save();
        return response()->json(['ok' => true]);
    }

    public function toggle(Request $request, $id)
    {
        $rule = OrderCheckRule::findOrFail($id);
        $rule->is_active = $rule->is_active ? 0 : 1;
        $rule->save();
        return response()->json(['ok' => true, 'is_active' => (bool) $rule->is_active]);
    }
}

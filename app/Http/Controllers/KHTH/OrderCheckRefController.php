<?php

namespace App\Http\Controllers\KHTH;

use App\Http\Controllers\Controller;
use App\Models\OrderCheck\OrderCheckRefServiceRestriction;
use Illuminate\Http\Request;
use Yajra\Datatables\Datatables;

class OrderCheckRefController extends Controller
{
    public function index()
    {
        return view('khth.order-check-ref');
    }

    public function fetch()
    {
        return Datatables::of(OrderCheckRefServiceRestriction::query()->orderBy('service_code'))
            ->addColumn('gender_text', function ($r) {
                $map = [1 => 'Nữ', 2 => 'Nam'];
                return isset($map[$r->required_gender_id]) ? $map[$r->required_gender_id] : '';
            })
            ->addColumn('age_text', function ($r) {
                if ($r->age_from === null && $r->age_to === null) return '';
                return ($r->age_from === null ? '' : $r->age_from) . ' - ' . ($r->age_to === null ? '' : $r->age_to);
            })
            ->addColumn('active_text', function ($r) {
                return $r->is_active ? '<span class="label label-success">Bật</span>' : '<span class="label label-default">Tắt</span>';
            })
            ->addColumn('actions', function ($r) {
                return '<button class="btn btn-xs btn-primary ref-edit" data-id="' . $r->id . '">Sửa</button> '
                    . '<button class="btn btn-xs btn-danger ref-del" data-id="' . $r->id . '">Xóa</button>';
            })
            ->rawColumns(['active_text', 'actions'])
            ->make(true);
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);
        OrderCheckRefServiceRestriction::create($data);
        return response()->json(['ok' => true]);
    }

    public function update(Request $request, $id)
    {
        $row = OrderCheckRefServiceRestriction::findOrFail($id);
        $row->update($this->validateData($request, $id));
        return response()->json(['ok' => true]);
    }

    public function destroy($id)
    {
        OrderCheckRefServiceRestriction::where('id', $id)->delete();
        return response()->json(['ok' => true]);
    }

    private function validateData(Request $request, $id = null)
    {
        $unique = 'unique:order_check_ref_service_restriction,service_code' . ($id ? (',' . $id) : '');
        $request->validate([
            'service_code' => 'required|string|max:50|' . $unique,
            'service_name' => 'nullable|string|max:255',
            'required_gender_id' => 'nullable|in:1,2',
            'age_from' => 'nullable|integer|min:0|max:150',
            'age_to' => 'nullable|integer|min:0|max:150',
            'note' => 'nullable|string|max:255',
        ]);
        return [
            'service_code' => $request->input('service_code'),
            'service_name' => $request->input('service_name'),
            'required_gender_id' => $request->input('required_gender_id') ?: null,
            'age_from' => $request->input('age_from') !== null && $request->input('age_from') !== '' ? (int) $request->input('age_from') : null,
            'age_to' => $request->input('age_to') !== null && $request->input('age_to') !== '' ? (int) $request->input('age_to') : null,
            'note' => $request->input('note'),
            'is_active' => $request->input('is_active', 1) ? 1 : 0,
        ];
    }
}

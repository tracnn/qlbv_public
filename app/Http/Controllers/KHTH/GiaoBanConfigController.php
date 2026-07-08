<?php

namespace App\Http\Controllers\KHTH;

use App\Http\Controllers\Controller;
use App\Models\GiaoBan\GiaoBanDeptConfig;
use App\Models\GiaoBan\GiaoBanUserDepartment;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GiaoBanConfigController extends Controller
{
    public function __construct()
    {
        $this->middleware(function ($request, $next) {
            if (!auth()->user()->can('giaoban-admin')) abort(403);
            return $next($request);
        });
    }

    public function index()
    {
        $hisDepartments = collect(DB::connection('HISPro')->select(
            'SELECT id, department_code, department_name FROM his_department WHERE is_delete = 0 ORDER BY department_name'
        ))->map(function ($r) {
            return (object) array_change_key_case((array) $r, CASE_LOWER);
        });
        return view('khth.giaoban-config', [
            'hisDepartments' => $hisDepartments,
            'users' => User::orderBy('name')->get(['id', 'name', 'email']),
        ]);
    }

    public function fetch()
    {
        $configs = GiaoBanDeptConfig::orderBy('sort_order')->get();
        $assignments = GiaoBanUserDepartment::all();
        return response()->json(['configs' => $configs, 'assignments' => $assignments]);
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'display_name' => 'required|string|max:255',
            'his_department_id' => 'nullable|integer',
            'sort_order' => 'nullable|integer',
            'metrics' => 'required|string',
        ]);
        json_decode($request->input('metrics'), true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return response()->json(['message' => 'metrics không phải JSON hợp lệ'], 422);
        }
        $cfg = GiaoBanDeptConfig::create($request->only(['display_name', 'his_department_id', 'sort_order', 'metrics'])
            + ['is_active' => true]);
        return response()->json(['ok' => true, 'id' => $cfg->id]);
    }

    public function update(Request $request, $id)
    {
        $cfg = GiaoBanDeptConfig::findOrFail($id);
        if ($request->filled('metrics')) {
            json_decode($request->input('metrics'), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return response()->json(['message' => 'metrics không phải JSON hợp lệ'], 422);
            }
        }
        $cfg->update($request->only(['display_name', 'his_department_id', 'sort_order', 'metrics', 'is_active']));
        return response()->json(['ok' => true]);
    }

    /** Gán lại toàn bộ khoa cho 1 user. */
    public function assignUser(Request $request)
    {
        $this->validate($request, ['user_id' => 'required|integer', 'dept_config_ids' => 'nullable|array']);
        $userId = (int) $request->input('user_id');
        GiaoBanUserDepartment::where('user_id', $userId)->delete();
        foreach ((array) $request->input('dept_config_ids', []) as $deptId) {
            GiaoBanUserDepartment::create(['user_id' => $userId, 'dept_config_id' => (int) $deptId]);
        }
        return response()->json(['ok' => true]);
    }
}

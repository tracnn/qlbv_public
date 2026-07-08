<?php

namespace App\Http\Controllers\KHTH;

use App\Http\Controllers\Controller;
use App\Models\GiaoBan\GiaoBanDeptConfig;
use App\Models\GiaoBan\GiaoBanUserDepartment;
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
            'SELECT id, department_code, department_name, NVL(is_exam,0) is_exam, NVL(is_clinical,0) is_clinical
             FROM his_department WHERE is_delete = 0 ORDER BY department_name'
        ))->map(function ($r) {
            return (object) array_change_key_case((array) $r, CASE_LOWER);
        });
        return view('khth.giaoban-config', ['hisDepartments' => $hisDepartments]);
    }

    public function fetch()
    {
        $configs = GiaoBanDeptConfig::orderBy('sort_order')->get();
        $assignments = GiaoBanUserDepartment::all();
        $userIds = $assignments->pluck('user_id')->unique()->filter()->values()->all();
        $users = [];
        if (count($userIds)) {
            $ids = implode(',', array_map('intval', $userIds));
            try {
                $rows = DB::connection('ACS_RS')->select(
                    "SELECT id, loginname, username FROM acs_user WHERE id IN ($ids)"
                );
                foreach ($rows as $r) {
                    $u = (object) array_change_key_case((array) $r, CASE_LOWER);
                    $users[(int) $u->id] = $u->username ?: $u->loginname;
                }
            } catch (\Exception $e) {
                $users = [];
            }
        }
        return response()->json(['configs' => $configs, 'assignments' => $assignments, 'user_names' => $users]);
    }

    /** Tim acs_user (CustomUser HIS) theo ten/loginname. */
    public function searchUsers(Request $request)
    {
        $q = strtolower(trim((string) $request->input('q', '')));
        if (mb_strlen($q) < 2) return response()->json([]);
        try {
            $rows = DB::connection('ACS_RS')->select(
                "SELECT * FROM (
                    SELECT id, loginname, username FROM acs_user
                    WHERE is_active = 1
                      AND (LOWER(loginname) LIKE :q1 OR LOWER(username) LIKE :q2)
                    ORDER BY username
                 ) WHERE ROWNUM <= 20",
                ['q1' => '%' . $q . '%', 'q2' => '%' . $q . '%']
            );
        } catch (\Exception $e) {
            return response()->json([]);
        }
        $out = array_map(function ($r) {
            return (object) array_change_key_case((array) $r, CASE_LOWER);
        }, $rows);
        return response()->json($out);
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'display_name' => 'required|string|max:255',
            'block_type' => 'required|in:dieu_tri,kham,can_lam_sang',
            'his_department_ids' => 'nullable|string',
            'sort_order' => 'nullable|integer',
            'metrics' => 'required|string',
        ]);
        if (!$this->validJson($request->input('metrics'))) {
            return response()->json(['message' => 'metrics không phải JSON hợp lệ'], 422);
        }
        if ($request->filled('his_department_ids') && !$this->validJson($request->input('his_department_ids'))) {
            return response()->json(['message' => 'his_department_ids không phải JSON hợp lệ'], 422);
        }
        $cfg = GiaoBanDeptConfig::create(
            $request->only(['display_name', 'block_type', 'his_department_ids', 'sort_order', 'metrics'])
            + ['is_active' => true]
        );
        return response()->json(['ok' => true, 'id' => $cfg->id]);
    }

    public function update(Request $request, $id)
    {
        $cfg = GiaoBanDeptConfig::findOrFail($id);
        if ($request->filled('block_type')) {
            $this->validate($request, ['block_type' => 'in:dieu_tri,kham,can_lam_sang']);
        }
        if ($request->filled('metrics') && !$this->validJson($request->input('metrics'))) {
            return response()->json(['message' => 'metrics không phải JSON hợp lệ'], 422);
        }
        if ($request->filled('his_department_ids') && !$this->validJson($request->input('his_department_ids'))) {
            return response()->json(['message' => 'his_department_ids không phải JSON hợp lệ'], 422);
        }
        $cfg->update($request->only(['display_name', 'block_type', 'his_department_ids', 'sort_order', 'metrics', 'is_active']));
        return response()->json(['ok' => true]);
    }

    /** Gán lại toàn bộ khoa cho 1 acs_user. */
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

    protected function validJson($s)
    {
        json_decode($s, true);
        return json_last_error() === JSON_ERROR_NONE;
    }
}

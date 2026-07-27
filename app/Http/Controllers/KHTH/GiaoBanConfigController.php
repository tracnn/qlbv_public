<?php

namespace App\Http\Controllers\KHTH;

use App\Http\Controllers\Controller;
use App\Models\GiaoBan\GiaoBanDeptConfig;
use App\Models\GiaoBan\GiaoBanUserDepartment;
use App\Models\GiaoBan\GiaoBanDutyPosition;
use App\Services\GiaoBan\GiaoBanCatalogService;
use App\Services\GiaoBan\MetricValidator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GiaoBanConfigController extends Controller
{
    public function __construct()
    {
        // Toàn bộ cấu hình yêu cầu giaoban-admin, TRỪ searchUsers (dùng chung cho
        // ô nhập kíp trực ở màn giao ban - cả role khoa được phép).
        $this->middleware(function ($request, $next) {
            if (!auth()->user()->can('giaoban-admin')) abort(403);
            return $next($request);
        })->except('searchUsers');
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
        $dutyPositions = GiaoBanDutyPosition::orderBy('sort_order')->get();

        $editorIds = \App\Models\GiaoBan\GiaoBanDutyEditor::pluck('user_id')->all();
        $editorNames = [];
        if (count($editorIds)) {
            $ids = implode(',', array_map('intval', $editorIds));
            try {
                foreach (DB::connection('ACS_RS')->select("SELECT id, loginname, username FROM acs_user WHERE id IN ($ids)") as $r) {
                    $u = (object) array_change_key_case((array) $r, CASE_LOWER);
                    $editorNames[(int) $u->id] = $u->username ?: $u->loginname;
                }
            } catch (\Exception $e) {}
        }

        $metricTemplates = \App\Models\GiaoBan\GiaoBanMetricTemplate::where('is_active', true)
            ->orderBy('sort_order')->get(['id', 'name', 'block_type', 'metrics']);

        return response()->json(['configs' => $configs, 'assignments' => $assignments, 'user_names' => $users, 'duty_positions' => $dutyPositions,
            'duty_editors' => $editorIds, 'duty_editor_names' => $editorNames, 'metric_templates' => $metricTemplates]);
    }

    /** Tim acs_user (CustomUser HIS) theo ten/loginname. */
    public function searchUsers(Request $request)
    {
        $raw = trim((string) $request->input('q', ''));
        if (mb_strlen($raw) < 2) return response()->json([]);
        $q = \App\Services\GiaoBan\ViSearch::normalize($raw); // bỏ dấu + lowercase
        $nameExpr = \App\Services\GiaoBan\ViSearch::noDiacriticsSql('username');
        try {
            $rows = DB::connection('ACS_RS')->select(
                "SELECT * FROM (
                    SELECT id, loginname, username FROM acs_user
                    WHERE is_active = 1
                      AND (LOWER(loginname) LIKE :q1 OR $nameExpr LIKE :q2)
                    ORDER BY username
                 ) WHERE ROWNUM <= 30",
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

    /** Toan bo danh muc nho + nhan, cho form builder tai mot lan khi mo modal. */
    public function catalogs()
    {
        return response()->json([
            'catalogs' => GiaoBanCatalogService::allSmall(),
            'labels' => GiaoBanCatalogService::labels(),
        ]);
    }

    /** Tim danh muc lon theo tu khoa, hoac tra nguoc theo danh sach id. */
    public function catalog(Request $request, $key)
    {
        if (!GiaoBanCatalogService::isRemote($key)) {
            return response()->json(['message' => 'Danh mục không hợp lệ'], 422);
        }
        if ($request->filled('ids')) {
            $ids = array_filter(explode(',', (string) $request->input('ids')), 'strlen');
            return response()->json(GiaoBanCatalogService::byIds($key, $ids));
        }
        return response()->json(GiaoBanCatalogService::search($key, $request->input('q', '')));
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
        $loi = MetricValidator::validateJson($request->input('metrics'), $request->input('block_type'));
        if (!empty($loi)) {
            return $this->traLoiChiTieu($loi);
        }
        if ($request->filled('his_department_ids') && !$this->validJson($request->input('his_department_ids'))) {
            return response()->json(['message' => 'his_department_ids không phải JSON hợp lệ', 'errors' => []], 422);
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
        // block_type dung de validate: uu tien gia tri gui len, khong co thi lay tu ban ghi
        $blockType = $request->filled('block_type') ? $request->input('block_type') : $cfg->block_type;

        if ($request->filled('metrics')) {
            $loi = MetricValidator::validateJson($request->input('metrics'), $blockType);
            if (!empty($loi)) {
                return $this->traLoiChiTieu($loi);
            }
        }
        if ($request->filled('his_department_ids') && !$this->validJson($request->input('his_department_ids'))) {
            return response()->json(['message' => 'his_department_ids không phải JSON hợp lệ', 'errors' => []], 422);
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

    public function storeDutyPosition(Request $request)
    {
        $this->validate($request, ['name' => 'required|string|max:255', 'sort_order' => 'nullable|integer']);
        $p = GiaoBanDutyPosition::create($request->only(['name', 'sort_order']) + ['is_active' => true]);
        return response()->json(['ok' => true, 'id' => $p->id]);
    }

    public function updateDutyPosition(Request $request, $id)
    {
        $p = GiaoBanDutyPosition::findOrFail($id);
        $p->update($request->only(['name', 'sort_order', 'is_active']));
        return response()->json(['ok' => true]);
    }

    public function assignDutyEditors(Request $request)
    {
        $this->validate($request, ['user_ids' => 'nullable|array']);
        \App\Models\GiaoBan\GiaoBanDutyEditor::query()->delete();
        foreach ((array) $request->input('user_ids', []) as $uid) {
            \App\Models\GiaoBan\GiaoBanDutyEditor::create(['user_id' => (int) $uid]);
        }
        return response()->json(['ok' => true]);
    }

    protected function validJson($s)
    {
        json_decode($s, true);
        return json_last_error() === JSON_ERROR_NONE;
    }

    /** Gói danh sách lỗi chỉ tiêu thành 422 để form builder tô đỏ đúng card. */
    protected function traLoiChiTieu(array $loi)
    {
        return response()->json(MetricValidator::toResponsePayload($loi), 422);
    }
}

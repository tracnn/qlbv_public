<?php
// app/Services/RevenueDeptRoomService.php
namespace App\Services;

use Illuminate\Http\Request;
use Carbon\Carbon;

class RevenueDeptRoomService
{
    /**
     * Tổng hợp doanh thu theo khoa (thuần). Giữ thứ tự tự nhiên (không sắp xếp).
     * @param iterable $rows  mỗi phần tử: ->department_id, ->department_name, ->thanh_tien, ->so_luong
     * @param int $soPhong    số phòng (distinct) trong kỳ, đếm sẵn ở tầng query
     * @return array{kpi: array, by_department: array}
     */
    public static function buildDeptSummary($rows, $soPhong = 0)
    {
        $byDept = [];
        $tong = 0;
        foreach ($rows as $r) {
            $tt = (float) $r->thanh_tien;
            $byDept[] = [
                'department_id'   => (int) $r->department_id,
                'department_name' => $r->department_name,
                'thanh_tien'      => $tt,
                'so_luong'        => (float) $r->so_luong,
                'pct'             => 0,
            ];
            $tong += $tt;
        }
        foreach ($byDept as &$d) {
            $d['pct'] = $tong > 0 ? round($d['thanh_tien'] / $tong * 100, 1) : 0;
        }
        unset($d);

        return [
            'kpi' => [
                'tong_doanh_thu' => $tong,
                'so_khoa'        => count($byDept),
                'so_phong'       => (int) $soPhong,
            ],
            'by_department' => $byDept,
        ];
    }

    /** Chuẩn hóa from/to (Y-m-d hoặc Y-m-d H:i:s) -> YmdHis */
    protected function normalizeRange(Request $request)
    {
        $from = $request->input('date_from');
        $to   = $request->input('date_to');
        if (strlen($from) == 10) $from = Carbon::createFromFormat('Y-m-d', $from)->startOfDay()->format('Y-m-d H:i:s');
        if (strlen($to)   == 10) $to   = Carbon::createFromFormat('Y-m-d', $to)->endOfDay()->format('Y-m-d H:i:s');
        return [
            Carbon::createFromFormat('Y-m-d H:i:s', $from)->format('YmdHis'),
            Carbon::createFromFormat('Y-m-d H:i:s', $to)->format('YmdHis'),
        ];
    }

    /** WHERE + bindings dùng chung cho summary/detail/dropdown */
    protected function commonConditions(Request $request)
    {
        list($from, $to) = $this->normalizeRange($request);
        $fromDay = substr($from, 0, 8) . '000000';
        $toDay   = substr($to, 0, 8) . '000000';

        $conds = [
            "ss.tdl_intruction_date BETWEEN :from_day AND :to_day", // cột index HIS_SERE_SERV_INDEX16
            "sr.intruction_time BETWEEN :from_time AND :to_time",
            "sr.is_active = 1", "sr.is_delete = 0", "ss.is_delete = 0",
        ];
        $binds = ['from_day' => $fromDay, 'to_day' => $toDay, 'from_time' => $from, 'to_time' => $to];

        if ($request->filled('department_id')) {
            $conds[] = "ss.tdl_execute_department_id = :department_id";
            $binds['department_id'] = $request->input('department_id');
        }
        if ($request->filled('room_id')) {
            $conds[] = "ss.tdl_execute_room_id = :room_id";
            $binds['room_id'] = $request->input('room_id');
        }
        if ($request->filled('room_type_id')) {
            // Lọc theo loại phòng qua his_room (master, có room_type_id) — không ép join view vào mọi query
            $conds[] = "ss.tdl_execute_room_id IN (SELECT id FROM his_room WHERE room_type_id = :room_type_id)";
            $binds['room_type_id'] = $request->input('room_type_id');
        }
        return [$conds, $binds];
    }

    /** Oracle trả tên cột HOA -> lowercase (bắt buộc trước khi dùng) */
    public function normalizeRows($rawRows)
    {
        return array_map(function ($row) {
            return (object) array_change_key_case((array) $row, CASE_LOWER);
        }, $rawRows);
    }

    /** Doanh thu theo khoa (mỗi khoa 1 dòng) */
    public function buildDeptSummarySqlAndBindings(Request $request)
    {
        list($conds, $binds) = $this->commonConditions($request);
        $where = implode(' AND ', $conds);
        $sql = "
            SELECT ss.tdl_execute_department_id AS department_id,
                   d.department_name             AS department_name,
                   SUM(ss.amount * ss.vir_price) AS thanh_tien,
                   SUM(ss.amount)                AS so_luong
            FROM his_sere_serv ss
            JOIN his_service_req sr ON sr.id = ss.service_req_id
            JOIN his_department d   ON d.id  = ss.tdl_execute_department_id
            WHERE $where
            GROUP BY ss.tdl_execute_department_id, d.department_name
        ";
        return [$sql, $binds];
    }

    /** Đếm số phòng (distinct) trong kỳ theo bộ lọc */
    public function buildRoomCountSqlAndBindings(Request $request)
    {
        list($conds, $binds) = $this->commonConditions($request);
        $where = implode(' AND ', $conds);
        $sql = "
            SELECT COUNT(DISTINCT ss.tdl_execute_room_id) AS so_phong
            FROM his_sere_serv ss
            JOIN his_service_req sr ON sr.id = ss.service_req_id
            WHERE $where
        ";
        return [$sql, $binds];
    }

    /** Chi tiết doanh thu theo phòng (mỗi phòng 1 dòng) cho DataTables & Export */
    public function buildRoomDetailSqlAndBindings(Request $request)
    {
        list($conds, $binds) = $this->commonConditions($request);
        $where = implode(' AND ', $conds);
        $sql = "
            SELECT d.department_name        AS department_name,
                   NVL(v.room_name, '(không xác định)')      AS room_name,
                   NVL(v.room_type_name, '(không xác định)') AS room_type_name,
                   SUM(ss.amount * ss.vir_price) AS thanh_tien,
                   SUM(ss.amount)                AS so_luong
            FROM his_sere_serv ss
            JOIN his_service_req sr ON sr.id = ss.service_req_id
            JOIN his_department d   ON d.id  = ss.tdl_execute_department_id
            LEFT JOIN v_his_room v  ON v.id  = ss.tdl_execute_room_id
            WHERE $where
            GROUP BY ss.tdl_execute_department_id, d.department_name, ss.tdl_execute_room_id, v.room_name, v.room_type_name
        ";
        return [$sql, $binds];
    }

    /** Danh sách khoa có doanh thu trong kỳ */
    public function buildDepartmentsSqlAndBindings(Request $request)
    {
        list($conds, $binds) = $this->commonConditions($request);
        $where = implode(' AND ', $conds);
        $sql = "
            SELECT DISTINCT ss.tdl_execute_department_id AS department_id, d.department_name AS department_name
            FROM his_sere_serv ss
            JOIN his_service_req sr ON sr.id = ss.service_req_id
            JOIN his_department d   ON d.id  = ss.tdl_execute_department_id
            WHERE $where
            ORDER BY d.department_name
        ";
        return [$sql, $binds];
    }

    /** Danh sách phòng (theo khoa đang chọn nếu có) */
    public function buildRoomsSqlAndBindings(Request $request)
    {
        list($conds, $binds) = $this->commonConditions($request);
        $where = implode(' AND ', $conds);
        $sql = "
            SELECT DISTINCT ss.tdl_execute_room_id AS room_id, v.room_name AS room_name
            FROM his_sere_serv ss
            JOIN his_service_req sr ON sr.id = ss.service_req_id
            LEFT JOIN v_his_room v ON v.id = ss.tdl_execute_room_id
            WHERE $where AND v.room_name IS NOT NULL
            ORDER BY v.room_name
        ";
        return [$sql, $binds];
    }

    /** Danh sách loại phòng có trong kỳ theo bộ lọc (cho dropdown) */
    public function buildRoomTypesSqlAndBindings(Request $request)
    {
        list($conds, $binds) = $this->commonConditions($request);
        $where = implode(' AND ', $conds);
        $sql = "
            SELECT DISTINCT v.room_type_id AS room_type_id, v.room_type_name AS room_type_name
            FROM his_sere_serv ss
            JOIN his_service_req sr ON sr.id = ss.service_req_id
            JOIN v_his_room v ON v.id = ss.tdl_execute_room_id
            WHERE $where AND v.room_type_id IS NOT NULL
            ORDER BY v.room_type_name
        ";
        return [$sql, $binds];
    }

    /** Lấy doanh thu theo khoa + số phòng rồi tổng hợp. */
    public function getSummaryData(Request $request)
    {
        list($sqlD, $bD) = $this->buildDeptSummarySqlAndBindings($request);
        $deptRows = $this->normalizeRows(\DB::connection('HISPro')->select(\DB::raw($sqlD), $bD));

        list($sqlP, $bP) = $this->buildRoomCountSqlAndBindings($request);
        $cnt = $this->normalizeRows(\DB::connection('HISPro')->select(\DB::raw($sqlP), $bP));
        $soPhong = isset($cnt[0]) ? (int) $cnt[0]->so_phong : 0;

        return self::buildDeptSummary($deptRows, $soPhong);
    }
}

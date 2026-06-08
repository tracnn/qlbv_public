<?php
namespace App\Services;

use Illuminate\Http\Request;
use Carbon\Carbon;

class OnTimeResultService
{
    /**
     * Phân loại 1 dòng kết quả: chua_tra | bat_thuong | dung_hen | tre_hen
     */
    public function classify($row)
    {
        if (empty($row->finish_time)) {
            return 'chua_tra';
        }
        if ($row->actual_minutes < 0) {
            return 'bat_thuong';
        }
        return ($row->actual_minutes <= $row->estimate_duration) ? 'dung_hen' : 'tre_hen';
    }

    /**
     * Tổng hợp KPI và breakdown cho danh sách rows kết quả.
     *
     * @param  array|\Traversable $rows  Danh sách dòng dữ liệu (stdClass hoặc tương đương)
     * @return array{
     *   kpi: array{
     *     tong_co_hen: int,
     *     da_tra_hop_le: int,
     *     dung_hen: int,
     *     tre_hen: int,
     *     chua_tra: int,
     *     bat_thuong: int,
     *     pct_dung_hen: float,
     *     pct_tre_hen: float,
     *     tg_tra_tb: int
     *   },
     *   breakdown_loai_dich_vu: array,
     *   breakdown_phong: array,
     *   breakdown_dich_vu: array,
     *   trend_theo_ngay: array
     * }
     */
    public function summarize($rows)
    {
        $dung = $tre = $chua = $bat = 0;
        $sumActual = 0;
        foreach ($rows as $r) {
            switch ($this->classify($r)) {
                case 'dung_hen': $dung++; $sumActual += $r->actual_minutes; break;
                case 'tre_hen':  $tre++;  $sumActual += $r->actual_minutes; break;
                case 'chua_tra': $chua++; break;
                case 'bat_thuong': $bat++; break;
            }
        }
        $hopLe = $dung + $tre;
        $kpi = [
            'tong_co_hen'   => count($rows),
            'da_tra_hop_le' => $hopLe,
            'dung_hen'      => $dung,
            'tre_hen'       => $tre,
            'chua_tra'      => $chua,
            'bat_thuong'    => $bat,
            'pct_dung_hen'  => $hopLe > 0 ? round($dung / $hopLe * 100, 1) : 0,
            'pct_tre_hen'   => $hopLe > 0 ? round($tre / $hopLe * 100, 1) : 0,
            'tg_tra_tb'     => $hopLe > 0 ? round($sumActual / $hopLe) : 0,
        ];
        return [
            'kpi' => $kpi,
            'breakdown_loai_dich_vu' => $this->buildBreakdown($rows, 'service_type_id', 'service_type_name'),
            'breakdown_phong'        => $this->buildBreakdown($rows, 'execute_room_id', 'execute_room_name'),
            'breakdown_dich_vu'      => $this->buildBreakdown($rows, 'service_id', 'service_name'),
            'trend_theo_ngay'        => $this->buildBreakdown($rows, 'day_val', 'day_val'),
        ];
    }

    /**
     * Nhóm các rows theo một trường định danh và tổng hợp số liệu cho từng nhóm.
     *
     * @param  array|\Traversable $rows       Danh sách dòng dữ liệu
     * @param  string             $idField    Tên trường dùng làm khóa nhóm (vd: 'service_type_id')
     * @param  string             $nameField  Tên trường hiển thị tên nhóm (vd: 'service_type_name')
     * @return array  Mảng các nhóm, mỗi nhóm gồm:
     *                id, name, tong, dung_hen, tre_hen, chua_tra, bat_thuong,
     *                pct_dung_hen, pct_tre_hen, tg_tra_tb.
     *                Sắp xếp giảm dần theo pct_tre_hen.
     */
    public function buildBreakdown($rows, $idField, $nameField)
    {
        $groups = [];
        foreach ($rows as $r) {
            $key = $r->$idField;
            if (!isset($groups[$key])) {
                $groups[$key] = ['id'=>$r->$idField,'name'=>$r->$nameField,'tong'=>0,'dung_hen'=>0,'tre_hen'=>0,'chua_tra'=>0,'bat_thuong'=>0,'_sumActual'=>0];
            }
            $g =& $groups[$key];
            $g['tong']++;
            $cls = $this->classify($r);
            $g[$cls]++;
            if ($cls === 'dung_hen' || $cls === 'tre_hen') {
                $g['_sumActual'] += $r->actual_minutes;
            }
            unset($g); // break the reference so the next iteration doesn't overwrite the last group
        }
        $result = [];
        foreach ($groups as $g) {
            $hopLe = $g['dung_hen'] + $g['tre_hen'];
            $g['pct_dung_hen'] = $hopLe > 0 ? round($g['dung_hen']/$hopLe*100, 1) : 0;
            $g['pct_tre_hen']  = $hopLe > 0 ? round($g['tre_hen']/$hopLe*100, 1) : 0;
            $g['tg_tra_tb']    = $hopLe > 0 ? round($g['_sumActual']/$hopLe) : 0;
            unset($g['_sumActual']);
            $result[] = $g;
        }
        usort($result, function($a, $b) {
            return $b['pct_tre_hen'] <=> $a['pct_tre_hen'];
        });
        return $result;
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

    /** WHERE + bindings dùng chung cho summary & detail */
    protected function commonConditions(Request $request)
    {
        list($from, $to) = $this->normalizeRange($request);
        $conds = [
            "s.estimate_duration IS NOT NULL", "s.estimate_duration <> 0",
            "ss.is_delete = 0", "ss.is_no_execute IS NULL",
            "sr.is_active = 1", "sr.is_delete = 0",
            "sr.intruction_time BETWEEN :from AND :to",
        ];
        $binds = ['from' => $from, 'to' => $to];

        if ($request->filled('execute_room_id')) {
            $conds[] = "ss.tdl_execute_room_id = :room_id";
            $binds['room_id'] = $request->input('execute_room_id');
        }
        if ($request->filled('service_type_id')) {
            $conds[] = "ss.tdl_service_type_id = :service_type_id";
            $binds['service_type_id'] = $request->input('service_type_id');
        }
        if ($request->filled('service_id')) {
            $conds[] = "ss.service_id = :service_id";
            $binds['service_id'] = $request->input('service_id');
        }
        return [$conds, $binds];
    }

    /**
     * Oracle (connection HISPro) trả tên cột VIẾT HOA → chuẩn hóa về lowercase
     * để mọi truy cập $row->field (và DataTables data:'field') đọc đúng.
     * Bắt buộc áp dụng cho MỌI kết quả DB::select trước khi dùng (theo pattern DoctorService).
     */
    public function normalizeRows($rawRows)
    {
        return array_map(function ($row) {
            return (object) array_change_key_case((array) $row, CASE_LOWER);
        }, $rawRows);
    }

    /** SQL trả base rows (1 dòng / sere_serv) cho summarize() */
    public function buildBaseSqlAndBindings(Request $request)
    {
        list($conds, $binds) = $this->commonConditions($request);
        $where = implode(' AND ', $conds);

        $sql = "
            SELECT
                ss.tdl_service_type_id            AS service_type_id,
                st.service_type_name              AS service_type_name,
                ss.tdl_execute_room_id            AS execute_room_id,
                er.execute_room_name              AS execute_room_name,
                ss.service_id                     AS service_id,
                s.service_name                    AS service_name,
                TO_NUMBER(SUBSTR(sr.intruction_time,1,8)) AS day_val,
                s.estimate_duration               AS estimate_duration,
                sr.intruction_time                AS intruction_time,
                sr.finish_time                    AS finish_time,
                CASE WHEN sr.finish_time IS NULL THEN NULL
                     ELSE (TO_DATE(sr.finish_time,'YYYYMMDDHH24MISS') - TO_DATE(sr.intruction_time,'YYYYMMDDHH24MISS')) * 24 * 60
                END                               AS actual_minutes
            FROM his_sere_serv ss
            JOIN his_service_req sr ON sr.id = ss.service_req_id
            JOIN his_service s      ON s.id  = ss.service_id
            JOIN his_service_type st ON st.id = ss.tdl_service_type_id
            LEFT JOIN his_execute_room er ON er.room_id = ss.tdl_execute_room_id
            WHERE $where
        ";
        return [$sql, $binds];
    }

    /** SQL chi tiết từng dòng cho DataTables & Export; hỗ trợ drill-down status */
    public function buildDetailSqlAndBindings(Request $request)
    {
        list($conds, $binds) = $this->commonConditions($request);

        // predicate cho drill-down trang thai
        $actualExpr = "(TO_DATE(sr.finish_time,'YYYYMMDDHH24MISS') - TO_DATE(sr.intruction_time,'YYYYMMDDHH24MISS')) * 24 * 60";
        switch ($request->input('status')) {
            case 'chua_tra':
                $conds[] = "sr.finish_time IS NULL"; break;
            case 'bat_thuong':
                $conds[] = "sr.finish_time IS NOT NULL AND $actualExpr < 0"; break;
            case 'dung_hen':
                $conds[] = "sr.finish_time IS NOT NULL AND $actualExpr >= 0 AND $actualExpr <= s.estimate_duration"; break;
            case 'tre_hen':
                $conds[] = "sr.finish_time IS NOT NULL AND $actualExpr > s.estimate_duration"; break;
        }
        $where = implode(' AND ', $conds);

        $sql = "
            SELECT
                ss.tdl_treatment_code   AS tdl_treatment_code,
                ss.tdl_patient_name     AS tdl_patient_name,
                er.execute_room_name    AS execute_room_name,
                st.service_type_name    AS service_type_name,
                ss.tdl_service_name     AS service_name,
                sr.intruction_time      AS intruction_time,
                sr.finish_time          AS finish_time,
                s.estimate_duration     AS estimate_duration,
                CASE WHEN sr.finish_time IS NULL THEN NULL ELSE $actualExpr END AS actual_minutes
            FROM his_sere_serv ss
            JOIN his_service_req sr ON sr.id = ss.service_req_id
            JOIN his_service s      ON s.id  = ss.service_id
            JOIN his_service_type st ON st.id = ss.tdl_service_type_id
            LEFT JOIN his_execute_room er ON er.room_id = ss.tdl_execute_room_id
            WHERE $where
        ";
        return [$sql, $binds];
    }

    /** Danh sách phòng thực hiện có dịch vụ thuộc mẫu (distinct) */
    public function buildRoomsSqlAndBindings(Request $request)
    {
        list($conds, $binds) = $this->commonConditions($request);
        $where = implode(' AND ', $conds);
        $sql = "
            SELECT DISTINCT ss.tdl_execute_room_id AS room_id, er.execute_room_name AS execute_room_name
            FROM his_sere_serv ss
            JOIN his_service_req sr ON sr.id = ss.service_req_id
            JOIN his_service s      ON s.id  = ss.service_id
            LEFT JOIN his_execute_room er ON er.room_id = ss.tdl_execute_room_id
            WHERE $where AND er.execute_room_name IS NOT NULL
            ORDER BY er.execute_room_name
        ";
        return [$sql, $binds];
    }

    /** Lấy base rows từ DB rồi tổng hợp. */
    public function getSummaryData(Request $request)
    {
        list($sql, $binds) = $this->buildBaseSqlAndBindings($request);
        $rows = \DB::connection('HISPro')->select(\DB::raw($sql), $binds);
        return $this->summarize($this->normalizeRows($rows));
    }
}

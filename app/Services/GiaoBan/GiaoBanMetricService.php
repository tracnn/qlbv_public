<?php

namespace App\Services\GiaoBan;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Build SQL + bindings tính chỉ tiêu giao ban từ HIS Pro (Oracle, connection HISPro).
 * Thời gian HIS là số YYYYMMDDHHMISS. Chỉ tính điều trị nội trú (tdl_treatment_type_id = 3),
 * trừ các chỉ tiêu service_count/admission.
 */
class GiaoBanMetricService
{
    const CONN = 'HISPro';

    /** 'Y-m-d H:i:s' -> 'YmdHis' */
    public function toHisTime($datetime)
    {
        return Carbon::createFromFormat('Y-m-d H:i:s', $datetime)->format('YmdHis');
    }

    /** Oracle trả cột HOA -> lowercase */
    public function normalizeRows($rawRows)
    {
        return array_map(function ($row) {
            return (object) array_change_key_case((array) $row, CASE_LOWER);
        }, $rawRows);
    }

    /**
     * BN nội trú đang ở từng khoa tại thời điểm $at.
     * Trả về: department_id, so_bn. (oci8 không cho dùng lại 1 bind name -> ts1/ts2/ts3)
     */
    public function buildCensusSql($at)
    {
        $ts = $this->toHisTime($at);
        $sql = "
            SELECT dt.department_id, COUNT(DISTINCT dt.treatment_id) AS so_bn
            FROM his_department_tran dt
            JOIN his_treatment t ON t.id = dt.treatment_id
            WHERE dt.is_delete = 0 AND dt.is_active = 1 AND t.is_delete = 0
              AND t.tdl_treatment_type_id = 3
              AND dt.department_in_time <= :ts1
              AND (t.out_time IS NULL OR t.out_time = 0 OR t.out_time > :ts2)
              AND NOT EXISTS (
                    SELECT 1 FROM his_department_tran nx
                    WHERE nx.previous_id = dt.id AND nx.is_delete = 0
                      AND nx.department_in_time <= :ts3)
            GROUP BY dt.department_id";
        return [$sql, ['ts1' => $ts, 'ts2' => $ts, 'ts3' => $ts]];
    }

    /**
     * BN vào khoa trong kỳ, tách vào thẳng (previous_id IS NULL) / chuyển đến.
     * Trả về: department_id, bn_vao, bn_chuyen_den.
     */
    public function buildMovementInSql($from, $to)
    {
        $sql = "
            SELECT dt.department_id,
                   SUM(CASE WHEN dt.previous_id IS NULL THEN 1 ELSE 0 END) AS bn_vao,
                   SUM(CASE WHEN dt.previous_id IS NOT NULL THEN 1 ELSE 0 END) AS bn_chuyen_den
            FROM his_department_tran dt
            JOIN his_treatment t ON t.id = dt.treatment_id
            WHERE dt.is_delete = 0 AND dt.is_active = 1 AND t.is_delete = 0
              AND t.tdl_treatment_type_id = 3
              AND dt.department_in_time BETWEEN :from_time AND :to_time
            GROUP BY dt.department_id";
        return [$sql, ['from_time' => $this->toHisTime($from), 'to_time' => $this->toHisTime($to)]];
    }

    /**
     * BN chuyển khoa (đi) trong kỳ: đếm theo khoa nguồn (tran trước của tran mới).
     * Trả về: department_id, bn_chuyen_khoa.
     */
    public function buildMovementOutSql($from, $to)
    {
        $sql = "
            SELECT p.department_id, COUNT(*) AS bn_chuyen_khoa
            FROM his_department_tran nx
            JOIN his_department_tran p ON p.id = nx.previous_id
            JOIN his_treatment t ON t.id = nx.treatment_id
            WHERE nx.is_delete = 0 AND p.is_delete = 0 AND t.is_delete = 0
              AND t.tdl_treatment_type_id = 3
              AND nx.department_id <> p.department_id
              AND nx.department_in_time BETWEEN :from_time AND :to_time
            GROUP BY p.department_id";
        return [$sql, ['from_time' => $this->toHisTime($from), 'to_time' => $this->toHisTime($to)]];
    }

    /**
     * Kết thúc điều trị trong kỳ theo khoa cuối, kèm mã loại kết thúc.
     * Trả về: department_id, end_code, so_bn — service gộp theo end_codes của từng metric.
     */
    public function buildEndTypeSql($from, $to)
    {
        $sql = "
            SELECT t.last_department_id AS department_id,
                   et.treatment_end_type_code AS end_code,
                   COUNT(*) AS so_bn
            FROM his_treatment t
            JOIN his_treatment_end_type et ON et.id = t.treatment_end_type_id
            WHERE t.is_delete = 0 AND t.tdl_treatment_type_id = 3
              AND t.out_time BETWEEN :from_time AND :to_time
            GROUP BY t.last_department_id, et.treatment_end_type_code";
        return [$sql, ['from_time' => $this->toHisTime($from), 'to_time' => $this->toHisTime($to)]];
    }

    /**
     * Đếm BN đang nằm các giường chỉ định (giường yêu cầu) tại thời điểm $at.
     * $bedIds: danh sách his_bed.id cấu hình trong metric.
     */
    public function buildBedCountSql($at, array $bedIds)
    {
        $ts = $this->toHisTime($at);
        $ids = implode(',', array_map('intval', $bedIds)) ?: '-1';
        $sql = "
            SELECT COUNT(DISTINCT btr.treatment_id) AS so_bn
            FROM his_treatment_bed_room btr
            WHERE btr.is_delete = 0
              AND btr.bed_id IN ($ids)
              AND btr.add_time <= :ts1
              AND (btr.remove_time IS NULL OR btr.remove_time = 0 OR btr.remove_time > :ts2)";
        return [$sql, ['ts1' => $ts, 'ts2' => $ts]];
    }

    /**
     * Đếm dịch vụ thực hiện trong kỳ theo bộ lọc cấu hình.
     * $filter: service_type_ids[], service_ids[], execute_room_ids[],
     *          request_department_id, execute_department_id, priority_min, priority_max.
     */
    public function buildServiceCountSql($from, $to, array $filter)
    {
        $f = $this->toHisTime($from);
        $t = $this->toHisTime($to);
        $conds = [
            'ss.is_delete = 0', 'sr.is_delete = 0', 'sr.is_active = 1',
            'ss.tdl_intruction_date BETWEEN :from_day AND :to_day', // cột có index
            'sr.intruction_time BETWEEN :from_time AND :to_time',
        ];
        $binds = [
            'from_day' => substr($f, 0, 8) . '000000',
            'to_day'   => substr($t, 0, 8) . '235959',
            'from_time' => $f, 'to_time' => $t,
        ];

        if (!empty($filter['service_type_ids'])) {
            $ids = implode(',', array_map('intval', $filter['service_type_ids']));
            $conds[] = "ss.tdl_service_type_id IN ($ids)";
        }
        if (!empty($filter['service_ids'])) {
            $ids = implode(',', array_map('intval', $filter['service_ids']));
            $conds[] = "ss.service_id IN ($ids)";
        }
        if (!empty($filter['execute_room_ids'])) {
            $ids = implode(',', array_map('intval', $filter['execute_room_ids']));
            $conds[] = "ss.tdl_execute_room_id IN ($ids)";
        }
        if (!empty($filter['request_department_id'])) {
            $conds[] = 'sr.request_department_id = :req_dept';
            $binds['req_dept'] = (int) $filter['request_department_id'];
        }
        if (!empty($filter['execute_department_id'])) {
            $conds[] = 'ss.tdl_execute_department_id = :exec_dept';
            $binds['exec_dept'] = (int) $filter['execute_department_id'];
        }
        if (!empty($filter['execute_department_ids'])) {
            $ids = implode(',', array_map('intval', $filter['execute_department_ids']));
            $conds[] = "ss.tdl_execute_department_id IN ($ids)";
        }
        if (!empty($filter['request_department_ids'])) {
            $ids = implode(',', array_map('intval', $filter['request_department_ids']));
            $conds[] = "sr.request_department_id IN ($ids)";
        }
        if (isset($filter['priority_min'])) {
            $conds[] = 'sr.priority >= :priority_min';
            $binds['priority_min'] = (int) $filter['priority_min'];
        }
        if (isset($filter['priority_max'])) {
            $conds[] = '(sr.priority IS NULL OR sr.priority <= :priority_max)';
            $binds['priority_max'] = (int) $filter['priority_max'];
        }

        $where = implode(' AND ', $conds);
        $sql = "
            SELECT COUNT(*) AS so_luong
            FROM his_sere_serv ss
            JOIN his_service_req sr ON sr.id = ss.service_req_id
            WHERE $where";
        return [$sql, $binds];
    }

    /** Số BN nhập viện nội trú toàn viện trong kỳ (dùng cho dòng khoa Khám bệnh). */
    public function buildAdmissionCountSql($from, $to)
    {
        $sql = "
            SELECT COUNT(*) AS so_luong
            FROM his_treatment t
            WHERE t.is_delete = 0 AND t.tdl_treatment_type_id = 3
              AND t.in_time BETWEEN :from_time AND :to_time";
        return [$sql, ['from_time' => $this->toHisTime($from), 'to_time' => $this->toHisTime($to)]];
    }

    /**
     * Đếm lượt chuyển khoa NỘI BỘ trong tập khoa (cả nguồn và đích đều thuộc set).
     * Dùng để trừ khỏi chuyển đến / chuyển đi khi gộp nhiều khoa HIS.
     */
    public function buildInternalTransferSql($from, $to, array $deptIds)
    {
        $ids = implode(',', array_map('intval', $deptIds)) ?: '-1';
        $sql = "
            SELECT COUNT(*) AS so_noi_bo
            FROM his_department_tran nx
            JOIN his_department_tran p ON p.id = nx.previous_id
            JOIN his_treatment t ON t.id = nx.treatment_id
            WHERE nx.is_delete = 0 AND p.is_delete = 0 AND t.is_delete = 0
              AND t.tdl_treatment_type_id = 3
              AND nx.department_id <> p.department_id
              AND p.department_id IN ($ids) AND nx.department_id IN ($ids)
              AND nx.department_in_time BETWEEN :from_time AND :to_time";
        return [$sql, ['from_time' => $this->toHisTime($from), 'to_time' => $this->toHisTime($to)]];
    }

    /**
     * Đếm lượt khám (khối ngoại trú) do các khoa khám thực hiện trong kỳ.
     * $deptIds: danh sách khoa khám (his_department.id).
     * $filter: treatment_type_ids[], patient_type_ids[] (join his_treatment khi có).
     */
    public function buildExamVisitSql($from, $to, array $deptIds, array $filter = [])
    {
        $ids = implode(',', array_map('intval', $deptIds)) ?: '-1';
        $khamType = (int) config('__tech.service_req_type_kham', 1);
        $join = '';
        $extra = '';
        if (!empty($filter['treatment_type_ids'])) {
            $t = implode(',', array_map('intval', $filter['treatment_type_ids']));
            $join = ' JOIN his_treatment t ON t.id = sr.treatment_id';
            $extra .= " AND t.tdl_treatment_type_id IN ($t)";
        }
        if (!empty($filter['patient_type_ids'])) {
            $p = implode(',', array_map('intval', $filter['patient_type_ids']));
            if ($join === '') $join = ' JOIN his_treatment t ON t.id = sr.treatment_id';
            $extra .= " AND t.tdl_patient_type_id IN ($p)";
        }
        $sql = "
            SELECT COUNT(*) AS so_luong
            FROM his_service_req sr$join
            WHERE sr.is_delete = 0
              AND sr.service_req_type_id = :kham_type
              AND sr.is_main_exam = 1
              AND sr.execute_department_id IN ($ids)
              AND sr.intruction_time BETWEEN :from_time AND :to_time$extra";
        return [$sql, [
            'kham_type' => $khamType,
            'from_time' => $this->toHisTime($from),
            'to_time' => $this->toHisTime($to),
        ]];
    }

    /** Tổng giá trị map (department_id => value) trên danh sách khoa. */
    public function sumOverDepts(array $map, array $deptIds)
    {
        $s = 0.0;
        foreach ($deptIds as $id) {
            if (isset($map[(int) $id])) $s += (float) $map[(int) $id];
        }
        return $s;
    }

    /** Tổng end_type rows khớp khoa (trong deptIds) và mã kết thúc (trong endCodes). */
    public function sumEndType($endRows, array $deptIds, array $endCodes)
    {
        $set = array_map('intval', $deptIds);
        $s = 0.0;
        foreach ($endRows as $r) {
            if (in_array((int) $r->department_id, $set, true) && in_array($r->end_code, $endCodes, true)) {
                $s += (float) $r->so_bn;
            }
        }
        return $s;
    }

    // ================= chạy trên HISPro và ráp thành ma trận =================

    protected function selectHis($sqlAndBinds)
    {
        list($sql, $binds) = $sqlAndBinds;
        return $this->normalizeRows(DB::connection(self::CONN)->select($sql, $binds));
    }

    /**
     * Tính toàn bộ auto_value cho danh sách dept config.
     * @param \Illuminate\Support\Collection|array $deptConfigs  các GiaoBanDeptConfig active
     * @return array map "dept_config_id|metric_code" => float|null
     */
    public function computeAll($deptConfigs, $from, $to)
    {
        // 1. Batch queries dùng chung
        $censusFrom = $this->pluckByDept($this->selectHis($this->buildCensusSql($from)), 'so_bn');
        $censusTo   = $this->pluckByDept($this->selectHis($this->buildCensusSql($to)), 'so_bn');
        $moveIn     = $this->selectHis($this->buildMovementInSql($from, $to));
        $bnVao      = $this->pluckByDept($moveIn, 'bn_vao');
        $bnDen      = $this->pluckByDept($moveIn, 'bn_chuyen_den');
        $moveOut    = $this->pluckByDept($this->selectHis($this->buildMovementOutSql($from, $to)), 'bn_chuyen_khoa');
        $endRows    = $this->selectHis($this->buildEndTypeSql($from, $to)); // department_id, end_code, so_bn

        $values = [];
        foreach ($deptConfigs as $cfg) {
            $hisDept = $cfg->his_department_id;
            foreach ($cfg->metricList() as $m) {
                $key = $cfg->id . '|' . $m['code'];
                switch ($m['type']) {
                    case 'census_from':  $values[$key] = (float) ($censusFrom[$hisDept] ?? 0); break;
                    case 'census_to':    $values[$key] = (float) ($censusTo[$hisDept] ?? 0); break;
                    case 'movement_in':  $values[$key] = (float) ($bnVao[$hisDept] ?? 0); break;
                    case 'movement_transfer_in':  $values[$key] = (float) ($bnDen[$hisDept] ?? 0); break;
                    case 'movement_transfer_out': $values[$key] = (float) ($moveOut[$hisDept] ?? 0); break;
                    case 'end_type':
                        $sum = 0;
                        foreach ($endRows as $r) {
                            if ((int) $r->department_id === (int) $hisDept
                                && in_array($r->end_code, $m['end_codes'], true)) {
                                $sum += (int) $r->so_bn;
                            }
                        }
                        $values[$key] = (float) $sum;
                        break;
                    case 'bed_count':
                        $rows = $this->selectHis($this->buildBedCountSql($to, isset($m['bed_ids']) ? $m['bed_ids'] : []));
                        $values[$key] = (float) ($rows[0]->so_bn ?? 0);
                        break;
                    case 'service_count':
                        $filter = isset($m['filter']) ? $m['filter'] : [];
                        if (!empty($filter['execute_department_id_self'])) {
                            $filter['execute_department_id'] = $hisDept;
                            unset($filter['execute_department_id_self']);
                        } elseif ($hisDept && empty($filter['execute_room_ids']) && empty($filter['execute_department_id'])) {
                            $filter['request_department_id'] = $hisDept;
                        }
                        $rows = $this->selectHis($this->buildServiceCountSql($from, $to, $filter));
                        $values[$key] = (float) ($rows[0]->so_luong ?? 0);
                        break;
                    case 'admission':
                        $rows = $this->selectHis($this->buildAdmissionCountSql($from, $to));
                        $values[$key] = (float) ($rows[0]->so_luong ?? 0);
                        break;
                    case 'manual':
                    default:
                        $values[$key] = null; // ô nhập tay thuần
                }
            }
        }
        return $values;
    }

    /** rows(department_id, <col>) -> map department_id => value */
    protected function pluckByDept($rows, $col)
    {
        $map = [];
        foreach ($rows as $r) {
            $map[(int) $r->department_id] = $r->{$col};
        }
        return $map;
    }
}

<?php

namespace App\Services\Dashboard;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DoctorService
{
    /**
     * Chuyển date range từ Y-m-d sang format YmdHis cho Oracle
     */
    public function buildDateRange(string $from, string $to): array
    {
        return [
            'from' => Carbon::parse($from)->startOfDay()->format('YmdHis'),
            'to'   => Carbon::parse($to)->endOfDay()->format('YmdHis'),
        ];
    }

    /**
     * Format rows lượt khám theo bác sĩ
     */
    public function formatExaminationRows($rows): array
    {
        return collect($rows)->map(function ($row) {
            return [
                'loginname'      => $row->loginname,
                'username'       => $row->username,
                'total_exams'    => (int) $row->total_exams,
                'total_patients' => (int) ($row->total_patients ?? 0),
            ];
        })->toArray();
    }

    /**
     * Format rows doanh thu theo bác sĩ
     */
    public function formatRevenueRows($rows): array
    {
        return collect($rows)->map(function ($row) {
            return [
                'loginname'      => $row->loginname,
                'username'       => $row->username,
                'total_revenue'  => (float) $row->total_revenue,
                'total_patients' => (int) $row->total_patients,
            ];
        })->toArray();
    }

    /**
     * Format rows ca phẫu thuật theo bác sĩ (PTV chính)
     */
    public function formatSurgeryRows($rows): array
    {
        return collect($rows)->map(function ($row) {
            return [
                'loginname'       => $row->loginname,
                'username'        => $row->username,
                'total_surgeries' => (int) $row->total_surgeries,
            ];
        })->toArray();
    }

    /**
     * Lấy số lượt khám theo bác sĩ
     */
    public function getExaminations(string $from, string $to, ?int $departmentId = null): array
    {
        $dates = $this->buildDateRange($from, $to);

        $query = DB::connection('HISPro')
            ->table('his_service_req as sr')
            ->selectRaw('sr.EXECUTE_LOGINNAME as loginname, sr.EXECUTE_USERNAME as username,
                         COUNT(*) as total_exams,
                         COUNT(DISTINCT sr.TREATMENT_ID) as total_patients')
            ->where('sr.SERVICE_REQ_TYPE_ID', 1)
            ->where('sr.IS_DELETE', 0)
            ->where('sr.IS_ACTIVE', 1)
            ->whereBetween('sr.INTRUCTION_TIME', [$dates['from'], $dates['to']]);

        if ($departmentId) {
            $query->where('sr.EXECUTE_ROOM_ID', $departmentId);
        }

        $rows = $query->groupBy('sr.EXECUTE_LOGINNAME', 'sr.EXECUTE_USERNAME')
                      ->orderByRaw('total_exams DESC')
                      ->get();

        return $this->formatExaminationRows($rows);
    }

    /**
     * Lấy doanh thu theo bác sĩ
     */
    public function getRevenue(string $from, string $to, ?int $departmentId = null): array
    {
        $dates = $this->buildDateRange($from, $to);

        $query = DB::connection('HISPro')
            ->table('his_service_req as sr')
            ->join('his_sere_serv as ss', function ($join) {
                $join->on('ss.SERVICE_REQ_ID', '=', 'sr.ID')
                     ->where('ss.IS_DELETE', 0)
                     ->where('ss.IS_ACTIVE', 1);
            })
            ->selectRaw('sr.EXECUTE_LOGINNAME as loginname, sr.EXECUTE_USERNAME as username,
                         SUM(ss.VIR_TOTAL_PRICE) as total_revenue,
                         COUNT(DISTINCT sr.TREATMENT_ID) as total_patients')
            ->where('sr.IS_DELETE', 0)
            ->where('sr.IS_ACTIVE', 1)
            ->whereBetween('sr.INTRUCTION_TIME', [$dates['from'], $dates['to']]);

        if ($departmentId) {
            $query->where('sr.EXECUTE_ROOM_ID', $departmentId);
        }

        $rows = $query->groupBy('sr.EXECUTE_LOGINNAME', 'sr.EXECUTE_USERNAME')
                      ->orderByRaw('total_revenue DESC')
                      ->get();

        return $this->formatRevenueRows($rows);
    }

    /**
     * Lấy số ca phẫu thuật theo PTV chính
     * Dùng raw SQL vì Yajra OCI8 builder sinh sai SQL với nhiều JOIN phức tạp.
     */
    public function getSurgeries(string $from, string $to): array
    {
        $dates = $this->buildDateRange($from, $to);

        $sql = "
            SELECT eu.LOGINNAME   AS loginname,
                   eu.USERNAME    AS username,
                   COUNT(*)       AS total_surgeries
            FROM   his_sere_serv    ss
            JOIN   his_ekip_user    eu ON eu.EKIP_ID        = ss.EKIP_ID
                                      AND eu.IS_DELETE       = 0
            JOIN   his_execute_role er ON er.ID              = eu.EXECUTE_ROLE_ID
                                      AND er.EXECUTE_ROLE_CODE = '01'
            JOIN   his_service_req  sr ON sr.ID              = ss.SERVICE_REQ_ID
                                      AND sr.IS_DELETE        = 0
                                      AND sr.IS_ACTIVE        = 1
                                      AND sr.SERVICE_REQ_TYPE_ID = 10
            WHERE  ss.IS_DELETE  = 0
              AND  ss.IS_ACTIVE  = 1
              AND  ss.EKIP_ID    IS NOT NULL
              AND  sr.INTRUCTION_TIME BETWEEN ? AND ?
            GROUP BY eu.LOGINNAME, eu.USERNAME
            ORDER BY total_surgeries DESC
        ";

        $rawRows = DB::connection('HISPro')->select($sql, [
            $dates['from'],
            $dates['to'],
        ]);

        // Oracle trả về tên cột VIẾT HOA, cần chuyển về lowercase
        // để formatSurgeryRows đọc được đúng ($row->loginname v.v.)
        $rows = array_map(function ($row) {
            return (object) array_change_key_case((array) $row, CASE_LOWER);
        }, $rawRows);

        return $this->formatSurgeryRows($rows);
    }
}

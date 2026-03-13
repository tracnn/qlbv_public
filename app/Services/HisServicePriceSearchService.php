<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class HisServicePriceSearchService
{
    /**
     * Lấy danh sách patient_type đang active từ HIS.
     *
     * @return \Illuminate\Support\Collection
     */
    public function getPatientTypes()
    {
        return DB::connection('HISPro')
            ->table('his_patient_type')
            ->where('is_active', 1)
            ->where('is_delete', 0)
            ->select('id', 'patient_type_name')
            ->orderBy('id')
            ->get();
    }

    /**
     * Build query cho tra cứu giá dịch vụ HIS.
     *
     * Các cột giá được tạo động dựa trên danh sách patient_type,
     * so sánh bằng pt.id thay vì pt.patient_type_name để chính xác hơn.
     *
     * @param \Illuminate\Support\Collection $patientTypes
     * @return \Illuminate\Database\Query\Builder
     */
    public function buildServicePriceQuery($patientTypes)
    {
        $priceCols = $patientTypes->map(function ($pt) {
            $id = (int) $pt->id;
            return "MAX(CASE WHEN pt.id = {$id} THEN sp.price END) AS price_{$id}";
        })->implode(",\n                ");

        $subQuery = DB::connection('HISPro')
            ->table('his_service as s')
            ->join('his_service_type as st', 'st.id', '=', 's.service_type_id')
            ->join('his_service_unit as su', 'su.id', '=', 's.service_unit_id')
            ->join('his_service_paty as sp', 'sp.service_id', '=', 's.id')
            ->join('his_patient_type as pt', 'pt.id', '=', 'sp.patient_type_id')
            ->whereIn('s.service_type_id', [15, 1, 2, 3, 4, 5, 8, 9, 10, 11, 13])
            ->where('s.is_active', 1)
            ->where('s.is_delete', 0)
            ->where('sp.is_active', 1)
            ->where('sp.is_delete', 0)
            ->whereNull('sp.to_time')
            ->where('sp.price', '>', 0)
            ->where('sp.branch_id', 1)
            ->selectRaw("
                s.SERVICE_CODE      AS service_code,
                s.SERVICE_NAME      AS service_name,
                st.service_type_code AS service_type_code,
                st.service_type_name AS service_type_name,
                su.service_unit_code AS service_unit_code,
                su.service_unit_name AS service_unit_name,
                sp.from_time AS from_time,
                {$priceCols}
            ")
            ->groupBy(
                's.SERVICE_CODE',
                's.SERVICE_NAME',
                'st.service_type_code',
                'st.service_type_name',
                'su.service_unit_code',
                'su.service_unit_name',
                'sp.from_time'
            );

        return DB::connection('HISPro')
            ->table(DB::raw("({$subQuery->toSql()}) service_prices"))
            ->mergeBindings($subQuery)
            ->select('service_prices.*');
    }
}

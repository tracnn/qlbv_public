<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Support\Facades\DB;

trait ExcludesDashboardPatientTypes
{
    /** Cache theo request: id đã resolve từ code (tránh query his_patient_type nhiều lần/lần tải trang). */
    private $excludedPatientTypeIdsCache = null;

    /**
     * patient_type_code cấu hình cần loại khỏi thống kê dashboard (đọc thuần từ config).
     */
    protected function excludedPatientTypeCodes(): array
    {
        $codes = (array) config('organization.dashboard.exclude_patient_type_codes', []);
        return array_values(array_map('strval', $codes));
    }

    /**
     * Danh sách patient_type_id KHÔNG tính vào thống kê dashboard.
     * Resolve từ code cấu hình sang id qua his_patient_type (các bảng thống kê chỉ có cột id).
     * Rỗng nếu không cấu hình — khi đó KHÔNG query DB.
     */
    protected function excludedPatientTypeIds(): array
    {
        if ($this->excludedPatientTypeIdsCache !== null) {
            return $this->excludedPatientTypeIdsCache;
        }

        $codes = $this->excludedPatientTypeCodes();
        if (empty($codes)) {
            return $this->excludedPatientTypeIdsCache = [];
        }

        $ids = DB::connection('HISPro')
            ->table('his_patient_type')
            ->whereIn('patient_type_code', $codes)
            ->pluck('id')
            ->all();

        return $this->excludedPatientTypeIdsCache = array_values(array_map('intval', $ids));
    }
}

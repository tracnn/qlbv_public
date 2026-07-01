<?php

namespace App\Http\Controllers\Concerns;

trait ExcludesDashboardPatientTypes
{
    /**
     * Danh sách patient_type_id KHÔNG tính vào thống kê dashboard (đọc từ config).
     * Ép về int, reindex; rỗng nếu không cấu hình.
     */
    protected function excludedPatientTypeIds(): array
    {
        $ids = (array) config('organization.dashboard.exclude_patient_type_ids', []);
        return array_values(array_map('intval', $ids));
    }
}

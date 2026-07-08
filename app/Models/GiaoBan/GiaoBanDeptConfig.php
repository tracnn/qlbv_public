<?php

namespace App\Models\GiaoBan;

use Illuminate\Database\Eloquent\Model;

class GiaoBanDeptConfig extends Model
{
    protected $table = 'giaoban_dept_configs';
    protected $fillable = ['his_department_id', 'his_department_ids', 'block_type', 'display_name', 'sort_order', 'is_active', 'metrics'];
    protected $casts = ['is_active' => 'boolean'];

    /** @return array các chỉ tiêu đã decode */
    public function metricList()
    {
        $m = json_decode($this->metrics, true);
        return is_array($m) ? $m : [];
    }

    /** @return int[] danh sách khoa HIS (JSON his_department_ids; fallback cột đơn cũ) */
    public function hisDepartmentIds()
    {
        $ids = json_decode($this->his_department_ids, true);
        if (is_array($ids) && count($ids)) {
            return array_values(array_map('intval', $ids));
        }
        if ($this->his_department_id !== null && $this->his_department_id !== '') {
            return [(int) $this->his_department_id];
        }
        return [];
    }
}

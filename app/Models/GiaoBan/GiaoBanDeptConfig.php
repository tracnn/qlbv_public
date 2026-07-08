<?php

namespace App\Models\GiaoBan;

use Illuminate\Database\Eloquent\Model;

class GiaoBanDeptConfig extends Model
{
    protected $table = 'giaoban_dept_configs';
    protected $fillable = ['his_department_id', 'display_name', 'sort_order', 'is_active', 'metrics'];
    protected $casts = ['is_active' => 'boolean'];

    /** @return array các chỉ tiêu đã decode */
    public function metricList()
    {
        $m = json_decode($this->metrics, true);
        return is_array($m) ? $m : [];
    }
}

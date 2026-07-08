<?php

namespace App\Models\GiaoBan;

use Illuminate\Database\Eloquent\Model;

class GiaoBanReportCell extends Model
{
    protected $table = 'giaoban_report_cells';
    protected $fillable = [
        'report_id', 'dept_config_id', 'metric_code',
        'auto_value', 'manual_value', 'note', 'updated_by',
    ];

    /** Giá trị hiển thị: ưu tiên sửa tay */
    public function displayValue()
    {
        return $this->manual_value !== null ? $this->manual_value : $this->auto_value;
    }
}

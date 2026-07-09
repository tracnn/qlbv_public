<?php

namespace App\Models\GiaoBan;

use Illuminate\Database\Eloquent\Model;

class GiaoBanReportBed extends Model
{
    protected $table = 'giaoban_report_beds';
    protected $fillable = ['report_id', 'department_id', 'total_beds', 'used_beds'];
    protected $casts = ['report_id' => 'integer', 'department_id' => 'integer', 'total_beds' => 'integer', 'used_beds' => 'integer'];
}

<?php

namespace App\Models\GiaoBan;

use Illuminate\Database\Eloquent\Model;

class GiaoBanReportDuty extends Model
{
    protected $table = 'giaoban_report_duties';
    protected $fillable = ['report_id', 'position_id', 'employee_id', 'person_name', 'phone'];
    protected $casts = ['report_id' => 'integer', 'position_id' => 'integer', 'employee_id' => 'integer'];
}

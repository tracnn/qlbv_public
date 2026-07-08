<?php

namespace App\Models\GiaoBan;

use Illuminate\Database\Eloquent\Model;

class GiaoBanReport extends Model
{
    protected $table = 'giaoban_reports';
    protected $fillable = [
        'report_date', 'from_time', 'to_time', 'status', 'general_note',
        'created_by', 'finalized_by', 'finalized_at', 'unlocked_by', 'unlocked_at', 'data_fetched_at',
    ];

    public function cells()
    {
        return $this->hasMany(GiaoBanReportCell::class, 'report_id');
    }

    public function isFinal()
    {
        return $this->status === 'final';
    }
}

<?php

namespace App\Models\GiaoBan;

use Illuminate\Database\Eloquent\Model;

class GiaoBanDutyPosition extends Model
{
    protected $table = 'giaoban_duty_positions';
    protected $fillable = ['name', 'sort_order', 'is_active'];
    protected $casts = ['is_active' => 'boolean'];
}

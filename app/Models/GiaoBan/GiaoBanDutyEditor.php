<?php

namespace App\Models\GiaoBan;

use Illuminate\Database\Eloquent\Model;

class GiaoBanDutyEditor extends Model
{
    protected $table = 'giaoban_duty_editors';
    protected $fillable = ['user_id'];
    protected $casts = ['user_id' => 'integer'];
}

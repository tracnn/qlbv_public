<?php

namespace App\Models\OrderCheck;

use Illuminate\Database\Eloquent\Model;

class OrderCheckRefServiceRestriction extends Model
{
    protected $table = 'order_check_ref_service_restriction';
    protected $guarded = [];
    protected $casts = ['is_active' => 'boolean'];
}

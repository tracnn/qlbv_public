<?php

namespace App\Models\OrderCheck;

use Illuminate\Database\Eloquent\Model;

class OrderCheckRule extends Model
{
    protected $table = 'order_check_rules';
    protected $guarded = [];
    protected $casts = ['is_active' => 'boolean'];

    public function getParamsArrayAttribute()
    {
        return $this->params ? json_decode($this->params, true) : [];
    }

    public function getScopeArrayAttribute()
    {
        return $this->scope ? json_decode($this->scope, true) : [];
    }
}

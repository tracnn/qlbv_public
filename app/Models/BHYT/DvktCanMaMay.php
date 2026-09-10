<?php

namespace App\Models\BHYT;

use Illuminate\Database\Eloquent\Model;

class DvktCanMaMay extends Model
{
    protected $table = 'dvkt_can_ma_may';
    protected $fillable = ['ma_dvkt', 'ten_dvkt', 'is_active'];
}

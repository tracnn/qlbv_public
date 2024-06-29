<?php

namespace App\Models\His\Accountant;

use Illuminate\Database\Eloquent\Model;

class bn_vpsohd extends Model
{
    protected $connection = 'oracle';
    protected $table = 'bn_vpsohd';
    public $timestamps = false;
    protected $primaryKey = 'stt';
    public $incrementing = false;
}

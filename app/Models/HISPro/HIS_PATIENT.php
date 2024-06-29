<?php

namespace App\Models\HISPro;

use Illuminate\Database\Eloquent\Model;

class HIS_PATIENT extends Model
{
    protected $connection = 'HISPro';
    protected $table = 'HIS_PATIENT';
    public $timestamps = false;
}

<?php

namespace App\Models\HISPro;

use Illuminate\Database\Eloquent\Model;

class HIS_EMPLOYEE extends Model
{
    protected $connection = 'HISPro';
    protected $table = 'HIS_EMPLOYEE';
    public $timestamps = false;
}

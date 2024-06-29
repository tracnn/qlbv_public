<?php

namespace App\Models\HISPro;

use Illuminate\Database\Eloquent\Model;

class HIS_TRANSACTION extends Model
{
    protected $connection = 'HISPro';
    protected $table = 'HIS_TRANSACTION';
    public $timestamps = false;
}

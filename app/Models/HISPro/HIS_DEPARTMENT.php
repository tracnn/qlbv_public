<?php

namespace App\Models\HISPro;

use Illuminate\Database\Eloquent\Model;

class HIS_DEPARTMENT extends Model
{
    protected $connection = 'HISPro';
    protected $table = 'HIS_DEPARTMENT';
    public $timestamps = false;
}

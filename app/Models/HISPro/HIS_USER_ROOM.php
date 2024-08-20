<?php

namespace App\Models\HISPro;

use Illuminate\Database\Eloquent\Model;

class HIS_USER_ROOM extends Model
{
    protected $connection = 'HISPro';
    protected $table = 'HIS_USER_ROOM';
    public $timestamps = false;
}

<?php

namespace App\Models\HISPro;

use Illuminate\Database\Eloquent\Model;

class HIS_ICD extends Model
{
    protected $connection = 'HISPro';
    protected $table = 'HIS_ICD';
    public $timestamps = false;
}

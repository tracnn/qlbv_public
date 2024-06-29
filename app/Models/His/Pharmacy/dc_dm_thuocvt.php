<?php

namespace App\Models\His\Pharmacy;

use Illuminate\Database\Eloquent\Model;

class dc_dm_thuocvt extends Model
{
    protected $connection = 'oracle';
    protected $table = 'dc_dm_thuocvt';
    public $timestamps = false;
    protected $primaryKey = 'mavt';
    public $incrementing = false;
}

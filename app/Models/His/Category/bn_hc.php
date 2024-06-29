<?php

namespace App\Models\His\Category;

use Illuminate\Database\Eloquent\Model;

class bn_hc extends Model
{
    protected $connection = 'oracle';
    protected $table = 'bn_hc';
    public $timestamps = false;
    protected $primaryKey = 'mabn';
    public $incrementing = false;
}

<?php

namespace App\Models\His\Category;

use Illuminate\Database\Eloquent\Model;

class dm_kieukham extends Model
{
    protected $connection = 'oracle';
    protected $table = 'dm_kieukham';
    public $timestamps = false;
    protected $primaryKey = 'makk';
    public $incrementing = false;
}

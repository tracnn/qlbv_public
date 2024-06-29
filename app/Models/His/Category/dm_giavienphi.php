<?php

namespace App\Models\His\Category;

use Illuminate\Database\Eloquent\Model;

class dm_giavienphi extends Model
{
    protected $connection = 'oracle';
    protected $table = 'dm_giavienphi';
    public $timestamps = false;
    protected $primaryKey = 'mavp';
    public $incrementing = false;
}

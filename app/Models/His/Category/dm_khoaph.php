<?php

namespace App\Models\His\Category;

use Illuminate\Database\Eloquent\Model;

class dm_khoaph extends Model
{
    protected $connection = 'oracle';
    protected $table = 'dm_khoaph';
    public $timestamps = false;
    protected $primaryKey = 'makhp';
    public $incrementing = false;
}

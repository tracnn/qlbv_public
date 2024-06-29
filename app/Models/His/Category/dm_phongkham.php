<?php

namespace App\Models\His\Category;

use Illuminate\Database\Eloquent\Model;

class dm_phongkham extends Model
{
    protected $connection = 'oracle';
    protected $table = 'dm_phongkham';
    public $timestamps = false;
    protected $primaryKey = 'mapk';
    public $incrementing = false;
}

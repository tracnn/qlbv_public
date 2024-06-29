<?php

namespace App\Models\His\Category;

use Illuminate\Database\Eloquent\Model;

class dm_dichvudtnt extends Model
{
    protected $connection = 'oracle';
    protected $table = 'dm_dichvudtnt';
    public $timestamps = false;
    protected $primaryKey = 'madichvu';
    public $incrementing = false;
}

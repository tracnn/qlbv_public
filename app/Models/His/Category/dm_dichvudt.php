<?php

namespace App\Models\His\Category;

use Illuminate\Database\Eloquent\Model;

class dm_dichvudt extends Model
{
    protected $connection = 'oracle';
    protected $table = 'dm_dichvudt';
    public $timestamps = false;
    protected $primaryKey = 'madv';
    public $incrementing = false;
}

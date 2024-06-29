<?php

namespace App\Models\His\Category;

use Illuminate\Database\Eloquent\Model;

class dm_loaidvvp extends Model
{
    protected $connection = 'oracle';
    protected $table = 'dm_loaidvvp';
    public $timestamps = false;
    protected $primaryKey = 'stt';
    public $incrementing = false;
}

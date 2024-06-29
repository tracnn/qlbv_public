<?php

namespace App\Models\His\Category;

use Illuminate\Database\Eloquent\Model;

class dm_icd10bv extends Model
{
    protected $connection = 'oracle';
    protected $table = 'dm_icd10bv';
    public $timestamps = false;
    protected $primaryKey = 'id_icd10';
    public $incrementing = false;
}

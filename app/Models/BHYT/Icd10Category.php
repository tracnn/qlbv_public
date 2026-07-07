<?php

namespace App\Models\BHYT;

use Illuminate\Database\Eloquent\Model;

class Icd10Category extends Model
{
    protected $fillable = [
        'icd_code',
        'icd_name',
        'is_chronic',
        'is_active',
    ];
}

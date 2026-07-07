<?php

namespace App\Models\BHYT;

use Illuminate\Database\Eloquent\Model;

class IcdYhctCategory extends Model
{
    protected $fillable = [
        'icd_code',
        'icd_name',
        'icd_yhct_name',
        'icd10_code',
        'icd10_name',
        'is_active',
    ];
}

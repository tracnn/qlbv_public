<?php

namespace App\Models\BHYT;

use Illuminate\Database\Eloquent\Model;

class AdministrativeUnit extends Model
{
    protected $fillable = [
        'province_code',
        'province_name',
        'district_code',
        'district_name',
        'commune_code',
        'commune_name',
        'is_active'
    ];
}

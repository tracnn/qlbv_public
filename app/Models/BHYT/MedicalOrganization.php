<?php

namespace App\Models\BHYT;

use Illuminate\Database\Eloquent\Model;

class MedicalOrganization extends Model
{
    protected $fillable = [
        'ma_cskcb',
        'ten_cskcb',
        'dia_chi_cskcb',
        'is_active'
    ];
}

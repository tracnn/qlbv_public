<?php

namespace App\Models\BHYT;

use Illuminate\Database\Eloquent\Model;

class MedicalOrganization extends Model
{
    protected $fillable = [
        'ma_cskcb',
        'ten_cskcb',
        'tuyen_cmkt',
        'hang_benh_vien',
        'dia_chi_cskcb',
        'is_active'
    ];
}

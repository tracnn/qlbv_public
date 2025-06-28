<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BhxhEmrPermission extends Model
{
    protected $fillable = [
        'treatment_code', 'allow_view_at', 'patient_name', 'patient_dob',
        'patient_address', 'treatment_type_id', 'treatment_type_name', 'patient_type_id', 'patient_type_name',
        'hein_card_number', 'last_department_id', 'last_department_name', 'treatment_end_type_id', 'treatment_end_type_name',
        'in_time', 'out_time', 'fee_lock_time', 'patient_id', 'patient_code', 'allow_view_at'
    ];
}
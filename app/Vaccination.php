<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Vaccination extends Model
{
    protected $fillable = [
        'patient_id', 
        'vaccine_id', 
        'date_of_vaccination', 
        'dose_number', 
        'administered_amount', 
        'administered_by',
        'description_effect',
        'severity_effect',
        'date_noted_effect'
    ];

    // Mối quan hệ với bảng Patient
    public function patient()
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }

    // Mối quan hệ với bảng Vaccine
    public function vaccine()
    {
        return $this->belongsTo(Vaccine::class, 'vaccine_id');
    }
}

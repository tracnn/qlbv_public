<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Patient extends Model
{
    protected $fillable = ['code', 'name', 'date_of_birth', 'gender', 'contact_info', 'address'];

    // Mối quan hệ một-nhiều với bảng Vaccination
    public function vaccinations()
    {
        return $this->hasMany(Vaccination::class, 'patient_id');
    }

    public function preVaccinationChecks()
    {
        return $this->hasMany(PreVaccinationCheck::class, 'patient_id');
    }
}

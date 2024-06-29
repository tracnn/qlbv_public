<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class PreVaccinationCheck extends Model
{
    protected $fillable = [
        'patient_id', 
        'vaccine_id',
        'administered_by', 
        'weight', 
        'temperature', 
        'anaphylactic_reaction', 
        'acute_or_chronic_disease', 
        'corticosteroids', 
        'fever_or_hypothermia', 
        'immune_deficiency', 
        'abnormal_heart', 
        'abnormal_lungs', 
        'abnormal_consciousness', 
        'other_contraindications', 
        'specialist_exam', 
        'specialist_exam_details', 
        'eligible_for_vaccination', 
        'contraindication', 
        'postponed', 
        'time'
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class, 'patient_id');
    }

    public function vaccine()
    {
        return $this->belongsTo(Vaccine::class, 'vaccine_id');
    }
}

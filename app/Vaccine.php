<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Vaccine extends Model
{
    protected $fillable = ['code', 'name', 'manufacturer', 'recommended_age', 'dose_interval', 'storage_requirements'];

    // Mối quan hệ một-nhiều với bảng Vaccination
    public function vaccinations()
    {
        return $this->hasMany(Vaccination::class, 'vaccine_id');
    }
}

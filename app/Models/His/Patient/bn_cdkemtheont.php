<?php

namespace App\Models\His\Patient;

use Illuminate\Database\Eloquent\Model;

class bn_cdkemtheont extends Model
{
    protected $connection = 'oracle';
    protected $table = 'bn_cdkemtheont';
    public $timestamps = false;
    protected $primaryKey = 'idcdkt';
    public $incrementing = false;
    
    public function dm_icd10bv(){
        return $this->hasOne('App\Models\His\Category\dm_icd10bv','id_icd10','maicd');
    }
}

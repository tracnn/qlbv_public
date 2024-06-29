<?php

namespace App\Models\His\Patient;

use Illuminate\Database\Eloquent\Model;

class bn_nhapkhoa extends Model
{
    protected $connection = 'oracle';
    protected $table = 'bn_nhapkhoa';
    public $timestamps = false;
    protected $primaryKey = 'mavaokhoa';
    public $incrementing = false;

    public function dm_khoaph(){
        return $this->hasOne('App\Models\His\Category\dm_khoaph','makhp','makden');
    }
    
    public function dm_icd10bv(){
        return $this->hasOne('App\Models\His\Category\dm_icd10bv','id_icd10','maicd');
    }
    
    public function bn_hc(){
        return $this->hasOne('App\Models\His\Category\bn_hc','mabn','mabn');
    }

    public function bhyt(){
        return $this->hasOne('App\Models\His\Patient\bhyt','mabn','mabn');
    }

    public function bn_nhapvien(){
        return $this->hasOne('App\Models\His\Patient\bn_nhapvien','malankham','malankham');
    }

}

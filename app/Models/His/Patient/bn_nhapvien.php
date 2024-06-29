<?php

namespace App\Models\His\Patient;

use Illuminate\Database\Eloquent\Model;

class bn_nhapvien extends Model
{
    protected $connection = 'oracle';
    protected $table = 'bn_nhapvien';
    public $timestamps = false;
    protected $primaryKey = 'malankham';
    public $incrementing = false;

    public function dm_khoaph(){
        return $this->hasOne('App\Models\His\Category\dm_khoaph','makhp','makden');
    }
    
    public function dm_icd10bv(){
        return $this->hasOne('App\Models\His\Category\dm_icd10bv','id_icd10','maicd');
    }
    
    public function quevpnoitru(){
        return $this->hasMany('App\Models\His\Accountant\quevpnoitru','malankham','malankham');
    }

    public function bn_hc(){
        return $this->hasOne('App\Models\His\Category\bn_hc','mabn','mabn');
    }

    public function bhyt(){
        return $this->hasOne('App\Models\His\Patient\bhyt','mabn','mabn');
    }
}

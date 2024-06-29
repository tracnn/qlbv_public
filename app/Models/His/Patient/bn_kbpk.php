<?php

namespace App\Models\His\Patient;

use Illuminate\Database\Eloquent\Model;

class bn_kbpk extends Model
{
    protected $connection = 'oracle';
    protected $table = 'bn_kbpk';
    public $timestamps = false;
    protected $primaryKey = 'malankham';
    public $incrementing = false;

    public function dm_phongkham(){
    	return $this->hasOne('App\Models\His\Category\dm_phongkham','mapk','mapk');
    }
    
    public function dm_icd10bv(){
        return $this->hasOne('App\Models\His\Category\dm_icd10bv','id_icd10','maicd');
    }

    public function bn_cdkemtheo(){
        return $this->hasOne('App\Models\His\Patient\bn_cdkemtheo','malankham','malankham');
    }

    public function bn_quvppk(){
        return $this->hasMany('App\Models\His\Accountant\bn_quvppk','malankham','malankham');
    }

    public function bn_vienphipk(){
        return $this->hasOne('App\Models\His\Accountant\bn_vienphipk','malankham','malankham');
    }
}

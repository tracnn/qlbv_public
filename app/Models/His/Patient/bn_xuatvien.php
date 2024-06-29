<?php

namespace App\Models\His\Patient;

use Illuminate\Database\Eloquent\Model;

class bn_xuatvien extends Model
{
    protected $connection = 'oracle';
    protected $table = 'bn_xuatvien';
    public $timestamps = false;
    protected $primaryKey = 'malankham';
    public $incrementing = false;

    public function dm_khoaph(){
        return $this->hasOne('App\Models\His\Category\dm_khoaph','makhp','makhrav');
    }
    public function dm_icd10bv(){
        return $this->hasOne('App\Models\His\Category\dm_icd10bv','id_icd10','maicd');
    }

    public function bn_cdkemtheont(){
        return $this->hasMany('App\Models\His\Patient\bn_cdkemtheont','malankham','malankham');
    }

    public function hoadonravien(){
        return $this->hasOne('App\Models\His\Accountant\hoadonravien','malankham','malankham');
    }

    public function bn_nhapvien(){
        return $this->hasOne('App\Models\His\Patient\bn_nhapvien','malankham','malankham');
    }
}

<?php

namespace App\Models\His\Accountant;

use Illuminate\Database\Eloquent\Model;

class quevpnoitru extends Model
{
    protected $connection = 'oracle';
    protected $table = 'quevpnoitru';
    public $timestamps = false;
    protected $primaryKey = 'idquevpnt';
    public $incrementing = false;

    public function dm_dichvudtnt(){
        return $this->hasOne('App\Models\His\Category\dm_dichvudtnt','madichvu','madv');
    }

    public function dm_xetnghiembv(){
        return $this->hasOne('App\Models\His\Category\dm_xetnghiembv','maxn','madv');
    }
    
    public function dc_dm_thuocvt(){
        return $this->hasOne('App\Models\His\Pharmacy\dc_dm_thuocvt','mavt','madv');
    }
    
    public function bn_nhapvien(){
        return $this->hasOne('App\Models\His\Patient\bn_nhapvien','malankham','malankham');
    }
    public function bn_xuatvien(){
        return $this->hasOne('App\Models\His\Patient\bn_xuatvien','malankham','malankham');
    }
}

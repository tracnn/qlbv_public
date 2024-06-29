<?php

namespace App\Models\His\Insurance;

use Illuminate\Database\Eloquent\Model;

class baohiem_tong extends Model
{
    protected $connection = 'oracle';
    protected $table = 'baohiem_tong';
    public $timestamps = false;
    protected $primaryKey = 'sophieu';
    public $incrementing = false;

    public function bn_hc(){
    	return $this->hasOne('App\Models\His\Category\bn_hc','mabn','mabenhnhan');
    }

    public function primary_icd(){
        return $this->hasOne('App\Models\His\Category\dm_icd10bv','id_icd10','maicd');
    }

    public function secondary_icd(){
        return $this->hasOne('App\Models\His\Category\dm_icd10bv','id_icd10','cdkemtheo');
    }

    public function dm_phongkham(){
        return $this->hasOne('App\Models\His\Category\dm_phongkham','mapk','mapk');
    }

    public function bn_tiepdon(){
        return $this->hasOne('App\Models\His\patient\bn_tiepdon','sophieu','sophieu');
    }

    public function getSearchResults($params)
    {
        return $this->select()
        	->where('sothe', 'LIKE', '%'. $params['card-number'] .'%')
        	->where('hotenbn', 'LIKE', '%'. $params['name'] .'%')
        	->where('sophieu', 'LIKE', '%'. $params['id_number'] .'%')
            ->where('ngaykham', '>=', $params['date_checkup']['from'] ? $params['date_checkup']['from']:'1970-01-01')
            ->where('ngaykham', '<=', $params['date_checkup']['to'] ? date_format(date_create($params['date_checkup']['to']),'Y-m-d 23:59:59'):'2099-12-31 23:59:59');
    }

}

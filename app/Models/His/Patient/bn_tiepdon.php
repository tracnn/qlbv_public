<?php

namespace App\Models\His\Patient;

use Illuminate\Database\Eloquent\Model;

class bn_tiepdon extends Model
{
    protected $connection = 'oracle';
    protected $table = 'bn_tiepdon';
    public $timestamps = false;
    protected $primaryKey = 'malankham';
    public $incrementing = false;

    public function dm_phongkham(){
    	return $this->hasOne('App\Models\His\Category\dm_phongkham','mapk','mapk');
    }

    public function bn_vienphipk(){
        return $this->hasOne('App\Models\His\Accountant\bn_vienphipk','malankham','malankham');
    }

    public function bn_quvppk(){
        return $this->hasMany('App\Models\His\Accountant\bn_quvppk','malankham','malankham');
    }

    public function getSearchResults($params)
    {
        return $this->with('dm_phongkham')
        	->where('mapk', 'LIKE', '%'. $params['mapk'] .'%')
            ->where('ngaytd', '>=', $params['date_visit']['from'] ? $params['date_visit']['from']:'1970-01-01')
            ->where('ngaytd', '<=', $params['date_visit']['to'] ? date_format(date_create($params['date_visit']['to']),'Y-m-d 23:59:59'):'2999-12-31 23:59:59');
    }
}

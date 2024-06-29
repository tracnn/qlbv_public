<?php

namespace App\Models\His\Insurance;

use Illuminate\Database\Eloquent\Model;

class baohiem_cls extends Model
{
    protected $connection = 'oracle';
    protected $table = 'baohiem_cls';
    public $timestamps = false;
    protected $primaryKey = 'ma_bhcls';
    public $incrementing = false;

    public function dm_phongkham(){
    	return $this->hasOne('App\Models\His\Category\dm_phongkham','mapk','mapk');
    }

    public function dm_xetnghiembv(){
    	return $this->hasOne('App\Models\His\Category\dm_xetnghiembv','id','macls');
    }
}

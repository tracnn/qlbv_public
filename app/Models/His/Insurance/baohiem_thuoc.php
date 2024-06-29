<?php

namespace App\Models\His\Insurance;

use Illuminate\Database\Eloquent\Model;

class baohiem_thuoc extends Model
{
    protected $connection = 'oracle';
    protected $table = 'baohiem_thuoc';
    public $timestamps = false;
    protected $primaryKey = 'ma_bht';
    public $incrementing = false;

    public function dc_dm_thuocvt(){
    	return $this->hasOne('App\Models\His\Category\dc_dm_thuocvt','mavt','mathuoc');
    }
    public function dm_phongkham(){
    	return $this->hasOne('App\Models\His\Category\dm_phongkham','mapk','mapk');
    }
}

<?php

namespace App\Models\His\Accountant;

use Illuminate\Database\Eloquent\Model;

class bn_quvppk extends Model
{
    protected $connection = 'oracle';
    protected $table = 'bn_quvppk';
    public $timestamps = false;
    protected $primaryKey = 'mavpcho';
    public $incrementing = false;

    public function dm_phongkham(){
    	return $this->hasOne('App\Models\His\Category\dm_phongkham','mapk','mapk');
    }

    public function dm_giavienphi(){
    	return $this->hasOne('App\Models\His\Category\dm_giavienphi','mavp','mavp');
    }

    public function dc_dm_thuocvt(){
        return $this->hasOne('App\Models\His\Pharmacy\dc_dm_thuocvt','mavt','mavp');
    }

    public function dm_dichvudt(){
        return $this->hasOne('App\Models\His\Category\dm_dichvudt','madv','machmon');
    }
}

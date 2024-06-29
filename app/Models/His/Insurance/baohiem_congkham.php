<?php

namespace App\Models\His\Insurance;

use Illuminate\Database\Eloquent\Model;

class baohiem_congkham extends Model
{
    protected $connection = 'oracle';
    protected $table = 'baohiem_congkham';
    public $timestamps = false;
    protected $primaryKey = 'ma_bhck';
    public $incrementing = false;

    public function dm_phongkham(){
    	return $this->hasOne('App\Models\His\Category\dm_phongkham','mapk','mapk');
    }
    public function dm_kieukham(){
    	return $this->hasOne('App\Models\His\Category\dm_kieukham','makk','makk');
    }
}

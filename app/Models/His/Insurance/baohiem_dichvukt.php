<?php

namespace App\Models\His\Insurance;

use Illuminate\Database\Eloquent\Model;

class baohiem_dichvukt extends Model
{
    protected $connection = 'oracle';
    protected $table = 'baohiem_dichvukt';
    public $timestamps = false;
    protected $primaryKey = 'ma_bhdvkt';
    public $incrementing = false;

    public function dm_phongkham(){
    	return $this->hasOne('App\Models\His\Category\dm_phongkham','mapk','mapk');
    }

    public function dm_dichvudt(){
    	return $this->hasOne('App\Models\His\Category\dm_dichvudt','madv','madvkt');
    }
}

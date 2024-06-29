<?php

namespace App\Models\His\Accountant;

use Illuminate\Database\Eloquent\Model;

class hoadonrvchitiet extends Model
{
    protected $connection = 'oracle';
    protected $table = 'hoadonrvchitiet';
    public $timestamps = false;
    protected $primaryKey = 'idchitiet';
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
    
    public function hoadonravien(){
        return $this->belongsTo('App\Models\His\Accountant\hoadonravien','idhoadon','idhoadon');
    }
}

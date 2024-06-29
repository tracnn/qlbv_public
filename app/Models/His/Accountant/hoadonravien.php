<?php

namespace App\Models\His\Accountant;

use Illuminate\Database\Eloquent\Model;

class hoadonravien extends Model
{
    protected $connection = 'oracle';
    protected $table = 'hoadonravien';
    public $timestamps = false;
    protected $primaryKey = 'idhoadon';
    public $incrementing = false;

    public function bn_vpsohd(){
        return $this->hasOne('App\Models\His\Accountant\bn_vpsohd','stt','maquyenthu');
    }

    public function hoadonrvchitiet(){
        return $this->hasMany('App\Models\His\Accountant\hoadonrvchitiet','idhoadon','idhoadon');
    }

    public function bn_nhapvien(){
        return $this->hasOne('App\Models\His\Patient\bn_nhapvien','malankham','malankham');
    }
    public function bn_xuatvien(){
        return $this->hasOne('App\Models\His\Patient\bn_xuatvien','malankham','malankham');
    }
}

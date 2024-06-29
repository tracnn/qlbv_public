<?php

namespace App\Models\His\Accountant;

use Illuminate\Database\Eloquent\Model;

class bn_vienphipk extends Model
{
    protected $connection = 'oracle';
    protected $table = 'bn_vienphipk';
    public $timestamps = false;
    protected $primaryKey = 'mabienlai';
    public $incrementing = false;

    public function bn_vpsohd(){
        return $this->hasOne('App\Models\His\Accountant\bn_vpsohd','stt','quyenso');
    }

    public function bn_vppkct(){
        return $this->hasMany('App\Models\His\Accountant\bn_vppkct','mabienlai','mabienlai');
    }
}

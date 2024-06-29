<?php

namespace App\Models\His\Patient;

use Illuminate\Database\Eloquent\Model;

class bhyt extends Model
{
    protected $connection = 'oracle';
    protected $table = 'bhyt';
    public $timestamps = false;
    protected $primaryKey = 'maluubh';
    public $incrementing = false;

    public function bn_hc(){
        return $this->hasOne('App\Models\His\Category\bn_hc','mabn','mabn');
    }
}

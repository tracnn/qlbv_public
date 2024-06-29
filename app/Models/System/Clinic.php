<?php

namespace App\Models\System;

use Illuminate\Database\Eloquent\Model;

class Clinic extends Model
{
    public function getSearchResults($params)
    {
        return $this->select()
            ->where('code', 'LIKE', '%'. $params['code'] .'%')
            ->where('name', 'LIKE', '%'. $params['name'] .'%');
    }
}

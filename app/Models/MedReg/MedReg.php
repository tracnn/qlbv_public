<?php

namespace App\Models\MedReg;

use Illuminate\Database\Eloquent\Model;

class MedReg extends Model
{
    public function getSearchResults($params)
    {
        return $this->select()
            ->where('name', 'LIKE', '%'. $params['name'] .'%')
            ->where('gender', 'LIKE', '%'. $params['gender'] .'%')
            ->where('city', 'LIKE', '%'. $params['city'] .'%')
            ->where('district', 'LIKE', '%'. $params['district'] .'%')
            ->where('ward', 'LIKE', '%'. $params['ward'] .'%')
            ->where('email', 'LIKE', '%'. $params['email'] .'%')
            ->where('phone', 'LIKE', '%'. $params['phone'] .'%')
            ->where('healthcaredate', '>=', $params['healthcaredate']['from'] ? $params['healthcaredate']['from']:'1900-01-01')
            ->where('healthcaredate', '<=', $params['healthcaredate']['to'] ? date_format(date_create($params['healthcaredate']['to']),'Y-m-d 23:59:59'):'2999-12-31 23:59:59')
            ->where('healthcaretime', 'LIKE', '%'. $params['healthcaretime'] .'%');
    }
}

<?php

namespace App\Models\CheckBHYT;

use Illuminate\Database\Eloquent\Model;

class check_insurance extends Model
{
    public function getSearchResults($params)
    {
        return $this->select()
        	->where('result_code', '<>', $params['insurance_error_code'])
            ->where('check_code', '<>', $params['check_insurance_code'])
            ->where('date_examine', '>=', $params['date_checkup']['from'] ? $params['date_checkup']['from']:'1970-01-01')
            ->where('date_examine', '<=', $params['date_checkup']['to'] ? date_format(date_create($params['date_checkup']['to']),'Y-m-d 23:59:59'):'2999-12-31 23:59:59');
    }

    public function dm_phongkham(){
    	return $this->hasOne('App\Models\His\Category\dm_phongkham','mapk','clinic_code');
    }
}

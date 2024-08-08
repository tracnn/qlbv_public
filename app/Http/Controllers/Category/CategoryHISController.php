<?php

namespace App\Http\Controllers\Category;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class CategoryHISController extends Controller
{
    public function listKskContract()
    {
        return DB::connection('HISPro')
        ->table('his_ksk_contract')
        ->join('his_work_place', 'his_work_place.id', '=', 'his_ksk_contract.work_place_id')
        ->where('his_ksk_contract.is_active', 1)
        ->where('his_ksk_contract.is_delete', 0)
        ->select('his_ksk_contract.id', 'his_ksk_contract.ksk_contract_code', 'his_work_place.id as work_place_id', 'his_work_place.work_place_code', 'his_work_place.work_place_name')
        ->get();
    }
}
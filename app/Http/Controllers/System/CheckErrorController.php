<?php

namespace App\Http\Controllers\System;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use App\Models\His\Insurance\baohiem_tong;
use App\Models\His\Patient\bn_xuatvien;
use App\Models\His\Patient\bn_tiepdon;

class CheckErrorController extends Controller
{
    protected $searchParams;

    public function __construct()
    {
        $this->searchParams = [
            'date' => date_format(now(),'Y-m-d'),
            'malienket' => null,
            'loai_hoso' => null,
        ];      
    }

    public function index(Request $request)
    {

        $params = $this->searchParams;
        $loai_hoso = config('__tech.loai_hoso');

    	return view('system.check-error.index', compact('params','loai_hoso'));
    }

    public function search(Request $request)
    {

        $params = $this->__getSearchParam($request);
        $loai_hoso = config('__tech.loai_hoso');

        $result = $this->getData($params);

    	return view('system.check-error.index', compact('params','loai_hoso','result'));
    }

    private function __getSearchParam($request)
    {
        return [
            'date' => $request->get('date'),
            'malienket' => $request->get('malienket'),
            'loai_hoso' => $request->get('loai_hoso'),
        ];
    }

    private function getData($params)
    {
    	$loai_hoso = $params['loai_hoso'];
    	$result = [];
    	switch ($loai_hoso) {
    		case '1':
    			$result = $this->getDataNgoaitru($params);
    			break;
    		case '2':
    			$result = $this->getDataNoitru($params);
    			break;
    		default:
    			break;
    	}

    	return $result;
    }

    private function getDataNgoaitru($params)
    {
    	//return explode(',', $params['malienket']);
    	return bn_tiepdon::with('dm_phongkham','bn_vienphipk')
    		->whereIn('sophieu', explode(',',$params['malienket']))->get();
    }

    private function getDataNoitru($params)
    {
    	return bn_xuatvien::with('dm_khoaph','hoadonravien')
    		->whereIn('malankham', explode(',',$params['malienket']))->get();
    }
}

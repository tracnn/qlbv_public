<?php

namespace App\Http\Controllers\Insurance\Manager;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use DB;
use Yajra\Datatables\Datatables;

class MedicineSearchController extends Controller
{
    public function index(Request $request)
    {
    	return view('insurance.manager.medicine-search.index');
    }

    public function getdata(Request $request)
    {
    	if (!$request->ajax()) {
            return redirect()->route('home');
        }
    	return Datatables::of(DB::table('medicine_searchs')
    		->select('ma_thuoc','ten_thuoc','ma_hoat_chat',
    			'ten_hoat_chat','ma_duong_dung','ten_duong_dung',
    			'ham_luong','so_dang_ky','nhom_thuoc','don_vi_tinh',
    			'don_gia','so_luong','hang_san_xuat','nuoc_san_xuat',
    			'nha_thau','quyet_dinh','cong_bo'
    		))
    		->toJson();
        // $model = DB::table('medicine_searchs')
        //     ->get();
        // return $model;
    }
}

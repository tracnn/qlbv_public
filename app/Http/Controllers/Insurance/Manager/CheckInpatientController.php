<?php

namespace App\Http\Controllers\Insurance\Manager;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use App\Models\His\Category\dm_khoaph;
use App\Models\His\Patient\bn_nhapvien;
use App\Models\His\Category\bn_hc;
use App\Models\His\Patient\bhyt;
use App\Models\His\Patient\bn_nhapkhoa;

use App\Jobs\JobInpatient;

class CheckInpatientController extends Controller
{
    protected $searchParams;

    public function __construct()
    {

        $this->searchParams = [
            'date' => [
            	'from' => date_format(now(),'Y-m-d'),
            	'to' => date_format(now(),'Y-m-d')
            ],
            'status' => null,
            'department' => null,
            'patient_type' => null,
        ];
    }


    public function index()
    {
    	$params = $this->searchParams;
    	$trangthai_noitru = config('__tech.trangthai_noitru');
    	$duoc_doituong = config('__tech.duoc_doituong');
    	$dm_khoaph = $this->getDM_KHOAPH();

        return view('insurance.manager.check-inpatient.index', 
        	compact('params','trangthai_noitru','dm_khoaph','duoc_doituong')
        );
    }

    public function search(Request $request)
    {
    	$params = $this->__getSearchParam($request);
    	$trangthai_noitru = config('__tech.trangthai_noitru');
    	$duoc_doituong = config('__tech.duoc_doituong');
    	$dm_khoaph = $this->getDM_KHOAPH();

        $loginBHYT = \App\BHYT::loginBHYT();
        if($loginBHYT['maKetQua'] != '200') {
            flash(config('__tech.login_error_BHYT')[$loginBHYT['maKetQua']])->overlay();
            return redirect()->back();
        }

        $bn_nhapkhoa = $this->getBN_NHAPKHOA($params);//->chunk(10);
        $count = $bn_nhapkhoa->count();
        $bn_nhapkhoa = $bn_nhapkhoa->chunk(100);
        foreach ($bn_nhapkhoa as $key => $detail) {
            //echo $value . '<br>';
            $the = array('maThe' => $detail->bhyt ? $detail->bhyt->sothe : '', 
                'hoTen' => $detail->bn_hc->hotenbn, 
                'ngaySinh' => date_format(date_create($detail->bn_hc->ngaysinh),'d/m/Y'), 
                'gioiTinh' =>  doubleval($detail->bn_hc->gioi == 1 ? $detail->bn_hc->gioi : 2), 
                'maCSKCB' => $detail->bhyt ? $detail->bhyt->mabv : '', 
                'ngayBD' => $detail->bhyt ? date_format(date_create($detail->bhyt->giatritu),'d/m/Y') : '', 
                'ngayKT' => $detail->bhyt ? date_format(date_create($detail->bhyt->giatriden),'d/m/Y') : '');
            //echo date_format(date_create($detail->ngay),'d/m/Y') . '<br>';
            //echo $value . '<br>';//->mabn;//['hotenbn'];
            
            //$ngay = date_format(date_create($detail->ngay),'d/m/Y');
            //dump($detail->bn_nhapvien->madoituong);
            if ($the['maThe']) {
                dispatch(new JobInpatient($key+1,$count,$the,$loginBHYT['APIKey']['access_token'], 
                    $loginBHYT['APIKey']['id_token'],\Auth::user()->id,date_format(date_create($detail->ngay),'d/m/Y'),
                    $detail->dm_khoaph->tenkhp,$detail->act,$detail->bn_nhapvien->madoituong));
            }

            // dispatch(new JobInpatient($key+1,$count,$params, $loginBHYT['APIKey']['access_token'], 
            //     $loginBHYT['APIKey']['id_token'],\Auth::user()->id),
            //     $detail->date_format(date_create($detail->ngay),'d/m/Y'),
            //     $detail->dm_khoaphong->tenkhp,
            //     $detail->act,$detail->madoituong);
        }

        return view('insurance.manager.check-inpatient.index', 
        	compact('params','trangthai_noitru','dm_khoaph','duoc_doituong','bn_nhapkhoa')
        );
    }



    private function getDM_KHOAPH()
    {
    	return dm_khoaph::where('maloaikhp',2)
    		->where('act',1)
    		->orderBy('makhp')
    		->get();
    }

    private function __getSearchParam($request)
    {
        return [
            'date' => [
            	'from' => $request->get('date')['from'],
            	'to' => $request->get('date')['to']
            ],
            'status' => $request->get('status'),
            'department' => $request->get('department'),
            'patient_type' => $request->get('patient_type'),
        ];
    }

    private function getBN_NHAPKHOA($params)
    {
    	return bn_nhapkhoa::with('dm_khoaph','bn_nhapvien','bn_hc','bhyt')
    		->where('makden', 'LIKE', '%'.$params['department'].'%')
    		->where('act', 'LIKE', '%'.$params['status'].'%')
            ->whereHas('bn_nhapvien', function($q) use ($params) {
                $q->where(function ($q) use ($params) {
                   $q->where('madoituong', 'LIKE', '%'.$params['patient_type'].'%');
                });
            })
    		->where('ngay', '>=', $params['date']['from'] ? $params['date']['from']:'1970-01-01')
            ->where('ngay', '<=', $params['date']['to'] ? date_format(date_create($params['date']['to']),'Y-m-d 23:59:59'):'2999-12-31 23:59:59')->get();
    }
}

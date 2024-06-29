<?php

namespace App\Http\Controllers\Insurance\Manager;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use App\Models\His\Insurance\baohiem_tong;

use App\Models\His\Insurance\baohiem_thuoc;
use App\Models\His\Insurance\baohiem_cls;
use App\Models\His\Insurance\baohiem_dichvukt;
use App\Models\His\Insurance\baohiem_congkham;

use App\Models\CheckBHYT\check_insurance;

use App\BHYT;
use App\Jobs\JobBHYT;

use GuzzleHttp;

class CheckEnteredController extends Controller
{
    protected $searchParams;

    public function __construct()
    {

        $this->searchParams = [
            'date_checkup' => [
            	'from' => date_format(now(),'Y-m-d'),
            	'to' => date_format(now(),'Y-m-d')
            ],
            'card-number' => null,
            'name' => null,
            'id_number' => null,
            'insurance_error_code' => null,
            'check_insurance_code' => null
        ];
    }

    public function checkEnteredOutpatient(Request $request)
    {
        return view('insurance.manager.check-entered.index');
    }


    public function checkEnteredInsurance(Request $request)
    {
    	$params = $this->searchParams;

   		$models = new baohiem_tong;
   		$models = $models->getSearchResults($params);
        $models->with('bn_hc');
   		$sum_total = $models->sum('tongcong');
	    $models = $models->paginate(config('__tech.pagination_count'));

    	return view('insurance.manager.check-entered.insurance.index', compact('models','params','sum_total'));
    }

    public function searchEnteredInsurance(Request $request)
    {
        $params = $this->__getSearchParam($request);
        //return str_slug($params['name']);

   		$models = new baohiem_tong;
   		$models = $models->getSearchResults($params);
        $models->with('bn_hc');
   		$sum_total = $models->sum('tongcong');
	    $models = $models->paginate(config('__tech.pagination_count'));

		return view('insurance.manager.check-entered.insurance.index', compact('models','params','sum_total'));
    }

    public function detailEnteredInsurance(Request $request)
    {
        $id = $this->__getID($request);

        $baohiem_tong = $this->getbaohiem_tong($id);
        $baohiem_thuoc = $this->getbaohiem_thuoc($id);
        $baohiem_cls = $this->getbaohiem_cls($id);
        $baohiem_dichvukt = $this->getbaohiem_dichvukt($id);
        $baohiem_congkham = $this->getbaohiem_congkham($id);

        //return $baohiem_congkham;
        return view('insurance.manager.check-entered.insurance.detail', compact('baohiem_tong','baohiem_thuoc','baohiem_cls','baohiem_dichvukt','baohiem_congkham'));
    }

    public function checkBussinesRules(Request $request)
    {
        $params = $this->searchParams;
        if($request->get('date_checkup')){
            $params = $this->__getSearchParam($request);
        }

        if($request['date_checkup']['from'] != $request['date_checkup']['to']) {
            flash(__('insurance.backend.exceptions.fromdate_not_equal_todate'))->error();
            return redirect()->back();
        }

        $loginBHYT = BHYT::loginBHYT();
        if($loginBHYT['maKetQua'] != '200') {
            flash(config('__tech.login_error_BHYT')[$loginBHYT['maKetQua']])->overlay();
            return redirect()->back();
        }

        $models = new baohiem_tong;
        $models = $models->getSearchResults($params);
        $models->with('bn_hc');
        $models = $models->get();

        $delete_insurance = new check_insurance;
        $delete_insurance = $delete_insurance->getSearchResults($params);
        $delete_insurance->delete();

        $count = $models->count();

        foreach ($models as $key => $value) {
            $the = array('maThe' => $value->sothe, 
                'hoTen' => $value->hotenbn, 
                'ngaySinh' => date_format(date_create($value->bn_hc->ngaysinh),'d/m/Y'), 
                'gioiTinh' =>  doubleval($value->bn_hc->gioi == 1 ? $value->bn_hc->gioi : 2), 
                'maCSKCB' => $value->noidk, 
                'ngayBD' => date_format(date_create($value->giatritu),'d/m/Y'), 
                'ngayKT' => date_format(date_create($value->giatriden),'d/m/Y'),
                'sophieu' => $value->sophieu,
                'mabn' => $value->mabenhnhan,
                'ngaykham' => $value->ngaykham,
                'mapk' => $value->mapk);
            //dump($the);
            dispatch(new JobBHYT($key+1,$count,
                $the, 
                $loginBHYT['APIKey']['access_token'], 
                $loginBHYT['APIKey']['id_token'],
                \Auth::user()->id));
        }

        flash(__('manager.backend.labels.background_mode'));
        return view('system.broadcast.index');
    }

    public function reportBussinesRules(Request $request)
    {
        $params = $this->searchParams;

        $check_insurance = new check_insurance;
        $check_insurance = $check_insurance->getSearchResults($params);
        $check_insurance->with('dm_phongkham');
        $check_insurance = $check_insurance->get();//->paginate(config('__tech.pagination_count'));
        $insurance_error_code = config('__tech.insurance_error_code');
        $check_insurance_code = config('__tech.check_insurance_code');

        return view('insurance.manager.check-entered.insurance.reports.checkcard', compact('check_insurance',
            'insurance_error_code',
            'check_insurance_code',
            'params'));
    }

    public function searchReportBussinesRules(Request $request)
    {
        $params = $this->__getSearchParam($request);
        $check_insurance = new check_insurance;
        $check_insurance = $check_insurance->getSearchResults($params);
        $check_insurance->with('dm_phongkham');
        $check_insurance = $check_insurance->get();//->paginate(config('__tech.pagination_count'));
        //return $check_insurance;
        $insurance_error_code = config('__tech.insurance_error_code');
        $check_insurance_code = config('__tech.check_insurance_code');

        return view('insurance.manager.check-entered.insurance.reports.checkcard', compact('check_insurance',
            'insurance_error_code',
            'check_insurance_code',
            'params'));        
    }

    private function getbaohiem_tong($id)
    {
        return baohiem_tong::with('bn_hc','primary_icd','secondary_icd')->findOrfail($id)->first();
    }

    private function getbaohiem_thuoc($id)
    {
        return baohiem_thuoc::with('dc_dm_thuocvt','dm_phongkham')->where('sophieu', $id)->get();
    }

    private function getbaohiem_cls($id)
    {
        return baohiem_cls::with('dm_phongkham','dm_xetnghiembv')->where('sophieu', $id)->get();
    }

    private function getbaohiem_dichvukt($id)
    {
        return baohiem_dichvukt::where('sophieu', $id)->get();
    }

    private function getbaohiem_congkham($id)
    {
        return baohiem_congkham::with('dm_phongkham','dm_kieukham')->where('sophieu', $id)->get();
    }

    private function __getSearchParam($request)
    {
        return [
            'date_checkup' => [
            	'from' => $request->get('date_checkup')['from'],
            	'to' => $request->get('date_checkup')['to']
            ],
            'card-number' => mb_strtoupper($request->get('card-number')),
            'name' => mb_strtoupper($request->get('name')),
            'id_number' => mb_strtoupper($request->get('id_number')),
            'insurance_error_code' => $request->get('insurance_error_code'),
            'check_insurance_code' => $request->get('check_insurance_code')
        ];
    }

    private function __getID($request)
    {
        return [
            'id' => $request->get('id'),
        ];
    }
}

<?php

namespace App\Http\Controllers\System;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use DB;
use Redis;
use App\Models\His\Patient\bhyt;
use App\Models\His\Patient\bn_kbpk;
use App\Models\His\Patient\bn_nhapvien;
use App\Models\His\Patient\bn_xuatvien;
use App\Models\His\Category\bn_hc;
use App\Models\His\Patient\bn_cdkemtheo;
use App\Models\His\Patient\bn_cdkemtheont;

use App\Models\His\Accountant\bn_vienphipk;
use App\Models\His\Accountant\hoadonravien;
use App\Models\His\Accountant\bn_vpsohd;
use App\Models\His\Accountant\bn_quvppk;
use App\Models\His\Accountant\hoadonrvchitiet;

use App\Models\BHYT\sys_param;

class SystemController extends Controller
{

    protected $searchParams;

    public function __construct()
    {
        $this->searchParams = [
            'date' => [
                'from' => date_format(now(),'Y-m-d'),
                'to' => date_format(now(),'Y-m-d')
            ],
            'treatment_code' => null,
        ];      
    }

    public function index(Request $request)
    {
        $params = $this->searchParams;

    	return view('system.user-function.index', compact('params'));
    }

    public function search(Request $request)
    {
        $params = $this->__getSearchParam($request);
        $result = DB::connection('HISPro')
            ->table('his_treatment')
            ->where('treatment_code', $request->treatment_code)
            ->first();
    	return view('system.user-function.index', compact('params', 'result'));
    }

    public function detailInpatientBill(Request $request)
    {
        $huybl = config('__tech.huybl');
        $trangthai_noitru = config('__tech.trangthai_noitru');
        $duoc_doituong = config('__tech.duoc_doituong');
        $duoc_act = config('__tech.duoc_act');

        $malankham = $request->get('malankham');
        $hoadonravien = $this->getHOADONRAVIEN_BY_MALANKHAM($malankham);
        if(!$hoadonravien){
            flash('Không tìm thấy thông tin yêu cầu')->error();
            return view('system.user-function.detail-inpatient-bill', compact('params','hoadonravien','hoadonrvchitiet','huybl','duoc_doituong','duoc_act'));
        }
        $hoadonrvchitiet = $this->getHOADONRAVIENCHITIET_BY_IDHOADON($hoadonravien->idhoadon);
       
        return view('system.user-function.detail-inpatient-bill', compact('params','hoadonravien','hoadonrvchitiet','huybl','duoc_doituong','duoc_act'));
    }

    public function checkCard(Request $request)
    {
        $the = array('maThe' => $request->get('maThe'), 
            'hoTen' => $request->get('hoTen'), 
            'ngaySinh' => $request->get('ngaySinh'), 
            'gioiTinh' =>  doubleval($request->get('gioiTinh') == 1 ? $request->get('gioiTinh') : 2), 
            'maCSKCB' => $request->get('maCSKCB'), 
            'ngayBD' => $request->get('ngayBD'), 
            'ngayKT' => $request->get('ngayKT'));
        
        $login_result = \App\BHYT::loginBHYT();
        if($login_result['maKetQua'] != '200') {
            return $login_result;
        }

        $card_result = \App\BHYT::lichSuKCB($the,
            $login_result['APIKey']['access_token'],
            $login_result['APIKey']['id_token']);

        return $card_result;
    }

    public function checkError(Request $request)
    {
        $login_result = \App\BHYT::loginBHYT();
        if($login_result['maKetQua'] != '200') {
            flash(config('__tech.login_error_BHYT')[$login_result['maKetQua']])->overlay();
            return redirect()->back();
        }

        $params = array(
            'token' => $login_result['APIKey']['access_token'],
            'id_token' => $login_result['APIKey']['id_token'],
            'username' => config('__tech.BHYT.username'),
            'password' => config('__tech.BHYT.password'),
            'maCSKCB' => '01013'
        );

        $nhanThongTinCSKCB = \App\BHYT::nhanThongTinCSKCB($params);
        return $nhanThongTinCSKCB;
    }

    private function getHOADONRAVIEN_BY_MALANKHAM($malankham)
    {
        return hoadonravien::with('bn_nhapvien')
            ->where('malankham', $malankham)->first();
    }

    private function getHOADONRAVIENCHITIET_BY_IDHOADON($idhoadon)
    {
        return  hoadonrvchitiet::with('dm_dichvudtnt','dm_xetnghiembv')
            ->where('idhoadon', $idhoadon)->get();
    }

    private function getBHYT($params)
    {
        $date = $this->getYear($params['date']['to']).$this->getMonth($params['date']['to']);

        return bhyt::with('bn_hc')
            ->where('sothe', $params['sothe'])
            ->where('YYMM','<=',$date)
            ->orderBy('maluubh', 'DESC')->get();
    }

    private function getBN_HC($mabn)
    {
        return bn_hc::where('mabn',$mabn)->get();
    }

    private function getYear($date)
    {
        return substr(getdate(strtotime($date))['year'], -strlen(getdate(strtotime($date))['year'])+2) ;
    }

    private function getMonth($date)
    {
        return strlen(getdate(strtotime($date))['mon']) > 1 ? 
            getdate(strtotime($date))['mon'] : '0'.getdate(strtotime($date))['mon'];
    }    

    private function getDay($date)
    {
        return strlen(getdate(strtotime($date))['mday']) > 1 ?
            getdate(strtotime($date))['mday'] : '0'.getdate(strtotime($date))['mday'];
    }

    private function getBN_KBPK($mabn, $params)
    {
        return bn_kbpk::with('dm_phongkham','dm_icd10bv','bn_cdkemtheo','bn_quvppk','bn_vienphipk')
            ->where('mabn', $mabn)
            ->where('ngaypk', '>=', $params['date']['from'] ? $params['date']['from']:'1970-01-01')
            ->where('ngaypk', '<=', $params['date']['to'] ? date_format(date_create($params['date']['to']),'Y-m-d 23:59:59'):'2999-12-31 23:59:59')
            ->orderBy('malankham')
            ->get();
    }

    private function getBN_VIENPHIPK($bn_kbpk)
    {
        $malankham_kbpk = [];
        $bn_vienphipk = [];

        foreach ($bn_kbpk as $key => $value) {
            $malankham_kbpk[$key] = $value->malankham;
        }
        if($malankham_kbpk){
            $malankham_kbpk = implode(',',$malankham_kbpk);
        
            $bn_vienphipk = bn_vienphipk::with('bn_vppkct')
                ->whereIn('malankham', explode(',',$malankham_kbpk))
                ->orderBy('malankham')
                ->get();
        }

        return $bn_vienphipk;
    }

    private function getBN_QUVPPK($bn_kbpk)
    {
        $malankham_kbpk = [];
        $bn_quvppk = [];

        foreach ($bn_kbpk as $key => $value) {
            $malankham_kbpk[$key] = $value->malankham;
        }
        if($malankham_kbpk){
            $malankham_kbpk = implode(',',$malankham_kbpk);
        
            $bn_quvppk = bn_quvppk::whereIn('malankham', explode(',',$malankham_kbpk))
                ->orderBy('malankham')
                ->get();
        }

        return $bn_quvppk;
    }

    private function getBN_NHAPVIEN($mabn, $params)
    {
        return bn_nhapvien::with('dm_khoaph','dm_icd10bv','quevpnoitru')
            ->where('mabn', $mabn)
            ->where('ngay', '<=', $params['date']['to'] ? date_format(date_create($params['date']['to']),'Y-m-d 23:59:59'):'2999-12-31 23:59:59')
            ->orderBy('malankham')
            ->get();
    }

    private function getBN_XUATVIEN($bn_nhapvien, $params)
    {
        $malankham_nhapvien = [];
        $bn_xuatvien = [];
        foreach ($bn_nhapvien as $key => $value) {
            $malankham_nhapvien[$key] = $value->malankham;
        }
        if($malankham_nhapvien){
            $malankham_nhapvien = implode(',',$malankham_nhapvien);
            $bn_xuatvien = bn_xuatvien::with('dm_khoaph','dm_icd10bv','bn_cdkemtheont','hoadonravien','bn_nhapvien')
                ->whereIn('malankham', explode(',', $malankham_nhapvien))
                ->whereHas('hoadonravien', function($q) use ($params) {
                    $q->where(function ($q) use ($params) {
                       $q->where('ngaythu', '>=', $params['date']['from'] ? $params['date']['from']:'1970-01-01');
                    });
                })
                ->whereHas('hoadonravien', function($q) use ($params) {
                    $q->where(function ($q) use ($params) {
                       $q->where('ngaythu', '<=', $params['date']['to'] ? date_format(date_create($params['date']['to']),'Y-m-d 23:59:59'):'2999-12-31 23:59:59');
                    });
                })
                ->orderBy('malankham')
                ->get();
        }
                // ->where('ngay', '>=', $params['date']['from'] ? $params['date']['from']:'1970-01-01')
                // ->where('ngay', '<=', $params['date']['to'] ? date_format(date_create($params['date']['to']),'Y-m-d 23:59:59'):'2999-12-31 23:59:59')
        return $bn_xuatvien;
    }

    private function getHOADONRAVIEN($bn_xuatvien, $params)
    {
        $malankham_xuatvien = [];
        $hoadonravien = [];
        foreach ($bn_xuatvien as $key => $value) {
            $malankham_xuatvien[$key] = $value->malankham;
        }
        if($malankham_xuatvien){
            $malankham_xuatvien = implode(',',$malankham_xuatvien);
            
            $hoadonravien = hoadonravien::with('bn_vpsohd')
                ->whereIn('malankham', explode(',', $malankham_xuatvien))
                ->orderBy('malankham')
                ->get();
            //$hoadonravien = DB::connection('oracle')->select('select * from hoadonravien where malankham in ('.$malankham_xuatvien.')');
        }        

        return $hoadonravien;
    }

    private function getBN_CDKEMTHEO($bn_kbpk, $params)
    {
        $malankham_kbpk = [];
        $bn_cdkemtheo = [];

        foreach ($bn_kbpk as $key => $value) {
            $malankham_kbpk[$key] = $value->malankham;
        }
        if($malankham_kbpk){
            $malankham_kbpk = implode(',',$malankham_kbpk);
        
            $bn_cdkemtheo = bn_cdkemtheo::with('dm_icd10bv')
                ->whereIn('malankham', explode(',',$malankham_kbpk))
                ->orderBy('malankham')
                ->get();
        }

        return $bn_cdkemtheo;
    }

    private function getBN_CDKEMTHEONT($bn_nhapvien, $params)
    {
        $malankham_nhapvien = [];
        $bn_cdkemtheont = [];

        foreach ($bn_nhapvien as $key => $value) {
            $malankham_nhapvien[$key] = $value->malankham;
        }
        if($malankham_nhapvien){
            $malankham_nhapvien = implode(',',$malankham_nhapvien);
        
            $bn_cdkemtheont = bn_cdkemtheont::with('dm_icd10bv')
                ->whereIn('malankham', explode(',',$malankham_nhapvien))
                ->orderBy('malankham')
                ->get();
        }

        return $bn_cdkemtheont;
    }

    private function __getSothe(Request $request)
    {
    	return [
    		'sothe' => $request->input('sothe'),
    	];
    }

    private function __getSearchParam($request)
    {
        return [
            'date' => [
                'from' => $request->get('date') ? $request->get('date')['from'] : null,
                'to' => $request->get('date') ? $request->get('date')['to'] : null,
            ],
            'treatment_code' => $request->get('treatment_code') ? $request->get('treatment_code') : '',
        ];
    }

    private function __gettreatment_code(Request $request)
    {
        return [
            'treatment_code' => $request->input('treatment_code'),
        ];
    }

    public function checkQueueWork()
    {
        return view('system.broadcast.index');
    }

    public function getUserId()
    {
        return \Auth::User()->id;
    }

    public function sysparam() {

        $sys_params = sys_param::all();
        return view('system.sys-param.index', compact('sys_params'));
    }

    public function editSysparam(Request $request) {

        if (!$request->ajax()) {
            return redirect()->route('home');
        }
        
        $update_sysparam = sys_param::find($request->id);
        $update_sysparam->param_value = $request->value;
        if ($update_sysparam->save()) {
            return '200';
        }
        return '401';
    }

    public function entry_remove(Request $request) {
        $creator = array('thomtt-kcccd','hangtt-kkb','hunglm-cccd');
        $doctor = array('anhvt-kkb','sangt m-kn','dungvv-kkb','hinhlc-kcccd','duongdh-kcccd');
        $doctor_admin = 'anhvt -kkb';
        $doctor_des = 'anhvt-kkb';
//return;
        try {
            DB::connection('HISPro')
            ->table('his_treatment')
            ->where('treatment_code', $request->treatment_code)
            ->whereNotNull('is_lock_hein')
            ->whereNotNull('medi_org_code')
            ->where('treatment_end_type_id', config('__tech.treatment_end_type_cv'))
            ->where('tdl_treatment_type_id', config('__tech.treatment_type_kham'))
            //->whereIn('doctor_loginname', $doctor)
            //->whereIn('creator', $creator)
            ->update(['treatment_end_type_id' => 4]);

            DB::connection('HISPro')
            ->table('his_treatment')
            ->whereNotNull('fee_lock_time')
            ->whereNotNull('is_lock_hein')
            ->whereNotNull('medi_org_code')
            ->where('treatment_code', $request->treatment_code)
            ->where('treatment_end_type_id', config('__tech.treatment_end_type_cv'))
            ->where('tdl_treatment_type_id', config('__tech.treatment_type_kham'))
            ->where('fee_lock_loginname', $doctor_admin)
            ->update(['treatment_end_type_id' => 4,
                'doctor_loginname' => $doctor_des,
                'end_loginname' => $doctor_des
            ]);

            $treatments = DB::connection('HISPro')
            ->table('his_treatment')
            ->select('treatment_code')
            ->whereNotNull('medi_org_code')
            ->where('treatment_code', $request->treatment_code)
            ->where('treatment_end_type_id', 4)
            ->get();

            DB::connection('HISPro')
            ->table('his_service_req')
            ->where('service_req_type_id', 1)
            ->where('is_delete', 0)
            ->whereIn('tdl_treatment_code', $treatments->pluck('treatment_code'))
            ->update(['is_delete' => 1]);

            DB::connection('EMR_RS')
            ->table('emr_treatment')
            ->where('treatment_code', $request->treatment_code)
            ->where('treatment_end_type_name', '<>', 'Cấp toa cho về')
            ->update([
                'treatment_end_type_name' => 'Cấp toa cho về'
            ]);

            // DB::connection('HISPro')
            // ->table('his_sere_serv')
            // ->where('tdl_service_type_id', 1)
            // ->where('is_delete', 0)
            // ->whereIn('tdl_treatment_code', $treatments->pluck('treatment_code'))
            // ->update(['is_delete' => 1]);
                
            // DB::connection('HISPro')
            // ->statement('update his_treatment set in_time = in_time - 00030000000000,
            //     in_date = in_date - 00030000000000,
            //     out_time = out_time - 00030000000000,
            //     out_date = out_date - 00030000000000 where treatment_code = \'' .
            //     $request->treatment_code .'\' and treatment_end_type_id = 9' .
            //     ' and fee_lock_time <= out_time'
            // );
                     
        } catch (\Exception $e) {
            
        }

    }

    public function entry_update(Request $request) {
        $creator = config('__tech.creator');
        $doctor = config('__tech.doctor');
        $doctor_admin = 'anhvt -kkb';
//return;
        try {
            // DB::connection('HISPro')
            // ->statement('update his_treatment set in_time = in_time + 00030000000000,
            //     in_date = in_date + 00030000000000,
            //     out_time = out_time + 00030000000000,
            //     out_date = out_date + 00030000000000 where treatment_code = \'' .
            //     $request->treatment_code .'\' and treatment_end_type_id = 9' .
            //     ' and fee_lock_time > out_time'
            // );  

            $treatments = DB::connection('HISPro')
            ->table('his_treatment')
            ->select('treatment_code')
            ->whereNotNull('is_lock_hein')
            ->whereNotNull('medi_org_code')
            ->where('treatment_code', $request->treatment_code)
            ->where('treatment_end_type_id', 4)
            ->get();

            DB::connection('HISPro')
            ->table('his_service_req')
            ->where('service_req_type_id', 1)
            ->where('is_delete', 1)
            ->whereNull('exe_service_module_id')
            ->whereIn('tdl_treatment_code', $treatments->pluck('treatment_code'))
            ->update(['exe_service_module_id' => 1, 'is_delete' => 0]);

            // DB::connection('HISPro')
            // ->table('his_sere_serv')
            // ->where('tdl_service_type_id', 1)
            // ->where('is_delete', 1)
            // ->whereIn('tdl_treatment_code', $treatments->pluck('treatment_code'))
            // ->update(['is_delete' => 0]);

            DB::connection('HISPro')
            ->table('his_treatment')
            ->whereNotNull('medi_org_code')
            ->where('treatment_code', $request->treatment_code)
            ->where('treatment_end_type_id', 4)
            ->where('tdl_treatment_type_id', config('__tech.treatment_type_kham'))
            ->update(['treatment_end_type_id' => config('__tech.treatment_end_type_cv')]);

            DB::connection('EMR_RS')
            ->table('emr_treatment')
            ->where('treatment_code', $request->treatment_code)
            ->where('treatment_end_type_name', '<>', 'Chuyển viện')
            ->update([
                'treatment_end_type_name' => 'Chuyển viện'
            ]);

        } catch (\Exception $e) {
            
        }

    }

    public function entry_plus(Request $request) {
        try {
            $treatment_code = $request->treatment_code;

            DB::connection('HISPro')->statement('
                UPDATE his_treatment
                SET in_time = TO_CHAR(ADD_MONTHS(TO_DATE(in_time, \'YYYYMMDDHH24MISS\'), 36), \'YYYYMMDDHH24MISS\'),
                    out_time = TO_CHAR(ADD_MONTHS(TO_DATE(out_time, \'YYYYMMDDHH24MISS\'), 36), \'YYYYMMDDHH24MISS\')
                WHERE treatment_end_type_id = 2
                  AND medi_org_code IS NOT NULL
                  AND tdl_treatment_type_id = 1
                  AND out_time < fee_lock_time
                  AND treatment_code = :treatment_code
            ', ['treatment_code' => $treatment_code]);
                     
        } catch (\Exception $e) {
            return $e;
        }

    }
	
    public function entry_minus(Request $request) {
        $creator = array('thomtt-kcccd','hangtt-kkb','hunglm-cccd');
        $doctor = array('anhvt-kkb','sangt m-kn','dungvv-kkb','hinhlc-kcccd','duongdh-kcccd');
        $doctor_admin = 'anhvt -kkb';

        try {
            $treatment_code = $request->treatment_code;

            DB::connection('HISPro')->statement('
                UPDATE his_treatment
                SET in_time = TO_CHAR(ADD_MONTHS(TO_DATE(in_time, \'YYYYMMDDHH24MISS\'), -36), \'YYYYMMDDHH24MISS\'),
                    out_time = TO_CHAR(ADD_MONTHS(TO_DATE(out_time, \'YYYYMMDDHH24MISS\'), -36), \'YYYYMMDDHH24MISS\')
                WHERE treatment_end_type_id = 2
                  AND medi_org_code IS NOT NULL
                  AND tdl_treatment_type_id = 1
                  AND out_time >= fee_lock_time
                  AND treatment_code = :treatment_code
            ', ['treatment_code' => $treatment_code]);
             
        } catch (\Exception $e) {
            
        }

    }

    public function entry_open(Request $request) {
        try {
            $model = DB::connection('HISPro')
            ->table('his_treatment')
            ->select('id')
            ->where('treatment_code', $request->treatment_code)
            ->where(function($q){
                $q->whereNotNull('is_lock_hein')
                ->orWhere('is_active', 0);
            })
            ->first();

            //return $model->id;

            if ($model->id) {
                $rtn = DB::connection('HISPro')
                ->table('his_treatment')
                ->where('id', $model->id)
                ->update(['is_lock_hein' => null]);

                if ($rtn) {
                    DB::connection('HISPro')
                    ->statement('delete his_hein_approval where treatment_id = ' .
                        $model->id
                    );
                    
                    DB::connection('HISPro')
                    ->table('his_treatment')
                    ->where('id', $model->id)
                    ->update(['xml4210_url' => null,
                        'fee_lock_time' => null,
                        'fee_lock_room_id' => null,
                        'fee_lock_department_id' => null,
                        'fee_lock_loginname' => null,
                        'fee_lock_username' => null,
                        'is_active' => 1
                    ]);            
                }
            }
                     
        } catch (\Exception $e) {
            return $e->getMessage();
        }

    }

    public function sysMan(Request $request)
    {
        return view('system.sys-man.index');
    }
}

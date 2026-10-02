<?php

namespace App\Http\Controllers\System;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use DB;
use App\Models\BHYT\sys_param;

class SystemController extends Controller
{

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
            'username' => config('organization.BHYT.username'),
            'password' => config('organization.BHYT.password'),
            'maCSKCB' => '01013'
        );

        $nhanThongTinCSKCB = \App\BHYT::nhanThongTinCSKCB($params);
        return $nhanThongTinCSKCB;
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
            ->update(['treatment_end_type_id' => 4,
                'create_time' => DB::raw('in_time'),
                'modify_time' => DB::raw('in_time')
            ]);

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
                'treatment_end_type_name' => 'Cấp toa cho về',
                'create_time' => DB::raw('in_time'),
                'modify_time' => DB::raw('in_time')
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

            DB::connection('EMR_RS')->statement('
                UPDATE emr_treatment
                SET create_time = TO_CHAR(ADD_MONTHS(TO_DATE(create_time, \'YYYYMMDDHH24MISS\'), 36), \'YYYYMMDDHH24MISS\'),
                in_time = TO_CHAR(ADD_MONTHS(TO_DATE(in_time, \'YYYYMMDDHH24MISS\'), 36), \'YYYYMMDDHH24MISS\'),
                out_time = TO_CHAR(ADD_MONTHS(TO_DATE(out_time, \'YYYYMMDDHH24MISS\'), 36), \'YYYYMMDDHH24MISS\')
                WHERE treatment_code = :treatment_code
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

            DB::connection('EMR_RS')->statement('
                UPDATE emr_treatment
                SET create_time = TO_CHAR(ADD_MONTHS(TO_DATE(create_time, \'YYYYMMDDHH24MISS\'), -36), \'YYYYMMDDHH24MISS\'),
                in_time = TO_CHAR(ADD_MONTHS(TO_DATE(in_time, \'YYYYMMDDHH24MISS\'), -36), \'YYYYMMDDHH24MISS\'),
                out_time = TO_CHAR(ADD_MONTHS(TO_DATE(out_time, \'YYYYMMDDHH24MISS\'), -36), \'YYYYMMDDHH24MISS\')
                WHERE treatment_code = :treatment_code
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

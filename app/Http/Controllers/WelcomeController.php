<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Models\System\City;
use App\Models\System\District;
use App\Models\System\Ward;
use App\Models\System\Clinic;
use App\Models\System\Symptom;
use App\Models\MedReg\MedReg;

use App\Http\Requests\MedRegRequest;

use Mail;
use App\SendSms;

use App\Events\MedicalRegister;

class WelcomeController extends Controller
{
    public function index()
    {
    	$Cities = City::all();
        $Clinics = Clinic::all();
        $Symptom = Symptom::all();
    	return view('Welcome', compact('Cities','Clinics','Symptom'));
    }

    public function getdistrictbycity(Request $request)
    {
    	$City = City::FindOrFail($request->City);
    	$Districts = District::where('ma_tinh',$City->code)->get();
    	return $Districts;
    }

    public function getwardbydistrict(Request $request)
    {
    	$District = District::FindOrFail($request->District);
    	$Wards = Ward::where('ma_qhuyen', $District->code)->get();
    	return $Wards;
    }

    public function MedicalRegister(MedRegRequest $request)
    {

        $Med_Reg = $this->storeMedReg($request);

        if ($Med_Reg) {

            event(new MedicalRegister($Med_Reg));
            
            // Mail::send('templates.mail', array('name'=>$Med_Reg->name,
            //     'email'=>$Med_Reg->email,
            //     'phone'=>$Med_Reg->phone,
            //     'birthday'=>$Med_Reg->birthday,
            //     'healthcaredate'=>$Med_Reg->healthcaredate),
            // function ($message) use ($Med_Reg) {        
            //     $message->to($Med_Reg->email);
            //     $message->subject(__('medreg.labels.title'));
            // });

            SendSms::sendSms($Med_Reg);

            flash(__('medreg.success'))->overlay();
        } else
        {
            flash(__('medreg.failed'))->overlay();
        }

        return redirect()->route('welcome');
    }

    /*
     *  @Store Medical Register
     *  @Param: MedRegRequest $request
     *  @return: MedReg model
    **/
    public function storeMedReg(MedRegRequest $request)
    {

        $MedReg = new MedReg;

        $MedReg->name = $request->name;
        $MedReg->gender = $request->sexual;
        $MedReg->birthday = $request->birthday;
        $MedReg->city = $request->city;
        $MedReg->district = $request->district;
        $MedReg->ward = $request->ward;
        $MedReg->email = $request->email;
        $MedReg->phone = $request->phone;
        $MedReg->healthcaredate = $request->healthcaredate;
        $MedReg->healthcaretime = $request->healthcaretime;
        $MedReg->clinic = $request->clinic;
        $MedReg->symptoms = implode(',',$request->symptom);

        $MedReg->save();
        
        return $MedReg;     
    }

}

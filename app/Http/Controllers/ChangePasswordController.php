<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth;
use Validator;
use Hash;
use App\User;

class ChangePasswordController extends Controller
{
    //
    public function index() {
    	return view('auth.changepassword');
    }


    public function admin_credential_rules(array $data)
	{
	  $messages = [
	    'current-password.required' => 'Please enter current password',
	    'password.required' => 'Please enter password',
	  ];

	  $validator = Validator::make($data, [
	    'current-password' => 'required',
	    'password' => 'required|same:password',
	    'password_confirmation' => 'required|same:password',     
	  ], $messages);

	  return $validator;
	}

	public function postCredentials(Request $request)
	{
	  if(Auth::Check())
	  {
	    $request_data = $request->All();
	    $validator = $this->admin_credential_rules($request_data);
	    if($validator->fails())
	    {
	      return back()->with('status',$validator->getMessageBag());
	    }
	    else
	    {  
	      $current_password = Auth::User()->password;           
	      if(Hash::check($request_data['current-password'], $current_password))
	      {           
	        $user_id = Auth::User()->id;                       
	        $obj_user = User::find($user_id);
	        $obj_user->password = Hash::make($request_data['password']);;
	        $obj_user->save(); 
	        return back()->with('status','Đổi mật khẩu thành công');
	      }
	      else
	      {           
	        $error = array('current-password' => 'Please enter correct current password');
	        return response()->json(array('error' => $error), 400);   
	      }
	    }        
	  }
	  else
	  {
	    return redirect()->to('/');
	  }    
	}

}

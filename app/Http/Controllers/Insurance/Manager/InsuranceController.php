<?php

namespace App\Http\Controllers\Insurance\Manager;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use App\Http\Requests\InsuranceRequest;
use App\BHYT;

class InsuranceController extends Controller
{
	protected $username;
	protected $password;
	protected $login_url;
	protected $check_card_url;

	protected $access_token;
	protected $id_token;
	protected $token_type;	
	protected $expires_in;
	protected $logined;

	protected $searchParams;

	public function __construct()
	{
		$this->logined = false;

        $this->username = config('organization.BHYT.username');
        $this->password = config('organization.BHYT.password');
        $this->login_url = config('organization.BHYT.login_url');
        $this->check_card_url = config('organization.BHYT.check_card_url');

        $this->searchParams = [
            'card-number' => null,
            'name' => null,
            'birthday' => null,
            'qrcode' => null,
        ];		
	}

    public function checkCard(Request $request)
    {
    	$params = $this->searchParams;
        // Check if the organization allows checking insurance
        if (!config('organization.BHYT.enableCheck')) {
            // Return an error message and stop execution
            flash('Chưa thiết lập chức năng tra cứu thẻ BHYT cho phần mềm')->error();
        }
    	return view('insurance.manager.check-card.index', compact('params'));
    }

    public function search(InsuranceRequest $request)
    {
        // Check if the organization allows checking insurance
        if (!config('organization.BHYT.enableCheck')) {
            // Return an error message and redirect to the index page
            flash('Chưa thiết lập chức năng tra cứu thẻ BHYT cho phần mềm')->error();
            return redirect()->route('insurance.check-card');
        }

        $params = $this->__getSearchParam($request);

        $login_result = BHYT::loginBHYT();

        if($login_result['maKetQua'] != '200') {
            flash(config('__tech.login_error_BHYT')[$login_result['maKetQua']])->error();
            return view('insurance.manager.check-card.index', compact('params','result_insurance','ket_qua_dtri','tinh_trang_rv'));
        }

        $result_insurance = BHYT::checkInsuranceCard($params['card-number'],$params['name'],$params['birthday'],
            $login_result['APIKey']['access_token'],$login_result['APIKey']['id_token']);

        if ($result_insurance['maKetQua'] == '000') {
           $params = $this->__setSearchParam($result_insurance['maThe'], 
            $result_insurance['hoTen'], $result_insurance['ngaySinh'], $request);
        }

        $insurance_code = config('__tech.insurance_error_code');
        $ket_qua_dtri = config('__tech.ket_qua_dtri');
        $tinh_trang_rv = config('__tech.tinh_trang_rv');

		return view('insurance.manager.check-card.index', compact('params','result_insurance','ket_qua_dtri','tinh_trang_rv','insurance_code'));
    }

    private function __setSearchParam($maThe, $hoTen, $ngaySinh, Request $request)
    {
        return [
            'card-number' => mb_strtoupper($maThe),
            'name' => mb_strtoupper($hoTen),
            'birthday' => $ngaySinh,
            'qrcode' => $request->get('qrcode'),
        ];
    }

    private function __getSearchParam($request)
    {
        return [
            'card-number' => mb_strtoupper($request->get('card-number')),
            'name' => mb_strtoupper($request->get('name')),
            'birthday' => $request->get('birthday'),
            'qrcode' => $request->get('qrcode'),
        ];
    }

    public function getqrcode(Request $request)
    {
    	$qrcode = $this->__getQrCodeParam(explode('|', $request->get('qrcode')));

    	$returnData = array(
    		'card-number' => $qrcode['card-number'], 
    		'name' => ctype_xdigit($qrcode['name']) ? $this->getNameFromQrCode($qrcode['name']) : '',
    		'birthday' => $qrcode['birthday']
    	);
    	return $returnData;
    }

    private function getNameFromQrCode($hex)
    {
    	$string='';
	    for ($i=0; $i < strlen($hex)-1; $i+=2){
	        $string .= chr(hexdec($hex[$i].$hex[$i+1]));
	    }
	    return $string;
    }

    private function __getQrCodeParam($request)
    {
        return [
            'card-number' => isset($request[0]) ? $request[0] : '',
            'name' => isset($request[1]) ? $request[1] : '',
            'birthday' => isset($request[2]) ? $request[2] : '',
        ];
    }

}

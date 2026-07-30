<?php

namespace App\Http\Controllers\Insurance\Manager;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use App\Http\Requests\InsuranceRequest;
use App\BHYT;
use App\Services\BHYTLoginService;
use App\Services\BHYT\CoSoTraCuu;

class InsuranceController extends Controller
{
	protected $searchParams;

	public function __construct()
	{
        $this->searchParams = [
            'card-number' => null,
            'name' => null,
            'birthday' => null,
            'qrcode' => null,
            'ma_cskcb' => null,
        ];
	}

    public function checkCard(Request $request)
    {
    	$params = $this->searchParams;
        $danhSachCoSo = CoSoTraCuu::tuCauHinh();
        // Check if the organization allows checking insurance
        if (!config('organization.BHYT.enableCheck')) {
            // Return an error message and stop execution
            flash('Chưa thiết lập chức năng tra cứu thẻ BHYT cho phần mềm')->error();
        }
    	return view('insurance.manager.check-card.index', compact('params', 'danhSachCoSo'));
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
        $danhSachCoSo = CoSoTraCuu::tuCauHinh();

        // Ma co so den TU LUA CHON cua nguoi dung, khong muon cua ai. InsuranceRequest da
        // chan ma ngoai danh sach nen toi day chac chan hop le.
        // Dung service TRONG ham chu khong trong constructor: BHYTLoginService phan giai tai
        // khoan LUOI nen dung duoc kieu nay, va moi lan tra co the la mot co so khac.
        $loginService = new BHYTLoginService($params['ma_cskcb']);

        // Sử dụng BHYTLoginService để lấy token (tự động đăng nhập lại nếu hết hạn)
        try {
            $accessToken = $loginService->getAccessToken();
            $idToken = $loginService->getIdToken();
        } catch (\Exception $e) {
            flash('Lỗi đăng nhập cổng BHXH: ' . $e->getMessage())->error();
            return view('insurance.manager.check-card.index', compact('params', 'danhSachCoSo'));
        }

        // Chan som khi thieu thong tin can bo tra cuu. Khong chan thi cong BHXH tra ve
        // "Null hoTenCb" - thong bao khong noi duoc la thieu o dau, phai do nguoc moi ra.
        if (!config('organization.BHYT.check_by_user')) {
            if ($loginService->hoTenCb() === '' || $loginService->cccdCb() === '') {
                flash('Cơ sở ' . $params['ma_cskcb'] . ' chưa khai họ tên hoặc CCCD cán bộ tra cứu '
                    . 'trong config/organization.php, khối BHYT_CO_SO. Cần khai trước khi tra cứu.')->error();

                return view('insurance.manager.check-card.index', compact('params', 'danhSachCoSo'));
            }
        }

        // Truyen $loginService xuong de dung tai khoan CUA CO SO DO. Khong truyen thi
        // checkInsuranceCard() roi ve tai khoan chot cung trong khoi BHYT cu, va tinh nang
        // chon co so tro thanh vo nghia.
        $result_insurance = BHYT::checkInsuranceCard($params['card-number'],$params['name'],$params['birthday'],
            $accessToken, $idToken, $loginService);

        if ($result_insurance['maKetQua'] == '000') {
           $params = $this->__setSearchParam($result_insurance['maThe'], 
            $result_insurance['hoTen'], $result_insurance['ngaySinh'], $request);
        }

        $insurance_code = config('__tech.insurance_error_code');
        $ket_qua_dtri = config('__tech.ket_qua_dtri');
        $tinh_trang_rv = config('__tech.tinh_trang_rv');

		return view('insurance.manager.check-card.index', compact('params','result_insurance','ket_qua_dtri','tinh_trang_rv','insurance_code','danhSachCoSo'));
    }

    private function __setSearchParam($maThe, $hoTen, $ngaySinh, Request $request)
    {
        return [
            'card-number' => mb_strtoupper($maThe),
            'name' => mb_strtoupper($hoTen),
            'birthday' => $ngaySinh,
            'qrcode' => $request->get('qrcode'),
            // Giu lai de o chon tren man khop voi ket qua dang hien.
            'ma_cskcb' => trim((string) $request->get('ma_cskcb')),
        ];
    }

    private function __getSearchParam($request)
    {
        return [
            'card-number' => mb_strtoupper($request->get('card-number')),
            'name' => mb_strtoupper($request->get('name')),
            'birthday' => $request->get('birthday'),
            'qrcode' => $request->get('qrcode'),
            'ma_cskcb' => trim((string) $request->get('ma_cskcb')),
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

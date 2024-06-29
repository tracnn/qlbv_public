<?php

namespace App\Http\Controllers\Insurance\Manager;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use App\Models\CheckBHYT\InsuranceCard;
use App\BHYT;

use App\Http\Requests\InsuranceCardRequest;

class InsuranceCardController extends Controller
{
	protected $searchParams;

	public function __construct()
	{
        $this->searchParams = [
            'insurance-number' => null,
            'name' => null,
            'birthday' => null,
            'qrcode' => null,
            'date' => [
                'from' => date_format(now(),'Y-m-d'),
                'to' => date_format(now(),'Y-m-d')
            ],
            'order_by' => 'created_at',
            'order_type' => 'DESC',
        ];		
	}

    public function addnew(Request $request)
    {
    	$params = $this->searchParams;

    	return view('insurance.manager.insurance-card.add', compact('params'));
    }

    public function store(InsuranceCardRequest $request)
    {
    	$params = $this->__getSearchParam($request);

        $login_result = BHYT::loginBHYT();
        if($login_result['maKetQua'] != '200') {
            flash(config('__tech.login_error_BHYT')[$login_result['maKetQua']])->error();
            return view('insurance.manager.insurance-card.add', compact('params'));
        }
        
        $insurance_result = BHYT::checkInsuranceCard($params['insurance-number'],$params['name'],$params['birthday'],
            $login_result['APIKey']['access_token'],$login_result['APIKey']['id_token']);
        $Insurance_Card = $this->processStoreInsuranceCard($insurance_result, $params['insurance-number'], $params['name'], $params['birthday']);
        $insurance_code = config('__tech.insurance_error_code');
        $tinh_trang_rv = config('__tech.tinh_trang_rv');
        $ket_qua_dtri = config('__tech.ket_qua_dtri');

    	return view('insurance.manager.insurance-card.add', compact('params','insurance_result','Insurance_Card','insurance_code','tinh_trang_rv','ket_qua_dtri'));
    }

    public function index(Request $request)
    {
        $params = $this->searchParams;
        $InsuranceCards = new InsuranceCard;
        $InsuranceCards = $InsuranceCards->getSearchResults($params)->paginate(config('__tech.pagination_count'));
        $insurance_code = config('__tech.insurance_error_code');
        $order_by = config('__tech.isurance_card.order_by');
        $order_type = config('__tech.order_type');

    	return view('insurance.manager.insurance-card.index', compact('params','InsuranceCards','insurance_code','order_by','order_type'));
    }

    public function search(Request $request)
    {
        $params = $this->__getSearchParam($request);
        $InsuranceCards = new InsuranceCard;
        $InsuranceCards = $InsuranceCards->getSearchResults($params)->paginate(config('__tech.pagination_count'));
        $insurance_code = config('__tech.insurance_error_code');
        $order_by = config('__tech.isurance_card.order_by');
        $order_type = config('__tech.order_type');

        return view('insurance.manager.insurance-card.index', compact('params','InsuranceCards','insurance_code','order_by','order_type'));
    }

    public function delete(Request $request)
    {
        $id = $this->__getID($request);

        $InsuranceCards = InsuranceCard::findOrfail($id['id']);
        $InsuranceCards->delete();
        flash(__('insurance.backend.labels.delete_successful'));
        return redirect()->back();
    }

    public function detail(Request $request)
    {
        return redirect()->back();
    }

    private function __getSearchParam($request)
    {
        return [
            'insurance-number' => mb_strtoupper($request->get('insurance-number')),
            'name' => mb_strtoupper($request->get('name')),
            'birthday' => $request->get('birthday'),
            'qrcode' => $request->get('qrcode'),
            'date' => [
                'from' => $request->get('date')['from'],
                'to' => $request->get('date')['to'],
            ],
            'order_by' => $request->get('order_by'),
            'order_type' => $request->get('order_type'),
        ];
    }

    private function processStoreInsuranceCard($request, $maThe, $hoTen, $ngaySinh)
    {
        $Insurance_Card = new InsuranceCard;

        if($maThe)
        {
            $Insurance_Card->maThe = $maThe;
            $Insurance_Card->hoTen = $hoTen;
            $Insurance_Card->ngaySinh = $ngaySinh;
            $Insurance_Card->dsLichSuKCB2018 = $request['dsLichSuKCB2018'] ? json_encode($request['dsLichSuKCB2018']) : null;
            $Insurance_Card->fill($request);
            $Insurance_Card->save();       
        }

        return $Insurance_Card;
    }

    private function __getID($request)
    {
        return [
            'id' => $request->get('id'),
        ];
    }
}

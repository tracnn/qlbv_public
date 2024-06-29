<?php

namespace App\Http\Controllers\MedReg\Manager;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use App\Models\MedReg\MedReg;

use PHPExcel;
use PHPExcel_IOFactory;

class MedRegController extends Controller
{

    protected $searchParams;

    public function __construct()
    {
        $this->searchParams = [
            'name' => null,
            'gender' => null,
            'birthday' => ['from' => null, 'to' => null],
            'city' => null,
            'district' => null,
            'ward' => null,
            'email' => null,
            'phone' => null,
            'healthcaredate' => ['from' => null, 'to' => null],
            'healthcaretime' => null,
            'clinic' => null,
            'symptoms' => null,
            'created' => ['from' => null, 'to' => null],
        ];
    }

    public function index()
    {
    	$params = $this->searchParams;
    	//return $params['healthcaredate']['from'];
    	$gender = config('__tech.gender');
    	$healthcaretime = config('__tech.healthcaretime');

    	$MedRegs = new MedReg;
	    $MedRegs = $MedRegs->getSearchResults($params)->paginate(config('__tech.pagination_count'));

    	return view('medreg.manager.index', compact('MedRegs','params','gender','healthcaretime'));
    }

    public function search(Request $request)
    {
        $params = $this->__getSearchParam($request);
        //return str_slug($params['name']);
		$gender = config('__tech.gender');
		$healthcaretime = config('__tech.healthcaretime');

        $MedRegs = new MedReg;
        $MedRegs = $MedRegs->getSearchResults($params)->paginate(config('__tech.pagination_count'));

		return view('medreg.manager.index', compact('MedRegs','params','gender','healthcaretime'));
    }

    public function export(Request $request)
    {
    	$params = $this->__getSearchParam($request);

    	$MedRegs = new MedReg;
    	$MedRegs = $MedRegs->getSearchResults($params)->get();

		//Khởi tạo đối tượng
		$excel = new PHPExcel();

		//Chọn trang cần ghi (là số từ 0->n)
		$excel->setActiveSheetIndex(0);

		//Tạo tiêu đề cho trang. (có thể không cần)
		$excel->getActiveSheet()->setTitle(__('medreg.labels.title'));

		//Xét chiều rộng cho từng, nếu muốn set height thì dùng setRowHeight()
		$excel->getActiveSheet()->getColumnDimension('A')->setWidth(30);
		// $excel->getActiveSheet()->getColumnDimension('B')->setWidth(10);
		// $excel->getActiveSheet()->getColumnDimension('C')->setWidth(30);
		// $excel->getActiveSheet()->getColumnDimension('D')->setWidth(30);

		//Xét in đậm cho khoảng cột
		//$excel->getActiveSheet()->getStyle('A1:D1')->getFont()->setBold(true);

		//Tạo tiêu đề cho từng cột
		$excel->getActiveSheet()->setCellValue('A1', __('medreg.backend.name'));
		$excel->getActiveSheet()->setCellValue('B1', __('medreg.backend.gender'));
		$excel->getActiveSheet()->setCellValue('C1', __('medreg.backend.birthday'));
		$excel->getActiveSheet()->setCellValue('D1', __('medreg.backend.city'));
		$excel->getActiveSheet()->setCellValue('E1', __('medreg.backend.district'));
		$excel->getActiveSheet()->setCellValue('F1', __('medreg.backend.ward'));
		$excel->getActiveSheet()->setCellValue('G1', __('medreg.backend.email'));
		$excel->getActiveSheet()->setCellValue('H1', __('medreg.backend.phone'));
		$excel->getActiveSheet()->setCellValue('I1', __('medreg.backend.healthcaredate'));
		$excel->getActiveSheet()->setCellValue('J1', __('medreg.backend.healthcaretime'));
		$excel->getActiveSheet()->setCellValue('K1', __('medreg.backend.clinic'));
		$excel->getActiveSheet()->setCellValue('L1', __('medreg.backend.symptom'));
		$excel->getActiveSheet()->setCellValue('m1', __('medreg.backend.created'));

		//Thực hiện thêm dữ liệu vào từng ô bằng vòng lặp
		// dòng bắt đầu = 2
		$numRow = 2;
		foreach ($MedRegs as $row) {
		    $excel->getActiveSheet()->setCellValue('A' . $numRow, $row->name);
		    $excel->getActiveSheet()->setCellValue('B' . $numRow, $row->gender);
		    $excel->getActiveSheet()->setCellValue('C' . $numRow, $row->birthday);
		    $excel->getActiveSheet()->setCellValue('D' . $numRow, $row->city);
		    $excel->getActiveSheet()->setCellValue('E' . $numRow, $row->district);
		    $excel->getActiveSheet()->setCellValue('F' . $numRow, $row->ward);
		    $excel->getActiveSheet()->setCellValue('G' . $numRow, $row->email);
		    $excel->getActiveSheet()->setCellValue('H' . $numRow, $row->phone);
		    $excel->getActiveSheet()->setCellValue('I' . $numRow, $row->healthcaredate);
		    $excel->getActiveSheet()->setCellValue('J' . $numRow, $row->healthcaretime);
		    $excel->getActiveSheet()->setCellValue('K' . $numRow, $row->clinic);
		    $excel->getActiveSheet()->setCellValue('L' . $numRow, $row->symptoms);
		    $excel->getActiveSheet()->setCellValue('M' . $numRow, $row->created_at);
		    $numRow++;
		}

		// Khởi tạo đối tượng PHPExcel_IOFactory để thực hiện ghi file
		header('Content-type: application/vnd.ms-excel');
		header('Content-Disposition: attachment; filename="dkkcb_'.strtotime(now()).'.xlsx"');
		PHPExcel_IOFactory::createWriter($excel, 'Excel2007')->save('php://output');
    }

    public function view(Request $request)
    {
    
    }

    public function delete(Request $request)
    {
    	$id = $this->__getID($request);
    	$medreg_delete = MedReg::findOrfail($id)->first();
    	$medreg_delete->delete();

    	flash('Xoá thành công');
    	return redirect()->back();
    }

    private function __getID($request)
    {
    	return [
    		'id' => $request->get('id'),
    	];
    }

    private function __getSearchParam($request)
    {
        return [
            'name' => $request->get('name'),
            'gender' => $request->get('gender'),
            'birthday' => ['from' => $request->get('birthday_from'), 'to' => $request->get('birthday_to')],
            'city' => $request->get('city'),
            'district' => $request->get('district'),
            'ward' => $request->get('ward'),
            'email' => $request->get('email'),
            'phone' => $request->get('phone'),
            'healthcaredate' => ['from' => $request->get('healthcaredate')['from'], 'to' => $request->get('healthcaredate')['to']],
            'healthcaretime' => $request->get('healthcaretime'),
            'clinic' => $request->get('clinic'),
            'symptoms' => $request->get('symptoms'),
            'created' => ['from' => $request->get('from'), 'to' => $request->get('to')],
        ];
    }

}
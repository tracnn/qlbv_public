<?php

namespace App\Http\Controllers\BHYT;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use Yajra\Datatables\Datatables;
use App\Models\BHYT\XML1;
use App\Models\BHYT\XML2;
use App\Models\BHYT\XML3;
use App\Models\BHYT\XML4;
use App\Models\BHYT\XML5;
use App\Models\BHYT\department;
use App\BHYT;
use App\Jobs\JobKtTheBHYT;
use App\Models\BHYT\cat_cond_service;
use App\Models\BHYT\cat_cond_pharma;

use App\Models\XmlErrorCheck;
use App\Models\BHYT\XmlErrorCatalog;

use Carbon\Carbon;

class BHYTController extends Controller
{
    protected $searchParams;
    protected $department;

    public function __construct()
    {
        $this->department = department::all();
        $this->searchParams = [
            'ngay_ttoan_tu' => date_format(now(),'Y-m-d'),
            'ngay_ttoan_den' => date_format(now(),'Y-m-d'),
            'loai_kcb' => null,
            'ma_the' => null,    
            'khoa' => null,        
        ];
    }

    public function index()
    {	
    	$params = $this->searchParams;
        $department = $this->department;
        $loai_kcb = config('__tech.loai_kcb');

    	return view('bhyt.index', 
    		compact('params','department','loai_kcb'));
    }


    public function searchXML(Request $request)
    {
    	$params = $this->__getSearchParam($request);
        $department = $this->department;
        $loai_kcb = config('__tech.loai_kcb');

    	return view('bhyt.index', 
    		compact('params','department','loai_kcb'));
    }

    private function __getSearchParam($request)
    {
        return [
            'ngay_ttoan_tu' => $request->get('ngay_ttoan_tu') ? $request->get('ngay_ttoan_tu') : 
                    $this->searchParams['ngay_ttoan_tu'],
            'ngay_ttoan_den' => $request->get('ngay_ttoan_den') ? $request->get('ngay_ttoan_den') : 
                    $this->searchParams['ngay_ttoan_den'],
            'loai_kcb' => $request->get('loai_kcb'),
            'ma_the' => $request->get('ma_the'),
            'khoa' => $request->get('khoa'),
        ];
    }

    public function getxml(Request $request)
    {
        if (!$request->ajax()) {
            return redirect()->route('home');
        }
        
        $treatment_code = $request->input('treatment_code');
        $date_type = $request->input('date_type');
        $xml_filter_status = $request->input('xml_filter_status');

        $xml_error_catalog_id = $request->input('xml_error_catalog');

        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        if ($treatment_code) {
            $result = XML1::select('ma_lk', 'ma_bn', 'ho_ten', 'ma_the', 'ngay_sinh', 
                'ngay_vao', 'ngay_ra', 'ngay_ttoan', 'created_at', 'updated_at')
            ->where('ma_lk', $treatment_code)
            ->with(['check_hein_card' => function($query) {
                $query->select('ma_lk', 'ma_kiemtra', 'ma_tracuu', 'ghi_chu');
            }])
            ->with(['xmlErrorChecks' => function($query) {
                $query->select('ma_lk', 'error_code', 'ngay_yl', 'description');
            }]);            
        } else {
            // Check and convert date format
            if (strlen($dateFrom) == 10) { // Format YYYY-MM-DD
                $dateFrom = Carbon::createFromFormat('Y-m-d', $dateFrom)->startOfDay()->format('Y-m-d H:i:s');
            }

            if (strlen($dateTo) == 10) { // Format YYYY-MM-DD
                $dateTo = Carbon::createFromFormat('Y-m-d', $dateTo)->endOfDay()->format('Y-m-d H:i:s');
            }

            // Convert date format from 'YYYY-MM-DD HH:mm:ss' to 'YYYYMMDDHHI' for specific fields
            $formattedDateFromForFields = Carbon::createFromFormat('Y-m-d H:i:s', $dateFrom)->format('YmdHi');
            $formattedDateToForFields = Carbon::createFromFormat('Y-m-d H:i:s', $dateTo)->format('YmdHi');

            // Convert date format to 'Y-m-d H:i:s' for created_at and updated_at
            $formattedDateFromForTimestamp = Carbon::createFromFormat('Y-m-d H:i:s', $dateFrom)->format('Y-m-d H:i:s');
            $formattedDateToForTimestamp = Carbon::createFromFormat('Y-m-d H:i:s', $dateTo)->format('Y-m-d H:i:s');

            // Define the date field based on date_type
            switch ($date_type) {
                case 'date_in':
                    $dateField = 'ngay_vao';
                    $formattedDateFrom = $formattedDateFromForFields;
                    $formattedDateTo = $formattedDateToForFields;
                    break;
                case 'date_out':
                    $dateField = 'ngay_ra';
                    $formattedDateFrom = $formattedDateFromForFields;
                    $formattedDateTo = $formattedDateToForFields;
                    break;
                case 'date_payment':
                    $dateField = 'ngay_ttoan';
                    $formattedDateFrom = $formattedDateFromForFields;
                    $formattedDateTo = $formattedDateToForFields;
                    break;
                case 'date_create':
                    $dateField = 'created_at';
                    $formattedDateFrom = $formattedDateFromForTimestamp;
                    $formattedDateTo = $formattedDateToForTimestamp;
                    break;
                case 'date_update':
                    $dateField = 'updated_at';
                    $formattedDateFrom = $formattedDateFromForTimestamp;
                    $formattedDateTo = $formattedDateToForTimestamp;
                    break;
                default:
                    $dateField = 'ngay_ttoan';
                    $formattedDateFrom = $formattedDateFromForFields;
                    $formattedDateTo = $formattedDateToForFields;
                    break;
            }

            // $result = XML1::select('ma_lk', 'ma_bn', 'ho_ten', 'ma_the', 'ngay_sinh', 
            //     'ngay_vao', 'ngay_ra', 'ngay_ttoan', 'created_at', 'updated_at')
            // ->whereBetween($dateField, [$formattedDateFrom, $formattedDateTo])
            // ->with(['check_hein_card' => function($query) {
            //     $query->select('ma_lk', 'ma_kiemtra', 'ma_tracuu', 'ghi_chu');
            // }])
            // ->with(['xmlErrorChecks' => function($query) {
            //     $query->select('ma_lk', 'error_code', 'ngay_yl', 'description');
            // }]);

            $result = XML1::select('ma_lk', 'ma_bn', 'ho_ten', 'ma_the', 'ngay_sinh', 
                'ngay_vao', 'ngay_ra', 'ngay_ttoan', 'created_at', 'updated_at')
            ->whereBetween($dateField, [$formattedDateFrom, $formattedDateTo]);

            // Apply relationships
            $result = $result->with(['check_hein_card' => function($query) {
                $query->select('ma_lk', 'ma_kiemtra', 'ma_tracuu', 'ghi_chu');
            }]);

            if ($xml_error_catalog_id) {
                $xmlErrorCatalog = XmlErrorCatalog::find($xml_error_catalog_id);
                if ($xmlErrorCatalog) {
                    $result = $result->whereHas('xmlErrorChecks', function($query) use ($xmlErrorCatalog) {
                        $query->where('xml', $xmlErrorCatalog->xml)
                              ->where('error_code', $xmlErrorCatalog->error_code);
                    });
                }
            } else {
                $result = $result->with(['xmlErrorChecks' => function($query) {
                    $query->select('ma_lk', 'error_code', 'ngay_yl', 'description');
                }]);
            }
            
            // Apply filter based on xml_filter_status
            if ($xml_filter_status === 'has_error') {
                $result = $result->where(function ($query) {
                    $query->whereHas('xmlErrorChecks')
                    ->orWhereHas('check_hein_card', function ($subQuery) {
                        $subQuery->where('ma_kiemtra', '<>', '00')
                        ->orWhere('ma_tracuu', '<>', '000');
                    });
                });
            } elseif ($xml_filter_status === 'no_error') {
                $result = $result->whereDoesntHave('xmlErrorChecks')
                ->whereDoesntHave('check_hein_card', function ($subQuery) {
                    $subQuery->where('ma_kiemtra', '<>', '00')
                    ->orWhere('ma_tracuu', '<>', '000');
                 });
            }
        }

        // Fetch distinct error codes
        //$errorCodes = $result->get()->pluck('xmlErrorChecks.*.error_code')->flatten()->unique();

		return Datatables::of($result)
        ->editColumn('ngay_sinh', function($result) {
            return dob($result->ngay_sinh);
        })
        ->editColumn('ngay_vao', function($result) {
            return strtodatetime($result->ngay_vao);
        })
        ->editColumn('ngay_ra', function($result) {
            return $result->ngay_ra ? strtodatetime($result->ngay_ra) : $result->ngay_ra;
        })
        ->editColumn('ngay_ttoan', function($result) {
            return $result->ngay_ttoan ? strtodatetime($result->ngay_ttoan) : $result->ngay_ttoan;
        })
        ->addColumn('action', function ($result) {
            return '<a href="' . route('insurance.check-card.search',['card-number' => $result->ma_the, 'name' => $result->ho_ten, 'birthday' => date_format(date_create(substr($result->ngay_sinh,0,8)),'d/m/Y')]) . '" class="btn btn-sm btn-success" target="_blank"><span class="glyphicon glyphicon-check"></span> Tra thẻ</a>
                <a href="javascript:void(0);" onclick="deleteXML(\'' . $result->ma_lk . '\');" class="btn btn-sm btn-danger">
                                    <span class="glyphicon glyphicon-trash"></span> Xóa</a>
                <a href="' .route('treatment-result.search',['treatment_code'=>$result->ma_lk]) .'" class="btn btn-sm btn-primary" target="_blank">
                                    <span class="glyphicon glyphicon-envelope"></span> EMR</a>';
        })
        ->setRowClass(function ($result) {
            $highlight = false;
            if ($result->check_hein_card && ($result->check_hein_card->ma_kiemtra !== '00' || $result->check_hein_card->ma_tracuu !== '000')) {
                $highlight = true;
            }
            if (!$highlight && $result->xmlErrorChecks->isNotEmpty()) {
                $highlight = true;
            }
            return $highlight ? 'highlight-red' : '';
        })
        // ->with([
        //     'errorCodes' => $errorCodes
        // ])
        ->toJson();
    }

    public function checkcard(Request $request)
    {
        return view('bhyt.check-card');
    }

    public function processCheckcard(Request $request)
    {
        if (!$request->ajax()) {
            return redirect()->route('home');
        }
        
        $login_result = BHYT::loginBHYT();
        
        if($login_result['maKetQua'] == '200') {
            $params = $this->__getSearchParam($request);
     
            $xml1 = XML1::where('ngay_ra', '>=', datetostr_from($params['ngay_ttoan_tu']))
                ->where('ngay_ra', '<=', datetostr_to($params['ngay_ttoan_den']))
                ->where('ma_loai_kcb','like','%'.$params['loai_kcb'].'%')
                ->where('ma_the','like','%'.$params['ma_the'].'%')
                ->where('ma_khoa','like','%'.$params['khoa'].'%')
                ->select('MA_LK','MA_THE','HO_TEN','NGAY_SINH',
                    'GIOI_TINH','MA_DKBD','GT_THE_TU','GT_THE_DEN',
                    'NGAY_VAO','NGAY_RA','NGAY_TTOAN','MA_KHOA')
                ->get();

            $count = $xml1->count();
            foreach ($xml1 as $key => $value) {
                $stt = $key + 1;
                $ds_the = explode(";", $value->MA_THE);
                $ds_giatritu = explode(";", $value->GT_THE_TU);
                $ds_giatriden = explode(";", $value->GT_THE_DEN);
                $ds_madkbd = explode(";", $value->MA_DKBD);

                foreach ($ds_the as $key_the => $value_the) {
                    $params_lskcb = array('maThe' => $ds_the[$key_the], 
                        'hoTen' => $value->HO_TEN, 
                        'ngaySinh' => date_format(date_create($value->NGAY_SINH),'d/m/Y'), 
                        'gioiTinh' =>  $value->GIOI_TINH, 
                        'maCSKCB' => $ds_madkbd[$key_the], 
                        'ngayBD' => date_format(date_create($ds_giatritu[$key_the]),'d/m/Y'), 
                        'ngayKT' => date_format(date_create($ds_giatriden[$key_the]),'d/m/Y'));
                    //print_r($params_lskcb);
                    //echo '<br/>';
                    dispatch(new JobKtTheBHYT($key+1,$count,$params_lskcb,
                        $value->MA_LK,$value->NGAY_VAO,$value->NGAY_RA,
                        $value->NGAY_TTOAN,$value->MA_KHOA,
                        $login_result['APIKey']['access_token'], 
                        $login_result['APIKey']['id_token'],\Auth::user()->id,$login_result['APIKey']['expires_in']));
                }
            }
        }
        return $login_result;
    }

    public function detailxml($ma_lk)
    {
        $xml1 = XML1::where('ma_lk', $ma_lk)
        ->with(['xml2', 'xml3', 'xml4', 'xml5', 'check_hein_card', 'xmlErrorChecks'])
        ->firstOrFail();

        return view('bhyt.detail.detail-xml',  compact('xml1')); 
    }

    public function processCheckcard_backup(Request $request)
    {
        //event(new \App\Events\DemoPusherEvent('Số hồ sơ tải lên', \Auth::user()->id));

        $params = $this->__getSearchParam($request);

        echo 'Lấy dữ liệu...<br/';

        $xml1 = XML1::where('ngay_ra', '>=', datetostr_from($params['ngay_ttoan_tu']))
            ->where('ngay_ra', '<=', datetostr_to($params['ngay_ttoan_den']))
            ->where('ma_khoa','like','%' . $params['khoa'] . '%')
            ->select('MA_LK','MA_THE','HO_TEN','NGAY_SINH','GIOI_TINH','MA_DKBD','GT_THE_TU','GT_THE_DEN','NGAY_VAO','NGAY_RA','NGAY_TTOAN','MA_KHOA')
            ->get();

        $count = $xml1->count();

        echo 'Đăng nhập cổng BHXH...<br/>';
        $login_result = BHYT::loginBHYT();


        if($login_result['maKetQua'] != '200') {
            echo config('__tech.login_error_BHYT')[$login_result['maKetQua']];
            return;
        }
        
        echo 'Đăng nhập thành công...<br/>';

        echo 'Bắt đầu thực hiện kiểm tra...<br/>';
        echo '<div class="panel panel-default">
                <div class="panel-body table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>STT</th>
                                <th>Mã điều trị</th>
                                <th>Họ tên</th>
                                <th>Ngày sinh</th>
                                <th>Ngày vào</th>
                                <th>Ngày ra</th>
                                <th>Mã thẻ</th>
                                <th>Mã ĐKBĐ</th>
                                <th>Giá trị từ</th>
                                <th>Giá trị đến</th>
                                <th>Ngày t.toán</th>
                                <th>Khoa kết thúc</th>
                                <th>Kết quả tra cứu</th>
                                <th>Kết quả kiểm tra</th>
                            </tr>
                        </thead><tbody>';
        foreach ($xml1 as $key => $value) {
            $stt = $key+1;
            echo '<tr>';
            echo '<td style="text-align:right">' . $stt .'/'.$count .'</td>';
            echo '<td>' . $value->MA_LK .'</td>';
            echo '<td>' . $value->HO_TEN .'</td>';
            echo '<td>' . $value->NGAY_SINH .'</td>';
            echo '<td>' . $value->NGAY_VAO .'</td>';
            echo '<td>' . $value->NGAY_RA .'</td>';
            echo '<td>' . $value->MA_THE .'</td>';
            echo '<td>' . $value->MA_DKBD .'</td>';
            echo '<td>' . $value->GT_THE_TU .'</td>';
            echo '<td>' . $value->GT_THE_DEN .'</td>';
            echo '<td>' . $value->NGAY_TTOAN .'</td>';
            echo '<td>' . $value->MA_KHOA .'</td>';

            $result_insurance = BHYT::checkInsuranceCard(explode(";", $value->MA_THE)[0],$value->HO_TEN,
                date_format(date_create($value->NGAY_SINH),'d/m/Y'),
            $login_result['APIKey']['access_token'],$login_result['APIKey']['id_token']);

            echo '<td>' . config('__tech.insurance_error_code')[$result_insurance['maKetQua']] .'</td>';

            $params_lskcb = array('maThe' => explode(";", $value->MA_THE)[0], 
                'hoTen' => $value->HO_TEN, 
                'ngaySinh' => date_format(date_create($value->NGAY_SINH),'d/m/Y'), 
                'gioiTinh' =>  $value->GIOI_TINH, 
                'maCSKCB' => explode(";" , $value->MA_DKBD)[0], 
                'ngayBD' => date_format(date_create(explode(";", $value->GT_THE_TU)[0]),'d/m/Y'), 
                'ngayKT' => date_format(date_create(explode(";", $value->GT_THE_DEN)[0]),'d/m/Y'));
            $result_check = BHYT::lichSuKCB($params_lskcb,
                $login_result['APIKey']['access_token'],$login_result['APIKey']['id_token']);

            echo '<td>' . config('__tech.check_insurance_code')[$result_check['maKetQua']] .'</td>';
            echo '</tr>';
        }
        echo '</tbody></table>
            </div>
        </div>';
        echo 'Hoàn thành việc kiểm tra...<br/>';
    }


    /* Kiểm tra khám chữa bệnh trái tuyến*/
    public function kcbtraituyen(Request $request) {
        
        $cskcb_dung_tuyen = explode(",", getParam('cskcb_dung_tuyen')->param_value);
        $params = $this->__getSearchParam($request);

        $xml1 = XML1::with('department')
            ->where('ngay_ra', '>=', datetostr_from($params['ngay_ttoan_tu']))
            ->where('ngay_ra', '<=', datetostr_to($params['ngay_ttoan_den']))
            ->where('ma_loai_kcb','like','%' . $params['loai_kcb'] . '%')
            ->where('ma_the','like','%' . $params['ma_the'] . '%')
            ->where('ma_khoa','like','%' . $params['khoa'] . '%')
            ->whereNotIn('ma_dkbd', $cskcb_dung_tuyen)
            ->get();
        //return json_decode($xml1);    
        return view('bhyt.reports.kcb-trai-tuyen',
            compact('xml1'));
    }

    /* Kiểm tra dịch vụ kỹ thuật có điều kiện */
    public function dvktcodieukien(Request $request) {

        $params = $this->__getSearchParam($request);

        $list_dvkt = cat_cond_service::select('service_code')
            ->where('cond_status',1)
            ->get();
        $dvkt_codk = [];
        foreach ($list_dvkt as $key => $value) {
            $dvkt_codk[$key] = $value->service_code;
        }
        //return $a;

        $reports = XML1::select('MA_LK','HO_TEN','MA_THE','NGAY_VAO','NGAY_RA','MA_BENH','MA_BENHKHAC')
            ->with(['xml3' => function ($query) use ($dvkt_codk) {
                $query->select('MA_LK','MA_DICH_VU','NGAY_YL','MA_KHOA')
                    ->whereIn('MA_DICH_VU',$dvkt_codk);
            },'xml3.cat_cond_service' => function ($query){
                $query->select('service_code','cond_val');
            }])
            ->where('ngay_ra', '>=', datetostr_from($params['ngay_ttoan_tu']))
            ->where('ngay_ra', '<=', datetostr_to($params['ngay_ttoan_den']))
            ->where('ma_loai_kcb','like','%' . $params['loai_kcb'] . '%')
            ->where('ma_the','like','%' . $params['ma_the'] . '%')
            ->where('ma_khoa','like','%' . $params['khoa'] . '%')
            ->whereHas('xml3', function ($query) use ($dvkt_codk) {
                $query->whereIn('MA_DICH_VU',$dvkt_codk);
            })
            ->get();

        $checked = [];
        foreach ($reports as $key => $value) {
            $ma_benh_xml = $value->MA_BENH;
            if ($value->MA_BENHKHAC) {
                $ma_benh_xml = $ma_benh_xml . ';' . $value->MA_BENHKHAC;
            }
            foreach ($value->xml3 as $key_xml3 => $value_xml3) {
                $cond_service = preg_replace('/\s+/','',$value_xml3->cat_cond_service->cond_val);
                if (empty(array_intersect(explode(';', $ma_benh_xml), explode(';', $cond_service)))) {
                    $checked[$key][$key_xml3] = '';
                } else {
                    $checked[$key][$key_xml3] = 'ok';
                }
            }
        }

        return view('bhyt.reports.dvkt-co-dieu-kien', compact('reports','checked'));
    }

    /* Kiểm tra thuốc có điều kiện (TT30) */
    public function thuoccodieukien(Request $request) {

        $params = $this->__getSearchParam($request);

        $list_thuoc = cat_cond_pharma::select('pharma_code')
            ->where('pharma_status',1)
            ->get();
        $thuoc_codk = [];
        foreach ($list_thuoc as $key => $value) {
            $thuoc_codk[$key] = $value->pharma_code;
        }
        //return $a;

        $reports = XML1::select('MA_LK','HO_TEN','MA_THE','NGAY_VAO','NGAY_RA','MA_BENH','MA_BENHKHAC')
            ->with(['xml2' => function ($query) use ($thuoc_codk) {
                $query->select('MA_LK','MA_THUOC','NGAY_YL','MA_KHOA')
                    ->whereIn('MA_THUOC',$thuoc_codk);
            },'xml2.cat_cond_pharma' => function ($query){
                $query->select('pharma_code','pharma_val');
            }])
            ->where('ngay_ra', '>=', datetostr_from($params['ngay_ttoan_tu']))
            ->where('ngay_ra', '<=', datetostr_to($params['ngay_ttoan_den']))
            ->where('ma_loai_kcb','like','%' . $params['loai_kcb'] . '%')
            ->where('ma_the','like','%' . $params['ma_the'] . '%')
            ->where('ma_khoa','like','%' . $params['khoa'] . '%')
            ->whereHas('xml2', function ($query) use ($thuoc_codk) {
                $query->whereIn('MA_THUOC',$thuoc_codk);
            })
            ->get();
        $checked = [];
        foreach ($reports as $key => $value) {
            $ma_benh_xml = $value->MA_BENH;
            if ($value->MA_BENHKHAC) {
                $ma_benh_xml = $ma_benh_xml . ';' . $value->MA_BENHKHAC;
            }
            //print_r(explode(';', $ma_benh_xml));
            //echo '<br/>';
            foreach ($value->xml2 as $key_xml2 => $value_xml2) {
                $cond_pharma = preg_replace('/\s+/','',$value_xml2->cat_cond_pharma->pharma_val);
                if (empty(array_intersect(explode(';', $ma_benh_xml), explode(';', $cond_pharma)))) {
                    $checked[$key][$key_xml2] = '';
                } else {
                    $checked[$key][$key_xml2] = 'ok';
                }
                //print_r(explode(';', $ma_benh_xml));
                
                //print_r(explode(';', $cond_pharma));
                //echo '<br/>';
                //print_r(array_intersect(explode(';', $ma_benh_xml), explode(';', $cond_pharma)));
            }
        }

        return view('bhyt.reports.thuoc-co-dieu-kien', compact('reports','checked'));
    }

    public function xml_delete($ma_lk)
    {
        $record = XML1::where('MA_LK', $ma_lk)->first();

        if ($record) {
            XML5::where('MA_LK', $ma_lk)
                ->delete();
            XML4::where('MA_LK', $ma_lk)
                ->delete();
            XML3::where('MA_LK', $ma_lk)
                ->delete();
            XML2::where('MA_LK', $ma_lk)
                ->delete();
            XML1::where('MA_LK', $ma_lk)
                ->delete();     
            return response()->json(['success' => 'Record deleted successfully.']);      
        }

        return response()->json(['error' => 'Record not found.'], 404);
        
    }
}
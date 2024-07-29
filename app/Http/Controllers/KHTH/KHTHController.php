<?php

namespace App\Http\Controllers\KHTH;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Storage;
use DB;
use Artisan;

use App\sticky_note;
use DataTables;
use App\Exports\DVKTExport;

use Rap2hpoutre\FastExcel\FastExcel;
use Box\Spout\Writer\Common\Creator\Style\StyleBuilder;
use Box\Spout\Common\Entity\Style\CellAlignment;

use Picqer\Barcode\BarcodeGeneratorPNG;

use GuzzleHttp\Client;

class KHTHController extends Controller
{
	protected $ParamNgay;
	protected $ParamExamRoom;
    protected $ParamPatientType;
    protected $ParamUser;
    protected $ParamDepartment;
    protected $ParamDichvuXetnghiem;
    protected $ParamDanhmucICD;
    protected $ParamLoaiDVKT;
    protected $ParamExecuteRoom;
    protected $ParamDvkt;

	protected $exam_room;
    protected $patient_type;
    protected $user;
    protected $department;
    protected $DichvuXetnghiem;
    protected $DanhmucICD;
    protected $LoaiDVKT;
    protected $ExecuteRoom;
    protected $Room;

    protected $ParamTreatmentType;
    protected $TreatmentType;

    public function __construct()
    {
    	$this->exam_room = DB::connection('HISPro')
            ->table('his_execute_room')
            ->where('is_active', 1)
            ->where('is_exam', 1)
            ->get();
        $this->patient_type = DB::connection('HISPro')
            ->table('his_patient_type')
            ->where('is_active', 1)
            ->where('is_delete', 0)
            ->get();
        $this->user = DB::connection('ACS_RS')
            ->table('acs_user')
            ->where('is_active', 1)
            ->where('is_delete', 0)
            ->get();
        $this->department = DB::connection('HISPro')
            ->table('his_department')
            ->where('is_active', 1)
            ->where('is_delete', 0)
            ->get();
        $this->DichvuXetnghiem = DB::connection('HISPro')
            ->table('his_service')
            //->where('is_active', 1)
            ->where('is_delete', 0)
            ->where('service_type_id', 2)
            ->where('is_leaf', 1)
            ->get();
        $this->DanhmucICD = DB::connection('HISPro')
            ->table('his_icd')
            ->where('is_active', 1)
            ->where('is_delete', 0)
            ->whereNull('is_traditional')
            ->get();
        $this->LoaiDVKT = DB::connection('HISPro')
            ->table('his_service_type')
            ->where('is_active', 1)
            ->where('is_delete', 0)
            ->get();

        $this->ExecuteRoom = DB::connection('HISPro')
            ->table('his_execute_room')
            ->where('is_active', 1)
            ->where('is_delete', 0)
            ->get();

        $this->TreatmentType = DB::connection('HISPro')
            ->table('his_treatment_type')
            ->where('is_active', 1)
            ->where('is_delete', 0)
            ->get();

        $this->Room = DB::connection('HISPro')
            ->table('his_room')
            ->leftJoin('his_execute_room', 'his_execute_room.room_id', '=' ,'his_room.id')
            ->leftJoin('his_bed_room', 'his_bed_room.room_id', '=' ,'his_room.id')
            ->leftJoin('his_reception_room', 'his_reception_room.room_id', '=' ,'his_room.id')
            ->selectRaw('his_room.id as id, his_execute_room.execute_room_name || his_bed_room.bed_room_name || his_reception_room.reception_room_name as name')
            ->where('his_room.is_active', 1)
            ->where('his_room.is_delete', 0)
            ->whereIn('room_type_id', [1,4,3])
            ->get();

        $this->ParamNgay = [
            'tu_ngay' => date_format(now(),'Y-m-d'),
            'den_ngay' => date_format(now(),'Y-m-d'),    
        ];
        $this->ParamExamRoom = [
            'exam_room' => [],
        ];

        $this->ParamPatientType = [];
        $this->ParamUser = [];
        $this->ParamDepartment = [];
        $this->ParamDichvuXetnghiem = [];
        $this->ParamDanhmucICD = [];
        $this->ParamLoaiDVKT = [];
        $this->ParamExecuteRoom = [];
        $this->ParamDvkt = [];
    }	

    private function __ParamNgay($request)
    {
        return [
            'tu_ngay' => $request->get('tu_ngay') ? $request->get('tu_ngay') : 
                    $this->ParamNgay['tu_ngay'],
            'den_ngay' => $request->get('den_ngay') ? $request->get('den_ngay') : 
                    $this->ParamNgay['den_ngay'],
        ];
    }

    private function __ParamExamRoom($request)
    {
        return [
            'exam_room' => $request->get('exam_room') ? $request->get('exam_room') : $this->ParamExamRoom['exam_room'],
        ];
    }

    private function __ParamPatientType($request)
    {
        return $request->get('patient_type') ? $request->get('patient_type') : $this->ParamPatientType;
    }

    private function __ParamUser($request)
    {
        return $request->get('user') ? $request->get('user') : $this->ParamUser;
    }

    private function __ParamDepartment($request)
    {
        return $request->get('department') ? $request->get('department') : $this->ParamDepartment;
    }

    private function __ParamLoaiDVKT($request)
    {
        return $request->get('loai_dvkt') ? $request->get('loai_dvkt') : $this->ParamLoaiDVKT;
    }

    private function __ParamExecuteRoom($request)
    {
        return $request->get('execute_room') ? $request->get('execute_room') : $this->ParamExecuteRoom;
    }

    private function __ParamExecuteDepartment($request)
    {
        return $request->get('execute_department') ? $request->get('execute_department') : [];
    }

    private function __ParamDvkt($request)
    {
        //return $request->get('dvkt') ? $request->get('dvkt') : $this->ParamDvkt;
        if ($request->get('dvkt')) {
            return DB::connection('HISPro')
                ->table('his_service')
                //->where('is_active', 1)
                ->where('is_delete', 0)
                ->whereIn('service_code', $request->get('dvkt'))
                ->get();
        }

        return $this->ParamDvkt;

    }

    private function __ParamIcd($request)
    {
        if ($request->get('icd')) {
            return DB::connection('HISPro')
                ->table('his_icd')
                ->where('is_active', 1)
                ->where('is_delete', 0)
                ->whereNull('is_traditional')
                ->whereIn('icd_code', $request->get('icd'))
                ->get();
        }
        return [];
    }

    private function __ParamRequestRoom($request)
    {
        return $request->get('request_room') ? $request->get('request_room') : [];
    }

    private function __ParamTreatmentType($request)
    {
        return $request->get('treatment_type') ? $request->get('treatment_type') : [];
    }

    public function SoLuotKhamIndex(Request $request)
    {
        $ParamNgay = $this->__ParamNgay($request);
        $exam_room = $this->exam_room;
        $ParamExamRoom = $this->__ParamExamRoom($request);
        $patient_type = $this->patient_type;
        $ParamPatientType = $this->__ParamPatientType($request);

        //return $this->SoLuotKhamGetData($request);

        return view('khth.so-luot-kham-index',
    		compact('ParamNgay','exam_room','ParamExamRoom','patient_type','ParamPatientType'));
    }

    public function SoLuotKhamGetData(Request $request)
    {
        if (!$request->ajax()) {
            return redirect()->route('home');
        }

    	$ParamNgay = $this->__ParamNgay($request);
        $from_date = date_format(date_create($ParamNgay['tu_ngay']),'Ymd000000');
        $to_date = date_format(date_create($ParamNgay['den_ngay']),'Ymd235959');
        
        try {
            $model = DB::connection('HISPro')
            ->table('his_service_req')
            ->join('his_execute_room', 'his_service_req.execute_room_id', '=', 'his_execute_room.room_id')
            ->join('his_sere_serv', 'his_service_req.id', '=', 'his_sere_serv.service_req_id')
            ->join('his_patient_type', 'his_sere_serv.patient_type_id', '=', 'his_patient_type.id')
            ->selectRaw('count(*) as so_luong, his_execute_room.execute_room_name')
            ->where('his_execute_room.is_active', 1)
            ->where('his_execute_room.is_exam', 1)
            ->where('his_service_req.is_delete', 0)
            ->where('his_service_req.intruction_time', '>=', $from_date)
            ->where('his_service_req.intruction_time', '<=', $to_date)
            ->groupBy('his_execute_room.execute_room_name');

            if ($request->get('exam_room')) {
                $model->whereIn('room_id', $request->get('exam_room'));
            }

            if ($request->get('patient_type')) {
                $model->whereIn('his_sere_serv.patient_type_id', $request->get('patient_type'));
            }

            $model = $model->get();

            $labels[] = '';
            $data[] = '';
            foreach ($model as $key => $value) {
                $labels[$key] = $value->execute_room_name .'(' .number_format(($value->so_luong/$model->sum('so_luong'))*100,2) .'%)';
                $data[$key] = doubleval($value->so_luong);
            }
            $rtnData[] = array(
                'type' => 'bar',
                'title' => 'Số lượt khám / Bàn khám',
                'labels' => $labels,
                'datasets' => array(
                    array(
                        'data' => $data,
                        'backgroundColor' => "rgb(93, 158, 178)",
                        'label' => "Tổng cộng: " . number_format($model->sum('so_luong')),
                        'fill' => false
                    ),
                )
            );
            return json_encode($rtnData);        
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    public function ChiPhiKhamBenhIndex(Request $request)
    {
        $ParamNgay = $this->__ParamNgay($request);
        $patient_type = $this->patient_type;
        $ParamPatientType = $this->__ParamPatientType($request);
        $treatment_type = $this->TreatmentType;
        $ParamTreatmentType = $this->__ParamTreatmentType($request);

        return view('khth.chi-phi-kham-benh-index',
            compact('ParamNgay','patient_type','ParamPatientType','treatment_type','ParamTreatmentType')
        );
    }    

    public function getChiphiKCB(Request $request)
    {
        if (!$request->ajax()) {
            return redirect()->route('home');
        }

        $ParamNgay = $this->__ParamNgay($request);

        $from_date = date_format(date_create($ParamNgay['tu_ngay']),'Ymd000000');
        $to_date = date_format(date_create($ParamNgay['den_ngay']),'Ymd235959');

        try {
            $model = DB::connection('HISPro')
            ->table('his_sere_serv')
            ->join('his_service_type', 'his_sere_serv.tdl_service_type_id', '=', 'his_service_type.id')
            ->join('his_service_req', 'his_service_req.id', '=', 'his_sere_serv.service_req_id')
            ->selectRaw('sum(amount*price) as thanh_tien,service_type_name')
            ->where('tdl_intruction_time', '>=', $from_date)
            ->where('tdl_intruction_time', '<=', $to_date)
            ->where('his_sere_serv.is_delete', 0)
            ->groupBy('service_type_name');

            if ($request->get('treatment_type')) {
                $model->whereIn('his_service_req.tdl_treatment_type_id', $request->get('treatment_type'));
            }

            if ($request->get('patient_type')) {
                $model->whereIn('his_sere_serv.patient_type_id', $request->get('patient_type'));
            }

            $model = $model->get();

            $labels[] = '';
            $data[] = '';
            foreach ($model as $key => $value) {
                $labels[$key] = $value->service_type_name .'(' .number_format(($value->thanh_tien/$model->sum('thanh_tien'))*100,2) .'%)';
                $data[$key] = doubleval($value->thanh_tien);
            }
            $rtnData[] = array(
                'type' => 'bar',
                'title' => 'Chi phí khám bệnh, chữa bệnh',
                'labels' => $labels,
                'datasets' => array(
                    array(
                        'data' => $data,
                        'backgroundColor' => "rgb(93, 158, 178)",
                        'label' => "Tổng cộng: " . number_format($model->sum('thanh_tien')),
                        'fill' => false
                    ),
                )
            );
            return json_encode($rtnData);
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    public function DieuTriNoiTruIndex(Request $request)
    {
        $ParamNgay = $this->ParamNgay;
        $exam_room = $this->exam_room;
        $ParamExamRoom = $this->ParamExamRoom;
        $user = $this->user;
        $ParamUser = $this->ParamUser;

        $result_th = $this->danhsachnhapvientonghop(date('Ymd',strtotime($ParamNgay['tu_ngay'])) . '000000',
            date('Ymd',strtotime($ParamNgay['den_ngay'])) . '235959', $ParamExamRoom['exam_room'], $ParamUser);

        return view('khth.dieu-tri-noi-tru-index',
            compact('ParamNgay','exam_room','ParamExamRoom','user','ParamUser','result_th'));
    }    

    public function DieuTriNoiTruSearch(Request $request)
    {
        $ParamNgay = $this->__ParamNgay($request);
        $exam_room = $this->exam_room;
        $ParamExamRoom = $this->__ParamExamRoom($request);
        $user = $this->user;
        $ParamUser = $this->__ParamUser($request);

        $result_th = $this->danhsachnhapvientonghop(date('Ymd',strtotime($ParamNgay['tu_ngay'])) . '000000',
            date('Ymd',strtotime($ParamNgay['den_ngay'])) . '235959', $ParamExamRoom['exam_room'], $ParamUser);

        return view('khth.dieu-tri-noi-tru-index',
            compact('ParamNgay','exam_room','ParamExamRoom','user','ParamUser','result_th'));
    }   

    private function danhsachnhapvientonghop($from_date, $to_date, $exam_room, $user) {

        $result = DB::connection('HISPro')
            ->table('his_service_req')
            ->join('his_execute_room', 'his_service_req.execute_room_id', '=', 'his_execute_room.room_id')
            ->join('his_treatment', 'his_treatment.id', '=', 'his_service_req.treatment_id')
            ->selectRaw('count(*) as so_luong, his_execute_room.execute_room_name')
            ->where('his_execute_room.is_active', 1)
            ->where('his_execute_room.is_exam', 1)
            ->where('his_service_req.is_delete', 0)
            ->where('his_service_req.service_req_type_id', config('__tech.service_req_type_kham'))
            ->where('his_treatment.tdl_treatment_type_id', config('__tech.treatment_type_noitru'))
            ->where('his_service_req.is_main_exam', 1)
            ->where('his_service_req.intruction_time', '>=', $from_date)
            ->where('his_service_req.intruction_time', '<=', $to_date)
            ->groupBy('his_execute_room.execute_room_name')
            ->orderBy('his_execute_room.execute_room_name');

        if ($exam_room) {
            $result->whereIn('room_id', $exam_room);
        }

        if ($user) {
            $result->whereIn('his_service_req.execute_loginname', $user);
        }

        return ($result->get());
    }

    public function NoiTruTheoKhoaIndex(Request $request)
    {
        $ParamNgay = $this->ParamNgay;
        $department = $this->department;
        $ParamDepartment = $this->ParamDepartment;
        $patient_type = $this->patient_type;
        $ParamPatientType = $this->ParamPatientType;

        $result_th = $this->danhsachnoitrutheokhoatonghop(date('Ymd',strtotime($ParamNgay['tu_ngay'])) . '000000',
            date('Ymd',strtotime($ParamNgay['den_ngay'])) . '235959', $ParamDepartment, $ParamPatientType);

        return view('khth.noi-tru-theo-khoa-index',
            compact('ParamNgay','department','ParamDepartment','patient_type','ParamPatientType','result_th'));
    }    

    public function NoiTruTheoKhoaSearch(Request $request)
    {
        $ParamNgay = $this->__ParamNgay($request);
        $department = $this->department;
        $ParamDepartment = $this->__ParamDepartment($request);
        $patient_type = $this->patient_type;
        $ParamPatientType = $this->__ParamPatientType($request);

        $result_th = $this->danhsachnoitrutheokhoatonghop(date('Ymd',strtotime($ParamNgay['tu_ngay'])) . '000000',
            date('Ymd',strtotime($ParamNgay['den_ngay'])) . '235959', $ParamDepartment, $ParamPatientType);


        return view('khth.noi-tru-theo-khoa-index',
            compact('ParamNgay','department','ParamDepartment','patient_type','ParamPatientType','result_th'));
    }   

    private function danhsachnoitrutheokhoatonghop($from_date, $to_date, $department, $patient_type) {

        $result = DB::connection('HISPro')
            ->table('his_treatment')
            ->join('his_department', 'his_department.id', '=', 'his_treatment.last_department_id')
            ->selectRaw('count(*) as so_luong, his_department.department_name')
            ->where('his_treatment.tdl_treatment_type_id', config('__tech.treatment_type_noitru'))
            ->where('his_treatment.in_time', '>=', $from_date)
            ->where('his_treatment.in_time', '<=', $to_date)
            ->groupBy('his_department.department_name')
            ->orderBy('his_department.department_name');

        if ($department) {
            $result->whereIn('his_treatment.last_department_id', $department);
        }

        if ($patient_type) {
            $result->whereIn('his_treatment.tdl_patient_type_id', $patient_type);
        }

        return ($result->get());
    }

    public function viewEmr(Request $request)
    {
        $emr_document = null;
        $emr_treatment = null;
        $document_type_code = [22,47,94];
        $patient_type_code = ['03','99'];
        $param = $request->get('treatment_code') ? $request->get('treatment_code') : '';

        $emr_treatment = DB::connection('EMR_RS')
        ->table('emr_treatment')
        ->select('treatment_code', 'patient_code', 'vir_patient_name', 'dob', 'hein_card_number', 'patient_type_name', 'icd_code', 'in_time', 'out_time' ,'end_code', 'treatment_result_name', 'treatment_end_type_name', 'current_department_code', 'current_department_name')
        ->where('is_delete', 0)
        ->where('treatment_code',$param)
        ->whereIn('patient_type_code', $patient_type_code)
        ->get();

        if (count($emr_treatment)) {
            $emr_document = DB::connection('EMR_RS')
            ->table('emr_document')
            ->join('emr_document_type', 'emr_document_type.id', '=', 'emr_document.document_type_id')
            ->select('emr_document.document_code','emr_document.document_name','emr_document_type.document_type_code','emr_document_type.document_type_name','emr_document.create_date')
            ->where('emr_document.is_delete', 0)
            ->where('emr_document.treatment_code', $param)
            ->whereIn('document_type_code', $document_type_code)
            ->get();
        }

        return view('khth.view-emr', compact('param','emr_treatment','emr_document'));
    }    

    public function XetNghiemChanDoan(Request $request)
    {
        $ParamNgay = $this->ParamNgay;
        $DichvuXetnghiem = $this->DichvuXetnghiem;
        $ParamDichvuXetnghiem = $this->ParamDichvuXetnghiem;
        $DanhmucICD = $this->DanhmucICD;
        $ParamDanhmucICD = $this->ParamDanhmucICD;
        return view('khth.xet-nghiem-chan-doan-index',
            compact('ParamNgay','DichvuXetnghiem','ParamDichvuXetnghiem','DanhmucICD','ParamDanhmucICD'));
    }   

    public function CongVan19031Index(Request $request)
    {
        $ParamNgay = $this->ParamNgay;

        $result_bn_nhap_vien = $this->BN_NhapVien_TheoNgay(date('Ymd',strtotime($ParamNgay['tu_ngay'])) . '000000',
            date('Ymd',strtotime($ParamNgay['den_ngay'])) . '235959');
        $result_bn_ra_vien = $this->BN_NoiTru_RaVien_TheoNgay(date('Ymd',strtotime($ParamNgay['tu_ngay'])) . '000000',
            date('Ymd',strtotime($ParamNgay['den_ngay'])) . '235959');
        $result_bn_chuyen_vien = $this->BN_NoiTru_ChuyenVien_TheoNgay(date('Ymd',strtotime($ParamNgay['tu_ngay'])) . '000000',
            date('Ymd',strtotime($ParamNgay['den_ngay'])) . '235959');
        $result_bn_tu_vong = $this->BN_NoiTru_TuVong_TheoNgay(date('Ymd',strtotime($ParamNgay['tu_ngay'])) . '000000',
            date('Ymd',strtotime($ParamNgay['den_ngay'])) . '235959');
        $result_bn_ngoai_tru = $this->BN_NgoaiTru_TheoNgay(date('Ymd',strtotime($ParamNgay['tu_ngay'])) . '000000',
            date('Ymd',strtotime($ParamNgay['den_ngay'])) . '235959');

        return view('khth.cong-van-19031-index',
            compact('ParamNgay','result_bn_nhap_vien','result_bn_ra_vien','result_bn_chuyen_vien','result_bn_tu_vong','result_bn_ngoai_tru'));
    }   

    public function CongVan19031Search(Request $request)
    {
        $ParamNgay = $this->__ParamNgay($request);

        $result_bn_nhap_vien = $this->BN_NhapVien_TheoNgay(date('Ymd',strtotime($ParamNgay['tu_ngay'])) . '000000',
            date('Ymd',strtotime($ParamNgay['den_ngay'])) . '235959');
        $result_bn_ra_vien = $this->BN_NoiTru_RaVien_TheoNgay(date('Ymd',strtotime($ParamNgay['tu_ngay'])) . '000000',
            date('Ymd',strtotime($ParamNgay['den_ngay'])) . '235959');
        $result_bn_chuyen_vien = $this->BN_NoiTru_ChuyenVien_TheoNgay(date('Ymd',strtotime($ParamNgay['tu_ngay'])) . '000000',
            date('Ymd',strtotime($ParamNgay['den_ngay'])) . '235959');
        $result_bn_tu_vong = $this->BN_NoiTru_TuVong_TheoNgay(date('Ymd',strtotime($ParamNgay['tu_ngay'])) . '000000',
            date('Ymd',strtotime($ParamNgay['den_ngay'])) . '235959');
        $result_bn_ngoai_tru = $this->BN_NgoaiTru_TheoNgay(date('Ymd',strtotime($ParamNgay['tu_ngay'])) . '000000',
            date('Ymd',strtotime($ParamNgay['den_ngay'])) . '235959');

        return view('khth.cong-van-19031-index',
            compact('ParamNgay','result_bn_nhap_vien','result_bn_ra_vien','result_bn_chuyen_vien','result_bn_tu_vong','result_bn_ngoai_tru'));
    } 

    //* Công văn 19031-BHXH *//
    private function BN_NhapVien_TheoNgay($from_date, $to_date) {

        $result = DB::connection('HISPro')
            ->table('his_treatment')
            ->where('his_treatment.tdl_treatment_type_id', config('__tech.treatment_type_noitru'))
            ->where('his_treatment.in_time', '>=', $from_date)
            ->where('his_treatment.in_time', '<=', $to_date)
            ->count();

        return $result;
    }

    private function BN_NoiTru_RaVien_TheoNgay($from_date, $to_date) {

        $result = DB::connection('HISPro')
            ->table('his_treatment')
            ->where('his_treatment.tdl_treatment_type_id', config('__tech.treatment_type_noitru'))
            ->whereNotIn('his_treatment.treatment_end_type_id', [1,2])
            ->where('his_treatment.out_time', '>=', $from_date)
            ->where('his_treatment.out_time', '<=', $to_date)
            ->count();

        return $result;
    }

    private function BN_NoiTru_ChuyenVien_TheoNgay($from_date, $to_date) {

        $result = DB::connection('HISPro')
            ->table('his_treatment')
            ->where('his_treatment.tdl_treatment_type_id', config('__tech.treatment_type_noitru'))
            ->whereIn('his_treatment.treatment_end_type_id', [2])
            ->where('his_treatment.out_time', '>=', $from_date)
            ->where('his_treatment.out_time', '<=', $to_date)
            ->count();

        return $result;
    }

    private function BN_NoiTru_TuVong_TheoNgay($from_date, $to_date) {

        $result = DB::connection('HISPro')
            ->table('his_treatment')
            ->where('his_treatment.tdl_treatment_type_id', config('__tech.treatment_type_noitru'))
            ->whereIn('his_treatment.treatment_end_type_id', [1])
            ->where('his_treatment.out_time', '>=', $from_date)
            ->where('his_treatment.out_time', '<=', $to_date)
            ->count();

        return $result;
    }

    private function BN_NgoaiTru_TheoNgay($from_date, $to_date) {

        $result = DB::connection('HISPro')
            ->table('his_treatment')
            ->whereIn('his_treatment.tdl_treatment_type_id', [1,2])
            ->whereIn('his_treatment.tdl_patient_type_id', [1,42])
            ->where('his_treatment.in_time', '>=', $from_date)
            ->where('his_treatment.in_time', '<=', $to_date)
            ->count();

        return $result;
    }
    //* Công văn 19031-BHXH *//

    private function get_emr_document($treatment_code)
    {
        $result = DB::connection('EMR_RS')
            ->table('emr_document')
            ->join('emr_document_type', 'emr_document_type.id', '=', 'emr_document.document_type_id')
            ->select('emr_document.id','emr_document.document_name','emr_document.create_date')
            ->where('emr_document.is_delete', 0)
            ->where('emr_document.treatment_code', $treatment_code)
            ->where('emr_document.document_type_id', 22)
            ->get();
        return $result;
    }

    public function TKDichVuKyThuatIndex(Request $request)
    {
        $ParamNgay = $this->__ParamNgay($request);
        $ParamLoaiDVKT = $this->ParamLoaiDVKT;
        $LoaiDVKT = $this->LoaiDVKT;
        $ParamExecuteRoom = $this->ParamExecuteRoom;
        $ExecuteRoom = $this->ExecuteRoom;
        $ParamDepartment = $this->ParamDepartment;
        $Department = $this->department;        
        $ParamDvkt = $this->__ParamDvkt($request);
        $ParamExecuteDepartment = $this->__ParamExecuteDepartment($request);
        $ParamIcd = $this->__ParamIcd($request);

        $Room = $this->Room;
        $ParamRequestRoom = $this->__ParamRequestRoom($request);

        return view('khth.dich-vu-ky-thuat-index',
            compact('ParamNgay', 'ParamLoaiDVKT', 'LoaiDVKT',
                'ParamExecuteRoom', 'ExecuteRoom', 'ParamDepartment', 'Department','ParamDvkt','ParamExecuteDepartment','ParamIcd','Room','ParamRequestRoom'));
    } 

    public function TKDichVuKyThuatSearch(Request $request)
    {
        $ParamNgay = $this->__ParamNgay($request);
        $ParamLoaiDVKT = $this->__ParamLoaiDVKT($request);
        $LoaiDVKT = $this->LoaiDVKT;
        $ParamExecuteRoom = $this->__ParamExecuteRoom($request);
        $ExecuteRoom = $this->ExecuteRoom;
        $ParamDepartment = $this->__ParamDepartment($request);
        $Department = $this->department;        
        $ParamDvkt = $this->__ParamDvkt($request);
        $ParamExecuteDepartment = $this->__ParamExecuteDepartment($request);
        $ParamIcd = $this->__ParamIcd($request);

        $Room = $this->Room;
        $ParamRequestRoom = $this->__ParamRequestRoom($request);

        return view('khth.dich-vu-ky-thuat-index',
            compact('ParamNgay', 'ParamLoaiDVKT', 'LoaiDVKT',
                'ParamExecuteRoom', 'ExecuteRoom', 'ParamDepartment', 'Department','ParamDvkt','ParamExecuteDepartment','ParamIcd','Room','ParamRequestRoom'));
    } 

    public function GetDanhMucDVKT(Request $request)
    {
        if (!$request->ajax()) {
            return redirect()->route('home');
        }
        
        $term = request('term');
        $loai_dvkt = request('loai_dvkt');

        $model = DB::connection('HISPro')
            ->table('his_service')
            ->select('service_code','service_name')
            //->where('is_active', 1)
            ->where('is_delete', 0)
            ->whereNotNull('is_leaf')
            ->whereIn('service_type_id', explode(',', config('__tech.tdl_service_req_type_id_dvkt')));

        if ($loai_dvkt) {
            $model = $model->whereIn('service_type_id', $loai_dvkt);
        }

        if ($term) {
            $model = $model->whereRaw("UPPER(service_name) like  '%" .mb_strtoupper($term) ."%'");
        }

        $model = $model->paginate(50);

        $formatted_model = [];
        foreach ($model as $key => $value) {
            $formatted_model[] = ['id' => $value->service_code, 'text' => $value->service_name];
        }
        return json_encode($formatted_model); 

    } 

    public function GetDanhMucICD(Request $request)
    {
        if (!$request->ajax()) {
            return redirect()->route('home');
        }
        
        $term = request('term');

        $model = DB::connection('HISPro')
            ->table('his_icd')
            ->select('icd_code','icd_name')
            ->where('is_active', 1)
            ->where('is_delete', 0)
            ->whereNull('is_traditional');

        if ($term) {
            $model = $model->whereRaw("UPPER(icd_code) like  '%" .mb_strtoupper($term) ."%'");
        }

        $model = $model->paginate(50);

        $formatted_model = [];
        foreach ($model as $key => $value) {
            $formatted_model[] = ['id' => $value->icd_code, 'text' => $value->icd_code . ' - ' . $value->icd_name];
        }
        return json_encode($formatted_model); 

    }

    public function get_dvkt(Request $request)
    {
        if (!$request->ajax()) {
            return redirect()->route('home');
        }
        
        $dvkt = [13,2,3,4,5,8,9,10,1,12,11,16,15];
        
        $ParamNgay = $this->__ParamNgay($request);
		$date_from = date_create($ParamNgay['tu_ngay'])->format('Ymd000000');
		$date_to = date_create($ParamNgay['den_ngay'])->format('Ymd235959');

		$model = DB::connection('HISPro')->table('his_sere_serv')
			->join('his_service_req', 'his_service_req.id', '=', 'his_sere_serv.service_req_id')
			->leftJoin('his_execute_room', 'his_execute_room.room_id', '=', 'his_sere_serv.tdl_execute_room_id')
			->select('his_sere_serv.tdl_treatment_code', 'his_service_req.tdl_patient_name', 'his_service_req.tdl_patient_dob',
				'his_sere_serv.tdl_service_name', 'his_sere_serv.tdl_intruction_time', 'his_sere_serv.hein_card_number', 
				'his_sere_serv.tdl_request_username', 'his_sere_serv.amount', 'his_sere_serv.price', 
				'his_execute_room.execute_room_name')
			->whereBetween('tdl_intruction_date', [$date_from, $date_to])
			->where([
				['his_sere_serv.is_active', 1],
				['his_sere_serv.is_delete', 0],
			])
			->whereIn('tdl_service_type_id', $dvkt);

		// Tối ưu hóa bằng cách sử dụng các loop để thêm điều kiện
		$filters = [
			'loai_dvkt' => 'tdl_service_type_id',
			'department' => 'tdl_request_department_id',
			'execute_room' => 'tdl_execute_room_id',
			'dvkt' => 'tdl_service_code',
			'execute_department' => 'tdl_execute_department_id',
			//'icd' => 'icd_code',
			'request_room' => 'tdl_request_room_id',
		];

		foreach ($filters as $requestKey => $dbColumn) {
			if ($requestValue = $request->get($requestKey)) {
				$model = $model->whereIn($dbColumn, $requestValue);
			}
		}

        return DataTables::of($model)->toJson();
    }

    public function exportDVKT(Request $request)
    {
        $ParamNgay = $this->__ParamNgay($request);
        $from_date = date_format(date_create($ParamNgay['tu_ngay']),'Ymd000000');
        $to_date = date_format(date_create($ParamNgay['den_ngay']),'Ymd235959');

        $header_style = (new StyleBuilder())
        ->setFontBold()
        ->setCellAlignment(CellAlignment::CENTER)
        ->build();
        $rows_style = (new StyleBuilder())
        ->setShouldWrapText(false)
        ->build();

        return (new FastExcel($this->expo_test($from_date, $to_date, $request)))
        ->headerStyle($header_style)
        ->rowsStyle($rows_style)
        ->download(date('YmdHi', strtotime(now())) . '_dvkt.xlsx');

        //return (new DVKTExport($from_date, $to_date, $request))->download(date('YmdHi', strtotime(now())) . '_dvkt.xlsx');
        //return \Excel::download(new DVKTExport($from_date, $to_date, $request), date('YmdHi', strtotime(now())) . '_dvkt.xlsx');
    }

    private function expo_test(string $from_date, string $to_date, Request $request)
    {
        try {
            
            $dvkt = [13,2,3,4,5,8,9,10,1,12,11,16,15];

            $query  = DB::connection('HISPro')
            ->table('his_sere_serv')
            ->join('his_service_req', 'his_service_req.id', '=' ,'his_sere_serv.service_req_id')
            ->join('his_treatment', 'his_treatment.id', '=' ,'his_sere_serv.tdl_treatment_id')
            ->join('his_department as rqt_dp', 'his_sere_serv.tdl_request_department_id', '=' ,'rqt_dp.id')
            ->join('his_department as exe_dp', 'his_sere_serv.tdl_execute_department_id', '=' ,'exe_dp.id')
            ->leftJoin('his_execute_room', 'his_execute_room.room_id', '=', 'his_sere_serv.tdl_execute_room_id');

            switch ($request->get('group_by')) {
                case '1':
                    $query = $query->selectRaw('his_sere_serv.tdl_service_name as DVKT,
                        sum(his_sere_serv.amount) as So_luong,
                        sum(his_sere_serv.amount * his_sere_serv.price) as So_tien');
                    break;
                case '2':
                    $query = $query->selectRaw('his_sere_serv.tdl_treatment_code as MA_LK,
                        sum(his_sere_serv.amount * his_sere_serv.price) as So_tien');
                    break;
                default:
                    $query = $query->select('his_sere_serv.tdl_treatment_code as Ma_dieu_tri',
                        'his_service_req.tdl_patient_name as Ho_ten_BN',
                        'his_service_req.tdl_patient_dob as Ngay_sinh',
                        'his_service_req.tdl_patient_address as Dia_chi',
                        'his_sere_serv.hein_card_number as Ma_the',
                        'his_sere_serv.tdl_service_name as Ten_dich_vu',
                        'his_sere_serv.tdl_intruction_time as Ngay_chi_dinh',
                        'his_treatment.in_time as Ngay_vao',
                        'his_treatment.out_time as Ngay_ra',
                        'his_treatment.fee_lock_time as Ngay_thanh_toan',
                        'his_sere_serv.tdl_request_username as BS_chi_dinh',
                        'rqt_dp.department_name as Khoa_chi_dinh',
                        'exe_dp.department_name as Khoa_thuc_hien',
                        'his_execute_room.execute_room_name as Phong_thuc_hien',
                        'his_service_req.execute_username as BS_thuc_hien',
                        'his_sere_serv.amount as So_luong',
                        'his_sere_serv.price as Don_gia');
                    break;
            }

            $query = $query->where('tdl_intruction_time', '>=', $from_date)
            ->where('tdl_intruction_time', '<=', $to_date)
            ->where('his_sere_serv.is_active', 1)
            ->where('his_sere_serv.is_delete', 0)
            ->whereIn('tdl_service_type_id', $dvkt);

            if ($request->get('loai_dvkt')) {
                $query = $query->whereIn('his_sere_serv.tdl_service_type_id', 
                    explode(',', $request->get('loai_dvkt')));
            }
            if ($request->get('department')) {
                $query = $query->whereIn('his_sere_serv.tdl_request_department_id', 
                    explode(',', $request->get('department')));
            }
            if ($request->get('execute_room')) {
                $query = $query->whereIn('his_sere_serv.tdl_execute_room_id', 
                    explode(',', $request->get('execute_room')));
            }
            if ($request->get('dvkt')) {
                $query = $query->whereIn('his_sere_serv.tdl_service_code', 
                    explode(',', $request->get('dvkt')));
            }
            if ($request->get('execute_department')) {
                $query = $query->whereIn('his_sere_serv.tdl_execute_department_id', 
                    explode(',', $request->get('execute_department')));
            }
            if ($request->get('icd')) {
                $query = $query->whereIn('his_service_req.icd_code', 
                    explode(',', $request->get('icd')));
            }
            if ($request->get('request_room')) {
                $query = $query->whereIn('his_sere_serv.tdl_request_room_id', 
                    explode(',', $request->get('request_room')));
            }

            switch ($request->get('group_by')) {
                case '1':
                    $query = $query->groupBy('his_sere_serv.tdl_service_name');
                    break;
                case '2':
                    $query = $query->groupBy('his_sere_serv.tdl_treatment_code');
                    break;
                
                default:
                    break;
            }

            foreach ($query->cursor() as $test) {
                yield $test;
            }
        } catch (\Exception $e) {
            return $e;
        }
    }

    public function dashboard(Request $request)
    {
        return view('khth.dashboard');
    }

    public function stickyNote(Request $request)
    {
        $note = sticky_note::where('note_name', 'khth')
        ->first();
        return view('khth.sticky-note')->with('note', $note);
    }

    public function saveStickyNote(Request $request)
    {
        try {
            $note = sticky_note::where('note_name', 'khth')
            ->first();
            $note->content = $request->get('content') ? $request->get('content') : '';
            $note->save();
            
            event(new \App\Events\DemoPusherEvent($request->get('content') ? $request->get('content'): '', 'khth-dashboard'));
            
            flash('Thành công')->overlay();
        } catch (\Exception $e) {
            return $e->getMessage();
        }

        return redirect()->back();
    }

    public function viewGuide(Request $request)
    {
        try {
            $param_code = $request->get('code') ? $request->get('code') : '';
            $param_phone = $request->get('phone') ? $request->get('phone') : '';

            $treatments = [];

            if ($param_code && $param_phone) {
                $treatments = DB::connection('HISPro')
                ->table('his_treatment')
                ->join('his_patient', 'his_treatment.patient_id', '=' ,'his_patient.id')
                ->join('his_patient_type', 'his_treatment.tdl_patient_type_id', '=', 'his_patient_type.id')
                ->join('his_treatment_type', 'his_treatment.tdl_treatment_type_id', '=', 'his_treatment_type.id')
                ->leftJoin('his_treatment_result', 'his_treatment.treatment_result_id', '=' ,'his_treatment_result.id')
                ->leftJoin('his_treatment_end_type', 'his_treatment.treatment_end_type_id', '=' ,'his_treatment_end_type.id')
                ->select('his_treatment.id','his_treatment.treatment_code','his_treatment.tdl_patient_name',
                    'his_patient.vir_dob_year','his_treatment.tdl_patient_gender_name','his_treatment.tdl_patient_type_id',
                    'his_patient_type.patient_type_name','his_treatment.tdl_patient_address','his_patient.phone',
                    'his_treatment_type.treatment_type_name','his_treatment.in_time','his_treatment.out_time',
                    'his_treatment.tdl_patient_career_name','his_treatment_result.treatment_result_name',
                    'his_treatment_end_type.treatment_end_type_name',
                    'his_treatment.tdl_patient_cmnd_number','his_treatment.tdl_patient_cccd_number',
                    'his_treatment.tdl_patient_passport_number','his_treatment.tdl_hein_card_number',
                    'his_treatment.icd_name', 'his_treatment.icd_text'
                )
                ->where(function($q) use ($param_code){
                    $q->where('his_treatment.treatment_code','like','%' . $param_code . '%')
                    ->orWhere('his_patient.patient_code', $param_code)
                    ->orWhere('his_patient.cmnd_number', $param_code)
                    ->orWhere('his_patient.cccd_number', $param_code)
                    ->orWhere('his_patient.passport_number', $param_code);
                })
                ->where(function($q) use ($param_phone){
                    $q->where('his_patient.phone', $param_phone)
                    ->orWhere('his_patient.mobile', $param_phone)
                    ->orWhere('his_patient.relative_mobile', $param_phone)
                    ->orWhere('his_patient.relative_phone', $param_phone)
                    ->orWhere('his_treatment.tdl_patient_phone', $param_phone)
                    ->orWhere('his_treatment.tdl_patient_mobile', $param_phone);
                })
                ->orderBy('in_time','desc')
                ->get();

                // if ($treatments) {
                //     dd($treatments);
                // }
                
            }
            
        } catch (\Exception $e) {
            return $e->getMessage();
        }

        return view('khth.view-guide',
            compact('param_code','param_phone','treatments')
        );
    }

    public function viewGuideContent(Request $request)
    {
        try {
            $param_madt = $request->get('treatment_code') ? $request->get('treatment_code') : '';
            $param_phone = $request->get('phone') ? $request->get('phone') : '';

            $treatment = null;
            $service_req = null;
            $emr_document = null;
            $service_kham = null;
            $sere_serv_cdha = null;
            $service_req_notStarted = null;
            $countServiceReqNotStartByRoom = null;
            $sere_serv_chiphi = null;
            $tracuuhoadon = null;
            $barcode = null;

            if ($param_madt && $param_phone) {
                $treatment = DB::connection('HISPro')
                ->table('his_treatment')
                ->join('his_patient', 'his_treatment.patient_id', '=' ,'his_patient.id')
                ->join('his_patient_type', 'his_treatment.tdl_patient_type_id', '=', 'his_patient_type.id')
                ->join('his_treatment_type', 'his_treatment.tdl_treatment_type_id', '=', 'his_treatment_type.id')
                ->leftJoin('his_treatment_result', 'his_treatment.treatment_result_id', '=' ,'his_treatment_result.id')
                ->leftJoin('his_treatment_end_type', 'his_treatment.treatment_end_type_id', '=' ,'his_treatment_end_type.id')
                ->select('his_treatment.id','his_treatment.treatment_code','his_treatment.tdl_patient_name',
                    'his_patient.vir_dob_year','his_treatment.tdl_patient_gender_name','his_treatment.tdl_patient_type_id',
                    'his_patient_type.patient_type_name','his_treatment.tdl_patient_address','his_patient.phone',
                    'his_treatment_type.treatment_type_name','his_treatment.in_time','his_treatment.out_time',
                    'his_treatment.tdl_patient_career_name','his_treatment_result.treatment_result_name',
                    'his_treatment_end_type.treatment_end_type_name',
                    'his_treatment.tdl_patient_cmnd_number','his_treatment.tdl_patient_cccd_number',
                    'his_treatment.tdl_patient_passport_number','his_treatment.tdl_hein_card_number',
                    'his_treatment.icd_name', 'his_treatment.icd_text'
                )
                ->where('his_treatment.treatment_code', $param_madt)
                ->where(function($q) use ($param_phone){
                    $q->where('his_patient.phone', $param_phone)
                    ->orWhere('his_patient.mobile', $param_phone)
                    ->orWhere('his_patient.relative_mobile', $param_phone)
                    ->orWhere('his_patient.relative_phone', $param_phone)
                    ->orWhere('his_treatment.tdl_patient_phone', $param_phone)
                    ->orWhere('his_treatment.tdl_patient_mobile', $param_phone);
                })
                ->first();
            }

            if (!empty($treatment->id)) {
                $service_req_notStarted = DB::connection('HISPro')
                ->table('his_service_req')
                ->join('his_sere_serv', 'his_sere_serv.service_req_id', '=', 'his_service_req.id')
                ->join('his_execute_room', 'his_execute_room.room_id', '=', 'his_service_req.execute_room_id')
                ->join('his_room', 'his_room.id', '=', 'his_execute_room.room_id')
                ->join('his_service_req_type', 'his_service_req_type.id', '=', 'his_service_req.service_req_type_id')
                ->join('his_service_req_stt', 'his_service_req_stt.id', '=', 'his_service_req.service_req_stt_id')
                ->select('his_service_req_type.service_req_type_name','his_service_req_stt.service_req_stt_name',
                    'his_service_req_stt.service_req_stt_code','his_execute_room.execute_room_name','his_room.address',
                    'his_service_req.num_order','his_service_req.execute_room_id','his_service_req.intruction_time',
                    'his_sere_serv.tdl_service_name'
                )
                ->where('his_service_req.treatment_id', $treatment->id)
                ->where('his_service_req.is_active', 1)
                ->where('his_service_req.is_delete', 0)
                ->where('his_sere_serv.is_delete', 0)
                ->whereNull('his_sere_serv.is_expend')
                ->whereNull('his_sere_serv.is_no_execute')
                ->where('his_service_req.service_req_stt_id', '=', 1)
                ->whereNotIn('his_service_req.service_req_type_id', [6,7,11,14,15,16,17])
                ->orderBy('his_service_req.num_order')
                ->get();

                $service_req = DB::connection('HISPro')
                ->table('his_service_req')
                ->join('his_sere_serv', 'his_sere_serv.service_req_id', '=', 'his_service_req.id')
                ->join('his_execute_room', 'his_execute_room.room_id', '=', 'his_service_req.execute_room_id')
                ->join('his_room', 'his_room.id', '=', 'his_execute_room.room_id')
                ->join('his_service_req_type', 'his_service_req_type.id', '=', 'his_service_req.service_req_type_id')
                ->join('his_service_req_stt', 'his_service_req_stt.id', '=', 'his_service_req.service_req_stt_id')
                ->select('his_service_req_type.service_req_type_name','his_service_req_stt.service_req_stt_name',
                    'his_service_req_stt.service_req_stt_code','his_execute_room.execute_room_name','his_room.address',
                    'his_service_req.num_order','his_service_req.execute_room_id','his_sere_serv.tdl_service_name'
                )
                ->where('his_service_req.treatment_id', $treatment->id)
                ->where('his_service_req.is_active', 1)
                ->where('his_service_req.is_delete', 0)
                ->where('his_service_req.service_req_stt_id', '<>', 1)
                ->whereNotIn('his_service_req.service_req_type_id', [7,11])
                ->orderBy('his_service_req.service_req_code')
                ->get();

                $emr_document = DB::connection('EMR_RS')
                ->table('emr_document')
                ->leftJoin('emr_document_type', 'emr_document_type.id', '=', 'emr_document.document_type_id')
                ->select('emr_document.document_name','emr_document.document_code',
                    'emr_document_type.document_type_name','emr_document.his_code', 'emr_document.treatment_code')
                ->where('emr_document.is_delete', 0)
                ->where('emr_document.treatment_code', $treatment->treatment_code)
                ->where( function ($q) {
                    $q->whereIn('emr_document.document_type_id', [22, 160, 3])
                    ->orWhereNull('emr_document.document_type_id');
                })            
                ->get();
                     
                $service_kham = DB::connection('HISPro')
                ->table('his_service_req')
                ->leftJoin('his_dhst', 'his_dhst.id', '=', 'his_service_req.dhst_id')
                ->where('his_service_req.treatment_id', $treatment->id)
                ->where('his_service_req.is_active', 1)
                ->where('his_service_req.is_delete', 0)
                ->where('his_service_req.service_req_type_id', 1)
                ->first();

                $sere_serv_cdha = DB::connection('HISPro')  
                ->table('his_sere_serv')
                ->select('id', 'tdl_service_name')
                ->where('is_delete', 0)
                ->where('tdl_service_type_id', 3)
                ->where('tdl_treatment_id', $treatment->id)
                ->get();

                $sere_serv_chiphi = DB::connection('HISPro')  
                ->table('his_sere_serv')
                ->join('his_service_type', 'his_service_type.id', '=', 'his_sere_serv.tdl_service_type_id')
                ->selectRaw('sum(amount*price) as thanh_tien, sum(amount) as so_luong, service_type_name')
                ->where('his_sere_serv.is_delete', 0)
                ->whereNull('his_sere_serv.is_expend')
                ->whereNull('his_sere_serv.is_no_pay')
                ->whereNull('his_sere_serv.is_no_execute')
                ->where('tdl_treatment_id', $treatment->id)
                ->groupBy('service_type_name')
                ->get();
                //dd($sere_serv_chiphi);
                $tracuuhoadon = DB::connection('HISPro')
                ->table('his_transaction')
                ->select('invoice_sys','treatment_total_price','treatment_hein_price','treatment_patient_price',
                    'einvoice_time','invoice_lookup_code','amount','treatment_bill_amount')
                ->where('treatment_id', $treatment->id)
                ->whereNotNull('einvoice_num_order')
                ->whereNull('is_cancel_einvoice')
                ->whereNull('is_cancel')
                ->get();
                //dd($tracuuhoadon);

                $generator = new BarcodeGeneratorPNG();
                $barcode =  base64_encode($generator->getBarcode($param_madt, $generator::TYPE_CODE_128));

            }            
        } catch (\Exception $e) {
            return $e->getMessage();
        }

        return view('khth.view-guide-content',
            compact('param_madt','param_phone','treatment','service_req','emr_document','service_kham','sere_serv_cdha',
                'service_req_notStarted', 'countServiceReqNotStartByRoom','sere_serv_chiphi','tracuuhoadon','barcode')
        );
    }

    public function getStickyNote(Request $request)
    {
        if (!$request->ajax()) {
            return redirect()->route('home');
        }

        try {
            return sticky_note::where('note_name', 'khth')
            ->first();
        } catch (\Exception $e) {
            flash($e->getMessage())->overlay();
        }     
    }

    public function chartNhapvien(Request $request)
    {
        if (!$request->ajax()) {
            return redirect()->route('home');
        }

        try {
            $model = DB::connection('HISPro')
                ->table('his_treatment')
                ->join('his_department', 'his_treatment.last_department_id', '=', 'his_department.id')
                ->selectRaw('count(*) as so_luong,department_name')
                ->where('in_time', '>=', date("Ymd", mktime(0, 0, 0, date("m"), date("d"), date("Y"))) . '000000')
                ->where('in_time', '<=', date("Ymd", mktime(0, 0, 0, date("m"), date("d"), date("Y"))) . '235959')
                ->where('tdl_treatment_type_id', config('__tech.treatment_type_noitru'))
                ->groupBy('department_name')
                ->get();
            $sum_sl = $model->sum('so_luong');
            $sum_sl_khoa = $model->count('so_luong');

            $labels[] = '';
            $data[] = '';
            foreach ($model as $key => $value) {
                $labels[$key] = $value->department_name;
                $data[$key] = doubleval($value->so_luong);
            }
            $returnData[] = array(
                'type' => 'bar',
                'title' => 'Nhập viện theo khoa',
                'labels' => $labels,
                'datasets' => array(
                    array(
                        'data' => $data,
                        //'borderColor' => "rgb(255, 129, 232)",
                        'backgroundColor' => "rgb(176, 145, 57)",
                        'label' => "Tổng cộng: " . number_format($sum_sl) .' BN / ' . $sum_sl_khoa . ' khoa',
                        'fill' => false
                    ),
                )
            );  
            return json_encode($returnData);
        } catch (\Exception $e) {
            flash($e->getMessage())->overlay();
        }     
    }

    public function chartKham(Request $request)
    {
        if (!$request->ajax()) {
            return redirect()->route('home');
        }

        try {
            $model = DB::connection('HISPro')
                ->table('his_service_req')
                ->join('his_execute_room', 'his_execute_room.room_id', '=', 'his_service_req.execute_room_id')
                ->selectRaw('count(*) as so_luong,his_execute_room.execute_room_name')
                ->where('intruction_time', '>=', date("Ymd", mktime(0, 0, 0, date("m"), date("d"), date("Y"))) . '000000')
                ->where('intruction_time', '<=', date("Ymd", mktime(0, 0, 0, date("m"), date("d"), date("Y"))) . '235959')
                ->where('his_service_req.service_req_type_id', 1)
                ->where('his_service_req.service_req_stt_id', 1)
                ->where('his_service_req.is_active', 1)
                ->where('his_service_req.is_delete', 0)
                ->groupBy('execute_room_name')
                ->get();

            $model_dangthuchien = DB::connection('HISPro')
                ->table('his_service_req')
                ->join('his_execute_room', 'his_execute_room.room_id', '=', 'his_service_req.execute_room_id')
                ->selectRaw('count(*) as so_luong,his_execute_room.execute_room_name')
                ->where('intruction_time', '>=', date("Ymd", mktime(0, 0, 0, date("m"), date("d"), date("Y"))) . '000000')
                ->where('intruction_time', '<=', date("Ymd", mktime(0, 0, 0, date("m"), date("d"), date("Y"))) . '235959')
                ->where('his_service_req.service_req_type_id', 1)
                ->where('his_service_req.service_req_stt_id', 2)
                ->where('his_service_req.is_active', 1)
                ->where('his_service_req.is_delete', 0)
                ->groupBy('execute_room_name')
                ->get();

            $model_hoanthanh = DB::connection('HISPro')
                ->table('his_service_req')
                ->join('his_execute_room', 'his_execute_room.room_id', '=', 'his_service_req.execute_room_id')
                ->selectRaw('count(*) as so_luong,his_execute_room.execute_room_name')
                ->where('intruction_time', '>=', date("Ymd", mktime(0, 0, 0, date("m"), date("d"), date("Y"))) . '000000')
                ->where('intruction_time', '<=', date("Ymd", mktime(0, 0, 0, date("m"), date("d"), date("Y"))) . '235959')
                ->where('his_service_req.service_req_type_id', 1)
                ->where('his_service_req.service_req_stt_id', 3)
                ->where('his_service_req.is_active', 1)
                ->where('his_service_req.is_delete', 0)
                ->groupBy('execute_room_name')
                ->get();

            $labels[] = '';
            $data[] = '';
            $data_hoanthanh[] = '';
            $data_dangthuchien[] = '';

            //return $model_hoanthanh;
            foreach ($model_hoanthanh as $key => $value) {
                $data_hoanthanh[$key] = doubleval($value->so_luong);
            }
            foreach ($model_dangthuchien as $key => $value) {
                $data_dangthuchien[$key] = doubleval($value->so_luong);
            }
            foreach ($model as $key => $value) {
                $labels[$key] = $value->execute_room_name;
                $data[$key] = doubleval($value->so_luong);
            }
            $returnData[] = array(
                'type' => 'bar',
                'title' => 'Phòng khám: ' . number_format($model->sum('so_luong') + $model_dangthuchien->sum('so_luong') + $model_hoanthanh->sum('so_luong')),
                'labels' => $labels,
                'datasets' => array(
                    array(
                        'data' => $data,
                        //'borderColor' => "rgb(128, 123, 187)",
                        'backgroundColor' => "rgb(77, 121, 255)",
                        'label' => "Chờ khám: " . number_format($model->sum('so_luong')),
                        'fill' => false
                    ),
                    array(
                        'data' => $data_dangthuchien,
                        //'borderColor' => "rgb(128, 123, 187)",
                        'backgroundColor' => "rgb(204, 204, 0)",
                        'label' => "Đang khám: " . number_format($model_dangthuchien->sum('so_luong')),
                        'fill' => false
                    ),
                    array(
                        'data' => $data_hoanthanh,
                        //'borderColor' => "rgb(128, 123, 187)",
                        'backgroundColor' => "rgb(204, 0, 0)",
                        'label' => "Hoàn thành: " . number_format($model_hoanthanh->sum('so_luong')),
                        'fill' => false
                    ),
                )
            );  
            return json_encode($returnData);
        } catch (\Exception $e) {
            flash($e->getMessage())->overlay();
        }     
    }

    public function chartXetnghiem(Request $request)
    {
        if (!$request->ajax()) {
            return redirect()->route('home');
        }

        try {
            $model = DB::connection('HISPro')
                ->table('his_sere_serv')
                ->join('his_service_req', 'his_service_req.id', '=', 'his_sere_serv.service_req_id')
                ->join('his_service_req_stt', 'his_service_req_stt.id', '=', 'his_service_req.service_req_stt_id')
                ->selectRaw('sum(amount) as so_luong,his_service_req_stt.service_req_stt_name')
                ->where('his_sere_serv.tdl_intruction_time', '>=', 
                    date("Ymd", mktime(0, 0, 0, date("m"), date("d"), date("Y"))) . '000000')
                ->where('his_sere_serv.tdl_intruction_time', '<=', 
                    date("Ymd", mktime(0, 0, 0, date("m"), date("d"), date("Y"))) . '235959')
                ->where('his_sere_serv.is_delete', 0)
                ->where('his_sere_serv.tdl_service_type_id', 2)
                ->groupBy('service_req_stt_name')
                ->get();
            $sum_sl = $model->sum('so_luong');

            $labels[] = '';
            $data[] = '';
            foreach ($model as $key => $value) {
                $labels[$key] = $value->service_req_stt_name;
                $data[$key] = doubleval($value->so_luong);
            }
            $returnData[] = array(
                'type' => 'bar',
                'title' => 'Xét nghiệm',
                'labels' => $labels,
                'datasets' => array(
                    array(
                        'data' => $data,
                        //'borderColor' => "rgb(255, 129, 232)",
                        'backgroundColor' => "rgb(176, 145, 57)",
                        'label' => "Tổng cộng: " . number_format($sum_sl),
                        'fill' => false
                    ),
                )
            );  
            return json_encode($returnData);
        } catch (\Exception $e) {
            return $e->getMessage();
        }     
    }

    public function chartPttt(Request $request)
    {
        if (!$request->ajax()) {
            return redirect()->route('home');
        }

        try {
            $model = DB::connection('HISPro')
                ->table('his_sere_serv')
                ->join('his_service_req', 'his_service_req.id', '=', 'his_sere_serv.service_req_id')
                ->join('his_service_req_stt', 'his_service_req_stt.id', '=', 'his_service_req.service_req_stt_id')
                ->selectRaw('sum(amount) as so_luong,his_service_req_stt.service_req_stt_name')
                ->where('his_sere_serv.tdl_intruction_time', '>=', 
                    date("Ymd", mktime(0, 0, 0, date("m"), date("d"), date("Y"))) . '000000')
                ->where('his_sere_serv.tdl_intruction_time', '<=', 
                    date("Ymd", mktime(0, 0, 0, date("m"), date("d"), date("Y"))) . '235959')
                ->where('his_sere_serv.is_delete', 0)
                ->whereIn('his_sere_serv.tdl_service_type_id', [4,11])
                ->groupBy('service_req_stt_name')
                ->get();
            $sum_sl = $model->sum('so_luong');

            $labels[] = '';
            $data[] = '';
            foreach ($model as $key => $value) {
                $labels[$key] = $value->service_req_stt_name;
                $data[$key] = doubleval($value->so_luong);
            }
            $returnData[] = array(
                'type' => 'bar',
                'title' => 'PTTT',
                'labels' => $labels,
                'datasets' => array(
                    array(
                        'data' => $data,
                        //'borderColor' => "rgb(255, 129, 232)",
                        'backgroundColor' => "rgb(77, 121, 255)",
                        'label' => "Tổng cộng: " . number_format($sum_sl),
                        'fill' => false
                    ),
                )
            );  
            return json_encode($returnData);
        } catch (\Exception $e) {
            return $e->getMessage();
        }     
    }

    public function chartCls(Request $request)
    {
        if (!$request->ajax()) {
            return redirect()->route('home');
        }

        try {
            $model_chuathuchien = DB::connection('HISPro')
            ->table('his_service_req')
            ->join('his_execute_room', 'his_execute_room.room_id', '=', 'his_service_req.execute_room_id')
            ->selectRaw('count(*) as so_luong,his_execute_room.execute_room_name')
            ->where('intruction_time', '>=', date("Ymd", mktime(0, 0, 0, date("m"), date("d"), date("Y"))) . '000000')
            ->where('intruction_time', '<=', date("Ymd", mktime(0, 0, 0, date("m"), date("d"), date("Y"))) . '235959')
            ->whereIn('his_service_req.service_req_type_id', [3, 5, 8, 9])
            ->where('his_service_req.service_req_stt_id', 1)
            ->where('his_service_req.is_active', 1)
            ->where('his_service_req.is_delete', 0)
            ->groupBy('execute_room_name')
            ->get();

            $model_dangthuchien = DB::connection('HISPro')
            ->table('his_service_req')
            ->join('his_execute_room', 'his_execute_room.room_id', '=', 'his_service_req.execute_room_id')
            ->selectRaw('count(*) as so_luong,his_execute_room.execute_room_name')
            ->where('intruction_time', '>=', date("Ymd", mktime(0, 0, 0, date("m"), date("d"), date("Y"))) . '000000')
            ->where('intruction_time', '<=', date("Ymd", mktime(0, 0, 0, date("m"), date("d"), date("Y"))) . '235959')
            ->whereIn('his_service_req.service_req_type_id', [3, 5, 8, 9])
            ->where('his_service_req.service_req_stt_id', 2)
            ->where('his_service_req.is_active', 1)
            ->where('his_service_req.is_delete', 0)
            ->groupBy('execute_room_name')
            ->get();

            $model_hoanthanh = DB::connection('HISPro')
            ->table('his_service_req')
            ->join('his_execute_room', 'his_execute_room.room_id', '=', 'his_service_req.execute_room_id')
            ->selectRaw('count(*) as so_luong,his_execute_room.execute_room_name')
            ->where('intruction_time', '>=', date("Ymd", mktime(0, 0, 0, date("m"), date("d"), date("Y"))) . '000000')
            ->where('intruction_time', '<=', date("Ymd", mktime(0, 0, 0, date("m"), date("d"), date("Y"))) . '235959')
            ->whereIn('his_service_req.service_req_type_id', [3, 5, 8, 9])
            ->where('his_service_req.service_req_stt_id', 3)
            ->where('his_service_req.is_active', 1)
            ->where('his_service_req.is_delete', 0)
            ->groupBy('execute_room_name')
            ->get();

            $labels[] = '';
            $data_chuathuchien[] = '';
            $data_dangthuchien[] = '';
            $data_hoanthanh[] = '';

            foreach ($model_dangthuchien as $key => $value) {
                $data_dangthuchien[$key] = doubleval($value->so_luong);
            }
            foreach ($model_hoanthanh as $key => $value) {
                $data_hoanthanh[$key] = doubleval($value->so_luong);
            }
            foreach ($model_chuathuchien as $key => $value) {
                $labels[$key] = $value->execute_room_name;
                $data_chuathuchien[$key] = doubleval($value->so_luong);
            }

            $returnData[] = array(
                'type' => 'bar',
                'title' => 'Cận lâm sàng',
                'labels' => $labels,
                'datasets' => array(
                    array(
                        'data' => $data_chuathuchien,
                        //'borderColor' => "rgb(128, 123, 187)",
                        'backgroundColor' => "rgb(77, 121, 255)",
                        'label' => "Chờ CLS: " . number_format($model_chuathuchien->sum('so_luong')),
                        'fill' => false
                    ),
                    array(
                        'data' => $data_dangthuchien,
                        //'borderColor' => "rgb(128, 123, 187)",
                        'backgroundColor' => "rgb(204, 204, 0)",
                        'label' => "Đang làm: " . number_format($model_dangthuchien->sum('so_luong')),
                        'fill' => false
                    ),
                    array(
                        'data' => $data_hoanthanh,
                        //'borderColor' => "rgb(128, 123, 187)",
                        'backgroundColor' => "rgb(204, 0, 0)",
                        'label' => "Hoàn thành: " . number_format($model_hoanthanh->sum('so_luong')),
                        'fill' => false
                    ),
                )
            );  
            return json_encode($returnData);
        } catch (\Exception $e) {
            return $e->getMessage();
        }     
    }

    public function BNSarCov2Index(Request $request)
    {
        return view('khth.BNSarCov2Index');
    } 

    public function getsarcov2(Request $request)
    {
        if (!$request->ajax()) {
            return redirect()->route('home');
        }

        try {
            $model = DB::connection('HISPro')
            ->table('his_treatment_bed_room')
            ->join('his_bed_room','his_treatment_bed_room.bed_room_id','=','his_bed_room.id')
            ->join('his_room','his_bed_room.room_id','=','his_room.id')
            ->join('his_department','his_room.department_id','=','his_department.id')
            ->join('his_treatment','his_treatment_bed_room.treatment_id','=','his_treatment.id')
            ->join('his_patient','his_treatment.patient_id','=','his_patient.id')
            ->leftjoin('his_co_treatment','his_treatment_bed_room.co_treatment_id','=','his_co_treatment.id')
            ->selectRaw('count(*) as so_luong,his_department.department_name')
            ->whereNull('his_treatment_bed_room.remove_time')
            ->whereNull('his_co_treatment.id')
            ->where('his_bed_room.is_active', 1)
            ->where('his_room.is_active', 1)
            ->whereIn('his_treatment.tdl_treatment_type_id', [2,3])
            ->where('his_treatment_bed_room.is_delete', 0)
            ->where('his_patient.patient_classify_id', 2)
            ->groupBy('his_department.department_name')
            ->orderBy('so_luong','desc')
            ->get();
            
            $labels[] = '';
            $data[] = '';
            foreach ($model as $key => $value) {
                $labels[$key] = $value->department_name;
                $data[$key] = doubleval($value->so_luong);
            }
            $returnData[] = array(
                'type' => 'bar',
                'title' => 'BN đang điều trị (+) SAR-COV-2',
                'labels' => $labels,
                'datasets' => array(
                    array(
                        'data' => $data,
                        //'borderColor' => "rgb(255, 129, 232)",
                        'backgroundColor' => "rgb(77, 121, 255)",
                        'label' => "Tổng cộng: " . number_format($model->sum('so_luong')),
                        'fill' => false
                    ),
                )
            );  
            return json_encode($returnData);
        } catch (\Exception $e) {
            return $e->getMessage();
        }     
    }


    public function get_result(Request $request)
    {
        if (!$request->ajax()) {
            return redirect()->route('home');
        }
        
        $model = DB::table('sarcov2_ctu');
        
        return DataTables::of($model)
        ->addColumn('action', function ($result) {
            return '<button class="btn btn-sm btn-primary view-modal"'
                .'data-id="' .$result->id .'"><span class="glyphicon glyphicon-eye-open"></span> Chi tiết</button>';
        })
        ->toJson();
    }

    public function get_sarcov2_ct(Request $request)
    {
        if (!$request->ajax()) {
            return redirect()->route('home');
        }  

        if (!$request->id) {
            return ['maKetqua' => '500',
                'noiDung' => 'Có lỗi trong quá trình xử lý!!!'];
        }
        
        try {
            $result = DB::table('sarcov2_ct')
            ->where('sarcov2_ctu_id', $request->id)
            ->get();

            $res = '';
            foreach ($result as $key => $value) {
                $res = $res .'<tr>';
                $res = $res .'<td>' .($key+1) .'</td>';
                $res = $res .'<td>' .$value->ten_khoa .'</td>';
                $res = $res .'<td>' .$value->so_luong .'</td>';
                $res = $res .'</tr>';
            }      
        } catch (\Exception $e) {
            return ['maKetqua' => '500',
                'noiDung' => $e->getMessage()];                
        }

        return $res;
    }

    public function thongkein(Request $request)
    {
        return view('khth.thong-ke-in');
    }

    public function patientChart(Request $request)
    {
        $tu_ngay = $request->input('tu_ngay');
        $den_ngay = $request->input('den_ngay');
        $date_type = $request->input('date_type');

        if (!$request->ajax()) {
            return redirect()->route('home');
        }

        $client = new Client();

        try {
            
            // Gọi API và lấy phản hồi, thêm các tham số truy vấn vào URL
            $url = 'http://localhost:5000/get_patient_exam_base64';
            $response = $client->request('GET', $url, [
                'query' => [
                    'tu_ngay' => $tu_ngay,
                    'den_ngay' => $den_ngay,
                    'date_type' => $date_type,
                ]
            ]);

            // Kiểm tra mã trạng thái HTTP để đảm bảo request thành công
            if ($response->getStatusCode() == 200) {
                // Lấy nội dung phản hồi và decode JSON
                $data = json_decode($response->getBody()->getContents(), true);

                // Giả sử bạn muốn lấy dữ liệu ảnh base64 và làm gì đó với nó
                $imageData = $data;

                // Làm gì đó với $imageData, ví dụ, trả về cho view
                return $imageData;
            } else {
                // Xử lý lỗi
                return response()->json(['error' => 'Không thể lấy dữ liệu từ API'], 500);
            }
        } catch (\GuzzleHttp\Exception\GuzzleException $e) {
            // Xử lý exception
            return response()->json(['error' => 'Lỗi khi gọi API'], 500);
        }
    }

    public function thongkenoitru(Request $request)
    {
        return view('khth.thong-ke-noitru');
    }

    public function inpatientProcessing(Request $request)
    {
        $tu_ngay = $request->input('tu_ngay');
        $den_ngay = $request->input('den_ngay');
        $date_type = $request->input('date_type');

        if (!$request->ajax()) {
            return redirect()->route('home');
        }

        $client = new Client();

        try {
            
            // Gọi API và lấy phản hồi, thêm các tham số truy vấn vào URL
            $url = 'http://localhost:5000/get_inpatient_base64';
            $response = $client->request('GET', $url, [
                'query' => [
                    'tu_ngay' => $tu_ngay,
                    'den_ngay' => $den_ngay,
                    'date_type' => $date_type,
                ]
            ]);

            // Kiểm tra mã trạng thái HTTP để đảm bảo request thành công
            if ($response->getStatusCode() == 200) {
                // Lấy nội dung phản hồi và decode JSON
                $data = json_decode($response->getBody()->getContents(), true);

                // Giả sử bạn muốn lấy dữ liệu ảnh base64 và làm gì đó với nó
                $imageData = $data;

                // Làm gì đó với $imageData, ví dụ, trả về cho view
                return $imageData;
            } else {
                // Xử lý lỗi
                return response()->json(['error' => 'Không thể lấy dữ liệu từ API'], 500);
            }
        } catch (\GuzzleHttp\Exception\GuzzleException $e) {
            // Xử lý exception
            return response()->json(['error' => 'Lỗi khi gọi API'], 500);
        }
    }

   public function getrevenue(Request $request)
    {
        $tu_ngay = $request->input('tu_ngay');
        $den_ngay = $request->input('den_ngay');
        $date_type = $request->input('date_type');
        $kieu_thong_ke = $request->input('kieu_thong_ke');

        if (!$request->ajax()) {
            return redirect()->route('home');
        }

        $client = new Client();

        try {
            
            // Gọi API và lấy phản hồi, thêm các tham số truy vấn vào URL
            $url = 'http://localhost:5000/get_revenue_base64';
            $response = $client->request('GET', $url, [
                'query' => [
                    'tu_ngay' => $tu_ngay,
                    'den_ngay' => $den_ngay,
                    'date_type' => $date_type,
                    'kieu_thong_ke' => $kieu_thong_ke,
                ]
            ]);

            // Kiểm tra mã trạng thái HTTP để đảm bảo request thành công
            if ($response->getStatusCode() == 200) {
                // Lấy nội dung phản hồi và decode JSON
                $data = json_decode($response->getBody()->getContents(), true);

                // Giả sử bạn muốn lấy dữ liệu ảnh base64 và làm gì đó với nó
                $imageData = $data;

                // Làm gì đó với $imageData, ví dụ, trả về cho view
                return $imageData;
            } else {
                // Xử lý lỗi
                return response()->json(['error' => 'Không thể lấy dữ liệu từ API'], 500);
            }
        } catch (\GuzzleHttp\Exception\GuzzleException $e) {
            // Xử lý exception
            return response()->json(['error' => 'Lỗi khi gọi API'], 500);
        }
    }

    public function thongkedoanhthu(Request $request)
    {
        return view('khth.thong-ke-doanh-thu');
    }

    public function giatangchiphi(Request $request)
    {
        return view('khth.gia-tang-chi-phi');
    }

    public function fetchChiphiND75(Request $request)
    {

        $tuNgay = $request->input('tu_ngay');
        $denNgay = $request->input('den_ngay');
        $kieuThongKe = $request->input('kieu_thong_ke');

        // Chuyển đổi ngày tháng sang định dạng phù hợp cho SQL
        $tuNgayFormatted = date('YmdHis', strtotime($tuNgay));
        $denNgayFormatted = date('YmdHis', strtotime($denNgay));

        // Tính toán khoảng thời gian cho last_data
        $lastTuNgayFormatted = '';
        $lastDenNgayFormatted = '';

        switch ($kieuThongKe) {
            case 'tuan':
                $lastTuNgayFormatted = date('YmdHis', strtotime($tuNgay . ' -7 days'));
                $lastDenNgayFormatted = date('YmdHis', strtotime($denNgay . ' -7 days'));
                break;
            case 'thang':
                // Lấy ngày đầu tiên của tháng hiện tại
                $firstDayOfCurrentMonth = date('Y-m-01', strtotime($tuNgay));
                // Lấy ngày đầu tiên của tháng trước
                $firstDayOfLastMonth = date('Y-m-01', strtotime($firstDayOfCurrentMonth . ' -1 month'));
                // Lấy ngày cuối cùng của tháng trước
                $lastDayOfLastMonth = date('Y-m-t', strtotime($firstDayOfLastMonth));

                $lastTuNgayFormatted = date('YmdHis', strtotime($firstDayOfLastMonth));
                $lastDenNgayFormatted = date('YmdHis', strtotime($lastDayOfLastMonth));
                break;
            case 'nam':
                $lastTuNgayFormatted = date('YmdHis', strtotime($tuNgay . ' -1 year'));
                $lastDenNgayFormatted = date('YmdHis', strtotime($denNgay . ' -1 year'));
                break;
            default:
                // Mặc định xử lý cho trường hợp khác nếu có
                $firstDayOfCurrentMonth = date('Y-m-01', strtotime($tuNgay));
                // Lấy ngày đầu tiên của tháng trước
                $firstDayOfLastMonth = date('Y-m-01', strtotime($firstDayOfCurrentMonth . ' -1 month'));
                // Lấy ngày cuối cùng của tháng trước
                $lastDayOfLastMonth = date('Y-m-t', strtotime($firstDayOfLastMonth));

                $lastTuNgayFormatted = date('YmdHis', strtotime($firstDayOfLastMonth));
                $lastDenNgayFormatted = date('YmdHis', strtotime($lastDayOfLastMonth));
                break;
        }

        // Tạo truy vấn SQL
        $query = "
        WITH this_data AS (
            SELECT 
                re_dept.department_name AS deptname,
                st.hein_service_type_code AS stc,
                ss.amount * ss.price AS q
            FROM 
                his_sere_serv ss
            JOIN 
                his_service_req sr ON sr.id = ss.service_req_id
            JOIN 
                his_hein_service_type st ON st.id = ss.tdl_hein_service_type_id
            JOIN 
                his_treatment tm ON tm.id = sr.treatment_id
            JOIN 
                his_treatment_type tt ON tt.id = tm.tdl_treatment_type_id
            LEFT JOIN 
                his_department re_dept ON re_dept.id = ss.tdl_request_department_id
            WHERE 
                sr.is_delete = 0
                AND ss.is_delete = 0
                AND ss.is_expend is null
                AND ss.is_no_pay is null
                AND ss.is_no_execute is null
                AND ss.patient_type_id = 1
                AND tm.out_date BETWEEN $tuNgayFormatted AND $denNgayFormatted
        ),
        this_data_aggregated AS (
            SELECT 
                deptname,
                SUM(CASE WHEN stc in('XN') THEN q ELSE 0 END) AS t_xn,
                SUM(CASE WHEN stc in ('CDHA') THEN q ELSE 0 END) AS t_cdha,
                SUM(CASE WHEN stc in ('TH_TDM','TH_TL') THEN q ELSE 0 END) AS t_thuoc,
                SUM(CASE WHEN stc in ('CPM') THEN q ELSE 0 END) AS t_mau,
                SUM(CASE WHEN stc in ('TT') THEN q ELSE 0 END) AS t_tt,
                SUM(CASE WHEN stc in ('VT_TDM','VT_TL') THEN q ELSE 0 END) AS t_vtyt,
                SUM(CASE WHEN stc in ('TDCN') THEN q ELSE 0 END) AS t_tdcn,
                SUM(CASE WHEN stc in ('PT') THEN q ELSE 0 END) AS t_pt,
                SUM(CASE WHEN stc in ('KH') THEN q ELSE 0 END) AS t_kh,
                SUM(CASE WHEN stc in ('GI_NGT','GI_BN','GI_NT','GI_L') THEN q ELSE 0 END) AS t_gi
            FROM 
                this_data
            GROUP BY 
                deptname
        ),
        -- Chi phí kỳ trước
        last_data AS (
            SELECT 
                re_dept.department_name AS deptname,
                st.hein_service_type_code AS stc,
                ss.amount * ss.price AS q
            FROM 
                his_sere_serv ss
            JOIN 
                his_service_req sr ON sr.id = ss.service_req_id
            JOIN 
                his_hein_service_type st ON st.id = ss.tdl_hein_service_type_id
            JOIN 
                his_treatment tm ON tm.id = sr.treatment_id
            JOIN 
                his_treatment_type tt ON tt.id = tm.tdl_treatment_type_id
            LEFT JOIN 
                his_department re_dept ON re_dept.id = ss.tdl_request_department_id
            WHERE 
                sr.is_delete = 0
                AND ss.is_delete = 0
                AND ss.is_expend is null
                AND ss.is_no_pay is null
                AND ss.is_no_execute is null
                AND ss.patient_type_id = 1
                AND tm.out_date BETWEEN $lastTuNgayFormatted AND $lastDenNgayFormatted
        ),
        last_data_aggregated AS (
            SELECT 
                deptname,
                SUM(CASE WHEN stc in('XN') THEN q ELSE 0 END) AS t_xn,
                SUM(CASE WHEN stc in ('CDHA') THEN q ELSE 0 END) AS t_cdha,
                SUM(CASE WHEN stc in ('TH_TDM','TH_TL') THEN q ELSE 0 END) AS t_thuoc,
                SUM(CASE WHEN stc in ('CPM') THEN q ELSE 0 END) AS t_mau,
                SUM(CASE WHEN stc in ('TT') THEN q ELSE 0 END) AS t_tt,
                SUM(CASE WHEN stc in ('VT_TDM','VT_TL') THEN q ELSE 0 END) AS t_vtyt,
                SUM(CASE WHEN stc in ('TDCN') THEN q ELSE 0 END) AS t_tdcn,
                SUM(CASE WHEN stc in ('PT') THEN q ELSE 0 END) AS t_pt,
                SUM(CASE WHEN stc in ('KH') THEN q ELSE 0 END) AS t_kh,
                SUM(CASE WHEN stc in ('GI_NGT','GI_BN','GI_NT','GI_L') THEN q ELSE 0 END) AS t_gi
            FROM 
                last_data
            GROUP BY 
                deptname
        )
        SELECT 
            this_data_aggregated.deptname,
            this_data_aggregated.t_xn AS cost_this_data_t_xn,
            last_data_aggregated.t_xn AS cost_last_data_t_xn,
            this_data_aggregated.t_cdha AS cost_this_data_t_cdha,
            last_data_aggregated.t_cdha AS cost_last_data_t_cdha,
            this_data_aggregated.t_thuoc AS cost_this_data_t_thuoc,
            last_data_aggregated.t_thuoc AS cost_last_data_t_thuoc,
            this_data_aggregated.t_mau AS cost_this_data_t_mau,
            last_data_aggregated.t_mau AS cost_last_data_t_mau,
            this_data_aggregated.t_tt AS cost_this_data_t_tt,
            last_data_aggregated.t_tt AS cost_last_data_t_tt,
            this_data_aggregated.t_vtyt AS cost_this_data_t_vtyt,
            last_data_aggregated.t_vtyt AS cost_last_data_t_vtyt,
            this_data_aggregated.t_tdcn AS cost_this_data_t_tdcn,
            last_data_aggregated.t_tdcn AS cost_last_data_t_tdcn,
            this_data_aggregated.t_pt AS cost_this_data_t_pt,
            last_data_aggregated.t_pt AS cost_last_data_t_pt,
            this_data_aggregated.t_kh AS cost_this_data_t_kh,
            last_data_aggregated.t_kh AS cost_last_data_t_kh,
            this_data_aggregated.t_gi AS cost_this_data_t_gi,
            last_data_aggregated.t_gi AS cost_last_data_t_gi
        FROM 
            this_data_aggregated
        LEFT JOIN 
            last_data_aggregated ON this_data_aggregated.deptname = last_data_aggregated.deptname
        ";
        //return $query;
        // Thực hiện truy vấn và lấy kết quả
        $result = DB::connection('HISPro')
        ->select(DB::raw($query));

        return view('khth.partials.chiphi_nd75', ['data' => $result]);

    }

}

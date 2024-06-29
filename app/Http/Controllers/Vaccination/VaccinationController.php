<?php

namespace App\Http\Controllers\Vaccination;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

use DB;
use Yajra\Datatables\Datatables;
use Carbon\Carbon;

use Validator;
use Response;

use App\Patient;
use App\Vaccination;
use App\Vaccine;
use App\PreVaccinationCheck;

class VaccinationController extends Controller
{
    protected $rules =
        [
            'ngay_thang' => 'required',
        ];

    private function registerVaccination($patient_code)
    {
        // Lấy thông tin bệnh nhân từ cơ sở dữ liệu HIS (giả sử đây là cách lấy dữ liệu)
        $patientInfo = DB::connection('HISPro')->table('HIS_PATIENT')
                         ->where('patient_code', $patient_code)
                         ->first();

        if (!$patientInfo) {
            return $patient_code;
        }

        $contact_info = $patientInfo->mobile 
            ?? $patientInfo->phone 
            ?? $patientInfo->relative_mobile 
            ?? $patientInfo->relative_phone 
            ?? '';

        // Kiểm tra bệnh nhân đã có trong bảng patients chưa
        $existingPatient = Patient::where('code', $patientInfo->patient_code)->first();

        if (!$existingPatient) {
            // Thêm bệnh nhân vào bảng patients nếu chưa có
            Patient::create([
                'code' => $patient_code,
                'name' => $patientInfo->vir_patient_name,
                'date_of_birth' => $patientInfo->dob,
                'gender' => $patientInfo->gender_id,
                'contact_info' => $contact_info,
                'address' => $patientInfo->vir_address
            ]);
        }
        return Patient::where('code', $patientInfo->patient_code)->first();
    }

    private function registerVaccine($treatment_code)
    {
        if (!$treatment_code) {
            return $treatment_code;
        }
        // Lấy thông tin vacxin từ cơ sở dữ liệu HIS (giả sử đây là cách lấy dữ liệu)
        $vaccineInfo = DB::connection('HISPro')->table('HIS_SERE_SERV')
                         ->where('tdl_treatment_code', $treatment_code)
                         ->whereNotNull('tdl_is_vaccine')
                         ->get();

        if (!$vaccineInfo) {
            return $treatment_code;
        }

        foreach ($vaccineInfo as $info) {
            // Kiểm tra nếu bảng vaccine chưa tồn tại code = tdl_service_code
            $existingVaccine = Vaccine::where('code', $info->tdl_service_code)->first();

            if (!$existingVaccine) {
                // Thêm mới vào bảng vaccines
                Vaccine::create([
                    'code' => $info->tdl_service_code,
                    'name' => $info->tdl_service_name,
                    // Các trường khác có thể bổ sung ở đây nếu cần thiết
                ]);
            }
        }

        return $treatment_code;
        
    }

    public function index(Request $request)
    {
    	return view('vaccination.index');
    }

    public function fetchVaccinations(Request $request)
    {
        if (!$request->ajax()) {
            return redirect()->route('home');
        }
        
        $query = Vaccination::with(['vaccine', 'patient'])->select('vaccinations.*');

        // Apply date range filter if provided
        if ($request->has('date_from') && $request->date_from != '') {
            $dateFrom = Carbon::parse($request->date_from)->format('Y-m-d');
            $query->whereDate('date_of_vaccination', '>=', $dateFrom);
        }

        if ($request->has('date_to') && $request->date_to != '') {
            $dateTo = Carbon::parse($request->date_to)->format('Y-m-d');
            $query->whereDate('date_of_vaccination', '<=', $dateTo);
        }
        
        return DataTables::of($query)
        ->addIndexColumn() // This will add the 'stt' column
        ->addColumn('vaccine_type', function($row) {
            return $row->vaccine->name;
        })
        ->addColumn('patient_name', function($row) {
            return $row->patient->name;
        })
        ->addColumn('actions', function($row){
            $editUrl = route('vaccination.edit', $row->id);
            $detailUrl = route('vaccination.data', ['patient_code'=>$row->patient->code]);
            return '<a href="'.$editUrl.'" class="btn btn-sm btn-primary">Edit</a>
                    <a href="'.$detailUrl .'" class="btn btn-sm btn-info">
                                    <span class="glyphicon glyphicon-plus"></span> Chi tiết </a>';
        })
        ->rawColumns(['actions']) // Ensure actions column is treated as raw HTML
        ->make(true);
    }

    public function storeVaccination(Request $request, $patient_id)
    {
        $request->validate([
            'vaccine_id' => 'required|exists:vaccines,id',
            'date_of_vaccination' => 'required|date',
            'dose_number' => 'required|integer',
            'administered_amount' => 'required|string',
            'administered_by' => 'nullable|string',
            'description_effect' => 'nullable|string',
            'severity_effect' => 'nullable|string',
            'date_noted_effect' => 'nullable|date'
        ]);

        Vaccination::create([
            'patient_id' => $patient_id,
            'vaccine_id' => $request->vaccine_id,
            'date_of_vaccination' => $request->date_of_vaccination,
            'dose_number' => $request->dose_number,
            'administered_amount' => $request->administered_amount,
            'administered_by' => $request->administered_by,
            'description_effect' => $request->description_effect,
            'severity_effect' => $request->severity_effect,
            'date_noted_effect' => $request->date_noted_effect
        ]);

        return redirect()->route('vaccination.data', ['patient_code' => $request->patient_code])->with('success', 'Thông tin tiêm chủng đã được thêm.');
    }

    public function dataVaccin(Request $request)
    {
        $patient = $this->registerVaccination($request->get('patient_code'));
        $this->registerVaccine($request->get('treatment_code'));
        if (!$patient) {
            return redirect()->route('patients.index');
        }
        $vaccinations = Vaccination::where('patient_id', $patient->id)
        ->with('vaccine')
        ->get();

        $prevaccinations = PreVaccinationCheck::where('patient_id', $patient->id)
        ->get();

        return view('vaccination.vaccindata', compact('patient', 'vaccinations', 'prevaccinations'));
    }

    public function createVaccination($patient_id)
    {
        $patient = Patient::find($patient_id);
        $vaccines = Vaccine::all(); // Lấy danh sách vắc xin
        $doctors = DB::connection('HISPro')
        ->table('HIS_EMPLOYEE')
        ->get(); //Lấy danh sách Bác sĩ

        return view('vaccination.create', compact('patient', 'vaccines', 'doctors'));
    }

    public function editVaccination($id)
    {
        $vaccination = Vaccination::find($id);
        
        if (!$vaccination) {
            return redirect()->route('vaccination.index')->with('error', 'Thông tin tiêm chủng không tồn tại.');
        }

        $vaccines = Vaccine::all();
        $doctors = DB::connection('HISPro')
        ->table('HIS_EMPLOYEE')
        ->get(); //Lấy danh sách Bác sĩ

        return view('vaccination.edit', compact('vaccination', 'vaccines', 'doctors'));
    }

    public function destroyVaccination($id)
    {
        $vaccination = Vaccination::find($id);
        $vaccination->delete();

        return redirect()->route('vaccination.data', ['patient_code' => $vaccination->patient->code])->with('success', 'Thông tin tiêm chủng đã được xóa.');
    }

    public function updateVaccination(Request $request, $id)
    {
        $request->validate([
            'vaccine_id' => 'required|exists:vaccines,id',
            'date_of_vaccination' => 'required|date',
            'dose_number' => 'required|integer',
            'administered_amount' => 'required|string',
            'administered_by' => 'nullable|string',
            'description_effect' => 'nullable|string',
            'severity_effect' => 'nullable|string',
            'date_noted_effect' => 'nullable|date'
        ]);

        $vaccination = Vaccination::find($id);
        
        if (!$vaccination) {
            return redirect()->route('vaccination.index')->with('error', 'Thông tin tiêm chủng không tồn tại.');
        }

        $vaccination->update([
            'vaccine_id' => $request->vaccine_id,
            'date_of_vaccination' => $request->date_of_vaccination,
            'dose_number' => $request->dose_number,
            'administered_amount' => $request->administered_amount,
            'administered_by' => $request->administered_by,
            'description_effect' => $request->description_effect,
            'severity_effect' => $request->severity_effect,
            'date_noted_effect' => $request->date_noted_effect
        ]);

        return redirect()->route('vaccination.data', ['patient_code' => $vaccination->patient->code])
                         ->with('success', 'Thông tin tiêm chủng đã được cập nhật.');
    }

    public function get_vaccin(Request $request)
    {
        if (!$request->ajax()) {
            return redirect()->route('home');
        }
        
		return Datatables::of(DB::table('vaccinations'))
			->addColumn('action', function ($result) {
				if ($result->trang_thai) {
					return null;
				}
                return '<a href="#" class="btn btn-sm btn-success edit-modal" data-id="' .$result->id .'" data-ngay_thang="' .$result->ngay_thang .'" data-mien_phi_thuong_tin="' .$result->mien_phi_thuong_tin .'" data-dich_vu_thuong_tin="' .$result->dich_vu_thuong_tin .'" data-mien_phi_thanh_tri="' .$result->mien_phi_thanh_tri .'" data-dich_vu_thanh_tri="' .$result->dich_vu_thanh_tri .'" data-mien_phi_noi_khac="' .$result->mien_phi_noi_khac .'" data-dich_vu_noi_khac="' .$result->dich_vu_noi_khac .'"><span class="glyphicon glyphicon-check"></span> Sửa</a>
                    <a href="#" class="btn btn-sm btn-danger delete-modal" data-id="' . $result->id . '">
                                        <span class="glyphicon glyphicon-trash"></span> Xóa</a>';
            })
			->editColumn('trang_thai', function($result) {
                return config('__tech.vaccin_status')[$result->trang_thai];
            })
            ->editColumn('mien_phi_thuong_tin', function($result) {
                return number_format($result->mien_phi_thuong_tin);
            })
            ->editColumn('dich_vu_thuong_tin', function($result) {
                return number_format($result->dich_vu_thuong_tin);
            })
            ->editColumn('mien_phi_thanh_tri', function($result) {
                return number_format($result->mien_phi_thanh_tri);
            })
            ->editColumn('dich_vu_thanh_tri', function($result) {
                return number_format($result->dich_vu_thanh_tri);
            })
            ->editColumn('mien_phi_noi_khac', function($result) {
                return number_format($result->mien_phi_noi_khac);
            })
            ->editColumn('dich_vu_noi_khac', function($result) {
                return number_format($result->dich_vu_noi_khac);
            })
	        ->toJson();
    }

    public function create(Request $request)
    {
        $validator = Validator::make($request->all(), $this->rules);
        if ($validator->fails()) {
            return 'Có lỗi trong quá trình nhập';
            return Response::json(array('errors' => $validator->getMessageBag()->toArray()));
        }
        
        DB::table('vaccinations')->insert(
            [
                'ngay_thang' => $request->ngay_thang, 
                'mien_phi_thuong_tin' => $request->mien_phi_thuong_tin ? $request->mien_phi_thuong_tin : 0,
                'dich_vu_thuong_tin' => $request->dich_vu_thuong_tin ? $request->dich_vu_thuong_tin : 0,
                'mien_phi_thanh_tri' => $request->mien_phi_thanh_tri ? $request->mien_phi_thanh_tri : 0,
                'dich_vu_thanh_tri' => $request->dich_vu_thanh_tri ? $request->dich_vu_thanh_tri : 0,
                'mien_phi_noi_khac' => $request->mien_phi_noi_khac ? $request->mien_phi_noi_khac : 0,
                'dich_vu_noi_khac' => $request->dich_vu_noi_khac ? $request->dich_vu_noi_khac : 0,
                'trang_thai' => 0
            ]
        );
    	return 'Thành công!';
    }

    public function delete(Request $request)
    {
        if (DB::table('vaccinations')->where('id', $request->id)->delete()) {
            return 'Xóa thành công!';
        }
        return 'Thất bại';
    }
    public function update(Request $request)
    {
        $update = DB::table('vaccinations')
            ->where('id', $request->id)
            ->first();
        if (!$update) {
            return 'Không tìm thấy điều bản ghi thỏa mãn';
        }
        $update = DB::table('vaccinations')
            ->where('id', $request->id)
            ->update(['mien_phi_thuong_tin' => $request->mien_phi_thuong_tin,
                'dich_vu_thuong_tin' => $request->dich_vu_thuong_tin,
                'mien_phi_thanh_tri' => $request->mien_phi_thanh_tri,
                'dich_vu_thanh_tri' => $request->dich_vu_thanh_tri,
                'mien_phi_noi_khac' => $request->mien_phi_noi_khac,
                'dich_vu_noi_khac' => $request->dich_vu_noi_khac
            ]);
        return 'Cập nhật thành công!';
    }
}

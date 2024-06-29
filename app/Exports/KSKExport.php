<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Illuminate\Http\Request;

use DB;

class KSKExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    protected $from_date;
    protected $to_date;
    protected $dvkt;

    public function __construct(string $from_date, string $to_date, Request $request) {
     $this->request = $request;
     $this->from_date = $from_date;
     $this->to_date = $to_date;
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        $execute_room_id = [58,126,642];
     
        $model = DB::connection('HISPro')
        ->table('his_service_req')
        ->leftJoin('his_execute_room', 'his_execute_room.room_id', '=', 'his_service_req.execute_room_id')
        ->leftJoin('his_dhst', 'his_dhst.id', '=', 'his_service_req.dhst_id')
        ->leftJoin('his_health_exam_rank', 'his_health_exam_rank.id', '=', 'his_service_req.health_exam_rank_id')
        ->join('his_service_req_stt', 'his_service_req_stt.id', '=', 'his_service_req.service_req_stt_id')
        ->join('his_treatment', 'his_treatment.id', '=', 'his_service_req.treatment_id')
        ->join('his_patient', 'his_patient.id', '=', 'his_service_req.tdl_patient_id')
        ->selectRaw('his_service_req.num_order,his_service_req.tdl_treatment_code,his_service_req_stt.service_req_stt_name,his_service_req.tdl_patient_name, 
        substr(his_service_req.tdl_patient_dob,0,4), his_service_req.tdl_patient_gender_name,his_patient.phone,
        his_dhst.blood_pressure_max||\'/\'||his_dhst.blood_pressure_min,his_dhst.note,
        pathological_history,his_service_req.part_exam,part_exam_circulation,part_exam_respiratory,part_exam_digestion,
        part_exam_kidney_urology,part_exam_neurological,part_exam_muscle_bone,part_exam_oend,part_exam_mental,
        part_exam_nutrition,part_exam_motion,part_exam_dermatology,part_exam_stomatology,part_exam_lower_jaw,
        part_exam_upper_jaw,part_exam_ear,part_exam_nose,part_exam_throat,part_exam_obstetric,part_exam_eye,
        his_service_req.subclinical,his_health_exam_rank.health_exam_rank_name,his_service_req.note as service_req_note,
        his_service_req.treatment_instruction||his_service_req.next_treatment_instruction')
        ->where('his_service_req.intruction_time', '>=', $this->from_date)
        ->where('his_service_req.intruction_time', '<=', $this->to_date)
        ->where('his_service_req.is_active', 1)
        ->where('his_service_req.is_delete', 0)
        ->where('his_service_req.service_req_type_id', 1)
        ->whereIn('his_service_req.execute_room_id', $execute_room_id);

        if ($this->request->get('hop_dong')) {
            $model = $model->whereIn('his_treatment.tdl_ksk_contract_id', $this->request->get('hop_dong'));
        }
        if ($this->request->get('trang_thai')) {
            $model = $model->whereIn('his_service_req.service_req_stt_id', $this->request->get('trang_thai'));
        }
        return $model
        ->orderBy('num_order')
        ->get();
    }

    public function headings(): array
    {
        return ["STT","Mã điều trị","Trạng thái","Họ và tên",
            "Ngày sinh", "Giới tính","Số điện thoại",
            "Huyết áp", "Phân loại thể lực",
            "Tiền sử","Ngoại chung","Tuần hoàn","Hô hấp","Tiêu hóa",
            "Thận tiết niệu","Thần kinh","Cơ xương khớp","Nội tiết","Tâm thần",
            "Dinh dưỡng","Vận động","Da liễu","Răng hàm mặt","Hàm dưới",
            "Hàm trên","Tai","Mũi","Họng","Sản phụ khoa","Mắt",
            "Tóm tắt CLS", "Phân loại KSK", "Ghi chú", "PP điều trị/BS tư vấn"];
    }
}

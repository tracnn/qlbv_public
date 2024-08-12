<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Illuminate\Http\Request;

use DB;
use Carbon\Carbon;

class KSKExport implements FromCollection, WithHeadings, ShouldAutoSize
{
    protected $date_from;
    protected $date_to;
    protected $ksk_contract;
    protected $service_req_stt;

    public function __construct($date_from, $date_to, $ksk_contract, $service_req_stt) 
    {
        $this->date_from = $date_from;
        $this->date_to = $date_to;
        $this->ksk_contract = $ksk_contract;
        $this->service_req_stt = $service_req_stt;
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        set_time_limit(1800); // Tăng thời gian thực thi lên 1800 giây (30 phút)
        ini_set('memory_limit', '4096M'); // Tăng giới hạn bộ nhớ nếu cần thiết

        if (strlen($this->date_from) == 10) { // Format YYYY-MM-DD
            $this->date_from = Carbon::createFromFormat('Y-m-d', $this->date_from)->startOfDay()->format('Y-m-d H:i:s');
        }

        if (strlen($this->date_to) == 10) { // Format YYYY-MM-DD
            $this->date_to = Carbon::createFromFormat('Y-m-d', $this->date_to)->endOfDay()->format('Y-m-d H:i:s');
        }

        $formattedDateFrom = Carbon::createFromFormat('Y-m-d H:i:s', $this->date_from)->format('YmdHis');
        $formattedDateTo = Carbon::createFromFormat('Y-m-d H:i:s', $this->date_to)->format('YmdHis');

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
        ->whereBetween('his_service_req.intruction_time', [$formattedDateFrom, $formattedDateTo])
        ->where([
            ['his_service_req.is_active', 1],
            ['his_service_req.is_delete', 0],
            ['his_service_req.service_req_type_id', 1],
        ])
        ->whereIn('his_service_req.execute_room_id', $execute_room_id);

        if ($this->ksk_contract) {
            $model = $model->where('his_treatment.tdl_ksk_contract_id', $this->ksk_contract);
        }
        if ($this->service_req_stt) {
            $model = $model->where('his_service_req.service_req_stt_id', $this->service_req_stt);
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

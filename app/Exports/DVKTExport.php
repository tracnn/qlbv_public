<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\Exportable;
use Illuminate\Http\Request;

use DB;

class DVKTExport implements FromQuery, WithHeadings, ShouldAutoSize
{
    use Exportable;
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
    public function query()
    {
        set_time_limit(1800); // Tăng thời gian thực thi lên 1800 giây (30 phút)
        ini_set('memory_limit', '4096M'); // Tăng giới hạn bộ nhớ nếu cần thiết
        
        $model = DB::connection('HISPro')
            ->table('his_sere_serv')
            ->join('his_service_req', 'his_service_req.id', '=' ,'his_sere_serv.service_req_id')
            ->join('his_treatment', 'his_treatment.id', '=' ,'his_sere_serv.tdl_treatment_id')
            ->leftJoin('his_execute_room', 'his_execute_room.room_id', '=', 'his_sere_serv.tdl_execute_room_id')
            ->select('his_sere_serv.tdl_treatment_code', 'his_service_req.tdl_patient_name', 'his_service_req.tdl_patient_dob',
                'his_service_req.tdl_patient_address',
                'his_sere_serv.hein_card_number', 'his_sere_serv.tdl_service_name', 'his_sere_serv.tdl_intruction_time',
                'his_treatment.in_time', 'his_treatment.out_time', 'his_treatment.fee_lock_time',
                'his_sere_serv.tdl_request_username', 'his_service_req.execute_username'
                'his_execute_room.execute_room_name', 'his_sere_serv.amount', 'his_sere_serv.price')
            ->where('tdl_intruction_time', '>=', $this->from_date)
            ->where('tdl_intruction_time', '<=', $this->to_date)
            ->where('his_sere_serv.is_active', 1)
            ->where('his_sere_serv.is_delete', 0)
            ->whereNull('his_sere_serv.is_no_execute')
            ->whereIn('tdl_service_type_id', explode(',', config('__tech.tdl_service_req_type_id_dvkt')));

        if ($this->request->get('loai_dvkt')) {
            $model = $model->whereIn('his_sere_serv.tdl_service_type_id', $this->request->get('loai_dvkt'));
        }
        if ($this->request->get('department')) {
            $model = $model->whereIn('his_sere_serv.tdl_request_department_id', $this->request->get('department'));
        }
        if ($this->request->get('execute_room')) {
            $model = $model->whereIn('his_sere_serv.tdl_execute_room_id', $this->request->get('execute_room'));
        }
        if ($this->request->get('dvkt')) {
            $model = $model->whereIn('his_sere_serv.tdl_service_code', $this->request->get('dvkt'));
        }
        if ($this->request->get('execute_department')) {
            $model = $model->whereIn('his_sere_serv.tdl_execute_department_id', $this->request->get('execute_department'));
        }
        if ($this->request->get('request_room')) {
            $model = $model->whereIn('his_sere_serv.tdl_request_room_id', $this->request->get('request_room'));
        }
        $rtn = $model->orderBy('tdl_intruction_time');
        return $rtn; 
    }

    public function headings(): array
    {
        return ["STT", "Mã điều trị", "Họ và tên BN", "Ngày sinh",
            "Địa chỉ", "Thẻ BHYT", "Tên dịch vụ", "Ngày chỉ định",
            "Ngày vào", "Ngày ra", "Ngày t.toán",
            "Người chỉ định", "Người thực hiện"
            "Phòng xử lý", "Số lượng", "Đơn giá"];
    }
}

<?php

namespace App\Http\Controllers;

use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;

class MedicalCenterDashboardController extends Controller
{
    /**
     * Hiển thị dashboard trung tâm y tế
     */
    public function index()
    {
        return view('medical-center-dashboard.index');
    }

    /**
     * Lấy dữ liệu tổng hợp cho dashboard (giữ lại để tương thích)
     */
    public function getDashboardData(Request $request)
    {
        $date = $request->input('date', Carbon::now()->format('Y-m-d'));
        $from_date = Carbon::parse($date)->startOfDay()->format('YmdHis');
        $to_date = Carbon::parse($date)->endOfDay()->format('YmdHis');

        $data = [
            'kham_benh' => $this->getKhamBenhData($from_date, $to_date),
            'noi_tru' => $this->getNoiTruData($from_date, $to_date),
            'ngoai_tru' => $this->getNgoaiTruData($from_date, $to_date),
            'can_lam_sang' => $this->getCanLamSangData($from_date, $to_date),
            'phau_thuat_thu_thuat' => $this->getPhauThuatThuThuatData($from_date, $to_date),
            'thoi_gian_cho_kham' => $this->getThoiGianChoKhamData($from_date, $to_date),
            'thoi_gian_kham_trung_binh' => $this->getThoiGianKhamTrungBinhData($from_date, $to_date),
            'thoi_gian_cho_khac' => $this->getThoiGianChoKhacData($from_date, $to_date),
        ];

        return response()->json($data);
    }

    /**
     * Lấy dữ liệu khám bệnh (endpoint riêng)
     */
    public function getKhamBenh(Request $request)
    {
        $date = $request->input('date', Carbon::now()->format('Y-m-d'));
        $from_date = Carbon::parse($date)->startOfDay()->format('YmdHis');
        $to_date = Carbon::parse($date)->endOfDay()->format('YmdHis');

        return response()->json($this->getKhamBenhData($from_date, $to_date));
    }

    /**
     * Lấy dữ liệu nội trú (endpoint riêng)
     */
    public function getNoiTru(Request $request)
    {
        $date = $request->input('date', Carbon::now()->format('Y-m-d'));
        $from_date = Carbon::parse($date)->startOfDay()->format('YmdHis');
        $to_date = Carbon::parse($date)->endOfDay()->format('YmdHis');

        return response()->json($this->getNoiTruData($from_date, $to_date));
    }

    /**
     * Lấy dữ liệu ngoại trú (endpoint riêng)
     */
    public function getNgoaiTru(Request $request)
    {
        $date = $request->input('date', Carbon::now()->format('Y-m-d'));
        $from_date = Carbon::parse($date)->startOfDay()->format('YmdHis');
        $to_date = Carbon::parse($date)->endOfDay()->format('YmdHis');

        return response()->json($this->getNgoaiTruData($from_date, $to_date));
    }

    /**
     * Lấy dữ liệu cận lâm sàng (endpoint riêng)
     */
    public function getCanLamSang(Request $request)
    {
        $date = $request->input('date', Carbon::now()->format('Y-m-d'));
        $from_date = Carbon::parse($date)->startOfDay()->format('YmdHis');
        $to_date = Carbon::parse($date)->endOfDay()->format('YmdHis');

        return response()->json($this->getCanLamSangData($from_date, $to_date));
    }

    /**
     * Lấy dữ liệu phẫu thuật - thủ thuật (endpoint riêng)
     */
    public function getPhauThuatThuThuat(Request $request)
    {
        $date = $request->input('date', Carbon::now()->format('Y-m-d'));
        $from_date = Carbon::parse($date)->startOfDay()->format('YmdHis');
        $to_date = Carbon::parse($date)->endOfDay()->format('YmdHis');

        return response()->json($this->getPhauThuatThuThuatData($from_date, $to_date));
    }

    /**
     * Lấy dữ liệu thời gian chờ khám (endpoint riêng)
     */
    public function getThoiGianChoKham(Request $request)
    {
        $date = $request->input('date', Carbon::now()->format('Y-m-d'));
        $from_date = Carbon::parse($date)->startOfDay()->format('YmdHis');
        $to_date = Carbon::parse($date)->endOfDay()->format('YmdHis');

        return response()->json($this->getThoiGianChoKhamData($from_date, $to_date));
    }

    /**
     * Lấy dữ liệu thời gian khám trung bình (endpoint riêng)
     */
    public function getThoiGianKhamTrungBinh(Request $request)
    {
        $date = $request->input('date', Carbon::now()->format('Y-m-d'));
        $from_date = Carbon::parse($date)->startOfDay()->format('YmdHis');
        $to_date = Carbon::parse($date)->endOfDay()->format('YmdHis');

        return response()->json($this->getThoiGianKhamTrungBinhData($from_date, $to_date));
    }

    /**
     * Lấy dữ liệu thời gian chờ khác (endpoint riêng)
     */
    public function getThoiGianChoKhac(Request $request)
    {
        $date = $request->input('date', Carbon::now()->format('Y-m-d'));
        $from_date = Carbon::parse($date)->startOfDay()->format('YmdHis');
        $to_date = Carbon::parse($date)->endOfDay()->format('YmdHis');

        return response()->json($this->getThoiGianChoKhacData($from_date, $to_date));
    }

    /**
     * Lấy dữ liệu khám bệnh
     */
    private function getKhamBenhData($from_date, $to_date)
    {
        // Tổng lượt khám
        $total = DB::connection('HISPro')
            ->table('his_treatment')
            ->whereBetween('in_time', [$from_date, $to_date])
            //->where('tdl_treatment_type_id', 1) // Khám bệnh
            ->where('is_delete', 0)
            ->count();

        // BHYT
        $bhyt = DB::connection('HISPro')
            ->table('his_treatment')
            ->whereBetween('his_treatment.in_time', [$from_date, $to_date])
            ->where('his_treatment.tdl_patient_type_id', config('__tech.patient_type_bhyt', 1))
            ->where('his_treatment.is_delete', 0)
            ->count();

        // Viện phí
        $vien_phi = $total - $bhyt;

        // Cấp cứu
        $cap_cuu = DB::connection('HISPro')
            ->table('his_treatment')
            ->whereBetween('in_time', [$from_date, $to_date])
            ->where('is_emergency', 1)
            ->where('is_delete', 0)
            ->count();

        // Các trạng thái khám
        $trang_thai = $this->getTrangThaiKham($from_date, $to_date);

        // Tính phần trăm thay đổi so với ngày trước
        $previous_date = Carbon::createFromFormat('YmdHis', $from_date)->subDay()->format('YmdHis');
        $previous_to_date = Carbon::createFromFormat('YmdHis', $to_date)->subDay()->format('YmdHis');
        $previous_total = DB::connection('HISPro')
            ->table('his_treatment')
            ->whereBetween('in_time', [$previous_date, $previous_to_date])
            ->where('is_delete', 0)
            ->count();

        $change_percent = $previous_total > 0 
            ? round((($total - $previous_total) / $previous_total) * 100, 1)
            : 0;

        return [
            'total' => $total,
            'total_change' => $change_percent,
            'bhyt' => $bhyt,
            'bhyt_percent' => $total > 0 ? round(($bhyt / $total) * 100, 1) : 0,
            'vien_phi' => $vien_phi,
            'vien_phi_percent' => $total > 0 ? round(($vien_phi / $total) * 100, 1) : 0,
            'cap_cuu' => $cap_cuu,
            'cap_cuu_percent' => $total > 0 ? round(($cap_cuu / $total) * 100, 1) : 0,
            'trang_thai' => $trang_thai,
        ];
    }

    /**
     * Lấy các trạng thái khám
     */
    private function getTrangThaiKham($from_date, $to_date)
    {
        // Total
        $total = DB::connection('HISPro')
            ->table('his_treatment')
            ->whereBetween('in_time', [$from_date, $to_date])
            ->where('is_delete', 0)
            ->count();

        // Chờ khám
        $cho_kham = DB::connection('HISPro')
            ->table('his_treatment')
            ->whereBetween('in_time', [$from_date, $to_date])
            ->where('is_delete', 0)
            ->whereNull('out_time')
            ->whereNull('clinical_in_time')
            ->count();

        // Đang khám
        $dang_kham = DB::connection('HISPro')
            ->table('his_treatment')
            ->whereBetween('in_time', [$from_date, $to_date])
            ->where('is_delete', 0)
            ->whereNotNull('clinical_in_time')
            ->whereNull('out_time')
            ->count();

        // Xử trí (đã kết thúc khám)
        $xu_tri = DB::connection('HISPro')
            ->table('his_treatment')
            ->whereBetween('out_time', [$from_date, $to_date])
            ->where('is_delete', 0)
            ->whereNotNull('out_time')
            ->count();

        // Chưa chỉ định
        $chua_chi_dinh = DB::connection('HISPro')
            ->table('his_treatment')
            ->whereBetween('in_time', [$from_date, $to_date])
            ->where('is_delete', 0)
            ->whereNull('last_department_id')
            ->count();

        // Nhập viện - từ khám bệnh chuyển sang nội trú
        $nhap_vien = DB::connection('HISPro')
            ->table('his_treatment')
            ->whereBetween('in_time', [$from_date, $to_date])
            ->whereIn('tdl_treatment_type_id', [3, 4]) // Nội trú
            ->where('is_delete', 0)
            ->count();

        // Đã chỉ định
        $da_chi_dinh = DB::connection('HISPro')
            ->table('his_treatment')
            ->whereBetween('in_time', [$from_date, $to_date])
            ->where('is_delete', 0)
            ->whereNotNull('last_department_id')
            ->count();

        // Chuyển viện
        $chuyen_vien = DB::connection('HISPro')
            ->table('his_treatment')
            ->whereBetween('in_time', [$from_date, $to_date])
            ->where('is_delete', 0)
            ->where('treatment_end_type_id', config('__tech.treatment_end_type_cv', 2))
            ->count();

        // Chờ kết luận
        $cho_ket_luan = DB::connection('HISPro')
            ->table('his_treatment')
            ->whereBetween('in_time', [$from_date, $to_date])
            ->where('is_delete', 0)
            ->whereNotNull('clinical_in_time')
            ->whereNull('out_time')
            ->whereNotNull('last_department_id')
            ->count();

        // Cấp toa về - kết thúc khám bệnh ngoại trú
        $cap_toa_ve = DB::connection('HISPro')
            ->table('his_treatment')
            ->whereBetween('out_time', [$from_date, $to_date])
            ->where('tdl_treatment_type_id', 1) // Khám bệnh
            ->where('is_delete', 0)
            ->whereNotNull('out_time')
            ->count();

        return [
            'cho_kham' => $cho_kham,
            'dang_kham' => $dang_kham,
            'xu_tri' => $xu_tri,
            'xu_tri_percent' => $total > 0 ? round(($xu_tri / $total) * 100, 1) : 0,
            'chua_chi_dinh' => $chua_chi_dinh,
            'nhap_vien' => $nhap_vien,
            'nhap_vien_percent' => $total > 0 ? round(($nhap_vien / $total) * 100, 1) : 0,
            'da_chi_dinh' => $da_chi_dinh,
            'chuyen_vien' => $chuyen_vien,
            'cho_ket_luan' => $cho_ket_luan,
            'cap_toa_ve' => $cap_toa_ve,
            'cap_toa_ve_percent' => $total > 0 ? round(($cap_toa_ve / $total) * 100, 1) : 0,
        ];
    }

    /**
     * Lấy dữ liệu nội trú
     */
    private function getNoiTruData($from_date, $to_date)
    {
        // Đang điều trị
        $dang_dieu_tri = DB::connection('HISPro')
            ->table('his_treatment_bed_room')
            ->join('his_bed_room','his_treatment_bed_room.bed_room_id','=','his_bed_room.id')
            ->join('his_room','his_bed_room.room_id','=','his_room.id')
            ->join('his_department','his_room.department_id','=','his_department.id')
            ->join('his_treatment','his_treatment_bed_room.treatment_id','=','his_treatment.id')
            ->leftjoin('his_co_treatment','his_treatment_bed_room.co_treatment_id','=','his_co_treatment.id')
            ->whereNull('his_treatment_bed_room.remove_time')
            ->whereNull('his_co_treatment.id')
            ->where('his_bed_room.is_active',1)
            ->where('his_room.is_active',1)
            ->whereIn('his_treatment.tdl_treatment_type_id', [3, 4])
            ->where('his_treatment_bed_room.is_delete',0)
            ->where( function($q) use ($to_date) {
                $q->whereNull('out_time')
                ->orWhere('out_time', '>', $to_date);
            })
            ->count();

        // Bệnh nhân nhập viện
        $benh_nhan_nhap_vien = DB::connection('HISPro')
            ->table('his_treatment')
            ->whereBetween('in_time', [$from_date, $to_date])
            ->whereIn('tdl_treatment_type_id', [3, 4]) // Nội trú
            ->where('is_delete', 0)
            ->count();

        // Chờ nhập viện
        $cho_nhap_vien = DB::connection('HISPro')
            ->table('his_department_tran')
            ->join('his_treatment', 'his_department_tran.treatment_id', '=', 'his_treatment.id')
            ->whereBetween('request_time', [$from_date, $to_date])
            ->where('his_treatment.is_delete', 0)
            ->whereNull('his_department_tran.department_in_time')
            ->whereIn('his_treatment.tdl_treatment_type_id', [3, 4])
            ->count();

        // Xuất viện
        $xuat_vien = DB::connection('HISPro')
            ->table('his_treatment')
            ->whereBetween('out_time', [$from_date, $to_date])
            ->whereIn('tdl_treatment_type_id', [3, 4])
            ->where('is_delete', 0)
            ->whereNotNull('out_time')
            ->count();

        // Chuyển viện
        $chuyen_vien = DB::connection('HISPro')
            ->table('his_treatment')
            ->whereBetween('out_time', [$from_date, $to_date])
            ->whereIn('tdl_treatment_type_id', [3, 4])
            ->where('is_delete', 0)
            ->where('treatment_end_type_id', config('__tech.treatment_end_type_cv', 2))
            ->count();

        // Tử vong
        $tu_vong = DB::connection('HISPro')
            ->table('his_treatment')
            ->whereBetween('out_time', [$from_date, $to_date])
            ->whereIn('tdl_treatment_type_id', [3, 4])
            ->where('is_delete', 0)
            ->where('treatment_end_type_id', 1) // Tử vong
            ->count();

        // Tính phần trăm thay đổi
        $previous_date = Carbon::createFromFormat('YmdHis', $from_date)->subDay()->format('YmdHis');
        $previous_to_date = Carbon::createFromFormat('YmdHis', $to_date)->subDay()->format('YmdHis');
        $previous_benh_nhan_nhap_vien = DB::connection('HISPro')
            ->table('his_treatment')
            ->whereBetween('in_time', [$previous_date, $previous_to_date])
            ->whereIn('tdl_treatment_type_id', [3, 4])
            ->where('is_delete', 0)
            ->count();

        $change_percent = $previous_benh_nhan_nhap_vien > 0 
            ? round((($benh_nhan_nhap_vien - $previous_benh_nhan_nhap_vien) / $previous_benh_nhan_nhap_vien) * 100, 1)
            : 0;

        return [
            'dang_dieu_tri' => $dang_dieu_tri,
            'benh_nhan_nhap_vien' => $benh_nhan_nhap_vien,
            'benh_nhan_nhap_vien_change' => $change_percent,
            'cho_nhap_vien' => $cho_nhap_vien,
            'xuat_vien' => $xuat_vien,
            'chuyen_vien' => $chuyen_vien,
            'tu_vong' => $tu_vong,
        ];
    }

    /**
     * Lấy dữ liệu ngoại trú
     */
    private function getNgoaiTruData($from_date, $to_date)
    {
        // Đang điều trị
        $dang_dieu_tri = DB::connection('HISPro')
            ->table('his_treatment')
            ->whereBetween('in_time', [$from_date, $to_date])
            ->where('tdl_treatment_type_id', 2) // Ngoại trú
            ->where('is_delete', 0)
            ->whereNull('out_time')
            ->count();

        // Chờ nhập viện
        $cho_nhap_vien = 0;

        // Xuất viện
        $xuat_vien = DB::connection('HISPro')
            ->table('his_treatment')
            ->whereBetween('out_time', [$from_date, $to_date])
            ->where('tdl_treatment_type_id', 2)
            ->where('is_delete', 0)
            ->whereNotNull('out_time')
            ->count();

        // Chuyển viện
        $chuyen_vien = DB::connection('HISPro')
            ->table('his_treatment')
            ->whereBetween('out_time', [$from_date, $to_date])
            ->where('tdl_treatment_type_id', 2)
            ->where('is_delete', 0)
            ->where('treatment_end_type_id', config('__tech.treatment_end_type_cv', 2))
            ->count();

        // Tử vong
        $tu_vong = DB::connection('HISPro')
            ->table('his_treatment')
            ->whereBetween('out_time', [$from_date, $to_date])
            ->where('tdl_treatment_type_id', 2)
            ->where('is_delete', 0)
            ->where('treatment_end_type_id', 4) // Tử vong
            ->count();

        return [
            'dang_dieu_tri' => $dang_dieu_tri,
            'cho_nhap_vien' => $cho_nhap_vien,
            'xuat_vien' => $xuat_vien,
            'chuyen_vien' => $chuyen_vien,
            'tu_vong' => $tu_vong,
        ];
    }

    /**
     * Lấy dữ liệu cận lâm sàng
     */
    private function getCanLamSangData($from_date, $to_date)
    {
        // Xét nghiệm
        $xet_nghiem = $this->getServiceReqStatus($from_date, $to_date, config('__tech.service_req_type_xn', 2));

        // Chẩn đoán hình ảnh
        $cdha = $this->getServiceReqStatus($from_date, $to_date, config('__tech.service_req_type_cdha', 3));

        return [
            'xet_nghiem' => $xet_nghiem,
            'cdha' => $cdha,
        ];
    }

    /**
     * Lấy trạng thái service request
     */
    private function getServiceReqStatus($from_date, $to_date, $service_type_id)
    {
        $data = DB::connection('HISPro')
            ->table('his_service_req')
            ->selectRaw('
                COUNT(*) as total,
                SUM(CASE WHEN service_req_stt_id = 1 THEN 1 ELSE 0 END) as chua_lam,
                SUM(CASE WHEN service_req_stt_id = 2 THEN 1 ELSE 0 END) as dang_lam,
                SUM(CASE WHEN service_req_stt_id = 3 THEN 1 ELSE 0 END) as da_lam
            ')
            ->whereBetween('intruction_time', [$from_date, $to_date])
            ->where('service_req_type_id', $service_type_id)
            ->where('is_active', 1)
            ->where('is_delete', 0)
            ->first();

        return [
            'total' => $data->total ?? 0,
            'chua_lam' => $data->chua_lam ?? 0,
            'dang_lam' => $data->dang_lam ?? 0,
            'da_lam' => $data->da_lam ?? 0,
        ];
    }

    /**
     * Lấy dữ liệu phẫu thuật - thủ thuật
     */
    private function getPhauThuatThuThuatData($from_date, $to_date)
    {
        // Phẫu thuật
        $phau_thuat = DB::connection('HISPro')
            ->table('his_service_req')
            ->whereBetween('intruction_time', [$from_date, $to_date])
            ->where('service_req_type_id', config('__tech.service_req_type_phauthuat', 10))
            ->where('is_active', 1)
            ->where('is_delete', 0)
            ->count();

        // Thủ thuật
        $thu_thuat = DB::connection('HISPro')
            ->table('his_service_req')
            ->whereBetween('intruction_time', [$from_date, $to_date])
            ->where('service_req_type_id', config('__tech.service_req_type_thuthuat', 4))
            ->where('is_active', 1)
            ->where('is_delete', 0)
            ->count();

        return [
            'phau_thuat' => $phau_thuat,
            'thu_thuat' => $thu_thuat,
        ];
    }

    /**
     * Lấy dữ liệu thời gian chờ khám
     */
    private function getThoiGianChoKhamData($from_date, $to_date)
    {
        $data = DB::connection('HISPro')
            ->table('his_treatment')
            ->selectRaw('
                AVG(
                    CASE 
                        WHEN clinical_in_time IS NOT NULL AND in_time IS NOT NULL
                        THEN (TO_DATE(clinical_in_time, \'YYYYMMDDHH24MISS\') - TO_DATE(in_time, \'YYYYMMDDHH24MISS\')) * 24 * 60
                        ELSE NULL
                    END
                ) as trung_binh,
                MAX(
                    CASE 
                        WHEN clinical_in_time IS NOT NULL AND in_time IS NOT NULL
                        THEN (TO_DATE(clinical_in_time, \'YYYYMMDDHH24MISS\') - TO_DATE(in_time, \'YYYYMMDDHH24MISS\')) * 24 * 60
                        ELSE NULL
                    END
                ) as lau_nhat,
                MIN(
                    CASE 
                        WHEN clinical_in_time IS NOT NULL AND in_time IS NOT NULL
                        THEN (TO_DATE(clinical_in_time, \'YYYYMMDDHH24MISS\') - TO_DATE(in_time, \'YYYYMMDDHH24MISS\')) * 24 * 60
                        ELSE NULL
                    END
                ) as nhanh_nhat
            ')
            ->whereBetween('in_time', [$from_date, $to_date])
            ->where('tdl_treatment_type_id', 1)
            ->where('is_delete', 0)
            ->whereNotNull('clinical_in_time')
            ->first();

        return [
            'trung_binh' => round($data->trung_binh ?? 0),
            'lau_nhat' => round($data->lau_nhat ?? 0),
            'nhanh_nhat' => round($data->nhanh_nhat ?? 0),
        ];
    }

    /**
     * Lấy dữ liệu thời gian khám bệnh trung bình theo đối tượng
     */
    private function getThoiGianKhamTrungBinhData($from_date, $to_date)
    {
        // Tất cả đối tượng
        $tat_ca = DB::connection('HISPro')
            ->table('his_treatment')
            ->selectRaw('
                AVG(
                    CASE 
                        WHEN out_time IS NOT NULL AND clinical_in_time IS NOT NULL
                        THEN (TO_DATE(out_time, \'YYYYMMDDHH24MISS\') - TO_DATE(clinical_in_time, \'YYYYMMDDHH24MISS\')) * 24 * 60
                        ELSE NULL
                    END
                ) as trung_binh
            ')
            ->whereBetween('out_time', [$from_date, $to_date])
            ->where('tdl_treatment_type_id', 1)
            ->where('is_delete', 0)
            ->whereNotNull('out_time')
            ->whereNotNull('clinical_in_time')
            ->first();

        // Chỉ khám bệnh (không có xét nghiệm, không có CDHA)
        $chi_kham = DB::connection('HISPro')
            ->table('his_treatment')
            ->leftJoin('his_service_req', function($join) {
                $join->on('his_service_req.treatment_id', '=', 'his_treatment.id')
                     ->whereIn('his_service_req.service_req_type_id', [2, 3]); // Xét nghiệm, CDHA
            })
            ->selectRaw('
                AVG(
                    CASE 
                        WHEN his_treatment.out_time IS NOT NULL AND his_treatment.clinical_in_time IS NOT NULL
                        AND his_service_req.id IS NULL
                        THEN (TO_DATE(his_treatment.out_time, \'YYYYMMDDHH24MISS\') - TO_DATE(his_treatment.clinical_in_time, \'YYYYMMDDHH24MISS\')) * 24 * 60
                        ELSE NULL
                    END
                ) as trung_binh
            ')
            ->whereBetween('his_treatment.out_time', [$from_date, $to_date])
            ->where('his_treatment.tdl_treatment_type_id', 1)
            ->where('his_treatment.is_delete', 0)
            ->whereNotNull('his_treatment.out_time')
            ->whereNotNull('his_treatment.clinical_in_time')
            ->first();

        // Khám + XN
        $kham_xn = DB::connection('HISPro')
            ->table('his_treatment')
            ->join('his_service_req', 'his_service_req.treatment_id', '=', 'his_treatment.id')
            ->where('his_service_req.service_req_type_id', 2) // Xét nghiệm
            ->selectRaw('
                AVG(
                    CASE 
                        WHEN his_treatment.out_time IS NOT NULL AND his_treatment.clinical_in_time IS NOT NULL
                        THEN (TO_DATE(his_treatment.out_time, \'YYYYMMDDHH24MISS\') - TO_DATE(his_treatment.clinical_in_time, \'YYYYMMDDHH24MISS\')) * 24 * 60
                        ELSE NULL
                    END
                ) as trung_binh
            ')
            ->whereBetween('his_treatment.out_time', [$from_date, $to_date])
            ->where('his_treatment.tdl_treatment_type_id', 1)
            ->where('his_treatment.is_delete', 0)
            ->whereNotNull('his_treatment.out_time')
            ->whereNotNull('his_treatment.clinical_in_time')
            ->first();

        // Khám + XN + CĐHA
        $kham_xn_cdha = DB::connection('HISPro')
            ->table('his_treatment')
            ->join('his_service_req as sr1', 'sr1.treatment_id', '=', 'his_treatment.id')
            ->join('his_service_req as sr2', 'sr2.treatment_id', '=', 'his_treatment.id')
            ->where('sr1.service_req_type_id', 2) // Xét nghiệm
            ->where('sr2.service_req_type_id', 3) // CDHA
            ->selectRaw('
                AVG(
                    CASE 
                        WHEN his_treatment.out_time IS NOT NULL AND his_treatment.clinical_in_time IS NOT NULL
                        THEN (TO_DATE(his_treatment.out_time, \'YYYYMMDDHH24MISS\') - TO_DATE(his_treatment.clinical_in_time, \'YYYYMMDDHH24MISS\')) * 24 * 60
                        ELSE NULL
                    END
                ) as trung_binh
            ')
            ->whereBetween('his_treatment.out_time', [$from_date, $to_date])
            ->where('his_treatment.tdl_treatment_type_id', 1)
            ->where('his_treatment.is_delete', 0)
            ->whereNotNull('his_treatment.out_time')
            ->whereNotNull('his_treatment.clinical_in_time')
            ->first();

        return [
            'tat_ca' => round($tat_ca->trung_binh ?? 0),
            'chi_kham' => round($chi_kham->trung_binh ?? 0),
            'kham_xn' => round($kham_xn->trung_binh ?? 0),
            'kham_xn_cdha' => round($kham_xn_cdha->trung_binh ?? 0),
        ];
    }

    /**
     * Lấy dữ liệu thời gian chờ khác
     */
    private function getThoiGianChoKhacData($from_date, $to_date)
    {
        // Thời gian chờ xét nghiệm
        $xet_nghiem = DB::connection('HISPro')
            ->table('his_service_req')
            ->selectRaw('
                AVG(
                    CASE 
                        WHEN start_time IS NOT NULL AND intruction_time IS NOT NULL
                        THEN (TO_DATE(start_time, \'YYYYMMDDHH24MISS\') - TO_DATE(intruction_time, \'YYYYMMDDHH24MISS\')) * 24 * 60
                        ELSE NULL
                    END
                ) as trung_binh
            ')
            ->whereBetween('intruction_time', [$from_date, $to_date])
            ->where('service_req_type_id', config('__tech.service_req_type_xn', 2))
            ->where('is_active', 1)
            ->where('is_delete', 0)
            ->whereNotNull('start_time')
            ->first();

        // Thời gian chờ CDHA
        $cdha = DB::connection('HISPro')
            ->table('his_service_req')
            ->selectRaw('
                AVG(
                    CASE 
                        WHEN start_time IS NOT NULL AND intruction_time IS NOT NULL
                        THEN (TO_DATE(start_time, \'YYYYMMDDHH24MISS\') - TO_DATE(intruction_time, \'YYYYMMDDHH24MISS\')) * 24 * 60
                        ELSE NULL
                    END
                ) as trung_binh
            ')
            ->whereBetween('intruction_time', [$from_date, $to_date])
            ->where('service_req_type_id', config('__tech.service_req_type_cdha', 3))
            ->where('is_active', 1)
            ->where('is_delete', 0)
            ->whereNotNull('start_time')
            ->first();

        // Thời gian lấy thuốc
        $lay_thuoc = DB::connection('HISPro')
            ->table('his_service_req')
            ->selectRaw('
                AVG(
                    CASE 
                        WHEN finish_time IS NOT NULL AND start_time IS NOT NULL
                        THEN (TO_DATE(finish_time, \'YYYYMMDDHH24MISS\') - TO_DATE(start_time, \'YYYYMMDDHH24MISS\')) * 24 * 60
                        ELSE NULL
                    END
                ) as trung_binh
            ')
            ->whereBetween('intruction_time', [$from_date, $to_date])
            ->where('service_req_type_id', 6) // Lấy thuốc - cần kiểm tra config
            ->where('is_active', 1)
            ->where('is_delete', 0)
            ->whereNotNull('finish_time')
            ->whereNotNull('start_time')
            ->first();

        return [
            'xet_nghiem' => round($xet_nghiem->trung_binh ?? 0),
            'cdha' => round($cdha->trung_binh ?? 0),
            'lay_thuoc' => round($lay_thuoc->trung_binh ?? 0),
        ];
    }
}


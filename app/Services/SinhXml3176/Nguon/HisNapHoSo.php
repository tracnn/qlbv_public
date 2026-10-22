<?php

namespace App\Services\SinhXml3176\Nguon;

use App\Services\SinhXml3176\CauHinhSinhXml3176;
use App\Services\SinhXml3176\DuLieuHoSo;
use Illuminate\Support\Facades\DB;

/**
 * Nap du lieu HIS (chi doc) cho mot lo ho so: ho so + cac bang con ma XML1-3 can.
 * Moi nguon chi mot truy van cho ca lo (chia nhom NHOM_ID id moi lan); luon select cot tuong minh.
 * Danh sach cot = hop cac cot ma ban do [AX] XML1-3 tham chieu + khoa; dot sau mo rong cac hang so nay.
 */
class HisNapHoSo
{
    const NHOM_ID = 1000;

    /** v_his_treatment_12 (427 cot) - chi cac cot XML1 [AX]:167-249 + XML2/3 can */
    const COT_HO_SO = [
        'id', 'treatment_code', 'branch_id', 'tdl_patient_type_id', 'tdl_treatment_type_id',
        // XML1 1-16: hanh chinh
        'tdl_patient_code', 'tdl_patient_name', 'tdl_patient_cccd_number', 'tdl_patient_cmnd_number',
        'tdl_patient_passport_number', 'tdl_patient_dob', 'tdl_patient_is_has_not_day_dob', 'tdl_patient_gender_id',
        'tdl_patient_blood_abo_code', 'tdl_patient_mps_national_code', 'ethnic_code', 'tdl_patient_career_code',
        'tdl_patient_address', 'tdl_patient_province_code', 'tdl_patient_district_code', 'tdl_patient_commune_code',
        'ht_province_code', 'ht_district_code', 'ht_commune_code', 'tdl_patient_mobile', 'tdl_patient_phone',
        // XML1 22-30: ly do, chan doan
        'hospitalization_reason', 'hospitalize_reason_name', 'hospitalize_reason_code', 'provisional_diagnosis',
        'icd_code', 'icd_name', 'icd_text', 'icd_sub_code', 'traditional_icd_code', 'traditional_icd_sub_code',
        // XML1 31-34: doi tuong KCB, noi di/den
        'hein_patient_type_code', 'hein_medi_org_code', 'accept_hein_medi_org_code', 'sys_medi_org_code',
        'hein_province_code', 'hein_level_code', 'is_emergency', 'is_hiv', 'is_tuberculosis',
        'transfer_in_medi_org_code', 'medi_org_code', 'accident_hurt_type_bhyt_code',
        // XML1 35-44: thoi gian, ket qua
        'in_time', 'clinical_in_time', 'out_time', 'transfer_in_code', 'out_code', 'treatment_day_count',
        'treatment_method', 'treatment_result_code', 'treatment_end_type_id', 'tran_pati_reason_id', 'advise',
        'fee_lock_time', 'hein_lock_time',
        // XML1 56-65: loai KCB, khoa, tai kham, MA_TTDV
        'is_chronic', 'end_room_bhyt_code', 'exit_bhyt_code', 'end_department_bhyt_code', 'department_ids',
        'co_department_ids', 'appointment_time', 'representative_hein_code', 'hosp_subs_director_loginname',
        'hospital_director_loginname',
    ];

    /** v_his_patient_type_alter - XML1 17-21, 31, 59, 62 + PTA chinh */
    const COT_DIEN_DIEU_TRI = [
        'id', 'treatment_id', 'log_time', 'patient_type_id', 'patient_type_code', 'treatment_type_id',
        'hein_card_number', 'hein_medi_org_code', 'hein_card_from_time', 'hein_card_to_time', 'free_co_paid_time',
        'live_area_code', 'join_5_year_time', 'level_code', 'right_route_code', 'right_route_type_code',
        'has_birth_certificate', 'is_tt46', 'has_working_letter', 'has_absent_letter', 'is_newborn', 'hncode',
        'ksk_contract_id',
    ];

    /** v_his_sere_serv_2 (224 cot) - cac cot XML2 [AX]:319-445 va XML3 [AX]:447-570 dung */
    const COT_DICH_VU = [
        'id', 'tdl_treatment_id', 'service_id', 'service_req_id', 'parent_id', 'parent_service_req_id',
        'patient_type_id', 'primary_patient_type_id', 'hein_card_number', 'json_patient_type_alter',
        'tdl_service_type_id', 'tdl_hein_service_type_id', 'tdl_is_main_exam', 'tdl_hein_service_bhyt_code',
        'tdl_hein_service_bhyt_name', 'hst_bhyt_code', 'old_hst_bhyt_code', 'hst_bhyt_code_in_time',
        'is_no_execute', 'is_expend',
        // gia, tien
        'amount', 'price', 'original_price', 'primary_price', 'limit_price', 'vat_ratio', 'hein_ratio',
        'hein_limit_price', 'hein_limit_ratio', 'config_hein_limit_price', 'other_source_price', 'hein_pay_source_type_id',
        'vir_price', 'vir_total_hein_price', 'vir_total_patient_price', 'vir_total_patient_price_bhyt',
        'service_condition_id', 'stent_order', 'equipment_set_id', 'share_count',
        // thuoc
        'medicine_id', 'material_id', 'exp_mest_medicine_id', 'exp_mest_material_id', 'active_ingr_bhyt_code',
        'preprocessing_code', 'processing_code', 'transfer_medi_org_code', 'service_unit_name', 'convert_ratio',
        'convert_unit_name', 'use_original_unit_for_pres', 'concentra', 'medicine_use_form_code', 'dosage_form',
        'tutorial', 'medicine_tutorial', 'medicine_htu_text', 'remedy_count', 'advise', 'medicine_register_number',
        'medicine_tt_thau', 'medicine_bid_extra_code', 'medicine_bid_package_code', 'medicine_bid_group_code',
        'medicine_bid_year', 'medicine_is_anaesthesia',
        // vat tu
        'material_tt_thau', 'material_information_bid', 'material_bid_extra_code', 'material_bid_package_code',
        'material_bid_group_code', 'material_bid_year', 'material_serial_number', 'model_code',
        // thoi gian
        'intruction_time', 'tdl_intruction_time', 'tdl_intruction_date', 'start_time', 'finish_time', 'begin_time',
        'end_time', 'use_time', 'use_time_to', 'used_time', 'sample_time', 'receive_sample_time',
        // nguoi, khoa, benh, may
        'request_bhyt_code', 'request_loginname', 'execute_loginname', 'assigned_execute_loginname',
        'sampler_loginname', 'subclinical_result_loginname', 'ekip_id', 'icd_code', 'icd_sub_code',
        'traditional_icd_code', 'traditional_icd_sub_code', 'machine_group_code', 'source_code', 'source_name',
        'serial_number', 'bed_log_id', 'dhst_id',
    ];

    /** his_ekip_user - XML3 34 NGUOI_THUC_HIEN (ekip) */
    const COT_EKIP = ['id', 'ekip_id', 'loginname', 'username', 'execute_role_id'];

    /** v_his_bed_log - XML3 32 MA_GIUONG, 39 NGAY_KQ */
    const COT_GIUONG = [
        'id', 'treatment_id', 'bed_id', 'bed_code', 'bed_service_type_id', 'service_req_id', 'share_count',
        'start_time', 'finish_time',
    ];

    /** v_his_sere_serv_tein - XML3 44 MA_MAY */
    const COT_KET_QUA_CLS = [
        'id', 'tdl_treatment_id', 'sere_serv_id', 'machine_group_code', 'source_code', 'source_name', 'serial_number',
    ];

    /** v_his_sere_serv_pttt - XML1 30, XML2 33, XML3 3/4/42 */
    const COT_PTTT = [
        'id', 'tdl_treatment_id', 'sere_serv_id', 'icd_cm_code', 'icd_cm_sub_code', 'is_anaesthesia',
        'emme_hein_code', 'emme_second_hein_code',
    ];

    /** his_dhst - XML1 60 CAN_NANG, 66 DU_PHONG */
    const COT_DHST = ['id', 'treatment_id', 'execute_time', 'weight', 'height'];

    /** v_his_baby - XML1 61 CAN_NANG_CON */
    const COT_SO_SINH = ['id', 'treatment_id', 'weight'];

    /** his_exp_medimate_used - XML2 tach dong (NGAY_TH_YL_CUA_THUOC_OPTION) */
    const COT_SU_DUNG_THUOC = [
        'id', 'tdl_treatment_id', 'exp_mest_medicine_id', 'exp_mest_material_id', 'use_time', 'amount',
        'medication_session',
    ];

    protected $nhomId = self::NHOM_ID;

    private $cauHinh;
    private $ungVien;

    public function __construct(CauHinhSinhXml3176 $cauHinh, HisUngVien $ungVien)
    {
        $this->cauHinh = $cauHinh;
        $this->ungVien = $ungVien;
    }

    /**
     * Danh sach nguon: thuoc tinh DuLieuHoSo, bang/view, cot select, cot loc, cot gan ho so.
     * Phan tu dau la ho so; 'ekip' gan qua ekip_id cua dich vu.
     */
    public static function cacNguon()
    {
        return [
            ['thuoc_tinh' => 'hoSo', 'bang' => 'v_his_treatment_12', 'cot' => self::COT_HO_SO, 'cot_loc' => [], 'khoa' => 'id'],
            ['thuoc_tinh' => 'dienDieuTri', 'bang' => 'v_his_patient_type_alter', 'cot' => self::COT_DIEN_DIEU_TRI, 'cot_loc' => ['is_delete'], 'khoa' => 'treatment_id'],
            ['thuoc_tinh' => 'dichVu', 'bang' => 'v_his_sere_serv_2', 'cot' => self::COT_DICH_VU, 'cot_loc' => ['is_delete'], 'khoa' => 'tdl_treatment_id'],
            ['thuoc_tinh' => 'ekip', 'bang' => 'his_ekip_user', 'cot' => self::COT_EKIP, 'cot_loc' => ['is_delete'], 'khoa' => 'ekip_id'],
            ['thuoc_tinh' => 'giuong', 'bang' => 'v_his_bed_log', 'cot' => self::COT_GIUONG, 'cot_loc' => ['is_delete'], 'khoa' => 'treatment_id'],
            ['thuoc_tinh' => 'ketQuaCls', 'bang' => 'v_his_sere_serv_tein', 'cot' => self::COT_KET_QUA_CLS, 'cot_loc' => ['is_delete'], 'khoa' => 'tdl_treatment_id'],
            ['thuoc_tinh' => 'pttt', 'bang' => 'v_his_sere_serv_pttt', 'cot' => self::COT_PTTT, 'cot_loc' => ['is_delete'], 'khoa' => 'tdl_treatment_id'],
            ['thuoc_tinh' => 'dhst', 'bang' => 'his_dhst', 'cot' => self::COT_DHST, 'cot_loc' => ['is_delete'], 'khoa' => 'treatment_id'],
            ['thuoc_tinh' => 'soSinh', 'bang' => 'v_his_baby', 'cot' => self::COT_SO_SINH, 'cot_loc' => ['is_delete'], 'khoa' => 'treatment_id'],
            ['thuoc_tinh' => 'suDungThuoc', 'bang' => 'his_exp_medimate_used', 'cot' => self::COT_SU_DUNG_THUOC, 'cot_loc' => ['is_delete'], 'khoa' => 'tdl_treatment_id'],
        ];
    }

    /**
     * @param int[] $treatmentIds
     * @return DuLieuHoSo[] khoa la treatment_id; id khong co trong HIS thi khong co trong ket qua
     */
    public function nap(array $treatmentIds)
    {
        $ids = array_values(array_unique(array_map('intval', $treatmentIds)));
        if (empty($ids)) {
            return [];
        }

        $nguon = self::cacNguon();
        $nguonHoSo = array_shift($nguon);

        $kq = [];
        foreach ($this->doc($nguonHoSo, $ids) as $hs) {
            $kq[(int) $hs->id] = new DuLieuHoSo($hs);
        }
        if (empty($kq)) {
            return [];
        }
        $ids = array_keys($kq);

        foreach ($nguon as $n) {
            if ($n['thuoc_tinh'] === 'ekip') {
                continue;
            }
            foreach ($this->doc($n, $ids) as $dong) {
                $kq[(int) $dong->{$n['khoa']}]->{$n['thuoc_tinh']}[] = $dong;
            }
        }

        $this->ganEkip($kq);

        return $kq;
    }

    /** Ekip khong co treatment_id: gan qua ekip_id cua cac dich vu da nap. */
    private function ganEkip(array $kq)
    {
        $hoSoTheoEkip = [];
        foreach ($kq as $id => $dl) {
            foreach ($dl->dichVu as $dv) {
                if ($dv->ekip_id !== null && $dv->ekip_id !== '') {
                    $hoSoTheoEkip[(int) $dv->ekip_id][$id] = true;
                }
            }
        }
        if (empty($hoSoTheoEkip)) {
            return;
        }

        $nguonEkip = null;
        foreach (self::cacNguon() as $n) {
            if ($n['thuoc_tinh'] === 'ekip') {
                $nguonEkip = $n;
            }
        }

        foreach ($this->doc($nguonEkip, array_keys($hoSoTheoEkip)) as $dong) {
            foreach (array_keys($hoSoTheoEkip[(int) $dong->ekip_id]) as $id) {
                $kq[$id]->ekip[] = $dong;
            }
        }
    }

    /** Doc mot nguon theo danh sach khoa, chia nhom $nhomId; tra mang stdClass. */
    private function doc(array $n, array $khoa)
    {
        $ra = [];
        foreach (array_chunk($khoa, $this->nhomId) as $nhom) {
            $q = DB::connection($this->cauHinh->ketNoiHis())->table($n['bang'])
                ->select($n['cot'])
                ->whereIn($n['khoa'], $nhom);

            if (in_array('is_delete', $n['cot_loc'], true)) {
                $q->where('is_delete', 0);
            }

            if ($n['thuoc_tinh'] === 'dichVu') {
                $idTt = $this->ungVien->idDoiTuongTt();
                if (!empty($idTt)) {
                    $q->whereIn('patient_type_id', $idTt);
                }
            }

            if ($n['thuoc_tinh'] === 'dienDieuTri') {
                $q->orderBy('log_time', 'desc')->orderBy('id', 'desc');
            } else {
                $q->orderBy('id');
            }

            foreach ($q->get() as $dong) {
                $ra[] = $dong;
            }
        }

        // Moi ho so nam tron trong mot nhom nen thu tu ORDER BY trong tung ho so duoc giu nguyen.
        return $ra;
    }
}

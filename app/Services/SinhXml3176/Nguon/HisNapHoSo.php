<?php

namespace App\Services\SinhXml3176\Nguon;

use App\Services\SinhXml3176\CauHinhSinhXml3176;
use App\Services\SinhXml3176\DuLieuHoSo;
use Illuminate\Support\Facades\DB;

/**
 * Nap du lieu HIS (chi doc) cho mot lo ho so: ho so + cac bang con ma XML1-3 can.
 * Moi nguon chi mot truy van cho ca lo (chia nhom NHOM_ID id moi lan); luon select cot tuong minh.
 * Danh sach cot = hop cac cot ma ban do [AX] XML1-11, 13-15 tham chieu + khoa.
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
        // KiemDieuKien (Check/CheckCollinearXml)
        'end_department_id', 'treatment_result_id', 'tuberculosis_issued_org_name', 'tuberculosis_issued_date',
        // Dot 3 XML6-15 (kiem all_tab_columns 22/10/2026; bang cot -> truong trong docs/trien-khai-sinh-xml3176.md muc "Nguon HIS")
        // the BHYT, ma BHXH
        'tdl_hein_card_number', 'tdl_social_insurance_number', 'sick_hein_card_number',
        // so giay, ngay
        'end_code', 'extra_end_code', 'in_date', 'out_date', 'treatment_end_type_ext_id',
        // thai san, nghi duong thai, nghi viec
        'is_pregnancy_termination', 'pregnancy_termination_reason', 'pregnancy_termination_time', 'gestational_age',
        'outpatient_date_from', 'outpatient_date_to', 'sick_leave_day', 'sick_leave_from', 'sick_leave_to',
        'sick_loginname', 'sick_username',
        // cha me, nguoi than, noi lam viec
        'father_name', 'mother_name', 'father_social_insurance_number', 'mother_social_insurance_number',
        'tdl_patient_relative_name', 'tdl_patient_relative_type', 'tdl_patient_work_place', 'tdl_patient_work_place_name',
        // nguoi ky, bac si
        'end_head_loginname', 'end_dept_subs_head_loginname', 'end_department_head_loginname', 'end_loginname',
        'doctor_loginname',
        // lam sang, chuyen tuyen
        'clinical_note', 'clinical_signs', 'subclinical_result', 'used_medicine', 'treatment_direction',
        'transport_vehicle', 'transporter', 'transporter_loginnames',
    ];

    /** v_his_patient_type_alter - XML1 17-21, 31, 59, 62 + PTA chinh */
    const COT_DIEN_DIEU_TRI = [
        'id', 'treatment_id', 'log_time', 'patient_type_id', 'patient_type_code', 'treatment_type_id',
        'hein_card_number', 'hein_medi_org_code', 'hein_card_from_time', 'hein_card_to_time', 'free_co_paid_time',
        'live_area_code', 'join_5_year_time', 'level_code', 'right_route_code', 'right_route_type_code',
        'has_birth_certificate', 'is_tt46', 'has_working_letter', 'has_absent_letter', 'is_newborn', 'hncode',
        'ksk_contract_id', 'hein_treatment_type_code',
    ];

    /**
     * v_his_sere_serv_2 (224 cot) - cac cot XML2 [AX]:319-445 va XML3 [AX]:447-570 dung.
     * tdl_service_req_type_id: XML1 56 MA_LOAI_KCB ngoai tru man tinh (kiem all_tab_columns 22/10/2026, NUMBER).
     * Dot 2: XML4 [AX]:666-675 (description CLOB, conclude, result_read_time, subclinical_result_loginname),
     * XML5 [AX]:711-746 (tracking_id, dhst_id, tdl_is_main_exam, cac truong kham nhanh 2 - deu nam tren view).
     */
    const COT_DICH_VU = [
        'id', 'tdl_treatment_id', 'service_id', 'service_req_id', 'parent_id', 'parent_service_req_id',
        'patient_type_id', 'primary_patient_type_id', 'hein_card_number', 'json_patient_type_alter',
        'tdl_service_type_id', 'tdl_hein_service_type_id', 'tdl_is_main_exam', 'tdl_hein_service_bhyt_code',
        'tdl_hein_service_bhyt_name', 'hst_bhyt_code', 'old_hst_bhyt_code', 'hst_bhyt_code_in_time',
        'is_no_execute', 'is_expend', 'tdl_service_req_code', 'tdl_service_req_type_id',
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
        // XML4 MO_TA/KET_LUAN/NGAY_KQ, XML5 PHAU_THUAT va khop to dieu tri
        'description', 'conclude', 'result_read_time', 'tracking_id',
        // XML5 nhanh 2: ProcessDataExam doc thang tren V_HIS_SERE_SERV_2
        'hospitalization_reason', 'pathological_process', 'pathological_history', 'pathological_history_family',
        'full_exam', 'part_exam', 'subclinical', 'treatment_instruction',
    ];

    /** his_ekip_user - XML3 34 NGUOI_THUC_HIEN (ekip) */
    const COT_EKIP = ['id', 'ekip_id', 'loginname', 'username', 'execute_role_id'];

    /** v_his_bed_log - XML3 32 MA_GIUONG, 39 NGAY_KQ */
    const COT_GIUONG = [
        'id', 'treatment_id', 'bed_id', 'bed_code', 'bed_service_type_id', 'service_req_id', 'share_count',
        'start_time', 'finish_time',
    ];

    /** v_his_sere_serv_tein - XML3 44 MA_MAY; XML4 khoi A [AX]:668-673 (ma/ten chi so, gia tri, don vi, mo ta) */
    const COT_KET_QUA_CLS = [
        'id', 'tdl_treatment_id', 'sere_serv_id', 'machine_group_code', 'source_code', 'source_name', 'serial_number',
        'bhyt_code', 'bhyt_name', 'antibiotic_resistance_code', 'antibiotic_resistance_name', 'value',
        'test_index_unit_name', 'result_description',
    ];

    /** v_his_sere_serv_pttt - XML1 30, XML2 33, XML3 3/4/42 */
    const COT_PTTT = [
        'id', 'tdl_treatment_id', 'sere_serv_id', 'icd_cm_code', 'icd_cm_sub_code', 'is_anaesthesia',
        'emme_hein_code', 'emme_second_hein_code',
    ];

    /** his_dhst - XML1 60 CAN_NANG, 66 DU_PHONG; XML5 CSST [AX]:723 (gan to dieu tri qua tracking_id) */
    const COT_DHST = [
        'id', 'treatment_id', 'execute_time', 'weight', 'height', 'tracking_id', 'pulse', 'temperature',
        'breath_rate', 'spo2', 'belly', 'chest', 'urine', 'blood_pressure_max', 'blood_pressure_min',
        'capillary_blood_glucose',
    ];

    /**
     * v_his_baby - XML1 61 CAN_NANG_CON; Dot 3: XML9 [AX]:1048-1100 (giay chung sinh, cac 'item.' cua DLL)
     * va XML8 [AX]:900-983 (born_result_id/born_time/death_date cho NGAY_SINHCON, SO_CONCHET).
     */
    const COT_SO_SINH = [
        'id', 'treatment_id', 'weight', 'hein_card_number_tmp',
        'baby_name', 'gender_id', 'born_time', 'born_result_id', 'born_result_name', 'death_date', 'father_name',
        'number_children_birth', 'number_of_birth', 'current_alive', 'is_surgery', 'week_count',
        'birthplace_type', 'birth_hospital_name', 'birthplace', 'birth_commune_name', 'birth_district_name',
        'birth_province_name', 'note', 'midwife', 'issuer_username', 'issued_date', 'birth_cert_num',
        'birth_cert_book_name', 'tdl_hein_card_number', 'tdl_patient_name', 'tdl_patient_dob',
        'tdl_patient_ethnic_code', 'tdl_patient_address', 'tdl_patient_province_code', 'tdl_patient_district_code',
        'tdl_patient_commune_code', 'tdl_patient_cccd_number', 'tdl_patient_cccd_date', 'tdl_patient_cccd_place',
        'tdl_patient_cmnd_number', 'tdl_patient_cmnd_date', 'tdl_patient_cmnd_place', 'tdl_patient_passport_number',
        'tdl_patient_passport_date', 'tdl_patient_passport_place',
    ];

    /**
     * his_hiv_treatment (bang goc, co is_delete) - XML6 [AX]:768-840; treatment_id la khoa ngoai (id la khoa chinh).
     * Moi ho so lay ban ghi ID lon nhat (plugin sap theo ID roi ghi de vao tu dien theo TREATMENT_ID).
     */
    const COT_HIV = [
        'id', 'treatment_id', 'hiv_infection_date', 'test_sample_place', 'test_place_kd', 'arv_patient_begin_place',
        'arv_patient_begin', 'begin_regimen_hiv_code', 'begin_regimen_level', 'hiv_treatment_reason',
        'tuberculosis_treatment_type', 'tuberculosis_screening', 'tuberculosis_regimen',
        'tuberculosis_treatment_begin', 'tuberculosis_treatment_end', 'tuberculosis_treatment_result',
        'test_pcr_rna_reason', 'test_pcr_rna_date', 'test_pcr_rna_result', 'test_pcr_rna_result_date',
        'hiv_patient_type', 'clinical_stage', 'group_patient', 'hiv_patient_status', 'test_pcr_times',
        'test_pcr_date', 'test_pcr_result_date', 'test_pcr_result', 'date_of_pregnancy', 'ctx_treatment_begin',
        'hiv_treatment_code', 'arv_treatmen_begin', 'arv_treatmen_end', 'regimen_hiv_code', 'regimen_level',
        'prescription_arv_day', 'regimen_hiv_date', 'regimen_hiv_reason',
    ];

    /**
     * his_tuberculosis_treat (bang goc, co is_delete) - XML15 [AX]:1347-1386.
     * Moi ho so lay ban ghi cuoi theo id tang (plugin ghi de theo thu tu tra ve).
     * Ten cot that: tuberculosis_treatmen_result (thieu chu 't').
     */
    const COT_LAO = [
        'id', 'treatment_id', 'tuberculosis_classify_location', 'tuberculosis_classify_ts',
        'tuberculosis_classify_hiv', 'tuberculosis_classify_vk', 'tuberculosis_classify_kt',
        'tuberculosis_treatment_type', 'begin_time', 'regimen_tuberculosis_code', 'end_time',
        'tuberculosis_treatmen_result', 'hiv_kd_date', 'arv_begin_time', 'ctx_begin_time',
    ];

    /**
     * his_exp_medimate_used - XML2 tach dong (NGAY_TH_YL_CUA_THUOC_OPTION).
     * Bang Oracle KHONG co sere_serv_id/medicine_id/material_id (kiem all_tab_columns 22/10/2026):
     * chi khop duoc theo exp_mest_medicine_id -> exp_mest_material_id.
     */
    const COT_SU_DUNG_THUOC = [
        'id', 'tdl_treatment_id', 'exp_mest_medicine_id', 'exp_mest_material_id', 'use_time', 'amount',
        'medication_session',
    ];

    /** his_tracking (to dieu tri) - XML5 nhanh 1 [AX]:719-728; content la CLOB (driver oci8 tra chuoi) */
    const COT_THEO_DOI = [
        'id', 'treatment_id', 'tracking_time', 'content', 'care_instruction', 'disease_stage', 'creator', 'modifier',
    ];

    /** his_debate - XML5 HOI_CHAN [AX]:725 (CONCLUSION theo TRACKING_ID) */
    const COT_HOI_CHAN = ['id', 'treatment_id', 'tracking_id', 'conclusion'];

    /** v_his_sere_serv_suin (chi so CDHA/TDCN) - XML4 khoi C [AX]:661, 668-673; view co san tdl_treatment_id */
    const COT_CHI_SO_CDHA = [
        'id', 'tdl_treatment_id', 'sere_serv_id', 'suim_index_id', 'suim_index_code', 'suim_index_name',
        'suim_index_unit_name', 'value', 'description',
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
            ['thuoc_tinh' => 'theoDoi', 'bang' => 'his_tracking', 'cot' => self::COT_THEO_DOI, 'cot_loc' => ['is_delete'], 'khoa' => 'treatment_id'],
            ['thuoc_tinh' => 'hoiChan', 'bang' => 'his_debate', 'cot' => self::COT_HOI_CHAN, 'cot_loc' => ['is_delete'], 'khoa' => 'treatment_id'],
            ['thuoc_tinh' => 'chiSoCdha', 'bang' => 'v_his_sere_serv_suin', 'cot' => self::COT_CHI_SO_CDHA, 'cot_loc' => ['is_delete'], 'khoa' => 'tdl_treatment_id'],
            // Dot 3: mot ban ghi / ho so ('mot' => true): sap id tang, dong sau (id lon nhat) ghi de dong truoc
            ['thuoc_tinh' => 'hivTreatment', 'bang' => 'his_hiv_treatment', 'cot' => self::COT_HIV, 'cot_loc' => ['is_delete'], 'khoa' => 'treatment_id', 'mot' => true],
            ['thuoc_tinh' => 'dieuTriLao', 'bang' => 'his_tuberculosis_treat', 'cot' => self::COT_LAO, 'cot_loc' => ['is_delete'], 'khoa' => 'treatment_id', 'mot' => true],
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
            $mot = !empty($n['mot']);
            foreach ($this->doc($n, $ids) as $dong) {
                if ($mot) {
                    // nhu tu dien theo TREATMENT_ID cua plugin HIS: dong sau ghi de dong truoc
                    $kq[(int) $dong->{$n['khoa']}]->{$n['thuoc_tinh']} = $dong;
                } else {
                    $kq[(int) $dong->{$n['khoa']}]->{$n['thuoc_tinh']}[] = $dong;
                }
            }
        }

        // Ekip gan tu danh sach CHUA loc, nhu plugin HIS (UCExportXml.cs:1224 nam ngoai khoi loc).
        $this->ganEkip($kq);
        foreach ($kq as $dl) {
            $dl->dichVu = array_values(array_filter($dl->dichVu, [self::class, 'thanhToanNhuHis']));
        }

        return $kq;
    }

    /**
     * Bo loc dau vao cua plugin HIS truoc khi goi DLL (UCExportXml.cs:1217, 3868): co loai dich vu BHYT,
     * AMOUNT > 0, PRICE > 0, khong hao phi, khong phai dich vu khong thuc hien. Moi bang (va KiemDieuKien)
     * nhan cung dau vao nhu HIS: khong co dong _TB, khong co dong gia 0 (key LAY_CA_DVU_0_DONG het tac dung).
     */
    public static function thanhToanNhuHis($dv)
    {
        return $dv->tdl_hein_service_type_id !== null && $dv->tdl_hein_service_type_id !== ''
            && (float) $dv->amount > 0 && (float) $dv->price > 0
            && (int) $dv->is_expend !== 1 && (int) $dv->is_no_execute !== 1;
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
            } elseif ($n['thuoc_tinh'] === 'theoDoi') {
                // DLL sap to dieu tri theo TRACKING_TIME ([AX]:715); them id de on dinh khi trung gio
                $q->orderBy('tracking_time')->orderBy('id');
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

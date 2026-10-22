<?php

namespace Tests\Support;

use App\Services\SinhXml3176\Bang\Xml1Dung;
use App\Services\SinhXml3176\Nguon\HisDanhMuc;
use Illuminate\Support\Facades\DB;

/**
 * Ho so HIS day du (qua KiemDieuKien, dung duoc XML1 + XML3) tren SQLite cua DungBangHisSinhXml3176Sqlite.
 * Dung cho test cap luot (LuotQuet, lenh sinhxml3176:quet). Goi dungBangHis() truoc.
 */
trait HoSoHisSinhXml3176
{
    /** Danh muc toi thieu: co so 1 (01929), doi tuong BHYT id 1, config ma BHYT, bac si bs1, ICD J18. */
    protected function themDanhMucLuot()
    {
        $his = DB::connection('HISPro');
        $his->table('his_branch')->insert(['id' => 1, 'hein_medi_org_code' => '01929']);
        $his->table('his_patient_type')->insert(['id' => 1, 'patient_type_code' => 'BHYT', 'patient_type_name' => 'BHYT']);
        $his->table('his_config')->insert([
            ['id' => 1, 'key' => HisDanhMuc::KEY_MA_DOI_TUONG_BHYT, 'value' => 'BHYT', 'is_active' => 1],
            ['id' => 2, 'key' => Xml1Dung::KEY_BHYT, 'value' => 'BHYT', 'is_active' => 1],
        ]);
        $his->table('his_employee')->insert(['id' => 1, 'loginname' => 'bs1', 'diploma' => 'CCHN1']);
        $his->table('his_icd')->insert(['id' => 1, 'icd_code' => 'J18', 'icd_name' => 'Viem phoi']);
    }

    /**
     * Them mot ho so: his_treatment (ung vien) + v_his_treatment_12 + dien dieu tri + mot DVKT BHYT.
     * fee_lock_time/out_time/branch_id/treatment_code trong $ghiHoSo duoc ghi dong bo vao ca hai bang.
     *
     * @return int treatment id
     */
    protected function themHoSoLuot($id, array $ghiHoSo = [], array $ghiDichVu = [])
    {
        $hs = array_merge([
            'id' => $id, 'treatment_code' => 'HS' . $id, 'branch_id' => 1,
            'tdl_patient_type_id' => 1, 'tdl_treatment_type_id' => 1,
            'tdl_patient_code' => 'BN' . $id, 'tdl_patient_name' => 'Nguyen Van ' . $id,
            'tdl_patient_dob' => 19800102000000, 'tdl_patient_gender_id' => 2,
            'tdl_patient_mps_national_code' => '000', 'ethnic_code' => '01', 'tdl_patient_address' => 'Dien Bien',
            'tdl_patient_province_code' => '11', 'tdl_patient_commune_code' => '03199',
            'hein_medi_org_code' => '01929', 'icd_code' => 'J18', 'icd_name' => 'Viem phoi',
            'in_time' => 20261001080000, 'out_time' => 20261001100000, 'fee_lock_time' => 20261001110000,
            'end_department_id' => 3, 'treatment_end_type_id' => 1, 'treatment_result_id' => 1,
            'treatment_result_code' => '01', 'hein_patient_type_code' => '1.1',
        ], $ghiHoSo);

        $his = DB::connection('HISPro');
        $his->table('his_treatment')->insert([
            'id' => $id, 'treatment_code' => $hs['treatment_code'], 'branch_id' => $hs['branch_id'],
            'tdl_patient_type_id' => $hs['tdl_patient_type_id'], 'tdl_treatment_type_id' => $hs['tdl_treatment_type_id'],
            'fee_lock_time' => $hs['fee_lock_time'], 'out_time' => $hs['out_time'], 'is_delete' => 0,
        ]);
        $his->table('v_his_treatment_12')->insert($hs);
        $his->table('v_his_patient_type_alter')->insert([
            'id' => $id, 'treatment_id' => $id, 'log_time' => 20261001080000, 'patient_type_id' => 1,
            'patient_type_code' => 'BHYT', 'hein_card_number' => 'DN4010000000001', 'hein_medi_org_code' => '01929',
            'hein_card_from_time' => 20260101000000, 'hein_card_to_time' => 20261231000000,
            'right_route_code' => 'DT', 'hein_treatment_type_code' => 'KB', 'is_delete' => 0,
        ]);
        $his->table('v_his_sere_serv_2')->insert(array_merge([
            'id' => $id, 'tdl_treatment_id' => $id, 'service_id' => 301, 'service_req_id' => 401,
            'patient_type_id' => 1, 'tdl_service_type_id' => 2, 'tdl_hein_service_type_id' => 1,
            'tdl_hein_service_bhyt_code' => '23.0001.1000', 'tdl_hein_service_bhyt_name' => 'Xet nghiem',
            'service_unit_name' => 'Lan', 'hst_bhyt_code' => '1', 'amount' => 1, 'price' => 100000,
            'original_price' => 100000, 'vir_price' => 100000, 'vat_ratio' => 0, 'hein_ratio' => 0.8,
            'vir_total_hein_price' => 80000, 'vir_total_patient_price_bhyt' => 20000,
            'intruction_time' => 20261001083000, 'tdl_intruction_time' => 20261001083000,
            'tdl_service_req_code' => 'YL' . $id, 'icd_code' => 'J18', 'request_loginname' => 'bs1',
            'execute_loginname' => 'bs1', 'request_bhyt_code' => 'K01',
            'start_time' => 20261001090000, 'finish_time' => 20261001093000, 'is_delete' => 0,
        ], $ghiDichVu));

        return $id;
    }

    /** Doi moc khoa vien phi (mo khoa roi khoa lai) tren ca hai bang. */
    protected function khoaLaiHoSo($id, $feeLockTime)
    {
        $his = DB::connection('HISPro');
        $his->table('his_treatment')->where('id', $id)->update(['fee_lock_time' => $feeLockTime]);
        $his->table('v_his_treatment_12')->where('id', $id)->update(['fee_lock_time' => $feeLockTime]);
    }

    /** Xoa de quy thu muc tam cua test. */
    protected function xoaThuMuc($thuMuc)
    {
        if (is_file($thuMuc)) {
            @unlink($thuMuc);

            return;
        }
        if (!is_dir($thuMuc)) {
            return;
        }
        foreach (scandir($thuMuc) as $f) {
            if ($f === '.' || $f === '..') {
                continue;
            }
            $p = $thuMuc . DIRECTORY_SEPARATOR . $f;
            is_dir($p) ? $this->xoaThuMuc($p) : @unlink($p);
        }
        @rmdir($thuMuc);
    }
}

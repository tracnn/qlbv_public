<?php

namespace App\Services\OrderCheck\Scanners;

use App\Services\OrderCheck\Contracts\Scanner;
use App\Services\OrderCheck\OrderCheckEngine;
use App\Services\OrderCheck\Support\Violation;
use App\Services\OrderCheck\Support\ViolationContext;
use App\Services\OrderCheck\RuleHandlers\Clinical\GenderRestrictionRule;
use App\Services\OrderCheck\RuleHandlers\Clinical\AgeRestrictionRule;
use App\Models\OrderCheck\OrderCheckRefServiceRestriction;
use App\Services\OrderCheck\Support\CuaSoQuet;

class ServiceRestrictionScanner implements Scanner
{
    const SOURCE_KEY = 'his_sere_serv_restriction';
    const RULE_GENDER = 'A_GENDER_MISMATCH';
    const RULE_AGE = 'A_AGE_OUT_OF_RANGE';

    public function sourceKey()
    {
        return self::SOURCE_KEY;
    }

    public function scan(OrderCheckEngine $engine, $limit)
    {
        $rules = $engine->activeRules();
        $genderActive = isset($rules[self::RULE_GENDER]);
        $ageActive = isset($rules[self::RULE_AGE]);

        $source = $engine->source();
        $wm = $engine->getWatermark(self::SOURCE_KEY);
        // Lay max(id) THAT SU truoc khi chay bat ky truy van lo nao (xem docblock CuaSoQuet).
        $maxIdHis = $source->maxSereServId();

        // Danh muc gioi han rong thi hai quy tac deu KHONG THE sinh vi pham - khong truy
        // van HIS lam gi. Do tren production: 24.402 dong da quet, 0 vi pham, ma van ton
        // 43 phut tong cong.
        //
        // Van phai DAY MOC, neu khong den luc nhap danh muc se ton dong ca chuc trieu dong
        // va roi lai dung van de hieu nang nay. Nhung dong bi bo qua trong luc danh muc
        // rong se khong duoc kiem lai - khong mat gi so voi hien tai, vi hom nay chung van
        // duoc quet nhung luon cho ket qua rong, va bo quet von chi chay toi truoc.
        //
        // Day THANG toi max(id) that, KHONG bi gioi han boi mot cua so: nhanh nay khong
        // quet gi ca nen chan cua so khong bao ve gi het, chi lam mot the tien qua cham
        // moi luot mot cach vo ich.
        if (!OrderCheckRefServiceRestriction::where('is_active', true)->exists()) {
            $engine->saveWatermark(self::SOURCE_KEY, $wm->last_create_time, max((int) $wm->last_id, $maxIdHis));

            return ['scanned' => 0, 'violations' => 0];
        }

        $cuoiCuaSo = CuaSoQuet::ketThuc($wm->last_id, $source->cuaSo(), $maxIdHis);
        $rows = $source->fetchSereServWithPatient($wm->last_create_time, $wm->last_id, $limit, $cuoiCuaSo);
        $scanned = $rows->count();
        $violations = 0;

        // Khai truoc khoi if: cua so rong van phai day moc duoc.
        $maxCreate = $wm->last_create_time;
        $maxId = $wm->last_id;

        if ($scanned > 0) {
            // Nạp danh mục giới hạn 1 lần, key theo service_code.
            $catalog = OrderCheckRefServiceRestriction::where('is_active', true)->get()->keyBy('service_code');

            // Tra thông tin phiếu (mã/loại/khoa thực hiện) theo batch để tránh join chậm.
            $reqIds = $rows->pluck('service_req_id')->filter()->map(function ($v) { return (int) $v; })->unique()->all();
            $reqMap = $source->fetchServiceReqInfoByIds($reqIds);

            $genderRule = new GenderRestrictionRule();
            $ageRule = new AgeRestrictionRule();
            $refYmd = date('Ymd');

            foreach ($rows as $row) {
                if (($genderActive || $ageActive) && isset($catalog[$row->tdl_service_code])) {
                    $ref = $catalog[$row->tdl_service_code];
                    $sr = isset($reqMap[(int) $row->service_req_id]) ? $reqMap[(int) $row->service_req_id] : null;
                    $vctx = $this->context($row, $sr);

                    if ($genderActive && $genderRule->mismatch($row->tdl_patient_gender_id, $ref->required_gender_id)) {
                        $vio = new Violation(
                            self::RULE_GENDER, 'sere_serv', (int) $row->id,
                            'Chỉ định DV giới hạn giới tính sai: ' . $row->tdl_service_code . ' - ' . $row->tdl_service_name,
                            ['service_code' => $row->tdl_service_code, 'required_gender_id' => (int) $ref->required_gender_id, 'patient_gender_id' => (int) $row->tdl_patient_gender_id]
                        );
                        if ($engine->persist($vio, $vctx, $rules[self::RULE_GENDER])) {
                            $violations++;
                        }
                    }

                    if ($ageActive && $ageRule->outOfRange($row->tdl_patient_dob, $ref->age_from, $ref->age_to, $refYmd)) {
                        $age = $ageRule->ageInYears($row->tdl_patient_dob, $refYmd);
                        $vio = new Violation(
                            self::RULE_AGE, 'sere_serv', (int) $row->id,
                            'Chỉ định DV ngoài ngưỡng tuổi: ' . $row->tdl_service_code . ' (BN ' . $age . ' tuổi, cho phép ' . $ref->age_from . '-' . $ref->age_to . ')',
                            ['service_code' => $row->tdl_service_code, 'age' => $age, 'age_from' => $ref->age_from, 'age_to' => $ref->age_to]
                        );
                        if ($engine->persist($vio, $vctx, $rules[self::RULE_AGE])) {
                            $violations++;
                        }
                    }
                }

                if ((int) $row->create_time > $maxCreate || ((int) $row->create_time == $maxCreate && (int) $row->id > $maxId)) {
                    $maxCreate = (int) $row->create_time;
                    $maxId = (int) $row->id;
                }
            }
        }

        // Ngoai khoi if: cua so rong cung phai day moc, neu khong bo quet dung im vinh vien.
        $engine->saveWatermark(
            self::SOURCE_KEY,
            $maxCreate,
            CuaSoQuet::mocMoi(
                $wm->last_id, $scanned, $limit, $maxId, $cuoiCuaSo
            )
        );

        return ['scanned' => $scanned, 'violations' => $violations];
    }

    private function context($row, $sr)
    {
        return ViolationContext::make([
            'treatment_id' => (int) $row->tdl_treatment_id,
            'treatment_code' => $row->treatment_code,
            'patient_code' => $row->tdl_patient_code,
            'patient_name' => $row->tdl_patient_name,
            'department_id' => ($sr && $sr->execute_department_id !== null) ? (int) $sr->execute_department_id : null,
            'service_req_code' => $sr ? $sr->service_req_code : null,
            'service_req_type_id' => ($sr && $sr->service_req_type_id !== null) ? (int) $sr->service_req_type_id : null,
            'service_code' => $row->tdl_service_code,
            'service_name' => $row->tdl_service_name,
            // ma_cskcb da co san tren $row nho fetchSereServWithPatient() join his_branch.
            'ma_cskcb' => $row->ma_cskcb,
        ]);
    }
}

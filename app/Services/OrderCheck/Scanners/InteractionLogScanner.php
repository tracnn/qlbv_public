<?php

namespace App\Services\OrderCheck\Scanners;

use App\Services\OrderCheck\Contracts\Scanner;
use App\Services\OrderCheck\OrderCheckEngine;
use App\Services\OrderCheck\Support\Violation;
use App\Services\OrderCheck\Support\ViolationContext;
use App\Services\OrderCheck\Support\CuaSoQuet;

class InteractionLogScanner implements Scanner
{
    const SOURCE_KEY = 'his_medicine_interactive';
    const RULE_CODE = 'A_DRUG_INTERACTION';

    public function sourceKey()
    {
        return self::SOURCE_KEY;
    }

    public function scan(OrderCheckEngine $engine, $limit)
    {
        $rulesByCode = $engine->activeRules();

        // Rule A1 bị tắt → vẫn tiến watermark để không tồn đọng, nhưng không sinh violation.
        $ruleActive = isset($rulesByCode[self::RULE_CODE]);
        $rule = $ruleActive ? $rulesByCode[self::RULE_CODE] : null;

        $source = $engine->source();
        $wm = $engine->getWatermark(self::SOURCE_KEY);
        // Lay max(id) THAT SU truoc khi chay truy van lo (xem docblock CuaSoQuet).
        $maxIdHis = $source->maxMedicineInteractiveId();
        $cuoiCuaSo = CuaSoQuet::ketThuc($wm->last_id, $source->cuaSo(), $maxIdHis);
        $rows = $source->fetchInteractions($wm->last_create_time, $wm->last_id, $limit, $cuoiCuaSo);
        $scanned = $rows->count();
        $violations = 0;

        // Khai truoc khoi if: cua so rong van phai day moc duoc.
        $maxCreate = $wm->last_create_time;
        $maxId = $wm->last_id;

        if ($scanned > 0) {
            // Ma CSKCB khong co san tren his_medicine_interactive, tra theo treatment_id
            // qua HisOrderSource::fetchTreatmentInfo() (cung ham MedicineScanner dung).
            $info = [];
            if ($ruleActive) {
                $treatmentIds = [];
                foreach ($rows as $row) {
                    $treatmentIds[(int) $row->treatment_id] = true;
                }
                $info = $source->fetchTreatmentInfo(array_keys($treatmentIds));
            }

            foreach ($rows as $row) {
                if ($ruleActive) {
                    $tid = (int) $row->treatment_id;
                    $vctx = $this->context($row, isset($info[$tid]) ? $info[$tid] : null);

                    $vio = new Violation(
                        self::RULE_CODE,
                        'medicine_interactive',
                        (int) $row->id,
                        'Tương tác thuốc (HIS phát hiện): cặp thuốc ' . $row->medicine_type_id1 . ' - ' . $row->medicine_type_id2 . ', mức độ ' . $row->interactive_grade_id,
                        [
                            'medicine_type_id1' => (int) $row->medicine_type_id1,
                            'medicine_type_id2' => (int) $row->medicine_type_id2,
                            'interactive_grade_id' => $row->interactive_grade_id !== null ? (int) $row->interactive_grade_id : null,
                            'icd_code' => $row->icd_code,
                        ]
                    );

                    if ($engine->persist($vio, $vctx, $rule)) {
                        $violations++;
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

    /**
     * $info da chua san ma dot dieu tri va thong tin benh nhan (fetchTreatmentInfo tra
     * ve chung cung luot voi ma_cskcb) - lay het, dung lang phi mot truy van da chay.
     * Thieu chung thi bo loc tu khoa cua dashboard (ma BN, ten BN, ma dot) khong bao gio
     * tim ra dong tuong tac thuoc nao.
     *
     * KHONG gan service_req_* / service_*: ban ghi tuong tac thuoc khong gan voi phieu
     * chi dinh hay dich vu nao, de trong o day la dung ban chat.
     */
    private function context($row, $info)
    {
        return ViolationContext::make([
            'treatment_id' => (int) $row->treatment_id,
            'treatment_code' => $this->truong($info, 'treatment_code'),
            'patient_code' => $this->truong($info, 'tdl_patient_code'),
            'patient_name' => $this->truong($info, 'tdl_patient_name'),
            'doctor_loginname' => $row->request_loginname,
            // his_medicine_interactive KHONG co cot ten - ten den tu join his_employee
            // trong fetchInteractions(). Ban ghi cu khong co thuoc tinh nay thi de trong.
            'doctor_username' => $this->truong($row, 'request_username'),
            'department_id' => $row->request_department_id !== null ? (int) $row->request_department_id : null,
            'ma_cskcb' => $this->truong($info, 'ma_cskcb'),
        ]);
    }

    /**
     * Doc mot truong co the vang mat. Khong gia dinh $info luon du truong: mot vi pham
     * thieu thong tin van con dung hon la ca lo quet chet vi mot doi tuong khuyet cot.
     */
    private function truong($doiTuong, $ten)
    {
        return $doiTuong !== null && isset($doiTuong->$ten) ? $doiTuong->$ten : null;
    }
}

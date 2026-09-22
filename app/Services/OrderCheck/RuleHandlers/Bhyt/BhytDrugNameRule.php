<?php

namespace App\Services\OrderCheck\RuleHandlers\Bhyt;

/**
 * Ten HOAT CHAT khai o HIS lech ten hoat chat trong danh muc thuoc BHYT con hieu luc.
 *
 * HIS khai ten hoat chat (his_medicine_type.active_ingr_bhyt_name), nen phai doi chieu
 * voi cot ten_hoat_chat. Ban cu so voi ten_thuoc (ten thuong mai) - vi du ma 40.220 bi bao
 * "Clarithromycin" lech "Klacid MR", hai khai niem khac nhau (sua 2026-09-22).
 *
 * Giu nguyen ma quy tac A_BHYT_DRUG_NAME_MISMATCH de khong vo lich su vi pham va cau hinh
 * bat/tat da luu.
 */
class BhytDrugNameRule extends BhytNameMismatchRule
{
    public function code()          { return 'A_BHYT_DRUG_NAME_MISMATCH'; }
    protected function bang()       { return 'medicine_catalogs'; }
    protected function cot()        { return 'ma_thuoc'; }
    protected function cotTen()     { return 'ten_hoat_chat'; }
    protected function nhan()       { return 'Tên hoạt chất'; }
    protected function loaiDichVu() { return [self::LOAI_THUOC]; }

    /**
     * Ten hoat chat THUAN tu HIS. Rong (38/8.544 loai thuoc tren HIS co ma ma thieu ten)
     * thi quy tac im lang, khong roi ve ten dich vu - nguoi dung chot 2026-09-22.
     */
    protected function tenKhai($s)
    {
        return $s->activeIngrName;
    }
}

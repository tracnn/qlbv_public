<?php

namespace App\Services\OrderCheck\RuleHandlers\Clinical;

class GenderRestrictionRule
{
    public function code()
    {
        return 'A_GENDER_MISMATCH';
    }

    /**
     * True nếu giới tính BN khác giới tính DV yêu cầu (chỉ xét 1=Nữ, 2=Nam).
     */
    public function mismatch($patientGenderId, $requiredGenderId)
    {
        $p = (int) $patientGenderId;
        $r = (int) $requiredGenderId;
        if ($r !== 1 && $r !== 2) {
            return false; // DV không giới hạn / KXĐ
        }
        if ($p !== 1 && $p !== 2) {
            return false; // BN không xác định giới → không gắn cờ
        }
        return $p !== $r;
    }
}

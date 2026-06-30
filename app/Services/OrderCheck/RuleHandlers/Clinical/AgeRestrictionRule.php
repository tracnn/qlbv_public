<?php

namespace App\Services\OrderCheck\RuleHandlers\Clinical;

class AgeRestrictionRule
{
    public function code()
    {
        return 'A_AGE_OUT_OF_RANGE';
    }

    /**
     * True nếu tuổi BN (năm tròn) ngoài [ageFrom, ageTo]. Bỏ qua khi thiếu ngày sinh.
     * @param string|int $dob YYYYMMDD... ; @param int|null $ageFrom ; @param int|null $ageTo ; @param string $refYmd YYYYMMDD
     */
    public function outOfRange($dob, $ageFrom, $ageTo, $refYmd)
    {
        $age = $this->ageInYears($dob, $refYmd);
        if ($age === null) {
            return false;
        }
        if ($ageFrom !== null && $ageFrom !== '' && $age < (int) $ageFrom) {
            return true;
        }
        if ($ageTo !== null && $ageTo !== '' && $age > (int) $ageTo) {
            return true;
        }
        return false;
    }

    public function ageInYears($dob, $refYmd)
    {
        $dob = (string) $dob;
        $by = (int) substr($dob, 0, 4);
        if ($by <= 0) {
            return null; // không rõ năm sinh
        }
        $bm = (int) substr($dob, 4, 2);
        $bd = (int) substr($dob, 6, 2);

        $refYmd = (string) $refYmd;
        $ry = (int) substr($refYmd, 0, 4);
        $rm = (int) substr($refYmd, 4, 2);
        $rd = (int) substr($refYmd, 6, 2);

        $age = $ry - $by;
        if ($rm < $bm || ($rm === $bm && $rd < $bd)) {
            $age--;
        }
        return $age < 0 ? null : $age;
    }
}

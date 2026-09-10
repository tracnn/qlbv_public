<?php

namespace App\Services\Xml3176\Support;

/**
 * Tach ma DVKT goc tu ma khai trong XML3.
 *
 * Co so dat them hau to sau dau gach duoi de phan biet bien the cua cung mot dich vu
 * (vi du 02.0261.0319_TB), trong khi danh muc BHXH chi liet ke ma goc. Khong bo hau to
 * thi 11/307 dong do duoc tren du lieu that se lot luoi.
 *
 * Helper thuan - khong cham DB/model/config.
 */
class MaDvktMatcher
{
    public static function maGoc($ma): string
    {
        $ma = trim((string) $ma);
        $pos = strpos($ma, '_');

        return $pos === false ? $ma : trim(substr($ma, 0, $pos));
    }
}

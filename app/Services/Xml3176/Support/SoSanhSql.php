<?php

namespace App\Services\Xml3176\Support;

/**
 * Ngu nghia so sanh chuoi cua MySQL/MariaDB tren cot VARCHAR utf8_general_ci, dung chung cho
 * checker (NhoTheoHoSo) va DuLieuHoSo.
 */
class SoSanhSql
{
    /**
     * Khoa so sanh chuoi theo ngu nghia '=' cua MySQL (collation *_general_ci, PAD SPACE):
     * khong phan biet hoa thuong, bo khoang trang CUOI (chi dau cach, khong bo tab/xuong dong).
     * Khac biet con lai: general_ci con coi chu co dau = khong dau; ma DVKT la ASCII nen khong anh huong.
     */
    public static function khoa($s)
    {
        return mb_strtoupper(rtrim((string) $s, ' '), 'UTF-8');
    }
}

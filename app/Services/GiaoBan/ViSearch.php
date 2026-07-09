<?php

namespace App\Services\GiaoBan;

/**
 * Hỗ trợ tìm kiếm tiếng Việt KHÔNG phân biệt dấu (accent-insensitive).
 * Dữ liệu HIS lưu có dấu (VD "Nguyễn"); người dùng thường gõ không dấu ("nguyen").
 * - noDiacriticsSql(): biểu thức Oracle TRANSLATE bỏ dấu một cột (đã LOWER).
 * - normalize(): chuẩn hóa chuỗi người nhập (lowercase + bỏ dấu) phía PHP.
 * FROM/TO phải cùng số ký tự (Oracle TRANSLATE ánh xạ theo vị trí).
 */
class ViSearch
{
    const FROM = 'áàảãạăắằẳẵặâấầẩẫậéèẻẽẹêếềểễệíìỉĩịóòỏõọôốồổỗộơớờởỡợúùủũụưứừửữựýỳỷỹỵđ';
    const TO   = 'aaaaaaaaaaaaaaaaaeeeeeeeeeeeiiiiiooooooooooooooooouuuuuuuuuuuyyyyyd';

    /** Biểu thức SQL Oracle: bỏ dấu + lowercase cột. */
    public static function noDiacriticsSql($colExpr)
    {
        return "TRANSLATE(LOWER($colExpr), '" . self::FROM . "', '" . self::TO . "')";
    }

    /** Chuẩn hóa chuỗi người nhập: lowercase + bỏ dấu. */
    public static function normalize($s)
    {
        $from = preg_split('//u', self::FROM, -1, PREG_SPLIT_NO_EMPTY);
        $to = preg_split('//u', self::TO, -1, PREG_SPLIT_NO_EMPTY);
        return str_replace($from, $to, mb_strtolower((string) $s));
    }
}

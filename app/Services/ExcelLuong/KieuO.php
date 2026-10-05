<?php

namespace App\Services\ExcelLuong;

use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Shared\StringHelper;

/**
 * Kieu mot o khi ghi luong - GIONG quy tac Laravel Excel dang dung (DefaultValueBinder) de
 * tep moi khong khac tep cu. Goi thang ham cua PhpSpreadsheet, khong viet lai quy tac.
 *
 * Khac biet CO Y: chuoi bat dau '=' ghi thanh CHU. Ban cu bien no thanh cong thuc Excel -
 * mo ta loi tu du lieu co the chen cong thuc vao tep.
 */
final class KieuO
{
    /** Nhu DefaultValueBinder. */
    const TU_DONG = 'tu_dong';

    /** Nhu StringValueBinder: moi o khac rong la chu. */
    const CHU = 'chu';

    /**
     * @param mixed $v
     * @return array [gia tri ghi, la so?] - null nghia la o trong
     */
    public static function chuyen($v, string $kieu): array
    {
        if ($v === null || $v === '') {
            return [null, false];
        }

        if ($kieu === self::CHU) {
            return [self::lamSachChuoi($v), false];
        }

        switch (DefaultValueBinder::dataTypeForValue($v)) {
            case DataType::TYPE_NUMERIC:
                // '202610050800' + 0 = int; '1.5' + 0 = float.
                return [is_string($v) ? $v + 0 : $v, true];
            case DataType::TYPE_BOOL:
                return [$v, false];
            default:
                // STRING, FORMULA (co y ghi chu), ERROR.
                return [self::lamSachChuoi($v), false];
        }
    }

    /**
     * Ban cu qua DefaultValueBinder: sanitizeUTF8 (bo byte UTF-8 hong) + checkString (cat 32.767 ky tu,
     * doi CRLF thanh LF). Ghi luong phai lam lai, neu khong Spout nem exception voi chuoi dai
     * (mo ta loi la TEXT toi 64KB) va nuot o co byte hong.
     *
     * @param mixed $v
     */
    private static function lamSachChuoi($v): string
    {
        return DataType::checkString(StringHelper::sanitizeUTF8((string) $v));
    }
}

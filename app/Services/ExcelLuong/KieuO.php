<?php

namespace App\Services\ExcelLuong;

use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;

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
            return [(string) $v, false];
        }

        switch (DefaultValueBinder::dataTypeForValue($v)) {
            case DataType::TYPE_NUMERIC:
                // '202610050800' + 0 = int; '1.5' + 0 = float.
                return [is_string($v) ? $v + 0 : $v, true];
            case DataType::TYPE_BOOL:
                return [$v, false];
            default:
                // STRING, FORMULA (co y ghi chu), ERROR.
                return [(string) $v, false];
        }
    }
}

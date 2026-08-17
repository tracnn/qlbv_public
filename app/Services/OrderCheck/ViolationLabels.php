<?php

namespace App\Services\OrderCheck;

/**
 * Nhan tieng Viet dung chung cho severity/status cua vi pham order-check.
 *
 * Mot nguon duy nhat: OrderCheckController (bang datatable), man tra cuu loi ho so (JS -
 * nhan mang nay qua bien PHP nhung xuong template, xem tra-cuu-loi-ho-so.blade.php) va
 * phieu in (tra-cuu-loi-ho-so-in.blade.php) deu doc tu day. Truoc day moi noi tu dich mot
 * kieu, dan den ba ban dich khac nhau cho cung mot enum.
 */
class ViolationLabels
{
    public static function severity()
    {
        return [
            'critical' => 'Nghiêm trọng',
            'warning'  => 'Cảnh báo',
            'info'     => 'Thông tin',
        ];
    }

    public static function status()
    {
        return [
            'new'            => 'Mới',
            'seen'           => 'Đã xem',
            'processed'      => 'Đã xử lý',
            'false_positive' => 'Bỏ qua',
        ];
    }

    public static function severityLabel($severity)
    {
        $map = self::severity();

        return isset($map[$severity]) ? $map[$severity] : $severity;
    }

    public static function statusLabel($status)
    {
        $map = self::status();

        return isset($map[$status]) ? $map[$status] : $status;
    }
}

<?php

/**
 * Do thoi gian va dinh bo nho khi xuat file loi XML3176 tren CSDL hien tai.
 *
 * Xuat TOAN BO ho so (khoang ngay tao 2000-2099, khong loc gi khac) de do dung khoi
 * luong xau nhat. Khong chay khi dang dang nhap nen Xml3176LocDanhSach khong ap pham vi
 * nguoi nap - tuc la lay het.
 *
 * KHONG CHAY tren may chu san xuat (qlbv_public): xuat TOAN BO ho so co the ton hon
 * 25 phut va hon 3 GB RAM, se choan tai nguyen may chu dang phuc vu nguoi dung that.
 * Chi chay tren may dev/CSDL doc de do dac.
 *
 * Chay: php scripts/do-xuat-loi-xml3176.php truoc.xlsx
 * Tep ra: storage/app/do-xuat-loi/<ten-tep>
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$ten = isset($argv[1]) ? $argv[1] : 'do-xuat-loi.xlsx';

$loc = array_merge(
    array_fill_keys(App\Services\BHYT\Xml3176LocDanhSach::KHOA, null),
    [
        'date_from' => '2000-01-01 00:00:00',
        'date_to'   => '2099-12-31 23:59:59',
        'date_type' => 'date_create',
    ]
);

$batDau = microtime(true);

Maatwebsite\Excel\Facades\Excel::store(
    new App\Exports\Xml3176ErrorMultiSheetExport($loc, []),
    'do-xuat-loi/' . $ten,
    'local'
);

printf(
    "thoi_gian_giay=%.1f dinh_bo_nho_mb=%.0f tep=%s\n",
    microtime(true) - $batDau,
    memory_get_peak_usage(true) / 1048576,
    storage_path('app/do-xuat-loi/' . $ten)
);

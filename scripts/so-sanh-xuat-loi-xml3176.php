<?php

/**
 * Xuat tep loi XML3176 MOT ngay bang MOT duong, in thoi gian + dinh bo nho - de doi chieu
 * duong luong (GhiExcelLuong) voi duong cu (Laravel Excel). Moi duong mot tien trinh rieng
 * de dinh bo nho khong lan nhau.
 *
 * KHONG CHAY tren may chu san xuat: ngay lon duong cu ton hon 4 GB RAM va 20 phut.
 *
 * Chay:  php scripts/so-sanh-xuat-loi-xml3176.php <luong|cu> <Y-m-d> <date_type> [xml_filter_status]
 * Vd:    php scripts/so-sanh-xuat-loi-xml3176.php luong 2026-10-04 date_payment
 *        php scripts/so-sanh-xuat-loi-xml3176.php luong 2026-10-05 date_create has_error
 * Tep:   storage/app/so-sanh/<ngay>-<duong>.xlsx
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

set_time_limit(0);
ini_set('memory_limit', '4096M');

list(, $duong, $ngay, $loaiNgay) = $argv + [null, null, null, null];
$trangThai = isset($argv[4]) ? $argv[4] : null;

if (!in_array($duong, ['luong', 'cu'], true) || !$ngay || !$loaiNgay) {
    fwrite(STDERR, "Cach dung: php scripts/so-sanh-xuat-loi-xml3176.php <luong|cu> <Y-m-d> <date_type> [xml_filter_status]\n");
    exit(2);
}

$loc = ['date_from' => "$ngay 00:00:00", 'date_to' => "$ngay 23:59:59", 'date_type' => $loaiNgay];
if ($trangThai) {
    $loc['xml_filter_status'] = $trangThai;
}

$tuongDoi = "so-sanh/$ngay-$duong.xlsx";
$tuyetDoi = storage_path('app/' . $tuongDoi);
$batDau = microtime(true);

register_shutdown_function(function () use ($batDau, $duong, $tuyetDoi) {
    printf("[%s] %.0f s, dinh %d MB, tep %s (%s)\n", $duong, microtime(true) - $batDau,
        memory_get_peak_usage(true) / 1048576, $tuyetDoi,
        is_file($tuyetDoi) ? number_format(filesize($tuyetDoi)) . ' byte' : 'KHONG CO');
});

$export = new App\Exports\Xml3176ErrorMultiSheetExport($loc, App\Services\BHYT\DanhSachCoSo::danhSach());

if ($duong === 'luong') {
    (new App\Services\ExcelLuong\GhiExcelLuong())->ghi($export->sheets(), $tuyetDoi);
} else {
    Maatwebsite\Excel\Facades\Excel::store($export, $tuongDoi, 'local');
}

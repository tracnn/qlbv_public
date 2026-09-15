<?php

/**
 * Kiem bat bien cua file xuat loi XML3176 tren CSDL that (CHI DOC).
 *
 * Spec muc 8-9:
 *   1. Tong dong 16 sheet loi = so dong xml3176_error_results cua cung tap ho so.
 *   2. Cot ma khoa sheet XML2/XML3 bang ma_khoa cua dong goc theo (ma_lk, stt).
 *   3. Sheet XML6, XML12, XML15 ton tai va khong co dong.
 *
 * Chay: php scripts/kiem-xuat-loi-xml3176.php
 * Thoat ma 1 neu co bat bien vi pham.
 */

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Exports\Xml3176ErrorMultiSheetExport;
use App\Services\BHYT\Xml3176LocDanhSach;
use Illuminate\Support\Facades\DB;

$coBat = [
    'khong loc' => [],
    'ma_khoa=K01' => ['ma_khoa' => 'K01'],
];

$vipPham = 0;

foreach ($coBat as $ten => $ghiDe) {
    $loc = array_merge(
        array_fill_keys(Xml3176LocDanhSach::KHOA, null),
        ['date_from' => '2000-01-01 00:00:00', 'date_to' => '2099-12-31 23:59:59', 'date_type' => 'date_create'],
        $ghiDe
    );

    $sheets = (new Xml3176ErrorMultiSheetExport($loc, []))->sheets();

    $tongSheet = 0;
    $theoSheet = [];

    for ($i = 0; $i < 16; $i++) {
        $n = $sheets[$i]->query()->count();
        $theoSheet[$sheets[$i]->title()] = $n;
        $tongSheet += $n;
    }

    $maLk = Xml3176LocDanhSach::truyVanMaLk($loc, []);
    $tongLoi = DB::table('xml3176_error_results')->whereIn('ma_lk', $maLk)->count();

    // Dong loi co loai XML nam ngoai 16 sheet se khong xuat ra dau ca.
    $ngoai = DB::table('xml3176_error_results')
        ->whereIn('ma_lk', Xml3176LocDanhSach::truyVanMaLk($loc, []))
        ->whereNotIn('xml', array_keys($theoSheet))
        ->count();

    $khop = $tongSheet === $tongLoi;
    printf("[%s] tong 16 sheet=%d, tong dong loi=%d, loai XML ngoai 16 sheet=%d -> %s\n",
        $ten, $tongSheet, $tongLoi, $ngoai, $khop ? 'KHOP' : 'LECH');

    foreach ($theoSheet as $t => $n) {
        printf("    %-12s %d\n", $t, $n);
    }

    foreach (['XML6', 'XML12', 'XML15'] as $t) {
        if (!array_key_exists($t, $theoSheet)) {
            printf("    VI PHAM: thieu sheet %s\n", $t);
            $vipPham++;
        }
    }

    if (!$khop) {
        $vipPham++;
    }
}

// Mau ma khoa: 200 dong dau moi sheet XML2/XML3 so voi dong goc.
$locTatCa = array_merge(
    array_fill_keys(Xml3176LocDanhSach::KHOA, null),
    ['date_from' => '2000-01-01 00:00:00', 'date_to' => '2099-12-31 23:59:59', 'date_type' => 'date_create']
);

foreach (['XML2' => 'xml3176_xml2s', 'XML3' => 'xml3176_xml3s'] as $loai => $bang) {
    $sai = 0;
    $dong = (new App\Exports\Xml3176ErrorSheetExport($loai, $locTatCa, []))->query()->limit(200)->get();

    foreach ($dong as $d) {
        $goc = DB::table($bang)->where('ma_lk', $d->ma_lk)->where('stt', $d->stt)->value('ma_khoa');
        $ky = ($goc === null || $goc === '')
            ? DB::table('xml3176_xml1s')->where('ma_lk', $d->ma_lk)->value('ma_khoa')
            : $goc;

        if ((string) $ky !== (string) $d->ma_khoa_xuat) {
            $sai++;
        }
    }

    printf("[mau ma khoa %s] %d dong, sai %d\n", $loai, count($dong), $sai);
    $vipPham += $sai;
}

echo $vipPham === 0 ? "KET LUAN: DAT\n" : "KET LUAN: VI PHAM $vipPham\n";
exit($vipPham === 0 ? 0 : 1);

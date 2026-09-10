<?php

namespace App\Services\Xml3176\Support;

/**
 * Doc truong LIEU_DUNG cua bang XML2 theo chuan du lieu dau ra (QD 130/QD-BYT, sua doi
 * theo QD 4750/QD-BYT).
 *
 * Chuan dinh nghia BA hinh dang hop le, khong phai mot:
 *
 *   1. Ba phan  - ngoai tru/noi tru va thuoc thang YHCT:
 *      "2 viên/lần * 2 lần/ngày * 5 ngày [4 viên/ngày]"   hoac  "12g * 1 thang * 5 ngày"
 *      Moi so DEU co the kem don vi ngay sau no.
 *   2. Hai phan - thuoc dung ngoai (nho giot, boi...) khong xac dinh duoc lieu luong:
 *      "So lan dung trong ngay * so ngay su dung"
 *   3. Theo buoi - lieu thay doi trong ngay:
 *      "Sáng: 3 viên, Chiều: 2 viên, Tối: 1 viên [6 viên/ngày]"  (khong co dau '*')
 *
 * Ban dau tien cua lop nay chi biet dang "so_tran * so_tran * so_tran" - mot dang chuan
 * KHONG he neu ra. No bac bo ca ba vi du in trong chuan, va tren du lieu that lam
 * XML2_LIEU_DUNG_INVALID_FORMAT no 15.585 lan tren 100% dong thuoc: 97,6% dong that la
 * dang hai phan, 2,1% la dang theo buoi, chi 0,8% bat dau bang mot chu so.
 *
 * Nang hon: parse that bai lam checker return som, nen ba quy tac phia sau
 * (QUANTITY_MISMATCH, UNIT_INVALID, PRESCRIPTION_EXCEEDS_30_DAYS) chua chay lan nao -
 * ca ba deu 0 loi tu truoc toi nay.
 *
 * Helper thuan - khong cham DB/model/config.
 */
class LieuDungParser
{
    /** So dan dau mot phan, cho phep dau phay hoac dau cham lam dau thap phan. */
    const SO_DAN_DAU = '/^\s*(\d+(?:[.,]\d+)?)/u';

    public static function parse($lieu): array
    {
        $rong = [
            'hop_le' => false, 'dang' => '', 'sl_lan' => 0.0, 'lan_ngay' => 0.0,
            'so_ngay' => 0, 'don_vi' => '', 'tong_ngay' => 0.0,
            'tong_luong' => 0.0, 'co_tong_luong' => false,
        ];

        if ($lieu === null) {
            return $rong;
        }

        $s = trim((string) $lieu);
        if ($s === '') {
            return $rong;
        }

        // Phan "[tong so thuoc/ngay]" o cuoi, chung cho ca ba dang.
        $tongNgay = 0.0;
        $donVi    = '';
        if (preg_match('/\[([^\]]*)\]\s*$/u', $s, $m)) {
            list($tongNgay, $donVi) = self::docNgoac($m[1]);
            $s = trim(substr($s, 0, strrpos($s, '[')));
        }

        $phan   = explode('*', $s);
        $soPhan = count($phan);

        if ($soPhan === 3) {
            return self::baPhan($phan, $rong, $tongNgay, $donVi);
        }

        if ($soPhan === 2) {
            return self::haiPhan($phan, $rong, $tongNgay, $donVi);
        }

        if ($soPhan === 1) {
            return self::theoBuoi($s, $rong, $tongNgay, $donVi);
        }

        // Bon phan tro len: khong dang nao cua chuan.
        return $rong;
    }

    /**
     * "2 viên/lần * 2 lần/ngày * 5 ngày" - ca ba phan deu phai co so dan dau.
     * Tong luong suy tu chinh ba so, khong can phan trong ngoac.
     */
    private static function baPhan(array $phan, array $rong, $tongNgay, $donVi): array
    {
        $a = self::soDanDau($phan[0]);
        $b = self::soDanDau($phan[1]);
        $c = self::soDanDau($phan[2]);

        if ($a === null || $b === null || $c === null) {
            return $rong;
        }

        return [
            'hop_le' => true, 'dang' => 'ba_phan',
            'sl_lan' => $a, 'lan_ngay' => $b, 'so_ngay' => (int) $c,
            'don_vi' => $donVi, 'tong_ngay' => $tongNgay,
            'tong_luong' => round($a * $b * (int) $c, 3), 'co_tong_luong' => true,
        ];
    }

    /**
     * "So lan dung trong ngay * so ngay su dung" - phan dau co the la loi dan van xuoi
     * (du lieu that: "Ngày uống 1 viên buổi sáng * 60 ngày"), nen chi doi phan thu hai
     * co so dan dau.
     *
     * KHONG suy ra tong luong ca dot tu phan [tong/ngay] x so ngay, du phep nhan do
     * nhin rat hop ly. Do tren du lieu that: cach do sinh 506 khac biet thi 457 (90%)
     * la ao - 187 do don vi trong ngoac khac DON_VI_TINH cua thuoc ("80 Ml" thanh toan
     * theo ml nhung ngoac ghi "0,80 Chai/ngay"), va 270 do gia tri trong ngoac bi lam
     * tron 1-2 chu so roi nhan len nhieu ngay ("[1,3 Vien/ngay]" x 30 = 39 trong khi
     * so that la 40). Ngoac la mot gia tri HIEN THI da lam tron, co the o don vi khac -
     * khong phai can cu de doi chieu tien hay so luong.
     *
     * Van tra 'tong_ngay' va 'don_vi' de nguoi doc tham khao, nhung co_tong_luong = false
     * de quy tac doi chieu so luong im lang: thieu can cu thi khong ket luan.
     */
    private static function haiPhan(array $phan, array $rong, $tongNgay, $donVi): array
    {
        $soNgay = self::soDanDau($phan[1]);
        if ($soNgay === null) {
            return $rong;
        }

        return [
            'hop_le' => true, 'dang' => 'hai_phan',
            'sl_lan' => 0.0, 'lan_ngay' => 0.0, 'so_ngay' => (int) $soNgay,
            'don_vi' => $donVi, 'tong_ngay' => $tongNgay,
            'tong_luong' => 0.0, 'co_tong_luong' => false,
        ];
    }

    /**
     * "Sáng: 3 viên, Chiều: 2 viên, Tối: 1 viên" - nhan dien bang dau hai cham ngan
     * buoi voi lieu.
     *
     * Dang nay KHONG ma hoa so ngay, nen khong bao gio suy ra duoc tong luong ca dot -
     * quy tac doi chieu so luong phai im lang.
     */
    private static function theoBuoi($s, array $rong, $tongNgay, $donVi): array
    {
        if (strpos($s, ':') === false) {
            return $rong;
        }

        return [
            'hop_le' => true, 'dang' => 'theo_buoi',
            'sl_lan' => 0.0, 'lan_ngay' => 0.0, 'so_ngay' => 0,
            'don_vi' => $donVi, 'tong_ngay' => $tongNgay,
            'tong_luong' => 0.0, 'co_tong_luong' => false,
        ];
    }

    /** Tach "4 viên/ngày" thanh [4.0, 'viên']; "Viên/ngày" thanh [0.0, 'Viên']. */
    private static function docNgoac($trong): array
    {
        $trong = trim($trong);
        $so    = 0.0;

        if (preg_match(self::SO_DAN_DAU, $trong, $m)) {
            $so    = (float) str_replace(',', '.', $m[1]);
            $trong = trim(substr($trong, strlen($m[0])));
        }

        // Bo hau to "/ngay".
        $donVi = preg_replace('#\s*/\s*ng[aà]y\s*$#ui', '', $trong);

        return [$so, trim($donVi)];
    }

    /** So dan dau cua mot phan, hoac null neu phan do khong bat dau bang so. */
    private static function soDanDau($phan)
    {
        if (!preg_match(self::SO_DAN_DAU, trim((string) $phan), $m)) {
            return null;
        }

        return (float) str_replace(',', '.', $m[1]);
    }
}

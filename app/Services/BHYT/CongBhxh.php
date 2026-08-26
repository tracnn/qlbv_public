<?php

namespace App\Services\BHYT;

/**
 * Diem ghep URL DUY NHAT cua cong tiep nhan du lieu BHXH.
 *
 * VI SAO TACH RA MOT LOP: host cua cong doi theo moi truong (thu nghiem / chinh thuc),
 * con duong dan tung dich vu la HANG SO GIAO THUC do BHXH quy dinh. Hai thu doi theo hai
 * nhip khac nhau nen phai o hai noi: host trong config/organization.php (tep rieng cua
 * tung may, nam trong .gitignore), duong dan trong config/ctdt.php va config/tt12.php
 * (di theo kho ma).
 *
 * VI SAO NEM CHU KHONG ROI VE MAC DINH khi thieu base_url: config/organization.php nam
 * trong .gitignore, nen mot ban sao moi KHONG co khoa nay. Neu thieu ma roi ve host that,
 * thi mot may thu nghiem quen khai se GUI HO SO THAT LEN CONG THAT - hong theo huong te
 * nhat co the, va khong co dau hieu gi cho toi luc doi soat. Nem thi hong ngay, on ao, va
 * dung cho.
 */
class CongBhxh
{
    /** Khoa cau hinh chua host cong BHXH */
    const KHOA_BASE_URL = 'organization.BHYT.base_url';

    /**
     * @param string $duongDan duong dan sau host, vi du '/api/token/take'
     * @return string URL day du
     * @throws \RuntimeException khi chua khai base_url
     */
    public static function url($duongDan)
    {
        return self::baseUrl() . '/' . ltrim(trim((string) $duongDan), '/');
    }

    /**
     * Host cong BHXH, da cat dau gach cuoi.
     *
     * Cat '/' cuoi vi nguoi khai cau hinh rat de go them mot dau. Ghep thang se sinh
     * '//api/...' va cong tra 404 kem thong bao khong noi gi ve nguyen nhan.
     *
     * @return string
     * @throws \RuntimeException
     */
    public static function baseUrl()
    {
        $base = trim((string) config(self::KHOA_BASE_URL, ''));

        if ($base === '') {
            throw new \RuntimeException(
                'Chưa khai ' . self::KHOA_BASE_URL . ' trong config/organization.php. '
                . 'Tệp đó nằm ngoài kho mã nên mỗi máy phải khai tay: đặt host cổng BHXH '
                . 'thử nghiệm hoặc chính thức tuỳ môi trường.'
            );
        }

        return rtrim($base, '/');
    }
}

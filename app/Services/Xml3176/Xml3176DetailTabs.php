<?php

namespace App\Services\Xml3176;

use App\Models\BHYT\Xml3176Xml2;
use App\Models\BHYT\Xml3176Xml3;
use App\Models\BHYT\Xml3176Xml4;
use App\Models\BHYT\Xml3176Xml5;

/**
 * Dang ky cac bang con nhieu dong cua man chi tiet XML3176.
 *
 * Bon bang nay duoc chia thanh tab con roi phan trang phia server. Cach nhom KHONG
 * dong nhat: XML2/4/5 nhom theo ngay (cat 8 ky tu dau cua cot thoi gian), rieng XML3
 * nhom theo ma_nhom (ma nhom dich vu, giu nguyen gia tri) - nen mot tab con cua XML3
 * co the chua vai tram dong, day la cho that su can phan trang.
 */
class Xml3176DetailTabs
{
    /** So dong moi trang. */
    const CO_TRANG = 100;

    const BANG_NHIEU_DONG = [
        'XML2' => ['model' => Xml3176Xml2::class, 'cot_nhom' => 'ngay_yl',        'cat' => 8],
        'XML3' => ['model' => Xml3176Xml3::class, 'cot_nhom' => 'ma_nhom',        'cat' => 0],
        'XML4' => ['model' => Xml3176Xml4::class, 'cot_nhom' => 'ngay_kq',        'cat' => 8],
        'XML5' => ['model' => Xml3176Xml5::class, 'cot_nhom' => 'thoi_diem_dbls', 'cat' => 8],
    ];

    public static function laBangNhieuDong($xml)
    {
        return is_string($xml) && isset(self::BANG_NHIEU_DONG[$xml]);
    }

    /**
     * Tham so {xml} den tu URL nen phai doi chieu danh sach trang truoc khi dung.
     */
    public static function cauHinh($xml)
    {
        if (!self::laBangNhieuDong($xml)) {
            abort(404);
        }

        return self::BANG_NHIEU_DONG[$xml];
    }

    /**
     * Bien danh sach gia tri cot thanh danh sach khoa nhom da sap xep.
     *
     * @param iterable $giaTri
     * @param int      $cat So ky tu dau lam khoa; 0 = giu nguyen gia tri
     * @return array Mang danh so lai tu 0
     */
    public static function khoaNhom($giaTri, $cat)
    {
        $khoa = [];

        foreach ($giaTri as $v) {
            if ($v === null) {
                continue;
            }

            $v = trim((string) $v);

            if ($v === '') {
                continue;
            }

            $khoa[] = $cat > 0 ? substr($v, 0, $cat) : $v;
        }

        $khoa = array_unique($khoa);
        sort($khoa);

        return array_values($khoa);
    }
}

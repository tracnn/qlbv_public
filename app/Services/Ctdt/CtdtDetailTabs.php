<?php

namespace App\Services\Ctdt;

use App\Models\BHYT\Ctdt\CtdtHoSo;

/**
 * Sinh danh sach tab cua man chi tiet, tu chung tu ho so THUC CO.
 *
 * KHAC XML3176 von co dinh XML1-15: mot ho so chung tu hiem khi co du chin loai, va chin
 * tab trong la chin lan nguoi dung bam vao roi thay khong co gi.
 */
class CtdtDetailTabs
{
    /** Tab xem XML nguyen van, luon dung cuoi. */
    const TAB_XML = '__XML__';

    /**
     * @return array Mang ['ma' =>, 'nhan' =>, 'so_luong' =>], tab XML goc o cuoi
     */
    public static function cua(CtdtHoSo $hoSo)
    {
        $dem = [];

        foreach ($hoSo->chungTu as $chungTu) {
            $loai = $chungTu->loai_ho_so;
            $dem[$loai] = isset($dem[$loai]) ? $dem[$loai] + 1 : 1;
        }

        $tabs = [];

        foreach ($dem as $loai => $soLuong) {
            $tabs[] = [
                'ma'       => $loai,
                'nhan'     => self::nhanLoai($loai),
                'so_luong' => $soLuong,
            ];
        }

        // Khi cong bao 205 (fileBase64Str khong hop le), xem XML nguyen van la cach duy nhat
        // doi chieu xem minh da gui gi.
        $tabs[] = ['ma' => self::TAB_XML, 'nhan' => 'XML gốc', 'so_luong' => count($hoSo->chungTu)];

        return $tabs;
    }

    /**
     * Tham so {loai} den tu URL nen PHAI doi chieu truoc khi dung. Khong doi chieu thi bat
     * ky ai cung ep duoc controller truy van mot bang khong lien quan toi ho so dang xem.
     */
    public static function hopLe(CtdtHoSo $hoSo, $ma)
    {
        foreach (self::cua($hoSo) as $tab) {
            if ($tab['ma'] === $ma) {
                return true;
            }
        }

        return false;
    }

    /** Nhan lay tu chinh lop loai, khong go tay lai lan thu hai. */
    private static function nhanLoai($loai)
    {
        if (!CtdtLoaiRegistry::co($loai)) {
            return $loai;
        }

        $lop = CtdtLoaiRegistry::cho($loai);

        return $lop::tenTab();
    }
}

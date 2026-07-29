<?php

namespace App\Services\Category;

/**
 * Nhan hien thi cho ten cot khi xem chi tiet mot ban ghi danh muc.
 *
 * Lay tu config/catalog_import_mapping.php: voi moi truong, phan tu DAU TIEN cua mang
 * ten cot chap nhan duoc chinh la ten chuan (vi du 'ma_thuoc' => ['MA_THUOC', ...]).
 *
 * Cot nao khong co trong mapping (id, ma_cskcb, created_at, updated_at...) thi giu
 * nguyen ten cot tho — tha hien ten ky thuat con hon bo trong.
 *
 * Ham THUAN de kiem duoc.
 */
class NhanTruong
{
    public static function cua($loai, $cot)
    {
        $cfg = config('catalog_import_mapping.' . $loai . '.mapping');

        if (!is_array($cfg) || !isset($cfg[$cot])) {
            return $cot;
        }

        $ten = $cfg[$cot];

        if (!is_array($ten) || empty($ten)) {
            return $cot;
        }

        return (string) reset($ten);
    }
}

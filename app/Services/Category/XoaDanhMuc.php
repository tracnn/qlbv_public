<?php

namespace App\Services\Category;

use InvalidArgumentException;

/**
 * Dung dieu kien xoa toan bo mot danh muc.
 *
 * Tach rieng khoi controller vi day la phan de sai nhat: loc theo co so. Ham THUAN nen
 * kiem duoc ma khong dung toi mot dong du lieu nao.
 */
class XoaDanhMuc
{
    /**
     * @param string $loai      khoa trong so dang ky danh_muc_bhyt
     * @param string $maCskcb   ma co so; rong nghia la "tat ca co so"
     * @param array  $soDangKy  config('danh_muc_bhyt')
     *
     * @return array ['bang' => string, 'dieu_kien' => array]
     *
     * @throws InvalidArgumentException khi $loai khong co trong so dang ky
     */
    public static function dieuKien($loai, $maCskcb, array $soDangKy)
    {
        if (!isset($soDangKy[$loai])) {
            throw new InvalidArgumentException('Loai danh muc khong hop le: ' . $loai);
        }

        $x = $soDangKy[$loai];
        $ma = trim((string) $maCskcb);

        // Danh muc dung chung: BO QUA ma co so du co truyen vao. Cot ma_cskcb khong ton
        // tai o cac bang do, loc theo no se lam vo truy van.
        if (empty($x['theo_co_so']) || $ma === '') {
            return ['bang' => $x['bang'], 'dieu_kien' => []];
        }

        return ['bang' => $x['bang'], 'dieu_kien' => ['ma_cskcb' => $ma]];
    }
}

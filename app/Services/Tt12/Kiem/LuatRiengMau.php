<?php

namespace App\Services\Tt12\Kiem;

use App\Services\Tt12\Mau\Mau03;
use App\Services\Tt12\Mau\Mau05;

/**
 * Hai luat khong suy ra duoc tu dac ta cot vi chung PHU THUOC DU LIEU cua chinh dong do.
 *
 * Khong nhet vao co che bat_buoc cua LuatO: bat_buoc la tinh chat co dinh cua mot cot,
 * con hai luat nay bat/tat theo gia tri cua cot khac trong cung dong.
 *
 * Ham THUAN.
 */
class LuatRiengMau
{
    /**
     * @param string $lop
     * @param array  $duLieu     [TEN_THE => gia tri] cua dong cha
     * @param int    $sttDong
     * @param array  $duLieuCon  cac dong bang con, moi dong [ten_cot_thuong => gia tri]
     * @return array cac Tt12Loi
     */
    public static function kiem($lop, array $duLieu, $sttDong, array $duLieuCon = array())
    {
        if ($lop === Mau03::class) {
            return self::mau03($duLieu, $sttDong);
        }

        if ($lop === Mau05::class) {
            return self::mau05($duLieuCon, $sttDong);
        }

        return array();
    }

    /**
     * MAU_03: nhom cot duoc lieu chi bat buoc voi thuoc dong y / vi thuoc y hoc co truyen.
     *
     * Danh dau chung bat_buoc = true trong dac ta se chan moi dong tan duoc - tuc gan
     * nhu toan bo danh muc thuoc cua mot benh vien.
     */
    private static function mau03(array $duLieu, $sttDong)
    {
        $loai = isset($duLieu['LOAI_THUOC']) ? trim((string) $duLieu['LOAI_THUOC']) : '';

        if (!in_array($loai, Mau03::loaiThuocDuocLieu(), true)) {
            return array();
        }

        $loi = array();

        foreach (Mau03::theDuocLieu() as $the) {
            $giaTri = isset($duLieu[$the]) ? trim((string) $duLieu[$the]) : '';

            if ($giaTri === '') {
                $loi[] = Tt12Loi::loi(
                    'THIEU_TRUONG_DUOC_LIEU',
                    'LOAI_THUOC = ' . $loai . ' (dược liệu) nên cột ' . $the . ' là bắt buộc',
                    $sttDong, $the
                );
            }
        }

        return $loi;
    }

    /**
     * MAU_05: dong bang con da duoc tao thi phai co ma thuoc va don gia.
     *
     * Tt12LuuHoSo chi tao ban ghi con khi dong co it nhat mot o THUOCPX_* co gia tri, nen
     * su ton tai cua $duLieuCon da la bang chung "dong nay co thuoc phong xa".
     */
    private static function mau05(array $duLieuCon, $sttDong)
    {
        if ($duLieuCon === array()) {
            return array();
        }

        $loi = array();

        foreach ($duLieuCon as $con) {
            foreach (Mau05::theConBatBuocKhiCo() as $the) {
                $cot = strtolower($the);
                $giaTri = isset($con[$cot]) ? trim((string) $con[$cot]) : '';

                if ($giaTri === '') {
                    $loi[] = Tt12Loi::loi(
                        'THIEU_TRUONG_THUOCPX',
                        'Dòng có dữ liệu thuốc phóng xạ nên cột THUOCPX_' . $the
                        . ' là bắt buộc',
                        $sttDong, 'THUOCPX_' . $the
                    );
                }
            }
        }

        return $loi;
    }
}

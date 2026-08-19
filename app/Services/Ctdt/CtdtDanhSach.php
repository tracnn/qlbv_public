<?php

namespace App\Services\Ctdt;

use App\Models\BHYT\Ctdt\CtdtHoSo;

/**
 * Dung truy van cho man danh sach ho so.
 *
 * TACH KHOI CONTROLLER co chu dich: bo loc la cho de sai nhat cua mot man danh sach, va
 * kiem duoc no ma khong can dung HTTP thi moi kiem het duoc cac nhanh.
 *
 * Loc theo loai chung tu va tim theo ma the / ho ten phai di qua bang ctdt_chung_tu. Dung
 * whereHas chu khong join: mot ho so co nhieu chung tu cung loai se sinh nhieu dong khi
 * join, va man danh sach hien mot ho so hai lan la loi de nguoi dung thay nhat.
 */
class CtdtDanhSach
{
    /**
     * @param array $loc tu_ngay, den_ngay, dich_vu, loai_ho_so, macskcb, tim,
     *                   chi_con_loi, trang_thai_gui - tat ca deu tuy chon
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public static function truyVan(array $loc)
    {
        $q = CtdtHoSo::query();

        if (self::coGiaTri($loc, 'tu_ngay')) {
            $q->where('imported_at', '>=', trim($loc['tu_ngay']) . ' 00:00:00');
        }

        if (self::coGiaTri($loc, 'den_ngay')) {
            // Phai la 23:59:59, khong phai '<= ngay'. So sanh voi chuoi ngay tran tren cot
            // datetime se bo het ho so nap trong chinh ngay do tru dung luc 00:00:00.
            $q->where('imported_at', '<=', trim($loc['den_ngay']) . ' 23:59:59');
        }

        if (self::coGiaTri($loc, 'dich_vu')) {
            $q->where('dich_vu', trim($loc['dich_vu']));
        }

        if (self::coGiaTri($loc, 'macskcb')) {
            $q->where('macskcb', trim($loc['macskcb']));
        }

        if (self::coGiaTri($loc, 'loai_ho_so')) {
            $loai = trim($loc['loai_ho_so']);

            $q->whereHas('chungTu', function ($con) use ($loai) {
                $con->where('loai_ho_so', $loai);
            });
        }

        if (self::coGiaTri($loc, 'tim')) {
            $tim = trim($loc['tim']);

            $q->where(function ($ngoai) use ($tim) {
                $ngoai->where('ma_ho_so', 'like', '%' . $tim . '%')
                    ->orWhereHas('chungTu', function ($con) use ($tim) {
                        $con->where('ma_the', 'like', '%' . $tim . '%')
                            ->orWhere('ho_ten', 'like', '%' . $tim . '%');
                    });
            });
        }

        if (!empty($loc['chi_con_loi'])) {
            $q->where('so_loi', '>', 0);
        }

        if (self::coGiaTri($loc, 'trang_thai_gui')) {
            self::locTrangThai($q, trim($loc['trang_thai_gui']));
        }

        return $q;
    }

    /**
     * Dich mot trang thai thanh dieu kien SQL.
     *
     * Phai GIU DUNG thu tu uu tien cua CtdtTrangThaiGui::cua(): neu o day "chua ky" khong
     * loai tru "con loi" thi mot ho so vua con loi vua chua ky se hien o ca hai bo loc, va
     * tong so cac bo loc khong bang tong so ho so - nguoi dung se khong tin man hinh nua.
     */
    private static function locTrangThai($q, $trangThai)
    {
        if ($trangThai === CtdtTrangThaiGui::CON_LOI) {
            return $q->where('so_loi', '>', 0);
        }

        // Moi trang thai con lai deu ngu y "khong con loi".
        $q->where('so_loi', '<=', 0);

        if ($trangThai === CtdtTrangThaiGui::CHUA_KY) {
            return $q->where('is_signed', false);
        }

        $q->where('is_signed', true);

        if ($trangThai === CtdtTrangThaiGui::DA_GUI) {
            return $q->where('ma_ket_qua', '200');
        }

        if ($trangThai === CtdtTrangThaiGui::CONG_TU_CHOI) {
            // Phai khop CHINH XAC voi !empty($hoSo->ma_ket_qua) cua CtdtTrangThaiGui::cua():
            // chuoi rong VA chuoi '0' deu la "chua co ket qua", khong phai "bi tu choi".
            return $q->whereNotNull('ma_ket_qua')
                ->where('ma_ket_qua', '<>', '200')
                ->where('ma_ket_qua', '<>', '')
                ->where('ma_ket_qua', '<>', '0');
        }

        // GUI_TAT va CHUA_GUI cung la "da ky, chua co ket qua tu cong"; phan biet chung
        // bang CAU HINH chu khong bang du lieu, nen khong the loc bang SQL rieng.
        // Gom ca null LAN chuoi rong/'0': !empty() cua PHP coi ca ba la "chua co ket qua".
        return $q->where(function ($q2) {
            $q2->whereNull('ma_ket_qua')
                ->orWhere('ma_ket_qua', '')
                ->orWhere('ma_ket_qua', '0');
        });
    }

    private static function coGiaTri(array $loc, $khoa)
    {
        return isset($loc[$khoa]) && trim((string) $loc[$khoa]) !== '';
    }
}

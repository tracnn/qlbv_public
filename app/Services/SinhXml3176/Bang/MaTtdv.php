<?php

namespace App\Services\SinhXml3176\Bang;

use App\Services\SinhXml3176\DuLieuHoSo;
use App\Services\SinhXml3176\Nguon\HisDanhMuc;

/**
 * MA_TTDV dung chung cho XML1, 7, 8, 9, 10, 11, 13, 14 (quyet dinh cua chu du an, spec 5.6).
 *
 * Key HIS.QD_130_BYT.MA_TTDV_FROM_PROFILE_XML co gia tri (benh vien chon) => di tron nhanh QD 130 cua DLL ([AX]:1025):
 *   key chua so hieu bang  => ma BHXH (social_insurance_number) cua HOSP_SUBS_DIRECTOR_LOGINNAME,
 *                             khong co thi HOSPITAL_DIRECTOR_LOGINNAME; khong co nhan vien/ma BHXH => "";
 *                             khong co ca hai loginname => REPRESENTATIVE_HEIN_CODE;
 *   key khong chua so bang => REPRESENTATIVE_HEIN_CODE.
 * Key rong => DIPLOMA (CCHN) cua (pho) giam doc nhu nhanh IS_3176, khong co thi "".
 *
 * Cach DLL kiem "key chua so hieu bang" khac nhau tung bang, giu dung:
 *   XML1  : tach theo ',', ';', ' ' (bo phan tu rong), so khop day du ([AX] X1P:665)
 *   XML7  : tach theo ',', KHONG trim, so khop day du ("1, 7" khong khop "7")
 *   XML13 : chuoi con (Contains("13")): "113", "130" cung khop
 *   con lai (8, 9, 10, 11, 14): tach theo ',', trim tung phan tu, so khop day du ("1" khong khop "13")
 *
 * Chon loginname: DLL dung "??" (XML1, 7, 11, 13) hoac IsNullOrEmpty (XML8, 9, 10, 14). Oracle khong phan biet ""
 * va NULL nen hai cach cho cung ket qua; module dung GiaTriBang::dauTien (bo qua rong) cho moi bang.
 */
class MaTtdv
{
    const KEY = 'HIS.QD_130_BYT.MA_TTDV_FROM_PROFILE_XML';

    /**
     * @param int $soBang so hieu bang XML (1, 7, 8, 9, 10, 11, 13, 14)
     */
    public static function cua(DuLieuHoSo $d, HisDanhMuc $dm, int $soBang): string
    {
        $hs = $d->hoSo;
        $cauHinh = (string) $dm->config(self::KEY);
        $login = GiaTriBang::dauTien([$hs->hosp_subs_director_loginname, $hs->hospital_director_loginname]);
        $nv = $login !== '' ? $dm->nhanVien($login) : null;

        if ($cauHinh !== '') {
            if (!self::keyChuaSoBang($cauHinh, $soBang) || $login === '') {
                return GiaTriBang::chu($hs->representative_hein_code);
            }

            return $nv !== null ? GiaTriBang::chu($nv->social_insurance_number) : '';
        }

        return $nv !== null ? GiaTriBang::chu($nv->diploma) : '';
    }

    /** Kiem "key chua so hieu bang" theo DLL tung bang (xem docblock lop). */
    public static function keyChuaSoBang(string $key, int $soBang): bool
    {
        $so = (string) $soBang;
        if ($key === '') {
            return false;
        }
        if ($soBang === 13) {
            return strpos($key, $so) !== false;
        }
        if ($soBang === 1) {
            return in_array($so, preg_split('/[,; ]+/', $key, -1, PREG_SPLIT_NO_EMPTY), true);
        }
        foreach (explode(',', $key) as $phan) {
            if (($soBang === 7 ? $phan : trim($phan)) === $so) {
                return true;
            }
        }

        return false;
    }
}

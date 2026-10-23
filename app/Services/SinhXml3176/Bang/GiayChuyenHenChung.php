<?php

namespace App\Services\SinhXml3176\Bang;

use App\Services\SinhXml3176\Nguon\HisDanhMuc;

/**
 * Phan XML13 (giay chuyen tuyen) va XML14 (giay hen kham lai) dung chung: ngay sinh, gioi tinh, ma benh kem theo,
 * ma benh YHCT, ma bac si (DLL XML 130.XML13 / XML14, ban do [AX]:1231-1346). Cac quy tac nay giong het nhau
 * o hai bang trong DLL.
 */
class GiayChuyenHenChung
{
    /** MA_BENH_KT toi da 12 ma */
    const TOI_DA_MA_BENH_KT = 12;

    /**
     * NGAY_SINH: TDL_PATIENT_IS_HAS_NOT_DAY_DOB == 1 thi nam + "00000000", khong thi 12 ky tu dau (yyyyMMddHHmm).
     * Thieu ngay sinh => rong (DLL Substring nem loi; XML1 da dung LoiDungBang khi thieu ngay sinh).
     */
    public static function ngaySinh($hs)
    {
        $s = ThoiGianHis::chuoi($hs->tdl_patient_dob);
        if ($s === null) {
            return '';
        }
        $khongNgay = GiaTriBang::co($hs->tdl_patient_is_has_not_day_dob) && (int) $hs->tdl_patient_is_has_not_day_dob === 1;

        return $khongNgay ? substr($s, 0, 4) . '00000000' : substr($s, 0, 12);
    }

    /** GENDER_ID 1 => "2", 2 => "1", khac "3". */
    public static function gioiTinh($hs)
    {
        $g = GiaTriBang::co($hs->tdl_patient_gender_id) ? (int) $hs->tdl_patient_gender_id : 0;

        return $g === 1 ? '2' : ($g === 2 ? '1' : '3');
    }

    /** MA_BENH_KT: ICD_SUB_CODE tach ';' bo phan tu rong, toi da 12 ma. */
    public static function maBenhKt($hs)
    {
        return implode(';', array_slice(GiaTriBang::tachMa($hs->icd_sub_code), 0, self::TOI_DA_MA_BENH_KT));
    }

    /**
     * MA_BENH_YHCT: TRADITIONAL_ICD_CODE + TRADITIONAL_ICD_SUB_CODE (tach ';'), bo ma trung, bo ma nam trong ICD_CODE.
     *
     * @param int|null $toiDa XML14 ban 3176 chi lay 13 ma dau; XML13 khong gioi han (null)
     */
    public static function maBenhYhct($hs, $toiDa = null)
    {
        $co = array_merge(GiaTriBang::tachMa($hs->traditional_icd_code), GiaTriBang::tachMa($hs->traditional_icd_sub_code));
        $loai = GiaTriBang::tachMa($hs->icd_code);
        $ma = [];
        foreach ($co as $m) {
            if (!in_array($m, $ma, true) && !in_array($m, $loai, true)) {
                $ma[] = $m;
            }
        }
        if ($toiDa !== null) {
            $ma = array_slice($ma, 0, $toiDa);
        }

        return implode(';', $ma);
    }

    /**
     * MA_BAC_SI: DIPLOMA cua DOCTOR_LOGINNAME; DOCTOR_LOGINNAME rong thi cua END_LOGINNAME; khong co ca hai => "".
     * Giu DLL: co DOCTOR_LOGINNAME nhung khong tim thay nhan vien thi KHONG lui ve END_LOGINNAME ([AX]:1292), ket qua rong.
     */
    public static function maBacSi($hs, HisDanhMuc $dm)
    {
        $login = GiaTriBang::co($hs->doctor_loginname) ? $hs->doctor_loginname : $hs->end_loginname;
        if (!GiaTriBang::co($login)) {
            return '';
        }
        $nv = $dm->nhanVien($login);

        return $nv !== null ? GiaTriBang::chu($nv->diploma) : '';
    }

    /** Thoi gian HIS => 12 ky tu dau (yyyyMMddHHmm), dung cho NGAY_VAO / NGAY_VAO_NOI_TRU / NGAY_RA. */
    public static function phut($v)
    {
        return GiaTriBang::thoiGian($v, 12);
    }
}

<?php

namespace App\Services\SinhXml3176\Bang;

/**
 * Phan XML7 (giay ra vien) va XML8 (tom tat ho so benh an) dung chung: chan doan ra vien, ten cha/me, ma the tam.
 */
class RaVienChung
{
    /** TDL_PATIENT_RELATIVE_TYPE (lower) cua cha / me */
    const QUAN_HE_CHA = ['cha', 'bố', 'bo'];
    const QUAN_HE_ME = ['mẹ', 'me'];

    /**
     * ICD_NAME + ";" + ICD_TEXT.
     * Sua loi DLL [AX]:892 (XML7), [AX]:979 (XML8): ICD_TEXT null/rong thi khong them ';' thua o cuoi
     * (DLL them ';' sau ICD_NAME roi noi ICD_TEXT null). ICD_NAME rong chi con ICD_TEXT, khong ';' dau.
     */
    public static function chanDoanRv($icdName, $icdText)
    {
        return GiaTriBang::noi([$icdName === null || trim((string) $icdName) === '' ? null : $icdName, $icdText]);
    }

    /** FATHER_NAME co gia tri (khong chi khoang trang) thi lay, khong thi nguoi than khi quan he la cha/bo. */
    public static function hoTenCha($hs)
    {
        return self::tenChaMe($hs->father_name, $hs, self::QUAN_HE_CHA);
    }

    public static function hoTenMe($hs)
    {
        return self::tenChaMe($hs->mother_name, $hs, self::QUAN_HE_ME);
    }

    private static function tenChaMe($ten, $hs, array $quanHe)
    {
        if ($ten !== null && trim((string) $ten) !== '') {
            return (string) $ten;
        }
        $loai = mb_strtolower($hs->tdl_patient_relative_type === null ? '_' : (string) $hs->tdl_patient_relative_type, 'UTF-8');

        return in_array($loai, $quanHe, true) ? GiaTriBang::chu($hs->tdl_patient_relative_name) : '';
    }

    /** MA_THE_TAM: TDL_HEIN_CARD_NUMBER khi PTA chinh co HAS_BIRTH_CERTIFICATE == "C". */
    public static function maTheTam($hs, $pta)
    {
        return $pta !== null && (string) $pta->has_birth_certificate === 'C' ? GiaTriBang::chu($hs->tdl_hein_card_number) : '';
    }

    /** PTA chinh theo config ma doi tuong BHYT ([AX]:126). */
    public static function ptaChinh($d, $dm)
    {
        return PtaChinh::chon($d->dienDieuTri, GiaTriBang::chu($dm->config(PtaChinh::KEY_BHYT)));
    }
}

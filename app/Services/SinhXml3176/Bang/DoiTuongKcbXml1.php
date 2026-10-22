<?php

namespace App\Services\SinhXml3176\Bang;

use App\Services\SinhXml3176\Nguon\HisDanhMuc;

/**
 * XML1 #31 MA_DOITUONG_KCB tu suy khi HEIN_PATIENT_TYPE_CODE rong (DLL X1P:238-353). Giu nguyen thu tu
 * nhanh cua DLL; chi sua cac cho DLL nem loi.
 *
 * Giu DLL (X1P:285, 293 co kiem val3/val4 != null): ICD chinh/phu khong co trong danh muc coi nhu null (khong lao
 *   tiem an, khong COVID), van suy tiep. Sua loi DLL [AX]:300: dong danh muc co ICD_CODE null khong lam vo
 *   o.ICD_CODE.Equals (HisDanhMuc da bo ma rong khi danh chi muc).
 * Sua loi DLL [AX]:301: HEIN_MEDI_ORG_CODE 1 ky tu khong lam vo Substring(0,2).
 * Sua loi DLL [AX]:302: PTA chinh khong co so the (the lay tu PTA khac) khong lam vo StartsWith("HN").
 * Tuyen noi DKBD chi tra HisDanhMuc::noiKcb (his_medi_org), khong co danh muc cung ConfigHeinMediOrg du phong
 *   khi danh muc HIS rong - can xac nhan.
 * So khop DKBD == ma co so nhu DLL: PTA HEIN_MEDI_ORG_CODE "" (khac null) va ma co so rong van tinh la khop.
 * Giu DLL: ICD phu chon ban ghi danh muc dau tien (ID nho nhat) co ma nam trong ICD_SUB_CODE, chi xet co cua ban ghi do.
 */
class DoiTuongKcbXml1
{
    const LOAI = 'XML1';

    /**
     * @param object $hs ho so (v_his_treatment_12)
     * @param object $pta dien dieu tri chinh
     * @param string $maThe MA_THE_BHYT da dung
     * @param bool $kieuXml14 true => luat cua DLL XML14 (khac DLL XML1 o ba cho): DT xet GT, HK truoc IS_NEWBORN;
     *   noi tru TT chi tinh TDL_TREATMENT_TYPE_ID == 3; khong khop nhanh nao thi rong (khong phai "9"). Mac dinh la XML1.
     * @return string
     */
    public static function suy($hs, $pta, $maThe, HisDanhMuc $dm, $kieuXml14 = false)
    {
        if ($maThe === '') {
            return '9';
        }
        $maCs = trim(GiaTriBang::chu($hs->hein_medi_org_code));
        $dkbd = GiaTriBang::chu($pta->hein_medi_org_code);
        $tinhDkbd = $dkbd !== '' ? substr($dkbd, 0, 2) : '';
        $tuyenDkbd = '';
        if ($dkbd !== '') {
            $noi = $dm->noiKcb($dkbd);
            if ($noi !== null) {
                $tuyenDkbd = GiaTriBang::chu($noi->level_code);
            }
        }
        $tinhCs = strlen($maCs) > 2 ? substr($maCs, 0, 2) : null;
        $tuyen = GiaTriBang::chu($pta->right_route_code);
        $loaiTuyen = GiaTriBang::chu($pta->right_route_type_code);
        $cap = GiaTriBang::chu($hs->hein_level_code);
        $loaiDt = GiaTriBang::co($hs->tdl_treatment_type_id) ? (int) $hs->tdl_treatment_type_id : null;

        if ($tuyen === 'DT' && $loaiTuyen === 'CC') {
            return '2';
        }
        if ($tuyen === 'DT') {
            if (!$kieuXml14 && self::bang($pta->is_newborn, 1)) {
                return '1.7';
            }
            if ($loaiTuyen === 'GT') {
                return '1.3';
            }
            if ($loaiTuyen === 'HK') {
                return '1.5';
            }
            if ($kieuXml14 && self::bang($pta->is_newborn, 1)) {
                return '1.7';
            }
            list($icd, $icdPhu) = self::icd($hs, $dm);
            if (self::co($icd, 'is_latent_tuberculosis') || self::co($icdPhu, 'is_latent_tuberculosis')
                || self::bang($hs->is_tuberculosis, 1)) {
                return '1.1';
            }
            if (self::bang($hs->is_hiv, 1)) {
                return '1.1';
            }
            if (self::co($icd, 'is_covid') || self::co($icdPhu, 'is_covid')) {
                return '1.1';
            }
            if (($pta->hein_medi_org_code !== null && (string) $pta->hein_medi_org_code === $maCs)
                || self::chapNhan($dkbd, $hs->accept_hein_medi_org_code) || self::chapNhan($dkbd, $hs->sys_medi_org_code)) {
                return '1.1';
            }
            if (GiaTriBang::chu($pta->has_birth_certificate) === 'C' || strpos($maThe, 'TE') === 0
                || (($cap === '3' || $cap === '4') && $tinhDkbd === $tinhCs && ($tuyenDkbd === '3' || $tuyenDkbd === '4'))
                || (self::bang($pta->is_tt46, 1) && !GiaTriBang::co($pta->has_absent_letter) && !GiaTriBang::co($pta->has_working_letter))
                || $loaiTuyen === 'TH') {
                return '1.2';
            }
            if (self::bang($pta->has_absent_letter, 1) || self::bang($pta->has_working_letter, 1)) {
                return '1.4';
            }

            return $kieuXml14 ? '' : '9';
        }
        if ($tuyen === 'TT') {
            $noiTru = $loaiDt === 3 || (!$kieuXml14 && $loaiDt === 4);
            $khuVuc = GiaTriBang::chu($pta->live_area_code);
            if (strpos(GiaTriBang::chu($pta->hein_card_number), 'HN') === 0 || in_array($khuVuc, ['K1', 'K2', 'K3'], true)) {
                return '3.6';
            }
            if ($cap === '1' && $noiTru) {
                return '3.1';
            }
            if ($cap === '2' && $noiTru) {
                return '1.15';
            }
            if ($cap === '3' && ($tinhDkbd !== $tinhCs || $tuyenDkbd === '1' || $tuyenDkbd === '2')) {
                return '3.3';
            }
            if ($cap === '1' && $loaiDt === 1) {
                return '3.1';
            }
            if ($cap === '2' && $loaiDt === 1) {
                return '3.3';
            }

            return $cap === '2' ? '1.15' : '3.3';
        }

        return $loaiDt === 6 ? '7.1' : ($kieuXml14 ? '' : '9');
    }

    /** [ICD chinh, ICD phu dau tien trong danh muc] (X1P:262-272); khong co => null nhu DLL. */
    private static function icd($hs, HisDanhMuc $dm)
    {
        $icd = null;
        if (GiaTriBang::co($hs->icd_code)) {
            $icd = $dm->icd($hs->icd_code);
        }
        $phu = null;
        foreach (GiaTriBang::tachMa($hs->icd_sub_code) as $ma) {
            $o = $dm->icd($ma);
            if ($o !== null && ($phu === null || (int) $o->id < (int) $phu->id)) {
                $phu = $o;
            }
        }

        return [$icd, $phu];
    }

    /** ValidAccept: ma nam trong danh sach tach ',' ';' (so sanh sau trim). */
    private static function chapNhan($ma, $danhSach)
    {
        if (trim((string) $ma) === '' || trim((string) $danhSach) === '') {
            return false;
        }
        foreach (preg_split('/[,;]/', (string) $danhSach) as $x) {
            if (trim($x) === trim((string) $ma)) {
                return true;
            }
        }

        return false;
    }

    private static function co($o, $cot)
    {
        return $o !== null && self::bang($o->$cot, 1);
    }

    private static function bang($v, $so)
    {
        return GiaTriBang::co($v) && (int) $v === $so;
    }
}

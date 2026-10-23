<?php

namespace App\Services\SinhXml3176\Bang;

/**
 * Danh sach the BHYT dung chung cho XML1, XML13, XML14 (DLL X1P:108-237, XML13 va XML14 cung cach nhom the).
 * Tach tu Xml1Dung de khong chep lai ma: sap PTA, nhom theo (so the, noi DKBD, tu ngay, den ngay) lay ban dau,
 * roi dung bon cot MA_THE_BHYT, MA_DKBD, GT_THE_TU, GT_THE_DEN GIU KHOP VI TRI (the thu i o ca bon cot; gia tri
 * thieu de rong). PTA khong co so the khong chiem vi tri.
 *
 * Sua loi DLL [AX]:305 (XML1), [AX]:1289 (XML13), [AX]:1343 (XML14): DLL loc MA_THE_BHYT theo so the va GT_THE_DEN theo
 * HEIN_CARD_TO_TIME rieng nen hai danh sach co the lech so phan tu va khong con ghep cap theo vi tri.
 */
class TheBhyt
{
    /**
     * Sap PTA va nhom lay ban dau cua moi (so the, noi DKBD, tu ngay, den ngay).
     *
     * @param array  $ptas   cac dong v_his_patient_type_alter
     * @param string $sapThe key SORT_MA_THE_BHYT: "1" => LOG_TIME, ID giam dan; khac => HEIN_CARD_FROM_TIME, TO_TIME tang dan
     * @return array PTA da nhom, giu thu tu
     */
    public static function nhom(array $ptas, $sapThe)
    {
        $sap = (string) $sapThe === '1' ? GiaTriBang::sapOnDinh($ptas, function ($a, $b) {
            return PtaChinh::giamDan($a, $b);
        }) : GiaTriBang::sapOnDinh($ptas, function ($a, $b) {
            $c = self::soSanhNull($a->hein_card_from_time, $b->hein_card_from_time);

            return $c !== 0 ? $c : self::soSanhNull($a->hein_card_to_time, $b->hein_card_to_time);
        });
        $nhom = [];
        foreach ($sap as $p) {
            $k = json_encode([$p->hein_card_number, $p->hein_medi_org_code, $p->hein_card_from_time, $p->hein_card_to_time]);
            if (!isset($nhom[$k])) {
                $nhom[$k] = $p;
            }
        }

        return array_values($nhom);
    }

    /**
     * Bon cot khop vi tri tu cac PTA da nhom: moi phan tu la mang gia tri (chua noi ';').
     *
     * @return array ['MA_THE_BHYT' => [...], 'MA_DKBD' => [...], 'GT_THE_TU' => [...], 'GT_THE_DEN' => [...]]
     */
    public static function cot(array $nhom)
    {
        $cot = ['MA_THE_BHYT' => [], 'MA_DKBD' => [], 'GT_THE_TU' => [], 'GT_THE_DEN' => []];
        foreach ($nhom as $p) {
            if (trim(GiaTriBang::chu($p->hein_card_number)) !== '') {
                $cot['MA_THE_BHYT'][] = (string) $p->hein_card_number;
                $cot['MA_DKBD'][] = trim(GiaTriBang::chu($p->hein_medi_org_code)) !== '' ? (string) $p->hein_medi_org_code : '';
                $cot['GT_THE_TU'][] = GiaTriBang::thoiGian($p->hein_card_from_time, 8);
                $cot['GT_THE_DEN'][] = GiaTriBang::thoiGian($p->hein_card_to_time, 8);
            }
        }

        return $cot;
    }

    /** Noi mot cot bang ';'; moi gia tri deu rong => rong (khong ra ";;"). */
    public static function noi(array $gt)
    {
        return implode('', $gt) === '' ? '' : implode(';', $gt);
    }

    /** Nullable<long> OrderBy: null dung truoc. */
    public static function soSanhNull($a, $b)
    {
        $coA = GiaTriBang::co($a);
        $coB = GiaTriBang::co($b);
        if (!$coA || !$coB) {
            return ($coA ? 1 : 0) - ($coB ? 1 : 0);
        }

        return GiaTriBang::soSanh((float) $a, (float) $b);
    }
}

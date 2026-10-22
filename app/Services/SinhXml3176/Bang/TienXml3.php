<?php

namespace App\Services\SinhXml3176\Bang;

/**
 * Tien mot dong XML3 (DVKT + VTYT), [AX]:520-539. Tra so (float) da lam tron; ty le la so nguyen da xuat.
 *
 * Mot cong thuc chung cho DVKT va VTYT, moi phep tinh dung dung gia tri xuat ra (tep tu nhat quan):
 *   BV = SL*DG_BV*TYLE_DV/100, BH = SL*DG_BH*TYLE_DV/100*TYLE_BH/100, BHTT = BH*HEIN_RATIO,
 *   BNCCT = max(0, BH-BHTT-NK), BNTT = BV-BHTT-BNCCT-NK (so du), chan am nhu khoi cuoi VTYT cua DLL.
 *
 * Sua loi DLL [AX]:609: VTYT THANH_TIEN_BH nhan TYLE_TT_BH (DLL BH = SL*DG_BH khi TYLE_BH != 0).
 * Sua loi DLL [AX]:611: T_BHTT VTYT tinh lai tu THANH_TIEN_BH moi; chi thay bang VIR_TOTAL_HEIN_PRICE khi VIR
 *   THAP hon qua 1 dong (HIS da ap tran), khong bao gio lon hon BH*HEIN_RATIO.
 * Sua loi DLL [AX]:612: T_NGUONKHAC_* con gan tu T_NGUONKHAC cuoi cung (sau chan am) nen tong con = T_NGUONKHAC.
 * Sua loi DLL [AX]:613: dong khong thuc hien (_TB) co T_NGUONKHAC = 0 (BV = 0).
 * Sua loi DLL [AX]:615: DVKT can doi T_BHTT+T_BNCCT+T_BNTT+T_NGUONKHAC = THANH_TIEN_BV (BNTT la so du);
 *   khong can doi duoc (BHTT > BV) thi nem LoiDungBang.
 * Sua loi DLL [AX]:616: TYLE_TT_DV, TYLE_TT_BH so nguyen HALF-UP (DLL banker); tien nhan dung ty le xuat.
 * Sua loi DLL [AX]:610 (phu): VTYT stent ep TYLE_TT_BH = 100 chi khi dong la doi tuong BHYT (DLL ep ca dong khong BHYT).
 */
class TienXml3
{
    /**
     * @param object $ss dong v_his_sere_serv_2
     * @param bool $laVatTu loai 14-17
     * @param bool $laBhyt dong thuoc doi tuong BHYT
     * @param bool $ganLai HIS.QD_130_BYT.XML3.GAN_LAI_TIEN_BNTT_BNCCT_OPTION == "1"
     * @return array
     * @throws LoiDungBang
     */
    public static function tinh($ss, $laVatTu, $laBhyt, $ganLai)
    {
        $vat = GiaTriBang::co($ss->vat_ratio) ? (float) $ss->vat_ratio : 0.0;
        $goc = (float) $ss->original_price;
        $khongTh = (int) $ss->is_no_execute === 1;
        $sl = round((float) $ss->amount, 3);

        $dgBv = self::donGiaBv($ss, $goc, $vat);

        $tyDv = 0.0;
        if ($goc > 0) {
            $tyDv = GiaTriBang::tyLeNguyen(GiaTriBang::co($ss->hein_limit_price)
                ? (float) $ss->hein_limit_price / ($goc * (1 + $vat)) * 100
                : (float) $ss->price / $goc * 100);
        }
        $tyBh = 0.0;
        if ($laBhyt) {
            $tyBh = GiaTriBang::co($ss->hein_limit_ratio) ? GiaTriBang::tyLeNguyen($ss->hein_limit_ratio) : 100.0;
        }

        $dgBhGoc = round($goc * (1 + $vat), 3);
        $dgBh = $dgBhGoc;
        $tran = null;
        if ($laVatTu) {
            $stent = GiaTriBang::co($ss->stent_order);
            $cauHinhTran = GiaTriBang::co($ss->config_hein_limit_price) ? (float) $ss->config_hein_limit_price : null;
            if (!$khongTh) {
                $tyDv = 100.0;
            }
            if ($stent && $laBhyt) {
                $tyBh = 100.0; // L:579
            }
            if (GiaTriBang::co($ss->equipment_set_id) && !$stent && $ganLai) {
                $dgBh = round($dgBv, 3);
            } elseif ($stent && $cauHinhTran !== null) {
                $dgBh = min($dgBhGoc, $cauHinhTran);
            }
            $tran = self::tranTt($ss, $stent, $cauHinhTran, $goc, $vat, $ganLai);
        }
        if ($khongTh) {
            $tyDv = 0.0;
            $tyBh = 0.0;
        }

        $bv = round($sl * $dgBv * $tyDv / 100, 2);
        $bh = round($sl * $dgBh * $tyDv / 100 * $tyBh / 100, 2);
        $nk = $khongTh ? 0.0 : round($sl * round((float) $ss->other_source_price, 3), 2);
        $bhtt = round($bh * (GiaTriBang::co($ss->hein_ratio) ? (float) $ss->hein_ratio : 0.0), 2);

        $apTran = false;
        if ($laVatTu && GiaTriBang::co($ss->vir_total_hein_price)) {
            $vir = round((float) $ss->vir_total_hein_price, 2);
            if ($vir < $bhtt - 1) {
                $bhtt = max(0.0, $vir);
                $apTran = true;
            }
        }

        if ($laVatTu && $ganLai) {
            $bncct = max(0.0, round($bh - $bhtt, 2));
        } else {
            $bncct = max(0.0, round($bh - $bhtt - $nk, 2));
            if ($apTran) {
                $bncct = min($bncct, max(0.0, round((float) $ss->vir_total_patient_price_bhyt, 2)));
            }
        }

        $bntt = round($bv - $bhtt - $bncct - $nk, 2);
        if ($bntt < 0) {
            $bntt = 0.0;
            $bncct = round($bv - $bhtt - $nk, 2);
            if ($bncct < 0) {
                $bncct = 0.0;
                $nk = round($bv - $bhtt, 2);
                if ($nk < 0) {
                    throw new LoiDungBang('XML3', 'Dịch vụ ID ' . $ss->id . ': T_BHTT (' . $bhtt
                        . ') lớn hơn THANH_TIEN_BV (' . $bv . '), không cân đối được tiền');
                }
            }
        }

        $nguon = GiaTriBang::co($ss->hein_pay_source_type_id) ? (int) $ss->hein_pay_source_type_id : null;

        return [
            'sl' => $sl, 'dg_bv' => $dgBv, 'dg_bh' => $dgBh, 'ty_le_dv' => $tyDv, 'ty_le_bh' => $tyBh,
            'bv' => $bv, 'bh' => $bh, 'tran' => $tran, 'bhtt' => $bhtt, 'bncct' => $bncct, 'bntt' => $bntt,
            'nk' => $nk,
            'nk_nsnn' => $nguon === 1 ? $nk : 0.0,
            'nk_vtnn' => $nguon === 2 ? $nk : 0.0,
            'nk_vttn' => $nguon === 3 ? $nk : 0.0,
            'nk_cl' => in_array($nguon, [1, 2, 3], true) ? 0.0 : $nk,
        ];
    }

    /**
     * DON_GIA_BV #14: PRIMARY_PRICE; co VIR_TOTAL_PATIENT_PRICE > 0 va PRIMARY_PATIENT_TYPE_ID thi LIMIT_PRICE
     * (khong SERVICE_CONDITION_ID) hoac ORIGINAL_PRICE. > 0 => round(p*(1+VAT),3), khong thi VIR_PRICE
     * (lam tron 3 chu so de gia tri xuat ra va gia tri dung tinh tien la mot; DLL khong lam tron).
     */
    private static function donGiaBv($ss, $goc, $vat)
    {
        $p = GiaTriBang::co($ss->primary_price) ? (float) $ss->primary_price : null;
        if (GiaTriBang::co($ss->vir_total_patient_price) && (float) $ss->vir_total_patient_price > 0
            && GiaTriBang::co($ss->primary_patient_type_id)) {
            if (!GiaTriBang::co($ss->service_condition_id)) {
                $p = GiaTriBang::co($ss->limit_price) ? (float) $ss->limit_price : null;
            } else {
                $p = $goc;
            }
        }

        return ($p !== null && $p > 0) ? round($p * (1 + $vat), 3) : round((float) $ss->vir_price, 3);
    }

    /** T_TRANTT #21 (VTYT): HEIN_LIMIT_PRICE; stent co CONFIG_HEIN_LIMIT_PRICE thi quy doi; GAN_LAI va stent > 2. */
    private static function tranTt($ss, $stent, $cauHinhTran, $goc, $vat, $ganLai)
    {
        $tran = GiaTriBang::co($ss->hein_limit_price) ? (float) $ss->hein_limit_price : null;
        if ($stent && $cauHinhTran !== null) {
            $tran = ($tran !== null && $goc > 0)
                ? round($cauHinhTran * ($tran / ($goc * (1 + $vat))), 0)
                : $cauHinhTran;
            if ($ganLai && (int) $ss->stent_order > 2) {
                $tran = GiaTriBang::co($ss->hein_limit_price) ? (float) $ss->hein_limit_price : null;
            }
        }

        return $tran;
    }
}

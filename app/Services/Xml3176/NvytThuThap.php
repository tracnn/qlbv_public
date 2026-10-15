<?php

namespace App\Services\Xml3176;

/**
 * Thu thap cac truong nhan vien y te cua MOI bang XML trong mot ho so (ma_lk) thanh list o
 * cho NvytRule::kiem. Doc tu DuLieuHoSo (nap san boi kiem tong the, hoac tu nap khi goi rieng
 * nhu lenh xml3176:kiem-lai-nvyt).
 *
 * Dieu kien bat_buoc cua tung o sao nguyen dieu kien cua block cu trong checker tung loai:
 * XML1/2/4/5/7/8/9/10/11/13/14: truong rong la thieu (khong co dieu kien rieng, chay cho moi dong);
 * XML3 ma_bac_si: bat buoc o moi dong (ca dong vat tu); XML3 nguoi_thuc_hien: chi bat buoc khi
 * ma_nhom thuoc config('xml3176.group_code_with_executor').
 */
class NvytThuThap
{
    /**
     * @return array list ['vai_tro','xml','gia_tri','bat_buoc','nhan']
     */
    public static function tuHoSo(string $ma_lk, DuLieuHoSo $du = null): array
    {
        $du = $du ?: DuLieuHoSo::nap($ma_lk, ['xml1', 'xml2', 'xml3', 'xml4', 'xml5', 'xml7', 'xml8', 'xml9', 'xml10', 'xml11', 'xml13', 'xml14']);
        $o = [];

        // Bang chi co mot truong TTDV: XML1, 8, 9, 10 (nhan rong)
        foreach ([1, 8, 9, 10] as $xml) {
            foreach (self::doc($du, $xml) as $r) {
                $o[] = self::o(NvytRule::TTDV, $xml, $r->ma_ttdv, true, '');
            }
        }

        // XML2: bac si chi dinh, nhan = ten thuoc
        foreach (self::doc($du, 2) as $r) {
            $o[] = self::o(NvytRule::BS_CHI_DINH, 2, $r->ma_bac_si, true, (string) $r->ten_thuoc);
        }

        // XML3: ma_bac_si va nguoi_thuc_hien tach ';'
        $nhomCoNguoiThucHien = (array) config('xml3176.group_code_with_executor');
        foreach (self::doc($du, 3) as $r) {
            $nhan = !empty($r->ten_vat_tu) ? (string) $r->ten_vat_tu : (string) $r->ten_dich_vu;
            foreach (self::tach($r->ma_bac_si) as $gt) {
                $o[] = self::o(NvytRule::BS_CHI_DINH, 3, $gt, true, $nhan);
            }
            $batBuoc = in_array($r->ma_nhom, $nhomCoNguoiThucHien);
            foreach (self::tach($r->nguoi_thuc_hien) as $gt) {
                $o[] = self::o(NvytRule::NGUOI_THUC_HIEN, 3, $gt, $batBuoc, $nhan);
            }
        }

        // XML4: BS doc ket qua, nhan = ten chi so (neu rong thi ma dich vu)
        foreach (self::doc($du, 4) as $r) {
            $nhan = trim((string) $r->ten_chi_so) !== '' ? (string) $r->ten_chi_so : (string) $r->ma_dich_vu;
            $o[] = self::o(NvytRule::BS_DOC_KQ, 4, $r->ma_bs_doc_kq, true, $nhan);
        }

        // XML5: nguoi thuc hien
        foreach (self::doc($du, 5) as $r) {
            $o[] = self::o(NvytRule::NGUOI_THUC_HIEN, 5, $r->nguoi_thuc_hien, true, '');
        }

        // XML7, XML11: TTDV + ma_bs (BS chi dinh)
        foreach ([7, 11] as $xml) {
            foreach (self::doc($du, $xml) as $r) {
                $o[] = self::o(NvytRule::TTDV, $xml, $r->ma_ttdv, true, '');
                $o[] = self::o(NvytRule::BS_CHI_DINH, $xml, $r->ma_bs, true, '');
            }
        }

        // XML13, XML14: TTDV + ma_bac_si (BS chi dinh)
        foreach ([13, 14] as $xml) {
            foreach (self::doc($du, $xml) as $r) {
                $o[] = self::o(NvytRule::TTDV, $xml, $r->ma_ttdv, true, '');
                $o[] = self::o(NvytRule::BS_CHI_DINH, $xml, $r->ma_bac_si, true, '');
            }
        }

        // Thu tu xml tang dan cho de doc (NvytRule::kiem tu sap theo vai tro / xml khi in)
        // (usort cua PHP 7 khong on dinh nen kem chi so de giu thu tu goc trong cung XML)
        $chiSo = array_keys($o);
        array_multisort(array_column($o, 'xml'), SORT_ASC, $chiSo, SORT_ASC, $o);

        return $o;
    }

    /**
     * Cac dong cua bang XML $xml - cu: DB::table(xml{N}s)->where('ma_lk')->get([cot...]) khong orderBy,
     * nen giu thu tu MariaDB quet (khoa ma_lk_stt_unique -> stt).
     */
    private static function doc(DuLieuHoSo $du, int $xml): array
    {
        return $du->dongTheoStt('xml' . $xml);
    }

    /** Tach theo ';', trim, bo manh rong; ca truong rong -> mot gia tri rong de con bao thieu. */
    private static function tach($v): array
    {
        $manh = [];
        foreach (explode(';', (string) $v) as $p) {
            $p = trim($p);
            if ($p !== '') {
                $manh[] = $p;
            }
        }

        return $manh ?: [''];
    }

    private static function o(string $vaiTro, int $xml, $giaTri, bool $batBuoc, string $nhan): array
    {
        return [
            'vai_tro' => $vaiTro,
            'xml' => $xml,
            // '0' coi nhu rong (giu hanh vi empty() cua block cu: tren thuc te co XML4 ma_bs_doc_kq = '0')
            'gia_tri' => trim((string) $giaTri) === '0' ? '' : trim((string) $giaTri),
            'bat_buoc' => $batBuoc,
            'nhan' => trim($nhan),
        ];
    }
}

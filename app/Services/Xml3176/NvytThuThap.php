<?php

namespace App\Services\Xml3176;

use Illuminate\Support\Facades\DB;

/**
 * Thu thap cac truong nhan vien y te cua MOI bang XML trong mot ho so (ma_lk) thanh list o
 * cho NvytRule::kiem. Moi bang XML doc bang mot truy van, chi lay cot can.
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
    public static function tuHoSo(string $ma_lk): array
    {
        $o = [];

        // Bang chi co mot truong TTDV: XML1, 8, 9, 10 (nhan rong)
        foreach ([1, 8, 9, 10] as $xml) {
            foreach (self::doc($xml, $ma_lk, ['ma_ttdv']) as $r) {
                $o[] = self::o(NvytRule::TTDV, $xml, $r->ma_ttdv, true, '');
            }
        }

        // XML2: bac si chi dinh, nhan = ten thuoc
        foreach (self::doc(2, $ma_lk, ['ma_bac_si', 'ten_thuoc']) as $r) {
            $o[] = self::o(NvytRule::BS_CHI_DINH, 2, $r->ma_bac_si, true, (string) $r->ten_thuoc);
        }

        // XML3: ma_bac_si va nguoi_thuc_hien tach ';'
        $nhomCoNguoiThucHien = (array) config('xml3176.group_code_with_executor');
        foreach (self::doc(3, $ma_lk, ['ma_bac_si', 'nguoi_thuc_hien', 'ma_nhom', 'ten_vat_tu', 'ten_dich_vu']) as $r) {
            $nhan = !empty($r->ten_vat_tu) ? (string) $r->ten_vat_tu : (string) $r->ten_dich_vu;
            foreach (self::tach($r->ma_bac_si) as $gt) {
                $o[] = self::o(NvytRule::BS_CHI_DINH, 3, $gt, true, $nhan);
            }
            $batBuoc = in_array($r->ma_nhom, $nhomCoNguoiThucHien);
            foreach (self::tach($r->nguoi_thuc_hien) as $gt) {
                $o[] = self::o(NvytRule::NGUOI_THUC_HIEN, 3, $gt, $batBuoc, $nhan);
            }
        }

        // XML4: BS doc ket qua, nhan = ma dich vu (neu rong thi ten chi so)
        foreach (self::doc(4, $ma_lk, ['ma_bs_doc_kq', 'ma_dich_vu', 'ten_chi_so']) as $r) {
            $nhan = !empty($r->ma_dich_vu) ? (string) $r->ma_dich_vu : (string) $r->ten_chi_so;
            $o[] = self::o(NvytRule::BS_DOC_KQ, 4, $r->ma_bs_doc_kq, true, $nhan);
        }

        // XML5: nguoi thuc hien
        foreach (self::doc(5, $ma_lk, ['nguoi_thuc_hien']) as $r) {
            $o[] = self::o(NvytRule::NGUOI_THUC_HIEN, 5, $r->nguoi_thuc_hien, true, '');
        }

        // XML7, XML11: TTDV + ma_bs (BS chi dinh)
        foreach ([7, 11] as $xml) {
            foreach (self::doc($xml, $ma_lk, ['ma_ttdv', 'ma_bs']) as $r) {
                $o[] = self::o(NvytRule::TTDV, $xml, $r->ma_ttdv, true, '');
                $o[] = self::o(NvytRule::BS_CHI_DINH, $xml, $r->ma_bs, true, '');
            }
        }

        // XML13, XML14: TTDV + ma_bac_si (BS chi dinh)
        foreach ([13, 14] as $xml) {
            foreach (self::doc($xml, $ma_lk, ['ma_ttdv', 'ma_bac_si']) as $r) {
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

    private static function doc(int $xml, string $ma_lk, array $cot)
    {
        return DB::table("xml3176_xml{$xml}s")->where('ma_lk', $ma_lk)->get($cot);
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
            'gia_tri' => $giaTri === null ? null : trim((string) $giaTri),
            'bat_buoc' => $batBuoc,
            'nhan' => trim($nhan),
        ];
    }
}

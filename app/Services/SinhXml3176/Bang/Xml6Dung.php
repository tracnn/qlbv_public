<?php

namespace App\Services\SinhXml3176\Bang;

use App\Services\SinhXml3176\DuLieuHoSo;
use App\Services\SinhXml3176\Nguon\HisDanhMuc;

/**
 * XML6 - ho so benh an cham soc va dieu tri HIV/AIDS. Port Xml6Processor.GenerateXml6ADO + MapADOToXml
 * (DLL XML 130.XML6, ban do [AX]:768-840), luon theo nhanh IS_3176. Khong co MA_TTDV, khong doc HIS_CONFIG.
 * Dau vao $d->hivTreatment: ban ghi his_hiv_treatment co ID lon nhat cua ho so (plugin sap theo ID roi ghi de).
 *
 * Giu nhu DLL (can xac nhan): [AX]:832 dieu kien sinh dong la TREATMENT_ID != ID (so khoa ngoai voi khoa chinh
 *   cua chinh ban ghi; hai so tinh co trung thi bo im lang); [AX]:833 MA_CSKCB = HEIN_MEDI_ORG_CODE;
 *   [AX]:836 DIA_CHI khong cat do dai; ma tinh/huyen/xa lay thang ma HIS.
 * IS_3176: SO_CCCD = CCCD ?? CMND ?? ho chieu ?? "" ([AX]:782); chuoi rong coi nhu null nhu Xml1Dung.
 * Sua loi DLL [AX]:777: ngay sinh null / ngay HIV ngan hon 8 chu so khong lam vo (DLL Substring nem loi, mat ca bang);
 *   ngay sinh thieu => rong, ngay ngan => lay het chuoi co.
 * Phan tu null (4 so long? khi null) xuat rong, xem ghi chu chung ve phan tu null.
 */
class Xml6Dung implements DungBang
{
    const LOAI = 'XML6';

    public function loai(): string
    {
        return self::LOAI;
    }

    public function coTrongHoSo(DuLieuHoSo $d): bool
    {
        $h = $d->hivTreatment;

        return $h !== null && (int) $h->treatment_id !== (int) $h->id;
    }

    public function dung(DuLieuHoSo $d, HisDanhMuc $dm, array $daDung): array
    {
        if (!$this->coTrongHoSo($d)) {
            return [];
        }
        $hs = $d->hoSo;
        $h = $d->hivTreatment;
        $ngaySinh = ThoiGianHis::chuoi($hs->tdl_patient_dob);
        if ($ngaySinh === null) {
            $ngaySinh = '';
        }
        $khongNgay = GiaTriBang::co($hs->tdl_patient_is_has_not_day_dob) && (int) $hs->tdl_patient_is_has_not_day_dob === 1;
        $gioi = GiaTriBang::co($hs->tdl_patient_gender_id) ? (int) $hs->tdl_patient_gender_id : 0;

        return [[
            'MA_LK' => GiaTriBang::chu($hs->treatment_code),
            'MA_THE_BHYT' => GiaTriBang::chu($hs->tdl_hein_card_number),
            'SO_CCCD' => GiaTriBang::dauTien([$hs->tdl_patient_cccd_number, $hs->tdl_patient_cmnd_number, $hs->tdl_patient_passport_number]),
            'NGAY_SINH' => $khongNgay && $ngaySinh !== '' ? substr($ngaySinh, 0, 4) . '00000000' : substr($ngaySinh, 0, 12),
            'GIOI_TINH' => $gioi === 1 ? '2' : ($gioi === 2 ? '1' : '3'),
            'DIA_CHI' => GiaTriBang::chu($hs->tdl_patient_address),
            'MATINH_CU_TRU' => GiaTriBang::chu($hs->tdl_patient_province_code),
            'MAHUYEN_CU_TRU' => GiaTriBang::chu($hs->tdl_patient_district_code),
            'MAXA_CU_TRU' => GiaTriBang::chu($hs->tdl_patient_commune_code),
            'NGAYKD_HIV' => self::ngay($h->hiv_infection_date),
            'NOI_LAY_MAU_XN' => GiaTriBang::chu($h->test_sample_place),
            'NOI_XN_KD' => GiaTriBang::chu($h->test_place_kd),
            'NOI_BDDT_ARV' => GiaTriBang::chu($h->arv_patient_begin_place),
            'BDDT_ARV' => self::ngay($h->arv_patient_begin),
            'MA_PHAC_DO_DIEU_TRI_BD' => GiaTriBang::chu($h->begin_regimen_hiv_code),
            'MA_BAC_PHAC_DO_BD' => self::so($h->begin_regimen_level),
            'MA_LYDO_DTRI' => self::so($h->hiv_treatment_reason),
            'LOAI_DTRI_LAO' => self::so($h->tuberculosis_treatment_type),
            'SANG_LOC_LAO' => self::so($h->tuberculosis_screening),
            'PHACDO_DTRI_LAO' => self::so($h->tuberculosis_regimen),
            'NGAYBD_DTRI_LAO' => self::ngay($h->tuberculosis_treatment_begin),
            'NGAYKT_DTRI_LAO' => self::ngay($h->tuberculosis_treatment_end),
            'KQ_DTRI_LAO' => self::so($h->tuberculosis_treatment_result),
            'MA_LYDO_XNTL_VR' => self::so($h->test_pcr_rna_reason),
            'NGAY_XN_TLVR' => self::ngay($h->test_pcr_rna_date),
            'KQ_XNTL_VR' => self::so($h->test_pcr_rna_result),
            'NGAY_KQ_XN_TLVR' => self::ngay($h->test_pcr_rna_result_date),
            'MA_LOAI_BN' => self::so($h->hiv_patient_type),
            'GIAI_DOAN_LAM_SANG' => self::so($h->clinical_stage),
            'NHOM_DOI_TUONG' => self::so($h->group_patient),
            'MA_TINH_TRANG_DK' => GiaTriBang::chu($h->hiv_patient_status),
            'LAN_XN_PCR' => self::so($h->test_pcr_times),
            'NGAY_XN_PCR' => self::ngay($h->test_pcr_date),
            'NGAY_KQ_XN_PCR' => self::ngay($h->test_pcr_result_date),
            'MA_KQ_XN_PCR' => self::so($h->test_pcr_result),
            'NGAY_NHAN_TT_MANG_THAI' => self::ngay($h->date_of_pregnancy),
            'NGAY_BAT_DAU_DT_CTX' => self::ngay($h->ctx_treatment_begin),
            'MA_XU_TRI' => GiaTriBang::chu($h->hiv_treatment_code),
            'NGAY_BAT_DAU_XU_TRI' => self::ngay($h->arv_treatmen_begin),
            'NGAY_KET_THUC_XU_TRI' => self::ngay($h->arv_treatmen_end),
            'MA_PHAC_DO_DIEU_TRI' => GiaTriBang::chu($h->regimen_hiv_code),
            'MA_BAC_PHAC_DO' => self::so($h->regimen_level),
            'SO_NGAY_CAP_THUOC_ARV' => self::so($h->prescription_arv_day),
            'NGAY_CHUYEN_PHAC_DO' => self::ngay($h->regimen_hiv_date),
            'LY_DO_CHUYEN_PHAC_DO' => self::so($h->regimen_hiv_reason),
            'MA_CSKCB' => GiaTriBang::chu($hs->hein_medi_org_code),
            'DU_PHONG' => '',
        ]];
    }

    /** 8 ky tu dau cua so ngay HIS (yyyyMMdd...); null => "". */
    private static function ngay($v)
    {
        $s = ThoiGianHis::chuoi($v);

        return $s === null ? '' : substr($s, 0, 8);
    }

    /** Cot so Oracle (NUMBER) thanh chuoi nhu long.ToString(); null => "". */
    private static function so($v)
    {
        if ($v === null || $v === '') {
            return '';
        }
        $s = is_float($v) ? sprintf('%.0f', $v) : trim((string) $v);
        if (preg_match('/^-?\d+\.\d+$/', $s)) {
            $s = rtrim(rtrim($s, '0'), '.');
        }

        return $s;
    }
}

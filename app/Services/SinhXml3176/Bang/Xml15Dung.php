<?php

namespace App\Services\SinhXml3176\Bang;

use App\Services\SinhXml3176\DuLieuHoSo;
use App\Services\SinhXml3176\Nguon\HisDanhMuc;

/**
 * XML15 - chi tiet dieu tri lao. Port XML15Processor.GenerateXML15ADO (DLL XML 130.XML15, [AX]:1347-1386),
 * luon theo nhanh IS_3176. Khong co MA_TTDV, khong doc HIS_CONFIG.
 * Dau vao $d->dieuTriLao: ban ghi his_tuberculosis_treat cuoi theo id tang (plugin ghi de theo thu tu tra ve).
 * Dieu kien sinh: co ban ghi lao va ID != 0.
 *
 * Sua loi DLL [AX]:1379: DLL them cung mot ban ghi hai lan (new List { x, x }) => file co 2 dong giong het;
 *   module chi sinh DUNG 1 dong.
 * Sua loi DLL [AX]:1380: STT DLL gan cung "15" (so hieu bang, khong phai so thu tu); module dat STT = 1.
 * Sua phong ngua ([AX]:1366-1373, cac hang truong ngay; [AX] khong liet ke loi nay): ngay ngan hon 8 chu so khong lam vo
 *   (neu DLL Substring thi nem loi, mat ca bang); lay het chuoi co.
 * IS_3176: SO_CCCD = CCCD ?? CMND ?? ho chieu ?? "" (bo bac cuoi ma BHXH); chuoi rong coi nhu null nhu Xml1Dung.
 * Giu nhu DLL (can xac nhan): chi ho tro mot dot dieu tri lao/ho so; MA_CSKCB = HEIN_MEDI_ORG_CODE.
 * Phan tu null xuat rong, xem ghi chu chung ve phan tu null.
 */
class Xml15Dung implements DungBang
{
    const LOAI = 'XML15';

    public function loai(): string
    {
        return self::LOAI;
    }

    public function coTrongHoSo(DuLieuHoSo $d): bool
    {
        return $d->dieuTriLao !== null && (int) $d->dieuTriLao->id !== 0;
    }

    public function dung(DuLieuHoSo $d, HisDanhMuc $dm, array $daDung): array
    {
        if (!$this->coTrongHoSo($d)) {
            return [];
        }
        $hs = $d->hoSo;
        $l = $d->dieuTriLao;

        return [[
            'MA_LK' => GiaTriBang::chu($hs->treatment_code),
            'STT' => '1',
            'MA_BN' => GiaTriBang::chu($hs->tdl_patient_code),
            'HO_TEN' => GiaTriBang::chu($hs->tdl_patient_name),
            'SO_CCCD' => GiaTriBang::dauTien([$hs->tdl_patient_cccd_number, $hs->tdl_patient_cmnd_number, $hs->tdl_patient_passport_number]),
            'PHANLOAI_LAO_VITRI' => GiaTriBang::chu($l->tuberculosis_classify_location),
            'PHANLOAI_LAO_TS' => GiaTriBang::chu($l->tuberculosis_classify_ts),
            'PHANLOAI_LAO_HIV' => GiaTriBang::chu($l->tuberculosis_classify_hiv),
            'PHANLOAI_LAO_VK' => GiaTriBang::chu($l->tuberculosis_classify_vk),
            'PHANLOAI_LAO_KT' => GiaTriBang::chu($l->tuberculosis_classify_kt),
            'LOAI_DTRI_LAO' => GiaTriBang::chu($l->tuberculosis_treatment_type),
            'NGAYBD_DTRI_LAO' => self::ngay($l->begin_time),
            'PHACDO_DTRI_LAO' => GiaTriBang::chu($l->regimen_tuberculosis_code),
            'NGAYKT_DTRI_LAO' => self::ngay($l->end_time),
            'KET_QUA_DTRI_LAO' => GiaTriBang::chu($l->tuberculosis_treatmen_result),
            'MA_CSKCB' => GiaTriBang::chu($hs->hein_medi_org_code),
            'NGAYKD_HIV' => self::ngay($l->hiv_kd_date),
            'BDDT_ARV' => self::ngay($l->arv_begin_time),
            'NGAY_BAT_DAU_DT_CTX' => self::ngay($l->ctx_begin_time),
            'DU_PHONG' => '',
        ]];
    }

    /** 8 ky tu dau cua so ngay HIS (yyyyMMdd...); null => "". */
    private static function ngay($v)
    {
        $s = ThoiGianHis::chuoi($v);

        return $s === null ? '' : substr($s, 0, 8);
    }
}

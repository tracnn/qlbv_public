<?php

namespace App\Services\SinhXml3176\Bang;

use App\Services\SinhXml3176\DuLieuHoSo;
use App\Services\SinhXml3176\Nguon\HisDanhMuc;

/**
 * XML9 - giay chung sinh. Port Xml9Processor.GenerateXml9ADO + MapADOToXml (DLL XML 130.XML9, ban do [AX]:1048-1100),
 * luon theo nhanh IS_3176 (chi khac o MA_TTDV). Bang danh sach: goc CHI_TIEU_DU_LIEU_GIAY_CHUNG_SINH, ds
 * DSACH_GIAYCHUNGSINH, ban ghi DU_LIEU_GIAY_CHUNG_SINH; moi V_HIS_BABY cua ho so mot dong ($d->soSinh).
 * Truong CDATA: HO_TEN_NND, NOICAP_CCCD_NND, NOI_CU_TRU_NND, HO_TEN_CHA, HO_TEN_CON, NOI_SINH_CON, TINH_TRANG_CON,
 * GHI_CHU, NGUOI_DO_DE, NGUOI_GHI_PHIEU, QUYEN_SO.
 * Dieu kien: co it nhat mot be cua ho so (TREATMENT_ID == ID). Khong loc theo trang thai cap giay.
 * MA_TTDV (spec 5.6): dung chung MaTtdv::cua(.., 9).
 *
 * Giu nhu DLL (can xac nhan): [AX]:1093 NGAY_CT lay IN_DATE cua me, de len ISSUED_DATE (ngay cap giay bi bo);
 *   [AX]:1096 ngay cap / noi cap giay to (CCCD, CMND, ho chieu) chon theo "truong nao co gia tri", doc lap voi so giay
 *   o SO_CCCD_NND nen co the ra so CMND kem ngay cap CCCD.
 * Sua loi DLL [AX]:1094: MA_BHXH_NND lay 10 ky tu cuoi cua the me; the ngan hon 10 ky tu thi DLL nem loi (Substring)
 *   va mat ca XML9 cua ho so => lay het chuoi the.
 * Sua loi DLL [AX]:1079 (dong 24; [AX] chua liet ke loi nay): NOI_SINH_CON khi BIRTHPLACE_TYPE = 1 ma BIRTH_HOSPITAL_NAME null thi DLL goi Replace tren
 *   null (NullReference, mat ca bang) => rong.
 * Phan tu null xuat rong, xem ghi chu chung ve phan tu null.
 */
class Xml9Dung implements DungBang
{
    const LOAI = 'XML9';

    public function loai(): string
    {
        return self::LOAI;
    }

    public function coTrongHoSo(DuLieuHoSo $d): bool
    {
        return count($this->beCuaHoSo($d)) > 0;
    }

    public function dung(DuLieuHoSo $d, HisDanhMuc $dm, array $daDung): array
    {
        $hs = $d->hoSo;
        $ra = [];
        foreach ($this->beCuaHoSo($d) as $be) {
            $ra[] = $this->dong($d, $dm, $hs, $be);
        }

        return $ra;
    }

    /** V_HIS_BABY co TREATMENT_ID == ID ho so (DLL: Where(o => o.TREATMENT_ID == vTreatment.ID)). */
    private function beCuaHoSo(DuLieuHoSo $d)
    {
        $ra = [];
        foreach ($d->soSinh as $be) {
            if ((int) $be->treatment_id === (int) $d->hoSo->id) {
                $ra[] = $be;
            }
        }

        return $ra;
    }

    private function dong(DuLieuHoSo $d, HisDanhMuc $dm, $hs, $be)
    {
        $gioi = GiaTriBang::co($be->gender_id) ? (int) $be->gender_id : 0;

        return [
            'MA_LK' => GiaTriBang::chu($hs->treatment_code),
            'MA_BHXH_NND' => GiaTriBang::co($hs->tdl_social_insurance_number)
                ? (string) $hs->tdl_social_insurance_number
                : (GiaTriBang::co($hs->tdl_hein_card_number) ? GiaTriBang::cuoi($hs->tdl_hein_card_number, 10) : ''),
            'MA_THE_NND' => GiaTriBang::chu($be->tdl_hein_card_number),
            'HO_TEN_NND' => GiaTriBang::kyTuXml($be->tdl_patient_name),
            'NGAYSINH_NND' => GiaTriBang::thoiGian($be->tdl_patient_dob, 8),
            'MA_DANTOC_NND' => GiaTriBang::chu($be->tdl_patient_ethnic_code),
            'SO_CCCD_NND' => GiaTriBang::dauTien([$be->tdl_patient_cccd_number, $be->tdl_patient_cmnd_number, $be->tdl_patient_passport_number]),
            'NGAYCAP_CCCD_NND' => self::ngayCap($be),
            'NOICAP_CCCD_NND' => GiaTriBang::kyTuXml(GiaTriBang::dauTien([$be->tdl_patient_cccd_place, $be->tdl_patient_cmnd_place, $be->tdl_patient_passport_place])),
            'NOI_CU_TRU_NND' => GiaTriBang::kyTuXml($be->tdl_patient_address),
            'MA_QUOCTICH' => GiaTriBang::chu($hs->tdl_patient_mps_national_code),
            'MATINH_CU_TRU' => GiaTriBang::chu($be->tdl_patient_province_code),
            'MAHUYEN_CU_TRU' => GiaTriBang::chu($be->tdl_patient_district_code),
            'MAXA_CU_TRU' => GiaTriBang::chu($be->tdl_patient_commune_code),
            'HO_TEN_CHA' => GiaTriBang::kyTuXml($be->father_name),
            'MA_THE_TAM' => GiaTriBang::chu($be->hein_card_number_tmp),
            'HO_TEN_CON' => GiaTriBang::kyTuXml($be->baby_name),
            'GIOI_TINH_CON' => $gioi === 1 ? '2' : ($gioi === 2 ? '1' : '3'),
            'SO_CON' => GiaTriBang::soChuoi($be->number_children_birth),
            'LAN_SINH' => GiaTriBang::soChuoi($be->number_of_birth),
            'SO_CON_SONG' => GiaTriBang::soChuoi($be->current_alive),
            // (long)WEIGHT cat phan thap phan
            'CAN_NANG_CON' => GiaTriBang::co($be->weight) ? (string) (int) (float) $be->weight : '',
            'NGAY_SINH_CON' => GiaTriBang::thoiGian($be->born_time, 12),
            'NOI_SINH_CON' => GiaTriBang::kyTuXml(self::noiSinh($be)),
            'TINH_TRANG_CON' => GiaTriBang::kyTuXml($be->born_result_name),
            'SINHCON_PHAUTHUAT' => GiaTriBang::co($be->is_surgery) ? GiaTriBang::soChuoi($be->is_surgery) : '0',
            'SINHCON_DUOI32TUAN' => GiaTriBang::co($be->week_count) && (float) $be->week_count < 32 ? '1' : '0',
            'GHI_CHU' => GiaTriBang::kyTuXml($be->note),
            'NGUOI_DO_DE' => GiaTriBang::kyTuXml($be->midwife),
            'NGUOI_GHI_PHIEU' => GiaTriBang::kyTuXml($be->issuer_username),
            // giu nhu DLL: IN_DATE cua me de len ISSUED_DATE cua be
            'NGAY_CT' => GiaTriBang::thoiGian($hs->in_date, 8),
            'SO' => GiaTriBang::soChuoi($be->birth_cert_num),
            'QUYEN_SO' => GiaTriBang::kyTuXml($be->birth_cert_book_name),
            'MA_TTDV' => MaTtdv::cua($d, $dm, 9),
            'DU_PHONG' => '',
        ];
    }

    /** CCCD_DATE, roi CMND_DATE, roi PASSPORT_DATE: cai dau tien co gia tri, yyyyMMdd. */
    private static function ngayCap($be)
    {
        foreach ([$be->tdl_patient_cccd_date, $be->tdl_patient_cmnd_date, $be->tdl_patient_passport_date] as $v) {
            if (GiaTriBang::co($v)) {
                return GiaTriBang::thoiGian($v, 8);
            }
        }

        return '';
    }

    /** Noi sinh theo BIRTHPLACE_TYPE (1..5); don ", , " va dau phay/khoang trang cuoi. */
    private static function noiSinh($be)
    {
        $loai = GiaTriBang::co($be->birthplace_type) ? (int) $be->birthplace_type : 0;
        $diaChi = GiaTriBang::chu($be->birthplace) . ', ' . GiaTriBang::chu($be->birth_commune_name) . ', '
            . GiaTriBang::chu($be->birth_district_name) . ', ' . GiaTriBang::chu($be->birth_province_name);
        $vienSinh = GiaTriBang::chu($be->birth_hospital_name);
        $chu = '';
        if ($loai === 1) {
            $chu = $vienSinh;
        } elseif ($loai === 2) {
            $chu = $vienSinh . ', ' . $diaChi;
        } elseif ($loai === 3) {
            $chu = 'Sinh tại nhà, ' . $diaChi;
        } elseif ($loai === 4) {
            $chu = 'Đẻ trên đường đi, ' . $diaChi;
        } elseif ($loai === 5) {
            $chu = 'Trẻ bị bỏ rơi, ' . $diaChi;
        }

        return rtrim(str_replace(', ,', ',', str_replace(', , ', ', ', $chu)), ', ');
    }
}

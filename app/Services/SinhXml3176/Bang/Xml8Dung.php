<?php

namespace App\Services\SinhXml3176\Bang;

use App\Services\SinhXml3176\DuLieuHoSo;
use App\Services\SinhXml3176\Nguon\HisDanhMuc;

/**
 * XML8 - chi tieu du lieu tom tat ho so benh an. Port Xml8Processor.GenerateXml8ADO + MapADOToXml (DLL XML 130.XML8,
 * ban do [AX]:900-983), luon theo nhanh IS_3176. Bang phang: mot ban ghi, the goc chinh la ban ghi
 * (CHI_TIEU_DU_LIEU_TOM_TAT_HO_SO_BENH_AN). Truong CDATA: TOMTAT_KQ, PP_DIEUTRI, KET_QUA_DTRI.
 *
 * Dieu kien ([AX]:905): TDL_TREATMENT_TYPE_ID = 3 hoac 4, hoac = 2 va IS_CHRONIC null/0 (loai ngoai tru man tinh).
 * MA_LOAI_KCB theo bang [AX]:908: mac dinh "10"; 1 => 01; 2 => 02 (IS_CHRONIC != 1); 3 => 09 neu OUT_TIME > CLINICAL_IN_TIME
 *   va khoang cach < 4 gio, con lai 03; 4 => 04; 5 => 06; 6 => 07. Cac nhanh 01/05/06/07/08/10 la ma chet do dieu kien sinh
 *   (module van cai de khop bang). "09" gan voi noi tru (type 3), khong phai ban ngay; moc gio la CLINICAL_IN_TIME (can xac nhan).
 * PTA cho MA_THE_TAM: PtaChinh ([AX]:126). MA_TTDV (spec 5.6): MaTtdv::cua(.., 8).
 *
 * Giu nhu DLL (can xac nhan): [AX]:978 NGUOI_GIAM_HO luon la nguoi than, GHI_CHU dien ten cha/me cho tre < 16;
 *   [AX]:977 NGAY_SINHCON/NGAY_CONCHET chi lay be dau tien co BORN_RESULT_ID = 2 (khong sap xep);
 *   [AX]:976 KET_QUA_DTRI la ky tu cuoi cua ma ket qua HIS; NGAY_CT lay OUT_DATE (XML7 lay OUT_TIME).
 *   Tuoi < 16 tinh theo nam tu TDL_PATIENT_DOB den bay gio; ngay sinh khong hop le => khong dien GHI_CHU.
 *   Khoang cach gio dung so giay thuc (DLL goi Calculation.DifferenceTime, thu vien ngoai khong co ma).
 * Sua loi DLL [AX]:971: HO_TEN_CHA / HO_TEN_ME bi dao do thu tu uu tien toan tu (ghi de FATHER_NAME khi quan he la "cha",
 *   bo trong khi FATHER_NAME rong); module lam dung chieu nhu XML7 (RaVienChung::hoTenCha/hoTenMe).
 * Sua loi DLL [AX]:973: TOMTAT_KQ cat 2000 KY TU (DLL cat 2000 byte UTF-8, de lai ky tu bi cat do).
 * Sua loi DLL [AX]:979: CHAN_DOAN_RV khong con ';' thua khi ICD_TEXT null/rong.
 * Danh sach rong (PTA, be, dich vu) va TREATMENT_RESULT_CODE rong => rong, khong nem ([AX]:980 .First() an toan, giu).
 * Phan tu null xuat rong, xem ghi chu chung ve phan tu null.
 */
class Xml8Dung implements DungBang
{
    const LOAI = 'XML8';

    /** TOMTAT_KQ cat toi da 2000 ky tu ([AX]:948) */
    const DAI_TOMTAT_KQ = 2000;

    /** Noi tru duoi bao nhieu gio thi la "09" */
    const GIO_NOI_TRU_NGAN = 4;

    /** TDL_SERVICE_REQ_TYPE_ID khong tinh la co thuc hien dich vu cua ngoai tru man tinh */
    const YEU_CAU_KHONG_DV = [1, 6, 14, 15];

    private $bayGio;

    /** @param \DateTime|null $bayGio moc tinh tuoi (test); null => luc dung */
    public function __construct(\DateTime $bayGio = null)
    {
        $this->bayGio = $bayGio;
    }

    public function loai(): string
    {
        return self::LOAI;
    }

    public function coTrongHoSo(DuLieuHoSo $d): bool
    {
        $hs = $d->hoSo;
        if (!GiaTriBang::co($hs->tdl_treatment_type_id)) {
            return false;
        }
        $loai = (int) $hs->tdl_treatment_type_id;

        return $loai === 3 || $loai === 4 || ($loai === 2 && !(GiaTriBang::co($hs->is_chronic) && (int) $hs->is_chronic !== 0));
    }

    public function dung(DuLieuHoSo $d, HisDanhMuc $dm, array $daDung): array
    {
        if (!$this->coTrongHoSo($d)) {
            return [];
        }
        $hs = $d->hoSo;
        $pta = RaVienChung::ptaChinh($d, $dm);
        $hoTenCha = RaVienChung::hoTenCha($hs);
        $hoTenMe = RaVienChung::hoTenMe($hs);
        $be = $this->beChet($d->soSinh);

        return [[
            'MA_LK' => GiaTriBang::chu($hs->treatment_code),
            'MA_LOAI_KCB' => $this->maLoaiKcb($d),
            'HO_TEN_CHA' => $hoTenCha,
            'HO_TEN_ME' => $hoTenMe,
            'NGUOI_GIAM_HO' => GiaTriBang::chu($hs->tdl_patient_relative_name),
            'DON_VI' => GiaTriBang::co($hs->tdl_patient_work_place) ? (string) $hs->tdl_patient_work_place : GiaTriBang::chu($hs->tdl_patient_work_place_name),
            'NGAY_VAO' => GiaTriBang::thoiGian($hs->in_time, 12),
            'NGAY_RA' => GiaTriBang::thoiGian($hs->out_time, 12),
            'CHAN_DOAN_VAO' => GiaTriBang::co($hs->provisional_diagnosis) ? (string) $hs->provisional_diagnosis : GiaTriBang::chu($hs->icd_name),
            'CHAN_DOAN_RV' => RaVienChung::chanDoanRv($hs->icd_name, $hs->icd_text),
            'QT_BENHLY' => GiaTriBang::chu($hs->clinical_note),
            'TOMTAT_KQ' => GiaTriBang::kyTuXml(GiaTriBang::catChu($hs->subclinical_result, self::DAI_TOMTAT_KQ)),
            'PP_DIEUTRI' => GiaTriBang::kyTuXml($hs->treatment_method),
            'NGAY_SINHCON' => $be === null ? '' : GiaTriBang::thoiGian($be[0]->born_time, 8),
            'NGAY_CONCHET' => $be === null ? '' : GiaTriBang::thoiGian($be[0]->death_date, 8),
            'SO_CONCHET' => $be === null ? '' : (string) count($be),
            'KET_QUA_DTRI' => GiaTriBang::kyTuXml(GiaTriBang::co($hs->treatment_result_code) ? mb_substr((string) $hs->treatment_result_code, -1, 1, 'UTF-8') : ''),
            'GHI_CHU' => $this->ghiChu($hs),
            'MA_TTDV' => MaTtdv::cua($d, $dm, 8),
            'NGAY_CT' => GiaTriBang::thoiGian($hs->out_date, 8),
            'MA_THE_TAM' => RaVienChung::maTheTam($hs, $pta),
            'DU_PHONG' => '',
        ]];
    }

    /** Cac be co BORN_RESULT_ID == 2 (giu thu tu nap); khong co => null. */
    private function beChet(array $soSinh)
    {
        $ra = [];
        foreach ($soSinh as $b) {
            if (GiaTriBang::co($b->born_result_id) && (int) $b->born_result_id === 2) {
                $ra[] = $b;
            }
        }

        return count($ra) > 0 ? $ra : null;
    }

    /** Bang MA_LOAI_KCB cua DLL ([AX]:908), xem docblock lop. */
    private function maLoaiKcb(DuLieuHoSo $d)
    {
        $hs = $d->hoSo;
        switch ((int) $hs->tdl_treatment_type_id) {
            case 1:
                return '01';
            case 2:
                if (!(GiaTriBang::co($hs->is_chronic) && (int) $hs->is_chronic === 1)) {
                    return '02';
                }
                foreach ($d->dichVu as $dv) {
                    if (!GiaTriBang::co($dv->tdl_service_req_type_id) || !in_array((int) $dv->tdl_service_req_type_id, self::YEU_CAU_KHONG_DV, true)) {
                        return '08';
                    }
                }

                return count($d->dichVu) > 0 ? '05' : '10';
            case 3:
                return $this->noiTruNgan($hs->clinical_in_time, $hs->out_time) ? '09' : '03';
            case 4:
                return '04';
            case 5:
                return '06';
            case 6:
                return '07';
        }

        return '10';
    }

    /** OUT_TIME co, CLINICAL_IN_TIME co, OUT > CLINICAL_IN va khoang cach < 4 gio. */
    private function noiTruNgan($vao, $ra)
    {
        $v = ThoiGianHis::ngay($vao);
        $r = ThoiGianHis::ngay($ra);
        if ($v === null || $r === null) {
            return false;
        }
        $giay = $r->getTimestamp() - $v->getTimestamp();

        return $giay > 0 && $giay < self::GIO_NOI_TRU_NGAN * 3600;
    }

    /** Duoi 16 tuoi: FATHER_NAME, MOTHER_NAME, nguoi than; tu 16 tuoi tro len: rong. */
    private function ghiChu($hs)
    {
        $sinh = ThoiGianHis::ngay($hs->tdl_patient_dob);
        if ($sinh === null) {
            return '';
        }
        $bayGio = $this->bayGio !== null ? $this->bayGio : new \DateTime();
        if ($sinh->diff($bayGio)->y >= 16) {
            return '';
        }
        if (GiaTriBang::co($hs->father_name)) {
            return (string) $hs->father_name;
        }
        if (GiaTriBang::co($hs->mother_name)) {
            return (string) $hs->mother_name;
        }

        return GiaTriBang::chu($hs->tdl_patient_relative_name);
    }
}

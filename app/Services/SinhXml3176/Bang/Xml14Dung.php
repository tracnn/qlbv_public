<?php

namespace App\Services\SinhXml3176\Bang;

use App\Services\SinhXml3176\DuLieuHoSo;
use App\Services\SinhXml3176\Nguon\HisDanhMuc;

/**
 * XML14 - giay hen kham lai. Port XML14Processor.GenerateXML14ADO (DLL XML 130.XML14, ban do [AX]:1298-1346), luon theo
 * nhanh IS_3176. Bang phang: mot ban ghi, the goc chinh la ban ghi (CHI_TIEU_GIAYHEN_KHAMLAI). Truong CDATA: HO_TEN,
 * DIA_CHI, CHAN_DOAN_RV.
 *
 * Dieu kien (loi, [AX]:113-134): TREATMENT_END_TYPE_ID == 3 (hen kham lai). Khac => khong co XML14.
 * Nhanh IS_3176: NGAY_HEN_KL 8 ky tu (yyyyMMdd), MA_BENH_YHCT toi da 13 ma, MA_TTDV = MaTtdv::cua(.., 14).
 * MA_DOITUONG_KCB: HEIN_PATIENT_TYPE_CODE; rong thi tu suy bang DoiTuongKcbXml1 (cung luat va cung danh muc his_medi_org
 * voi XML1, khong chep), voi PTA dai dien = PTA cua ho so (TREATMENT_ID == ID) theo thu tu BHYT, LOG_TIME, ID moi nhat.
 * The BHYT: TheBhyt (dung chung voi XML1), KHONG loc theo TREATMENT_ID nhu DLL ([AX]:1343).
 *
 * Giu nhu DLL (can xac nhan): [AX]:1338 chi tu suy khi HEIN_PATIENT_TYPE_CODE rong; module coi chuoi toan khoang trang
 *   nhu null (nhu XML1). Bo luat dung chung voi XML1 lech DLL XML14 o ba cho: (a) DT: DLL XML14 xet GT, HK truoc
 *   IS_NEWBORN, DLL XML1 xet IS_NEWBORN truoc; (b) TT: DLL XML14 chi tinh dieu tri noi tru la TDL_TREATMENT_TYPE_ID == 3,
 *   DLL XML1 tinh ca 4; (c) khong khop nhanh nao: DLL XML14 de rong, luat chung tra "9". [AX]:1342 MA_BAC_SI rong khi
 *   khong co nhan vien (DLL bo phan tu).
 * Sua loi DLL [AX]:1337: khong co PTA nao cua ho so thi .First() nem loi va mat XML14; module van sinh, MA_DOITUONG_KCB rong
 *   khi HEIN_PATIENT_TYPE_CODE rong.
 * Sua loi DLL [AX]:1339: ICD_CODE danh muc null, HEIN_MEDI_ORG_CODE ngan hon 2 ky tu, PTA khong co so the khong lam vo
 *   (DoiTuongKcbXml1).
 * Sua loi DLL [AX]:1343: MA_THE_BHYT va GT_THE_DEN giu khop vi tri tung the (xem TheBhyt).
 * Sua loi DLL [AX]:1293 (loi cung kieu o XML13): CHAN_DOAN_RV khong con ';' thua khi ICD_TEXT rong (RaVienChung::chanDoanRv).
 * Phan tu null xuat rong, xem ghi chu chung ve phan tu null.
 */
class Xml14Dung implements DungBang
{
    const LOAI = 'XML14';

    /** MA_BENH_YHCT ban 3176 toi da 13 ma */
    const TOI_DA_MA_BENH_YHCT = 13;

    public function loai(): string
    {
        return self::LOAI;
    }

    public function coTrongHoSo(DuLieuHoSo $d): bool
    {
        $v = $d->hoSo->treatment_end_type_id;

        return GiaTriBang::co($v) && (int) $v === 3;
    }

    public function dung(DuLieuHoSo $d, HisDanhMuc $dm, array $daDung): array
    {
        if (!$this->coTrongHoSo($d)) {
            return [];
        }
        $hs = $d->hoSo;
        $the = TheBhyt::cot(TheBhyt::nhom($d->dienDieuTri, (string) $dm->config(Xml1Dung::KEY_SAP_THE)));
        $maThe = TheBhyt::noi($the['MA_THE_BHYT']);
        $ngayVaoNt = GiayChuyenHenChung::phut($hs->clinical_in_time);
        if ((string) $dm->config(Xml1Dung::KEY_NGAY_VAO_NT) === '1' && GiaTriBang::co($hs->tdl_treatment_type_id) && (int) $hs->tdl_treatment_type_id === 2) {
            $ngayVaoNt = '';
        }

        return [[
            'MA_LK' => GiaTriBang::chu($hs->treatment_code),
            'SO_GIAYHEN_KL' => GiaTriBang::chu($hs->end_code),
            'MA_CSKCB' => GiaTriBang::chu($hs->hein_medi_org_code),
            'HO_TEN' => GiaTriBang::kyTuXml($hs->tdl_patient_name),
            'NGAY_SINH' => GiayChuyenHenChung::ngaySinh($hs),
            'GIOI_TINH' => GiayChuyenHenChung::gioiTinh($hs),
            'DIA_CHI' => GiaTriBang::kyTuXml($hs->tdl_patient_address),
            'MA_THE_BHYT' => $maThe,
            'GT_THE_DEN' => TheBhyt::noi($the['GT_THE_DEN']),
            'NGAY_VAO' => GiayChuyenHenChung::phut($hs->in_time),
            'NGAY_VAO_NOI_TRU' => $ngayVaoNt,
            'NGAY_RA' => GiayChuyenHenChung::phut($hs->out_time),
            'NGAY_HEN_KL' => GiaTriBang::thoiGian($hs->appointment_time, 8),
            'CHAN_DOAN_RV' => GiaTriBang::kyTuXml(RaVienChung::chanDoanRv($hs->icd_name, $hs->icd_text)),
            'MA_BENH_CHINH' => GiaTriBang::chu($hs->icd_code),
            'MA_BENH_KT' => GiayChuyenHenChung::maBenhKt($hs),
            'MA_BENH_YHCT' => GiayChuyenHenChung::maBenhYhct($hs, self::TOI_DA_MA_BENH_YHCT),
            'MA_DOITUONG_KCB' => $this->maDoiTuongKcb($d, $dm, $maThe),
            'MA_BAC_SI' => GiayChuyenHenChung::maBacSi($hs, $dm),
            'MA_TTDV' => MaTtdv::cua($d, $dm, 14),
            'NGAY_CT' => GiaTriBang::thoiGian($hs->out_date, 8),
            'DU_PHONG' => '',
        ]];
    }

    /** HEIN_PATIENT_TYPE_CODE ?? tu suy (DoiTuongKcbXml1, [AX]:1329-1334). */
    private function maDoiTuongKcb(DuLieuHoSo $d, HisDanhMuc $dm, $maThe)
    {
        $hs = $d->hoSo;
        $ma = GiaTriBang::dauTien([$hs->hein_patient_type_code]);
        if ($ma !== '') {
            return $ma;
        }
        $ptas = [];
        foreach ($d->dienDieuTri as $p) {
            if ((int) $p->treatment_id === (int) $hs->id) {
                $ptas[] = $p;
            }
        }
        $pta = PtaChinh::chon($ptas, GiaTriBang::chu($dm->config(PtaChinh::KEY_BHYT)));

        return $pta === null ? '' : DoiTuongKcbXml1::suy($hs, $pta, $maThe, $dm);
    }
}

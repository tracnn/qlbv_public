<?php

namespace App\Services\SinhXml3176\Bang;

use App\Services\SinhXml3176\DuLieuHoSo;
use App\Services\SinhXml3176\Nguon\HisDanhMuc;

/**
 * XML7 - chi tieu du lieu giay ra vien. Port Xml7Processor.ProcessData + MapADOToXml (DLL XML 130.XML7,
 * ban do [AX]:841-899), luon theo nhanh IS_3176. Bang phang: mot ban ghi, the goc chinh la ban ghi
 * (CHI_TIEU_DU_LIEU_GIAY_RA_VIEN). Truong CDATA: PP_DIEUTRI.
 *
 * Dieu kien: TDL_TREATMENT_TYPE_ID thuoc {2, 3, 4} (ngoai tru, noi tru, ban ngay). Khac => khong co XML7.
 * PTA dung cho MA_THE_TAM chon nhu loi HIS ([AX]:126): uu tien doi tuong BHYT, sau do LOG_TIME, ID moi nhat (PtaChinh).
 * MA_TTDV (spec 5.6): dung chung MaTtdv::cua(.., 7); DLL so config "7" khong trim ("1, 7" khong khop), giu nhu DLL.
 * MA_BS / TEN_BS nhanh IS_3176: DIPLOMA / TDL_USERNAME cua END_DEPT_SUBS_HEAD ?? END_DEPARTMENT_HEAD ?? END_HEAD.
 *
 * Giu nhu DLL (can xac nhan): [AX]:891 SO_NGAY_NGHI = (den - tu) theo ngay, khong cong 1; [AX]:894 pham vi XML7
 *   rong hon XML8 (gom ca ngoai tru man tinh); [AX]:887 so khop config "7" khong trim; [AX]:893 CHAN_DOAN_RV va GHI_CHU
 *   khong loc ky tu dieu khien (GHI_CHU chi cat 1500 ky tu).
 * Sua loi DLL [AX]:892: CHAN_DOAN_RV khong con ';' thua khi ICD_TEXT null/rong (RaVienChung::chanDoanRv).
 * Sua loi DLL [AX]:875: OUTPATIENT_DATE_FROM/TO khong hop le (TimeNumberToSystemDateTime tra null, .Value nem loi va
 *   mat ca bang) => SO_NGAY_NGHI = 0.
 * Phan tu null xuat rong, xem ghi chu chung ve phan tu null.
 */
class Xml7Dung implements DungBang
{
    const LOAI = 'XML7';

    const LOAI_DIEU_TRI = [2, 3, 4];

    /** GHI_CHU (ADVISE) cat toi da 1500 ky tu ([AX]:865) */
    const DAI_GHI_CHU = 1500;

    public function loai(): string
    {
        return self::LOAI;
    }

    public function coTrongHoSo(DuLieuHoSo $d): bool
    {
        $loai = $d->hoSo->tdl_treatment_type_id;

        return GiaTriBang::co($loai) && in_array((int) $loai, self::LOAI_DIEU_TRI, true);
    }

    public function dung(DuLieuHoSo $d, HisDanhMuc $dm, array $daDung): array
    {
        if (!$this->coTrongHoSo($d)) {
            return [];
        }
        $hs = $d->hoSo;
        $pta = RaVienChung::ptaChinh($d, $dm);

        // IS_3176: chuoi "??" cua DLL; Oracle khong phan biet '' va NULL nen bo qua rong (nhu MaTtdv)
        $loginBs = GiaTriBang::dauTien([$hs->end_dept_subs_head_loginname, $hs->end_department_head_loginname, $hs->end_head_loginname]);
        $bs = $loginBs !== '' ? $dm->nhanVien($loginBs) : null;

        return [[
            'MA_LK' => GiaTriBang::chu($hs->treatment_code),
            'SO_LUU_TRU' => GiaTriBang::co($hs->end_code) ? (string) $hs->end_code : '0',
            'MA_YTE' => GiaTriBang::chu($hs->tdl_patient_code),
            'MA_KHOA_RV' => GiaTriBang::chu($hs->end_department_bhyt_code),
            'NGAY_VAO' => GiaTriBang::thoiGian($hs->in_time, 12),
            'NGAY_RA' => GiaTriBang::thoiGian($hs->out_time, 12),
            'MA_DINH_CHI_THAI' => GiaTriBang::co($hs->is_pregnancy_termination) && (int) $hs->is_pregnancy_termination === 1 ? '1' : '0',
            'NGUYENNHAN_DINHCHI' => GiaTriBang::chu($hs->pregnancy_termination_reason),
            'THOIGIAN_DINHCHI' => GiaTriBang::thoiGian($hs->pregnancy_termination_time, 12),
            'TUOI_THAI' => GiaTriBang::soChuoi($hs->gestational_age),
            'CHAN_DOAN_RV' => RaVienChung::chanDoanRv($hs->icd_name, $hs->icd_text),
            'PP_DIEUTRI' => GiaTriBang::kyTuXml($hs->treatment_method),
            'GHI_CHU' => GiaTriBang::catChu($hs->advise, self::DAI_GHI_CHU),
            'MA_TTDV' => MaTtdv::cua($d, $dm, 7),
            'MA_BS' => $bs !== null ? GiaTriBang::chu($bs->diploma) : '',
            'TEN_BS' => $bs !== null ? GiaTriBang::chu($bs->tdl_username) : '',
            'NGAY_CT' => GiaTriBang::thoiGian($hs->out_time, 8),
            'MA_CHA' => GiaTriBang::chu($hs->father_social_insurance_number),
            'MA_ME' => GiaTriBang::chu($hs->mother_social_insurance_number),
            'MA_THE_TAM' => RaVienChung::maTheTam($hs, $pta),
            'HO_TEN_CHA' => RaVienChung::hoTenCha($hs),
            'HO_TEN_ME' => RaVienChung::hoTenMe($hs),
            'SO_NGAY_NGHI' => (string) self::soNgayNghi($hs->outpatient_date_from, $hs->outpatient_date_to),
            'NGOAITRU_TUNGAY' => GiaTriBang::thoiGian($hs->outpatient_date_from, 8),
            'NGOAITRU_DENNGAY' => GiaTriBang::thoiGian($hs->outpatient_date_to, 8),
            'DU_PHONG' => '',
        ]];
    }

    /** (den.Date - tu.Date).Days khi ca hai hop le, khong thi 0. */
    private static function soNgayNghi($tu, $den)
    {
        $t = ThoiGianHis::ngay($tu);
        $e = ThoiGianHis::ngay($den);
        if ($t === null || $e === null) {
            return 0;
        }

        return (int) $t->setTime(0, 0, 0)->diff($e->setTime(0, 0, 0))->format('%r%a');
    }
}

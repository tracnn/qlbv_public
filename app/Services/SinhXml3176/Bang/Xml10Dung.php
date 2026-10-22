<?php

namespace App\Services\SinhXml3176\Bang;

use App\Services\SinhXml3176\DuLieuHoSo;
use App\Services\SinhXml3176\Nguon\HisDanhMuc;

/**
 * XML10 - giay nghi duong thai. Port Xml10Processor.GenerateXml10ADO + MapADOToXml (DLL XML 130.XML10,
 * ban do [AX]:1101-1132), luon theo nhanh IS_3176. Bang phang: mot ban ghi, the goc chinh la ban ghi
 * (CHI_TIEU_DU_LIEU_GIAY_NGHI_DUONG_THAI). Truong CDATA: DON_VI, CHAN_DOAN_RV, TEN_BS.
 *
 * Dieu kien: TREATMENT_END_TYPE_EXT_ID == 2. Khac => khong co XML10.
 * MA_TTDV (spec 5.6): dung chung MaTtdv::cua(.., 10).
 * MA_BS nhanh IS_3176: DIPLOMA cua END_DEPT_SUBS_HEAD ?? END_DEPARTMENT_HEAD ?? SICK_LOGINNAME.
 *
 * Giu nhu DLL (can xac nhan): [AX]:1124 nhanh IS_3176 gan cung CHAN_DOAN_RV = "dưỡng thai", bo ICD_NAME / ICD_TEXT;
 *   [AX]:1125 MA_BS (uu tien truong/pho khoa) va TEN_BS (SICK_USERNAME) co the la hai nguoi khac nhau;
 *   [AX]:1129 SO_SERI va SO_CT cung lay EXTRA_END_CODE.
 * Sua loi DLL [AX]:1127: tim nhan vien bang LOGINNAME.Equals, nhan vien co LOGINNAME null thi
 *   NullReference va mat XML10; module tra nhan vien theo login nen khong vo.
 * Phan tu null (SO_NGAY khi SICK_LEAVE_DAY null) xuat rong, xem ghi chu chung ve phan tu null.
 */
class Xml10Dung implements DungBang
{
    const LOAI = 'XML10';

    public function loai(): string
    {
        return self::LOAI;
    }

    public function coTrongHoSo(DuLieuHoSo $d): bool
    {
        $v = $d->hoSo->treatment_end_type_ext_id;

        return GiaTriBang::co($v) && (int) $v === 2;
    }

    public function dung(DuLieuHoSo $d, HisDanhMuc $dm, array $daDung): array
    {
        if (!$this->coTrongHoSo($d)) {
            return [];
        }
        $hs = $d->hoSo;
        $loginBs = GiaTriBang::dauTien([$hs->end_dept_subs_head_loginname, $hs->end_department_head_loginname, $hs->sick_loginname]);
        $bs = $loginBs !== '' ? $dm->nhanVien($loginBs) : null;

        return [[
            'MA_LK' => GiaTriBang::chu($hs->treatment_code),
            'SO_SERI' => GiaTriBang::chu($hs->extra_end_code),
            'SO_CT' => GiaTriBang::chu($hs->extra_end_code),
            // (int)Math.Truncate(SICK_LEAVE_DAY)
            'SO_NGAY' => GiaTriBang::co($hs->sick_leave_day) ? (string) (int) (float) $hs->sick_leave_day : '',
            'DON_VI' => GiaTriBang::kyTuXml(trim((string) $hs->tdl_patient_work_place) !== ''
                ? $hs->tdl_patient_work_place
                : $hs->tdl_patient_work_place_name),
            'CHAN_DOAN_RV' => 'dưỡng thai',
            'TU_NGAY' => GiaTriBang::thoiGian($hs->sick_leave_from, 8),
            'DEN_NGAY' => GiaTriBang::thoiGian($hs->sick_leave_to, 8),
            'MA_TTDV' => MaTtdv::cua($d, $dm, 10),
            'TEN_BS' => GiaTriBang::kyTuXml($hs->sick_username),
            'MA_BS' => $bs !== null ? GiaTriBang::chu($bs->diploma) : '',
            'NGAY_CT' => GiaTriBang::thoiGian($hs->out_date, 8),
            'DU_PHONG' => '',
        ]];
    }
}

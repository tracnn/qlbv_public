<?php

namespace App\Services\SinhXml3176\Bang;

use App\Services\SinhXml3176\DuLieuHoSo;
use App\Services\SinhXml3176\Nguon\HisDanhMuc;

/**
 * XML11 - giay chung nhan nghi viec huong BHXH, mau CT07. Port Xml11Processor.GenerateXml11ADO + MapADOToXml
 * (DLL XML 130.XML11, ban do [AX]:1133-1175), luon theo nhanh IS_3176. Bang phang: mot ban ghi, the goc chinh la
 * ban ghi (CHI_TIEU_DU_LIEU_GIAY_CHUNG_NHAN_NGHI_VIEC_HUONG_BAO_HIEM_XA_HOI). Truong CDATA: DON_VI, CHAN_DOAN_RV,
 * PP_DIEUTRI, NGUYENNHAN_DINHCHI, HO_TEN_CHA, HO_TEN_ME.
 *
 * Dieu kien: TREATMENT_END_TYPE_EXT_ID == 1; MAU_SO gan cung "CT07".
 * MA_TTDV (spec 5.6): dung chung MaTtdv::cua(.., 11).
 * MA_BS nhanh IS_3176: DIPLOMA cua END_DEPT_SUBS_HEAD ?? END_DEPARTMENT_HEAD ?? SICK_LOGINNAME.
 * MA_THE_TAM: PTA cua ho so chon nhu loi HIS (PtaChinh), HAS_BIRTH_CERTIFICATE == "C" thi TDL_HEIN_CARD_NUMBER.
 *
 * Giu nhu DLL (can xac nhan): [AX]:1166 NGAY_CT la ngay vao vien (IN_DATE); [AX]:1145 DON_VI chi kiem != null (chuoi
 *   rong van la noi lam viec); [AX]:1168 chi co ICD_SUB_CODE hoac chi co ICD_TEXT thi CHAN_DOAN_RV ket thuc bang ';'
 *   khong co noi dung; ICD_TEXT nhieu phan tu hon ICD_SUB_CODE thi phan du bi bo; [AX]:1171 MA_BS uu tien truong khoa
 *   ra vien, khong phai bac si ky giay (SICK_LOGINNAME).
 * Sua loi DLL [AX]:1167: ICD_TEXT it phan tu hon ICD_SUB_CODE thi array2[i] nem IndexOutOfRange va mat ca XML11;
 *   module ghep den dau duoc den do, ten thieu de trong ("MA:").
 * Sua loi DLL [AX]:1170: MA_BHXH lay 10 ky tu cuoi cua so the; the ngan hon 10 ky tu thi DLL nem loi (Substring)
 *   va mat XML11 => lay het chuoi the.
 * Sua loi DLL [AX]:1169: .First() tren PTA khong co dong nao cua ho so nem loi va mat XML11 => MA_THE_TAM rong.
 * Phan tu null (SO_SERI, SO_CT khi EXTRA_END_CODE null) xuat rong, xem ghi chu chung ve phan tu null.
 */
class Xml11Dung implements DungBang
{
    const LOAI = 'XML11';

    public function loai(): string
    {
        return self::LOAI;
    }

    public function coTrongHoSo(DuLieuHoSo $d): bool
    {
        $v = $d->hoSo->treatment_end_type_ext_id;

        return GiaTriBang::co($v) && (int) $v === 1;
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
            'SO_KCB' => GiaTriBang::chu($hs->treatment_code),
            'DON_VI' => GiaTriBang::kyTuXml($hs->tdl_patient_work_place !== null ? $hs->tdl_patient_work_place : $hs->tdl_patient_work_place_name),
            'MA_BHXH' => self::maBhxh($hs),
            'MA_THE_BHYT' => self::khacTrang($hs->sick_hein_card_number) ? (string) $hs->sick_hein_card_number : GiaTriBang::chu($hs->tdl_hein_card_number),
            'CHAN_DOAN_RV' => GiaTriBang::kyTuXml(self::chanDoanRv($hs)),
            'PP_DIEUTRI' => GiaTriBang::kyTuXml($hs->treatment_method),
            'MA_DINH_CHI_THAI' => GiaTriBang::co($hs->is_pregnancy_termination) ? GiaTriBang::soChuoi($hs->is_pregnancy_termination) : '0',
            'NGUYENNHAN_DINHCHI' => GiaTriBang::kyTuXml($hs->pregnancy_termination_reason),
            'TUOI_THAI' => GiaTriBang::soChuoi($hs->gestational_age),
            'SO_NGAY_NGHI' => self::soNgayNghi($hs->sick_leave_from, $hs->sick_leave_to),
            'TU_NGAY' => GiaTriBang::thoiGian($hs->sick_leave_from, 8),
            'DEN_NGAY' => GiaTriBang::thoiGian($hs->sick_leave_to, 8),
            'HO_TEN_CHA' => GiaTriBang::kyTuXml(RaVienChung::hoTenCha($hs)),
            'HO_TEN_ME' => GiaTriBang::kyTuXml(RaVienChung::hoTenMe($hs)),
            'MA_TTDV' => MaTtdv::cua($d, $dm, 11),
            'MA_BS' => $bs !== null ? GiaTriBang::chu($bs->diploma) : '',
            'MA_THE_TAM' => RaVienChung::maTheTam($hs, self::ptaChinh($d, $dm, $hs)),
            'NGAY_CT' => GiaTriBang::thoiGian($hs->in_date, 8),
            'MAU_SO' => 'CT07',
            'DU_PHONG' => '',
        ]];
    }

    private static function khacTrang($v)
    {
        return $v !== null && trim((string) $v) !== '';
    }

    /** TDL_SOCIAL_INSURANCE_NUMBER, roi 10 ky tu cuoi SICK_HEIN_CARD_NUMBER, roi 10 ky tu cuoi TDL_HEIN_CARD_NUMBER. */
    private static function maBhxh($hs)
    {
        if (self::khacTrang($hs->tdl_social_insurance_number)) {
            return (string) $hs->tdl_social_insurance_number;
        }
        if (self::khacTrang($hs->sick_hein_card_number)) {
            return GiaTriBang::cuoi($hs->sick_hein_card_number, 10);
        }
        if (self::khacTrang($hs->tdl_hein_card_number)) {
            return GiaTriBang::cuoi($hs->tdl_hein_card_number, 10);
        }

        return '';
    }

    /**
     * ICD_CODE + ":" + ICD_NAME; co ICD_SUB_CODE hoac ICD_TEXT thi them ";"; co ca hai thi ghep cap sub[i]:text[i]
     * noi ';'. Sua loi DLL [AX]:1167: text thieu phan tu thi ten de trong.
     */
    private static function chanDoanRv($hs)
    {
        $ma = GiaTriBang::chu($hs->icd_code);
        if (trim($ma) !== '') {
            $ma .= ':';
        }
        $kq = $ma . GiaTriBang::chu($hs->icd_name);
        $sub = GiaTriBang::chu($hs->icd_sub_code);
        $text = GiaTriBang::chu($hs->icd_text);
        if ($kq !== '' && substr(rtrim($kq), -1) !== ';' && ($sub !== '' || $text !== '')) {
            $kq .= ';';
        }
        if ($sub === '' || $text === '') {
            return $kq;
        }
        $maPhu = explode(';', self::catBienChamPhay($sub));
        $tenPhu = explode(';', self::catBienChamPhay($text));
        $ghep = '';
        foreach ($maPhu as $i => $m) {
            $ghep .= $m . ':' . (isset($tenPhu[$i]) ? $tenPhu[$i] : '') . ';';
        }

        return $kq . rtrim($ghep, ';');
    }

    /** Cuoi ';' thi bo moi ';' cuoi, khong thi dau ';' thi bo moi ';' dau (else if cua DLL). */
    private static function catBienChamPhay($s)
    {
        if (substr($s, -1) === ';') {
            return rtrim($s, ';');
        }
        if (substr($s, 0, 1) === ';') {
            return ltrim($s, ';');
        }

        return $s;
    }

    /** (den.Date - tu.Date).TotalDays + 1 khi ca hai hop le, khong thi rong. */
    private static function soNgayNghi($tu, $den)
    {
        $t = ThoiGianHis::ngay($tu);
        $e = ThoiGianHis::ngay($den);
        if ($t === null || $e === null) {
            return '';
        }

        return (string) ((int) $t->setTime(0, 0, 0)->diff($e->setTime(0, 0, 0))->format('%r%a') + 1);
    }

    /** PTA thuoc ho so (TREATMENT_ID == ID), chon nhu loi HIS; khong co dong nao => null. */
    private static function ptaChinh(DuLieuHoSo $d, HisDanhMuc $dm, $hs)
    {
        $ptas = [];
        foreach ($d->dienDieuTri as $p) {
            if ((int) $p->treatment_id === (int) $hs->id) {
                $ptas[] = $p;
            }
        }

        return PtaChinh::chon($ptas, GiaTriBang::chu($dm->config(PtaChinh::KEY_BHYT)));
    }
}

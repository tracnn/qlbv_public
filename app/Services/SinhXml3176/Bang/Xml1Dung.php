<?php

namespace App\Services\SinhXml3176\Bang;

use App\Services\SinhXml3176\DuLieuHoSo;
use App\Services\SinhXml3176\Nguon\HisDanhMuc;

/**
 * XML1 - tong hop. Port Xml1Processor.GenerateXml1Data (DLL QD130/3176, ban do [AX]:167-249, key [AX]:250-269),
 * ap vo dieu kien khoi IS_3176 ([AX]:25: SO_CCCD, MA_NGHE_NGHIEP, CHAN_DOAN_VAO, MA_BENH_YHCT, MA_TTDV, DU_PHONG).
 * Tong tien la tong cac dong XML2/XML3 da dung ($daDung) nhu loi DLL (HoSoProcessor L:400-537). MA_DOITUONG_KCB tu
 * suy o DoiTuongKcbXml1. Chi sua cac loi DLL duoi day; con lai giu nguyen hanh vi DLL.
 *
 * Sua loi DLL [AX]:296: cat do dai theo QD130 cho cac truong chac chan gioi han (theo KY TU UTF-8): HO_TEN 255,
 *   DIA_CHI 1024; danh sach ma MA_BENH_KT 100, MA_BENH_YHCT 255 bo ma cuoi khong vua (khong cat doi ma). Cac truong
 *   khac giu nhu DLL (khong cat) - can xac nhan.
 * Sua loi DLL [AX]:297: CHAN_DOAN_RV ghep ICD_NAME, ICD_TEXT (da bo ';' o hai dau) bang ';' chi khi ca hai khac rong.
 * Sua loi DLL [AX]:298: chuoi rong/khoang trang coi nhu null trong cac chuoi '??': SO_CCCD, DIEN_THOAI, MA_KHOA,
 *   MA_DOITUONG_KCB, GIAY_CHUYEN_TUYEN, MA_TAI_NAN, loginname (pho) giam doc cua MA_TTDV.
 * Sua loi DLL [AX]:300-304: khong nem loi null; thieu IN_TIME, ngay sinh, OUT_TIME (khi can tinh SO_NGAY_DTRI) hoac
 *   TREATMENT_DAY_COUNT (SO_NGAY_DTRI_OPTION=1) => nem LoiDungBang co mo ta (xem them DoiTuongKcbXml1).
 * Sua loi DLL [AX]:305: MA_THE_BHYT, MA_DKBD, GT_THE_TU, GT_THE_DEN dung theo tung the (PTA co so the), vi tri thu i
 *   cua bon truong cung mot the; gia tri thieu de rong (vd "01929;"). PTA khong co so the khong chiem vi tri.
 * Sua loi DLL [AX]:311: MA_BENH_YHCT tach ca TRADITIONAL_ICD_CODE theo ';' (DLL them nguyen chuoi), bo ma trung, toi da 13.
 * Sua loi DLL [AX]:314: DU_PHONG (chieu cao) lam tron 2 chu so.
 * MA_TTDV (spec 5.6): key MA_TTDV_FROM_PROFILE_XML chua "1" (lua chon benh vien) => nhanh 130 cua DLL (ma BHXH cua
 *   (pho) giam doc; khong co loginname => REPRESENTATIVE_HEIN_CODE); khong thi DIPLOMA cua (pho) giam doc (khoi 3176).
 * Can xac nhan [AX]:308-309: MA_LOAI_KCB "100" cho KSK giu nhu DLL. Can xac nhan [AX]:313: SO_NGAY_DTRI giu nhu DLL
 *   (NGAY_RA - NGAY_VAO + 1 theo ngay).
 * Dinh dang: tien "%.2f" nhu Xml2Dung (DLL G27); can nang/chieu cao bo so 0 thua.
 */
class Xml1Dung implements DungBang
{
    const LOAI = 'XML1';

    /** Key DLL XML1 dung de chon PTA chinh (khac key MOS... cua loi). */
    const KEY_BHYT = 'HIS.HIS_PATIENT_TYPE.PATIENT_TYPE_CODE.BHYT';
    const KEY_KSK = 'MOS.HIS_PATIENT_TYPE.PATIENT_TYPE_CODE.KSK';
    const KEY_SAP_THE = 'HIS.QD_130_BYT.SORT_MA_THE_BHYT';
    const KEY_THOI_GIAN_QT = 'HIS.QD_130_BYT.THOI_GIAN_QT_OPTION';
    const KEY_MA_KHOA = 'HIS.QD_130_BYT.XML1.MA_KHOA_OPTION';
    const KEY_SO_NGAY = 'HIS.QD_130_BYT.XML1.SO_NGAY_DTRI_OPTION';
    const KEY_NGAY_VAO_NT = 'HIS.QD_130_BYT.XML1.NGAY_VAO_NOI_TRU_OPTION';
    const KEY_GIAY_CT = 'HIS.QD_130_BYT.GIAY_CHUYEN_TUYEN_XML1_FORMAT';
    const KEY_MA_TTDV = 'HIS.QD_130_BYT.MA_TTDV_FROM_PROFILE_XML';

    /** QD130 bang 1: do dai toi da (ky tu) cac truong module cat */
    const DAI_HO_TEN = 255;
    const DAI_DIA_CHI = 1024;
    const DAI_MA_BENH_KT = 100;
    const DAI_MA_BENH_YHCT = 255;

    /** TDL_SERVICE_REQ_TYPE_ID khong tinh la "co thuc hien dich vu" cua ngoai tru man tinh (X1P:597) */
    const YEU_CAU_KHONG_DV = [1, 6, 14, 15];

    private $bayGio;

    /** @param \DateTime|null $bayGio thoi diem xuat (THOI_GIAN_QT_OPTION=2); null => luc dung */
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
        return true;
    }

    public function dung(DuLieuHoSo $d, HisDanhMuc $dm, array $daDung): array
    {
        $hs = $d->hoSo;
        $ch = function ($k) use ($dm) {
            return (string) $dm->config($k);
        };

        $ngaySinh = ThoiGianHis::chuoi($hs->tdl_patient_dob);
        if ($ngaySinh === null || strlen($ngaySinh) < 12) {
            throw new LoiDungBang(self::LOAI, 'Hồ sơ ' . $hs->treatment_code . ' thiếu ngày sinh hợp lệ');
        }
        $ngayVao = self::tg($hs->in_time, 12);
        if (strlen($ngayVao) < 12) {
            throw new LoiDungBang(self::LOAI, 'Hồ sơ ' . $hs->treatment_code . ' thiếu thời gian vào viện hợp lệ');
        }

        $the = $this->the($d->dienDieuTri, $ch(self::KEY_BHYT), $ch(self::KEY_SAP_THE));
        $maDoiTuong = GiaTriBang::dauTien([$hs->hein_patient_type_code]);
        if ($maDoiTuong === '') {
            $maDoiTuong = $the['chinh'] !== null ? DoiTuongKcbXml1::suy($hs, $the['chinh'], $the['MA_THE_BHYT'], $dm) : '9';
        }

        $loaiKcb = $this->loaiKcb($d, $dm, $ch(self::KEY_KSK));
        $ngayVaoNt = self::tg($hs->clinical_in_time, 12);
        if ($ch(self::KEY_NGAY_VAO_NT) === '1' && in_array($loaiKcb, ['02', '05', '08'], true)) {
            $ngayVaoNt = '';
        }
        $ngayRa = self::tg($hs->out_time, 12);
        $tien = $this->tien($daDung);
        $qt = $this->thoiGianQt($hs, $ch(self::KEY_THOI_GIAN_QT));
        $ngheNghiep = GiaTriBang::chu($hs->tdl_patient_career_code);
        $coCuTru = GiaTriBang::co($hs->tdl_patient_province_code) && GiaTriBang::co($hs->tdl_patient_commune_code);

        return [[
            'MA_LK' => GiaTriBang::chu($hs->treatment_code),
            'STT' => '1',
            'MA_BN' => GiaTriBang::chu($hs->tdl_patient_code),
            'HO_TEN' => GiaTriBang::catChu(GiaTriBang::chu($hs->tdl_patient_name), self::DAI_HO_TEN),
            'SO_CCCD' => GiaTriBang::dauTien([$hs->tdl_patient_cccd_number, $hs->tdl_patient_cmnd_number, $hs->tdl_patient_passport_number]),
            'NGAY_SINH' => self::bang($hs->tdl_patient_is_has_not_day_dob, 1) ? substr($ngaySinh, 0, 4) . '00000000' : substr($ngaySinh, 0, 12),
            'GIOI_TINH' => self::bang($hs->tdl_patient_gender_id, 1) ? '2' : (self::bang($hs->tdl_patient_gender_id, 2) ? '1' : '3'),
            'NHOM_MAU' => GiaTriBang::chu($hs->tdl_patient_blood_abo_code),
            'MA_QUOCTICH' => GiaTriBang::chu($hs->tdl_patient_mps_national_code),
            'MA_DANTOC' => GiaTriBang::chu($hs->ethnic_code),
            'MA_NGHE_NGHIEP' => mb_strlen($ngheNghiep, 'UTF-8') > 2 ? mb_substr($ngheNghiep, 0, 2, 'UTF-8') : $ngheNghiep,
            'DIA_CHI' => GiaTriBang::catChu(GiaTriBang::chu($hs->tdl_patient_address), self::DAI_DIA_CHI),
            'MATINH_CU_TRU' => GiaTriBang::chu($coCuTru ? $hs->tdl_patient_province_code : $hs->ht_province_code),
            'MAHUYEN_CU_TRU' => GiaTriBang::chu($coCuTru ? $hs->tdl_patient_district_code : $hs->ht_district_code),
            'MAXA_CU_TRU' => GiaTriBang::chu($coCuTru ? $hs->tdl_patient_commune_code : $hs->ht_commune_code),
            'DIEN_THOAI' => GiaTriBang::dauTien([$hs->tdl_patient_mobile, $hs->tdl_patient_phone]),
            'MA_THE_BHYT' => $the['MA_THE_BHYT'],
            'MA_DKBD' => $the['MA_DKBD'],
            'GT_THE_TU' => $the['GT_THE_TU'],
            'GT_THE_DEN' => $the['GT_THE_DEN'],
            'NGAY_MIEN_CCT' => $the['NGAY_MIEN_CCT'],
            'LY_DO_VV' => GiaTriBang::chu($hs->hospitalization_reason),
            'LY_DO_VNT' => GiaTriBang::chu($hs->hospitalize_reason_name),
            'MA_LY_DO_VNT' => GiaTriBang::chu($hs->hospitalize_reason_code),
            'CHAN_DOAN_VAO' => GiaTriBang::dauTien([$hs->provisional_diagnosis, $hs->icd_name]),
            'CHAN_DOAN_RV' => GiaTriBang::noi([trim(GiaTriBang::chu($hs->icd_name), " ;"), trim(GiaTriBang::chu($hs->icd_text), " ;")]),
            'MA_BENH_CHINH' => GiaTriBang::chu($hs->icd_code),
            'MA_BENH_KT' => GiaTriBang::catMa(array_slice(GiaTriBang::tachMa($hs->icd_sub_code), 0, 12), self::DAI_MA_BENH_KT),
            'MA_BENH_YHCT' => $this->maBenhYhct($hs),
            'MA_PTTT_QT' => $this->maPtttQt($d->pttt),
            'MA_DOITUONG_KCB' => $maDoiTuong,
            'MA_NOI_DI' => GiaTriBang::chu($hs->transfer_in_medi_org_code),
            'MA_NOI_DEN' => GiaTriBang::chu($hs->medi_org_code),
            'MA_TAI_NAN' => GiaTriBang::dauTien([$hs->accident_hurt_type_bhyt_code, '0']),
            'NGAY_VAO' => $ngayVao,
            'NGAY_VAO_NOI_TRU' => $ngayVaoNt,
            'NGAY_RA' => $ngayRa,
            'GIAY_CHUYEN_TUYEN' => $ch(self::KEY_GIAY_CT) === '1'
                ? GiaTriBang::dauTien([$hs->out_code, $hs->transfer_in_code]) : GiaTriBang::chu($hs->transfer_in_code),
            'SO_NGAY_DTRI' => $this->soNgayDtri($hs, $loaiKcb, $ngayVao, $ngayRa, $ch(self::KEY_SO_NGAY)),
            'PP_DIEU_TRI' => GiaTriBang::chu($hs->treatment_method),
            'KET_QUA_DTRI' => GiaTriBang::co($hs->treatment_result_code) ? mb_substr((string) $hs->treatment_result_code, -1, 1, 'UTF-8') : '',
            'MA_LOAI_RV' => $this->maLoaiRv($hs),
            'GHI_CHU' => GiaTriBang::chu($hs->advise),
            'NGAY_TTOAN' => self::tg($hs->fee_lock_time, 12),
            'T_THUOC' => GiaTriBang::tien($tien['thuoc']),
            'T_VTYT' => GiaTriBang::tien($tien['vtyt']),
            'T_TONGCHI_BV' => GiaTriBang::tien($tien['bv']),
            'T_TONGCHI_BH' => GiaTriBang::tien($tien['bh']),
            'T_BNTT' => GiaTriBang::tien($tien['bntt']),
            'T_BNCCT' => GiaTriBang::tien($tien['bncct']),
            'T_BHTT' => GiaTriBang::tien($tien['bhtt']),
            'T_NGUONKHAC' => GiaTriBang::tien($tien['nk']),
            'T_BHTT_GDV' => GiaTriBang::tien(preg_match('/^(CA|QN|CY)/', $the['MA_THE_BHYT']) ? 0 : $tien['gdv']),
            'NAM_QT' => $qt[0],
            'THANG_QT' => $qt[1],
            'MA_LOAI_KCB' => $loaiKcb,
            'MA_KHOA' => $this->maKhoa($hs, $dm, $ch(self::KEY_MA_KHOA)),
            'MA_CSKCB' => GiaTriBang::chu($hs->hein_medi_org_code),
            'MA_KHUVUC' => $the['MA_KHUVUC'],
            'CAN_NANG' => $this->dhstMoiNhat($d->dhst, 'weight', true),
            'CAN_NANG_CON' => $this->canNangCon($d->soSinh),
            'NAM_NAM_LIEN_TUC' => $the['NAM_NAM_LIEN_TUC'],
            'NGAY_TAI_KHAM' => self::tg($hs->appointment_time, 8),
            'MA_HSBA' => GiaTriBang::chu($hs->treatment_code),
            'MA_TTDV' => $this->maTtdv($hs, $dm, $ch(self::KEY_MA_TTDV)),
            'DU_PHONG' => $this->dhstMoiNhat($d->dhst, 'height', false),
        ]];
    }

    /**
     * PTA chinh (X1P:30) + cac truong the (X1P:108-237, sua [AX]:305).
     * @return array ['chinh' => PTA|null, 'MA_THE_BHYT' => ..., ...]
     */
    private function the(array $ptas, $keyBhyt, $sapThe)
    {
        $ra = array_fill_keys(['MA_THE_BHYT', 'MA_DKBD', 'GT_THE_TU', 'GT_THE_DEN', 'NGAY_MIEN_CCT', 'MA_KHUVUC', 'NAM_NAM_LIEN_TUC'], '');
        $ra['chinh'] = null;
        if (count($ptas) === 0) {
            return $ra;
        }
        $giam = function ($a, $b) {
            $c = GiaTriBang::soSanh((float) $b->log_time, (float) $a->log_time);

            return $c !== 0 ? $c : GiaTriBang::soSanh((int) $b->id, (int) $a->id);
        };
        $chinh = GiaTriBang::sapOnDinh($ptas, function ($a, $b) use ($keyBhyt, $giam) {
            $c = GiaTriBang::soSanh((string) $b->patient_type_code === $keyBhyt ? 1 : 0, (string) $a->patient_type_code === $keyBhyt ? 1 : 0);

            return $c !== 0 ? $c : $giam($a, $b);
        })[0];

        $sap = $sapThe === '1' ? GiaTriBang::sapOnDinh($ptas, $giam) : GiaTriBang::sapOnDinh($ptas, function ($a, $b) {
            $c = self::soSanhNull($a->hein_card_from_time, $b->hein_card_from_time);

            return $c !== 0 ? $c : self::soSanhNull($a->hein_card_to_time, $b->hein_card_to_time);
        });
        $nhom = [];
        foreach ($sap as $p) {
            $k = json_encode([$p->hein_card_number, $p->hein_medi_org_code, $p->hein_card_from_time, $p->hein_card_to_time]);
            if (!isset($nhom[$k])) {
                $nhom[$k] = $p;
            }
        }

        $cot = ['MA_THE_BHYT' => [], 'MA_DKBD' => [], 'GT_THE_TU' => [], 'GT_THE_DEN' => []];
        $mot = ['NGAY_MIEN_CCT' => [], 'MA_KHUVUC' => [], 'NAM_NAM_LIEN_TUC' => []];
        foreach ($nhom as $p) {
            if (trim(GiaTriBang::chu($p->hein_card_number)) !== '') {
                $cot['MA_THE_BHYT'][] = (string) $p->hein_card_number;
                $cot['MA_DKBD'][] = trim(GiaTriBang::chu($p->hein_medi_org_code)) !== '' ? (string) $p->hein_medi_org_code : '';
                $cot['GT_THE_TU'][] = self::tg($p->hein_card_from_time, 8);
                $cot['GT_THE_DEN'][] = self::tg($p->hein_card_to_time, 8);
            }
            if (GiaTriBang::co($p->free_co_paid_time)) {
                $mot['NGAY_MIEN_CCT'][] = self::tg($p->free_co_paid_time, 8);
            }
            if (trim(GiaTriBang::chu($p->live_area_code)) !== '') {
                $mot['MA_KHUVUC'][] = (string) $p->live_area_code;
            }
            if (GiaTriBang::co($p->join_5_year_time)) {
                $mot['NAM_NAM_LIEN_TUC'][] = self::tg($p->join_5_year_time, 8);
            }
        }
        foreach ($cot as $k => $gt) {
            $ra[$k] = implode('', $gt) === '' ? '' : implode(';', $gt);
        }
        foreach ($mot as $k => $gt) {
            $ra[$k] = count($gt) === 1 ? $gt[0] : '';
        }

        // Rong thi lay cua PTA chinh (X1P:208-237)
        $duPhong = [
            'MA_THE_BHYT' => GiaTriBang::chu($chinh->hein_card_number),
            'MA_DKBD' => GiaTriBang::chu($chinh->hein_medi_org_code),
            'GT_THE_TU' => self::tg($chinh->hein_card_from_time, 8),
            'GT_THE_DEN' => self::tg($chinh->hein_card_to_time, 8),
            'NGAY_MIEN_CCT' => self::tg($chinh->free_co_paid_time, 8),
            'MA_KHUVUC' => trim(GiaTriBang::chu($chinh->live_area_code)) !== '' ? (string) $chinh->live_area_code : '',
            'NAM_NAM_LIEN_TUC' => self::tg($chinh->join_5_year_time, 8),
        ];
        foreach ($duPhong as $k => $v) {
            if ($ra[$k] === '') {
                $ra[$k] = $v;
            }
        }
        $ra['chinh'] = $chinh;

        return $ra;
    }

    /** Tong tien tu dong XML2/XML3 (HoSoProcessor L:400-537), cong theo dong xu de khong lech float. */
    private function tien(array $daDung)
    {
        $xu = ['thuoc' => 0, 'vtyt' => 0, 'bv' => 0, 'bh' => 0, 'bhtt' => 0, 'gdv' => 0, 'bncct' => 0, 'bntt' => 0, 'nk' => 0];
        $lay = function (array $dong, $k) {
            return isset($dong[$k]) && is_numeric($dong[$k]) ? (int) round((float) $dong[$k] * 100) : 0;
        };
        foreach (['XML2', 'XML3'] as $loai) {
            $ds = isset($daDung[$loai]) ? $daDung[$loai] : [];
            foreach ($ds as $dong) {
                $bv = $lay($dong, 'THANH_TIEN_BV');
                $bhtt = $lay($dong, 'T_BHTT');
                $xu['bv'] += $bv;
                if ($loai === 'XML2') {
                    $xu['thuoc'] += $bv;
                } elseif (isset($dong['MA_VAT_TU']) && trim((string) $dong['MA_VAT_TU']) !== '') {
                    $xu['vtyt'] += $bv;
                }
                $xu['bh'] += $lay($dong, 'THANH_TIEN_BH');
                $xu['bhtt'] += $bhtt;
                if (isset($dong['MA_PTTT']) && (string) $dong['MA_PTTT'] === '1') {
                    $xu['gdv'] += $bhtt;
                }
                $xu['bncct'] += $lay($dong, 'T_BNCCT');
                $xu['bntt'] += $lay($dong, 'T_BNTT');
                $xu['nk'] += $lay($dong, 'T_NGUONKHAC');
            }
        }

        return array_map(function ($x) {
            return $x / 100;
        }, $xu);
    }

    /** MA_LOAI_KCB (X1P:564-606). */
    private function loaiKcb(DuLieuHoSo $d, HisDanhMuc $dm, $keyKsk)
    {
        $hs = $d->hoSo;
        $loai = GiaTriBang::co($hs->tdl_treatment_type_id) ? (int) $hs->tdl_treatment_type_id : 0;
        switch ($loai) {
            case 1:
                $ma = '01';
                break;
            case 2:
                $ma = '02';
                if (self::bang($hs->is_chronic, 1)) {
                    $dv = $this->dichVuLoi($d->dichVu, $dm->idDoiTuongBhyt());
                    $ma = '10'; // giu DLL: man tinh khong co dich vu => giu mac dinh
                    if (count($dv) > 0) {
                        $ma = '05';
                        foreach ($dv as $x) {
                            if (!in_array((int) $x->tdl_service_req_type_id, self::YEU_CAU_KHONG_DV, true)) {
                                $ma = '08';
                                break;
                            }
                        }
                    }
                }
                break;
            case 3:
                $ma = '03';
                $ra = ThoiGianHis::ngay($hs->out_time);
                $vao = ThoiGianHis::ngay($hs->clinical_in_time);
                if ($ra !== null && $vao !== null && (float) $hs->out_time - (float) $hs->clinical_in_time > 0
                    && ($ra->getTimestamp() - $vao->getTimestamp()) / 3600 < 4) {
                    $ma = '09';
                }
                break;
            case 4:
                $ma = '04';
                break;
            case 5:
                $ma = '06';
                break;
            case 6:
                $ma = '07';
                break;
            default:
                $ma = '10';
        }
        if ($ma === '01' && $keyKsk !== '') {
            foreach ($d->dienDieuTri as $p) {
                if ((string) $p->patient_type_code === $keyKsk && GiaTriBang::co($p->patient_type_id)
                    && (string) (int) $p->patient_type_id === (string) (int) $hs->tdl_patient_type_id) {
                    return '100'; // can xac nhan [AX]:308-309
                }
            }
        }

        return $ma;
    }

    /** Bo loc dich vu cua loi (HoSoProcessor L:325-355, list2) - XML1 chi dung cho MA_LOAI_KCB man tinh. */
    private function dichVuLoi(array $dichVu, $idBhyt)
    {
        $ra = [];
        foreach ($dichVu as $o) {
            $coLoai = GiaTriBang::co($o->tdl_hein_service_type_id)
                || ((int) $o->tdl_service_type_id === 1 && (int) $o->tdl_is_main_exam === 1);
            if (!$coLoai || (float) $o->amount <= 0 || (int) $o->is_expend === 1) {
                continue;
            }
            $lay = (int) $o->is_no_execute === 1;
            if ((int) $o->is_no_execute !== 1 && (float) $o->price > 0) {
                $hein = $o->vir_total_hein_price;
                $lay = $idBhyt === null || (int) $o->patient_type_id !== $idBhyt
                    || (GiaTriBang::co($hein) && (float) $hein > 0)
                    || (GiaTriBang::co($hein) && (float) $hein == 0 && GiaTriBang::co($o->other_source_price) && (float) $o->other_source_price > 0)
                    || $lay;
            }
            if ($lay) {
                $ra[] = $o;
            }
        }

        return $ra;
    }

    /** SO_NGAY_DTRI (X1P:634-658); can xac nhan [AX]:313. */
    private function soNgayDtri($hs, $loaiKcb, $ngayVao, $ngayRa, $tuyChon)
    {
        if (!in_array($loaiKcb, ['02', '03', '04', '05', '08'], true)) {
            return '0';
        }
        if ($tuyChon === '1') {
            if (!GiaTriBang::co($hs->treatment_day_count)) {
                throw new LoiDungBang(self::LOAI, 'Hồ sơ ' . $hs->treatment_code
                    . ' thiếu TREATMENT_DAY_COUNT (SO_NGAY_DTRI_OPTION=1)');
            }

            return (string) (int) (float) $hs->treatment_day_count;
        }
        $ra = $ngayRa === '' ? false : \DateTime::createFromFormat('!Ymd', substr($ngayRa, 0, 8));
        $vao = \DateTime::createFromFormat('!Ymd', substr($ngayVao, 0, 8));
        if ($ra === false || $vao === false) {
            throw new LoiDungBang(self::LOAI, 'Hồ sơ ' . $hs->treatment_code
                . ' không có thời gian ra viện hợp lệ để tính SO_NGAY_DTRI');
        }
        $kc = $vao->diff($ra);

        return (string) (($kc->invert ? -1 : 1) * $kc->days + 1);
    }

    private function maLoaiRv($hs)
    {
        $ket = GiaTriBang::co($hs->treatment_end_type_id) ? (int) $hs->treatment_end_type_id : 0;
        if ($ket === 7) {
            return '3';
        }
        if ($ket === 8) {
            return '4';
        }
        if ($ket === 2 && self::bang($hs->tran_pati_reason_id, 1)) {
            return '2';
        }
        if ($ket === 2 && self::bang($hs->tran_pati_reason_id, 2)) {
            return '5';
        }

        return '1';
    }

    /** NAM_QT, THANG_QT (X1P:529-563). */
    private function thoiGianQt($hs, $tuyChon)
    {
        if ($tuyChon === '2') {
            $t = $this->bayGio ?: new \DateTime();

            return [$t->format('Y'), $t->format('m')];
        }
        $s = $tuyChon !== '1' && ThoiGianHis::chuoi($hs->hein_lock_time) !== null
            ? ThoiGianHis::chuoi($hs->hein_lock_time) : ThoiGianHis::chuoi($hs->out_time);
        if ($s === null) {
            return ['', ''];
        }

        return [substr($s, 0, 4), substr($s, 4, 2)];
    }

    /** MA_KHOA (X1P:365-401). */
    private function maKhoa($hs, HisDanhMuc $dm, $tuyChon)
    {
        if ($tuyChon !== '1') {
            return GiaTriBang::dauTien([$hs->end_room_bhyt_code, $hs->exit_bhyt_code, $hs->end_department_bhyt_code]);
        }
        $ids = [];
        foreach ([$hs->department_ids, $hs->co_department_ids] as $ds) {
            foreach (preg_split('/[,; ]+/', (string) $ds, -1, PREG_SPLIT_NO_EMPTY) as $x) {
                if (preg_match('/^-?\d+$/', $x) && !in_array((int) $x, $ids, true)) {
                    $ids[] = (int) $x;
                }
            }
        }
        $ma = [];
        foreach ($ids as $id) {
            $k = $dm->khoa($id);
            if ($k !== null && GiaTriBang::co($k->bhyt_code)) {
                $ma[] = (string) $k->bhyt_code;
            }
        }

        return implode(';', $ma);
    }

    /** Ban 3176 (X1P:696-705) + sua [AX]:311. */
    private function maBenhYhct($hs)
    {
        $ma = array_values(array_unique(array_merge(
            GiaTriBang::tachMa($hs->traditional_icd_code),
            GiaTriBang::tachMa($hs->traditional_icd_sub_code)
        )));

        return GiaTriBang::catMa(array_slice($ma, 0, 13), self::DAI_MA_BENH_YHCT);
    }

    /** MA_PTTT_QT (X1P:449-469): ICD_CM cua moi dong PTTT, distinct. Khong cat (can xac nhan). */
    private function maPtttQt(array $pttt)
    {
        $ma = [];
        foreach ($pttt as $p) {
            $ma = array_merge($ma, GiaTriBang::tachMa($p->icd_cm_code), GiaTriBang::tachMa($p->icd_cm_sub_code));
        }

        return implode(';', array_values(array_unique($ma)));
    }

    /** MA_TTDV (spec 5.6, X1P:661-684 va 707). */
    private function maTtdv($hs, HisDanhMuc $dm, $cauHinh)
    {
        $login = GiaTriBang::dauTien([$hs->hosp_subs_director_loginname, $hs->hospital_director_loginname]);
        $nv = $login !== '' ? $dm->nhanVien($login) : null;
        if (in_array('1', preg_split('/[,; ]+/', $cauHinh, -1, PREG_SPLIT_NO_EMPTY), true)) {
            if ($login === '') {
                return GiaTriBang::chu($hs->representative_hein_code);
            }

            return $nv !== null ? GiaTriBang::chu($nv->social_insurance_number) : '';
        }

        return $nv !== null ? GiaTriBang::chu($nv->diploma) : '';
    }

    /**
     * Gia tri DHST moi nhat. CAN_NANG (X1P:37-69): WEIGHT > 0, EXECUTE_TIME giam dan.
     * DU_PHONG (X1P:708-718): HEIGHT co gia tri, EXECUTE_TIME, ID giam dan. Lam tron 2 chu so.
     */
    private function dhstMoiNhat(array $dhst, $cot, $phaiDuong)
    {
        $ung = array_values(array_filter($dhst, function ($x) use ($cot, $phaiDuong) {
            return GiaTriBang::co($x->$cot) && (!$phaiDuong || (float) $x->$cot > 0);
        }));
        if (count($ung) === 0) {
            return '';
        }
        $sap = GiaTriBang::sapOnDinh($ung, function ($a, $b) use ($phaiDuong) {
            $c = GiaTriBang::soSanh((float) $b->execute_time, (float) $a->execute_time);

            return $c !== 0 || $phaiDuong ? $c : GiaTriBang::soSanh((int) $b->id, (int) $a->id);
        });

        return GiaTriBang::so($sap[0]->$cot, 2);
    }

    private function canNangCon(array $soSinh)
    {
        $ra = [];
        foreach ($soSinh as $be) {
            if (GiaTriBang::co($be->weight)) {
                $ra[] = GiaTriBang::so($be->weight, 2);
            }
        }

        return implode(';', $ra);
    }

    /** So thoi gian HIS -> $n ky tu dau (yyyyMMddHHmm / yyyyMMdd); khong co => ''. */
    private static function tg($v, $n)
    {
        $s = ThoiGianHis::chuoi($v);

        return $s === null ? '' : substr($s, 0, $n);
    }

    /** Nullable<long> OrderBy: null dung truoc. */
    private static function soSanhNull($a, $b)
    {
        $coA = GiaTriBang::co($a);
        $coB = GiaTriBang::co($b);
        if (!$coA || !$coB) {
            return ($coA ? 1 : 0) - ($coB ? 1 : 0);
        }

        return GiaTriBang::soSanh((float) $a, (float) $b);
    }

    private static function bang($v, $so)
    {
        return GiaTriBang::co($v) && (int) $v === $so;
    }
}

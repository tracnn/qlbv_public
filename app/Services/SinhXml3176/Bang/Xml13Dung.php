<?php

namespace App\Services\SinhXml3176\Bang;

use App\Services\SinhXml3176\DuLieuHoSo;
use App\Services\SinhXml3176\Nguon\HisDanhMuc;

/**
 * XML13 - giay chuyen tuyen. Port XML13Processor.GenerateXML13ADO (DLL XML 130.XML13, ban do [AX]:1231-1297), luon theo
 * nhanh IS_3176. Bang phang: mot ban ghi, the goc chinh la ban ghi (CHI_TIEU_GIAYCHUYENTUYEN). Truong CDATA: HO_TEN,
 * DIA_CHI, DAU_HIEU_LS, CHAN_DOAN_RV, QT_BENHLY, TOMTAT_KQ, PP_DIEUTRI, TEN_DICH_VU, TEN_THUOC, PP_DIEU_TRI,
 * HUONG_DIEU_TRI, PHUONGTIEN_VC, HOTEN_NGUOI_HT. 42 truong theo lop XML13Data (them MA_CSKCB_DI, MA_CSKCB_DEN,
 * TINH_TRANG_CT sau DU_PHONG).
 *
 * Dieu kien (loi, [AX]:113-134): TREATMENT_END_TYPE_ID == 2 (chuyen vien). Khac => khong co XML13.
 * MA_TTDV (spec 5.6): dung chung MaTtdv::cua(.., 13); DLL kiem key bang chuoi con (Contains("13")), giu nhu DLL.
 * The BHYT: TheBhyt (dung chung voi XML1), KHONG loc theo TREATMENT_ID nhu DLL ([AX]:1289).
 *
 * Nhanh IS_3176 gan null (module xuat rong): MA_NOI_DI, MA_NOI_DEN, TEN_DICH_VU, TEN_THUOC, PP_DIEU_TRI. Hai danh sach dich
 * vu (list4/list5) chi dung de gan TEN_DICH_VU/TEN_THUOC nen o nhanh nay khong anh huong ket qua; module khong doc $d->dichVu.
 * Giu nhu DLL (can xac nhan): [AX]:1283 5 truong bi xoa, MA_CSKCB_DI/DEN dat sau DU_PHONG theo lop XML13Data;
 *   [AX]:1285 USED_MEDICINE khong len bang nao, TINH_TRANG_CT lay TREATMENT_METHOD; [AX]:1294 MA_NOI_DI/MA_CSKCB_DI lay
 *   TRANSFER_IN_MEDI_ORG_CODE (noi chuyen BN den, khong chac la noi di); [AX]:1292 co DOCTOR_LOGINNAME nhung khong tim thay
 *   nhan vien thi MA_BAC_SI rong, khong lui ve END_LOGINNAME; [AX]:1291 MA_TTDV so khop chuoi con; [AX]:1290 dieu kien cat
 *   TOMTAT_KQ la NFKD > 2000 ky tu nhung cat theo 4000 byte (chuoi it ky tu nhieu byte khong bi cat).
 * Sua loi DLL [AX]:1286: vSereServ2 (list2) rong thi source = null va LINQ nem ArgumentNullException, mat ca XML13;
 *   module van sinh XML13 (TEN_DICH_VU/TEN_THUOC vua xuat rong o IS_3176 nen khong doc dich vu).
 * Sua loi DLL [AX]:1287: placeholder <#OUT_CODE_NUM_xxx;> can int.Parse(OUT_CODE.Substring(0, 6)); OUT_CODE ngan hon 6 ky
 *   tu hoac 6 ky tu dau khong phai so thi DLL nem loi va mat XML13; module giu nguyen chuoi sau khi thay cac placeholder khac
 *   (placeholder NUM khong duoc thay), khong nem loi.
 * Sua loi DLL [AX]:1289: MA_THE_BHYT va GT_THE_DEN giu khop vi tri tung the (xem TheBhyt).
 * Sua loi DLL [AX]:1290: cat TOMTAT_KQ 4000 byte khong dut giua ky tu UTF-8 (DLL sinh ky tu thay the U+FFFD).
 * Sua loi DLL [AX]:1293: CHAN_DOAN_RV khong con ';' thua khi ICD_TEXT rong (RaVienChung::chanDoanRv, nhu XML7).
 * Phan tu null xuat rong, xem ghi chu chung ve phan tu null.
 */
class Xml13Dung implements DungBang
{
    const LOAI = 'XML13';

    const KEY_SO_CHUYEN_TUYEN = 'HIS.QD_130_BYT.SO_CHUYENTUYEN_FORMAT';
    const KEY_GIAY_CHUYEN_TUYEN = 'HIS.QD_130_BYT.GIAY_CHUYEN_TUYEN_FORMAT';

    /** TOMTAT_KQ: cat khi NFKD dai hon 2000, cat 4000 byte ([AX]:1290) */
    const NGUONG_TOMTAT_KY_TU = 2000;
    const DAI_TOMTAT_BYTE = 4000;

    /** Chu Viet co 2 dau (them 2 dau ket hop khi tach NFKD) va 1 dau: dung khi may khong co ext intl */
    const CHU_HAI_DAU = 'ấầẩẫậắằẳẵặếềểễệốồổỗộớờởỡợứừửữự';
    const CHU_MOT_DAU = 'àáảãạâăèéẻẽẹêìíỉĩịòóỏõọôơùúủũụưỳýỷỹỵ';

    public function loai(): string
    {
        return self::LOAI;
    }

    public function coTrongHoSo(DuLieuHoSo $d): bool
    {
        $v = $d->hoSo->treatment_end_type_id;

        return GiaTriBang::co($v) && (int) $v === 2;
    }

    public function dung(DuLieuHoSo $d, HisDanhMuc $dm, array $daDung): array
    {
        if (!$this->coTrongHoSo($d)) {
            return [];
        }
        $hs = $d->hoSo;
        $the = TheBhyt::cot(TheBhyt::nhom($d->dienDieuTri, (string) $dm->config(Xml1Dung::KEY_SAP_THE)));
        $ngheNghiep = GiaTriBang::chu($hs->tdl_patient_career_code);

        return [[
            'MA_LK' => GiaTriBang::chu($hs->treatment_code),
            'SO_HOSO' => GiaTriBang::chu($hs->treatment_code),
            'SO_CHUYENTUYEN' => $this->soChuyenTuyen($hs, (string) $dm->config(self::KEY_SO_CHUYEN_TUYEN)),
            'GIAY_CHUYEN_TUYEN' => $this->giayChuyenTuyen($hs, (string) $dm->config(self::KEY_GIAY_CHUYEN_TUYEN)),
            'MA_CSKCB' => GiaTriBang::chu($hs->hein_medi_org_code),
            'MA_NOI_DI' => '',
            'MA_NOI_DEN' => '',
            'HO_TEN' => GiaTriBang::kyTuXml($hs->tdl_patient_name),
            'NGAY_SINH' => GiayChuyenHenChung::ngaySinh($hs),
            'GIOI_TINH' => GiayChuyenHenChung::gioiTinh($hs),
            'MA_QUOCTICH' => GiaTriBang::chu($hs->tdl_patient_mps_national_code),
            'MA_DANTOC' => GiaTriBang::chu($hs->ethnic_code),
            'MA_NGHE_NGHIEP' => mb_strlen($ngheNghiep, 'UTF-8') >= 2 ? mb_substr($ngheNghiep, 0, 2, 'UTF-8') : $ngheNghiep,
            'DIA_CHI' => GiaTriBang::kyTuXml($hs->tdl_patient_address),
            'MA_THE_BHYT' => TheBhyt::noi($the['MA_THE_BHYT']),
            'GT_THE_DEN' => TheBhyt::noi($the['GT_THE_DEN']),
            'NGAY_VAO' => GiayChuyenHenChung::phut($hs->in_time),
            'NGAY_VAO_NOI_TRU' => GiayChuyenHenChung::phut($hs->clinical_in_time),
            'NGAY_RA' => GiayChuyenHenChung::phut($hs->out_time),
            'DAU_HIEU_LS' => GiaTriBang::kyTuXml(GiaTriBang::co($hs->clinical_signs) ? $hs->clinical_signs : $hs->clinical_note),
            'CHAN_DOAN_RV' => GiaTriBang::kyTuXml(RaVienChung::chanDoanRv($hs->icd_name, $hs->icd_text)),
            'QT_BENHLY' => GiaTriBang::kyTuXml($hs->clinical_note),
            'TOMTAT_KQ' => GiaTriBang::kyTuXml($this->tomTatKq($hs->subclinical_result)),
            'PP_DIEUTRI' => GiaTriBang::kyTuXml($hs->treatment_method),
            'MA_BENH_CHINH' => GiaTriBang::chu($hs->icd_code),
            'MA_BENH_KT' => GiayChuyenHenChung::maBenhKt($hs),
            'MA_BENH_YHCT' => GiayChuyenHenChung::maBenhYhct($hs),
            'TEN_DICH_VU' => '',
            'TEN_THUOC' => '',
            'PP_DIEU_TRI' => '',
            'MA_LOAI_RV' => Xml1Dung::maLoaiRv($hs),
            'MA_LYDO_CT' => GiaTriBang::co($hs->tran_pati_reason_id) ? GiaTriBang::soChuoi($hs->tran_pati_reason_id) : '',
            'HUONG_DIEU_TRI' => GiaTriBang::kyTuXml($hs->treatment_direction),
            'PHUONGTIEN_VC' => GiaTriBang::kyTuXml($hs->transport_vehicle),
            'HOTEN_NGUOI_HT' => GiaTriBang::kyTuXml($hs->transporter),
            'CHUCDANH_NGUOI_HT' => $this->chucDanhNguoiHoTong($hs, $dm),
            'MA_BAC_SI' => GiayChuyenHenChung::maBacSi($hs, $dm),
            'MA_TTDV' => MaTtdv::cua($d, $dm, 13),
            'DU_PHONG' => '',
            'MA_CSKCB_DI' => GiaTriBang::chu($hs->transfer_in_medi_org_code),
            'MA_CSKCB_DEN' => GiaTriBang::chu($hs->medi_org_code),
            'TINH_TRANG_CT' => GiaTriBang::chu($hs->treatment_method),
        ]];
    }

    /** SO_CHUYENTUYEN: mau cau hinh thay <#OUT_CODE;>, <#OUT_MONTH;>, <#OUT_YEAR;>, <#OUT_YEAR_SHORT;>; rong => ".". */
    private function soChuyenTuyen($hs, $mau)
    {
        return $mau === '' ? '.' : self::thayMau($hs, $mau);
    }

    /**
     * GIAY_CHUYEN_TUYEN: OUT_CODE; co mau cau hinh thi thay nhu tren, them <#OUT_CODE_NUM_xxx;> => so nguyen cua 6 ky tu dau
     * OUT_CODE, dem '0' ben trai cho du so ky tu cua xxx (Sua loi DLL [AX]:1287: khong int.Parse duoc thi giu nguyen).
     */
    private function giayChuyenTuyen($hs, $mau)
    {
        $text = GiaTriBang::chu($hs->out_code);
        if ($mau === '') {
            return $text;
        }
        $text = self::thayMau($hs, $mau);
        if (preg_match('/<#OUT_CODE_NUM_.*;>/', $text, $kq) !== 1 || !GiaTriBang::co($hs->out_code)) {
            return $text;
        }
        $dau = mb_substr((string) $hs->out_code, 0, 6, 'UTF-8');
        // int.Parse: du 6 ky tu, cho phep khoang trang hai dau va dau +/-
        if (mb_strlen($dau, 'UTF-8') < 6 || preg_match('/^\s*[+-]?\d+\s*$/', $dau) !== 1) {
            return $text;
        }
        $so = (string) (int) trim($dau);
        $dem = mb_strlen(str_replace(['<#OUT_CODE_NUM_', ';>'], '', $kq[0]), 'UTF-8');

        return str_replace($kq[0], mb_strlen($so, 'UTF-8') >= $dem ? $so : str_pad($so, $dem, '0', STR_PAD_LEFT), $text);
    }

    private static function thayMau($hs, $mau)
    {
        $t = ThoiGianHis::chuoi($hs->out_time);

        return str_replace(
            ['<#OUT_CODE;>', '<#OUT_MONTH;>', '<#OUT_YEAR;>', '<#OUT_YEAR_SHORT;>'],
            [GiaTriBang::chu($hs->out_code), $t === null ? '' : substr($t, 4, 2), $t === null ? '' : substr($t, 0, 4), $t === null ? '' : substr($t, 2, 2)],
            $mau
        );
    }

    /** TITLE cac nhan vien co LOGINNAME trong TRANSPORTER_LOGINNAMES (tach ';'), theo thu tu danh sach nhan vien, noi ';'. */
    private function chucDanhNguoiHoTong($hs, HisDanhMuc $dm)
    {
        $logins = GiaTriBang::tachMa($hs->transporter_loginnames);
        if (count($logins) === 0) {
            return '';
        }
        $ra = [];
        foreach ($dm->nhanVienTheoDanhSach($logins) as $nv) {
            $ra[] = GiaTriBang::chu($nv->title);
        }

        return implode(';', $ra);
    }

    /** TOMTAT_KQ: dai (NFKD) hon 2000 ky tu thi cat 4000 byte UTF-8 nhung khong cat dut giua ky tu (Sua loi DLL [AX]:1290). */
    private function tomTatKq($v)
    {
        $s = GiaTriBang::chu($v);
        if ($s !== '' && self::doDaiFormKd($s) > self::NGUONG_TOMTAT_KY_TU) {
            return mb_strcut($s, 0, self::DAI_TOMTAT_BYTE, 'UTF-8');
        }

        return $s;
    }

    /**
     * Normalize(FormKD).Length cua .NET (don vi UTF-16). Co ext intl thi dung Normalizer; khong thi dem them dau
     * cua chu Viet co dau (tach NFKD thanh chu goc + 1 hoac 2 dau ket hop).
     */
    private static function doDaiFormKd($s)
    {
        if (class_exists('\Normalizer')) {
            $n = \Normalizer::normalize($s, \Normalizer::FORM_KD);
            if ($n !== false) {
                $s = $n;
            }

            return strlen(mb_convert_encoding($s, 'UTF-16LE', 'UTF-8')) / 2;
        }
        $dai = strlen(mb_convert_encoding($s, 'UTF-16LE', 'UTF-8')) / 2;
        $hai = self::CHU_HAI_DAU . mb_strtoupper(self::CHU_HAI_DAU, 'UTF-8');
        $mot = self::CHU_MOT_DAU . mb_strtoupper(self::CHU_MOT_DAU, 'UTF-8');

        return $dai + 2 * preg_match_all('/[' . $hai . ']/u', $s) + preg_match_all('/[' . $mot . ']/u', $s);
    }
}

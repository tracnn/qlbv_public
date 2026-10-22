<?php

namespace App\Services\SinhXml3176;

use App\Services\SinhXml3176\Nguon\HisDanhMuc;

/**
 * Quyet dinh mot ho so co duoc sinh XML3176 hay khong; tra ve ly do loai (tieng Viet) hoac null.
 *
 * Chinh thuc: CreateXmlProcessor.Check() (DLL dong 241-371), bo hai dieu kien HEIN_LOCK_TIME.
 * Thong tuyen: CheckCollinearXml() (dong 462-602).
 * Ham thuan tuy: khong sua DuLieuHoSo; thieu du lieu thi tra ly do, khong nem loi.
 */
class KiemDieuKien
{
    /** CheckDetailData: nhom loai dich vu thuoc/vat tu phai co lieu dung (dong 716) */
    const LOAI_CAN_LIEU_DUNG = [9, 10, 11, 12, 6, 21];

    /** HEIN_SERVICE_TYPE_ID khong thuc hien van bi loai khoi kiem ICD (dong 717) */
    const LOAI_KHONG_THUC_HIEN = 5;

    const KEY_KHONG_LIEU_DUNG = 'HIS.Desktop.Plugins.ExportXml.HeinServiceTypeCodeNoTutorial';

    /**
     * @param string $loaiBan CauHinhSinhXml3176::CHINH_THUC | THONG_TUYEN
     * @return string|null null = duoc sinh
     */
    public function lyDoLoai(DuLieuHoSo $d, $loaiBan, HisDanhMuc $dm)
    {
        if ($loaiBan === CauHinhSinhXml3176::CHINH_THUC) {
            return $this->kiemChinhThuc($d, $dm);
        }
        if ($loaiBan === CauHinhSinhXml3176::THONG_TUYEN) {
            return $this->kiemThongTuyen($d, $dm);
        }
        throw new \InvalidArgumentException('Loai ban khong hop le: ' . $loaiBan);
    }

    /** Check() dong 241-371 */
    private function kiemChinhThuc(DuLieuHoSo $d, HisDanhMuc $dm)
    {
        $hs = $d->hoSo;
        if ($hs === null) {
            return 'Không xác định được hồ sơ điều trị'; // 251-255
        }
        if (!$this->coDiaChi($hs)) {
            return 'Hồ sơ thiếu thông tin địa chỉ'; // 256-260
        }
        if ($this->trong($hs->tdl_patient_mps_national_code)) {
            return 'Hồ sơ thiếu thông tin quốc tịch'; // 261-265
        }
        if ($this->khongCo($hs->fee_lock_time)) {
            return 'Hồ sơ chưa có thời gian khóa viện phí'; // 266-270
        }
        if ($this->khongCo($hs->out_time)) {
            return 'Hồ sơ chưa có thời gian kết thúc điều trị'; // 271-275
        }
        if ((float) $hs->fee_lock_time < (float) $hs->out_time) {
            return 'Thời gian ra viện lớn hơn thời gian thanh toán'; // 276-280
        }
        // 281-300, dung chung voi thong tuyen
        if (($ly = $this->kiemKhoaKetQuaBenh($hs)) !== null) {
            return $ly;
        }
        if (($ly = $this->kiemDienDieuTri($d, $dm, $dienCuoi, $idBhyt)) !== null) {
            return $ly;
        }
        // 301-338 (da bo HEIN_LOCK_TIME 317-326)
        if ($dienCuoi->patient_type_id !== null && (int) $dienCuoi->patient_type_id === $idBhyt) {
            if (($ly = $this->kiemIcdBhyt($hs, $dm)) !== null) {
                return $ly;
            }
        }
        if (count($d->dichVu) === 0) {
            return 'Hồ sơ không có dịch vụ thanh toán'; // 339-343
        }
        // 344-349: loc chi de xet, khong sua $d->dichVu
        $dichVu = [];
        foreach ($d->dichVu as $dv) {
            if (($this->coGiaTri($dv->tdl_hein_service_type_id)
                    || ((int) $dv->tdl_service_type_id === 1 && (int) $dv->tdl_is_main_exam === 1))
                && (float) $dv->amount > 0 && (int) $dv->is_expend !== 1) {
                $dichVu[] = $dv;
            }
        }
        if (count($dichVu) === 0) {
            return 'Hồ sơ không có dịch vụ thanh toán';
        }
        // 350-358
        foreach ($d->soSinh as $be) {
            if ($this->trong($be->hein_card_number_tmp, false)) {
                return 'Thiếu thông tin thẻ tạm';
            }
        }

        return $this->kiemChiTiet($d, $dm, $dichVu); // 359-363
    }

    /** CheckCollinearXml() dong 462-602 */
    private function kiemThongTuyen(DuLieuHoSo $d, HisDanhMuc $dm)
    {
        $hs = $d->hoSo;
        if ($hs === null) {
            return 'Không xác định được hồ sơ điều trị'; // 472-476
        }
        if (!$this->coDiaChi($hs)) {
            return 'Hồ sơ thiếu thông tin địa chỉ'; // 477-481
        }
        if ($this->trong($hs->tdl_patient_mps_national_code)) {
            return 'Hồ sơ thiếu thông tin quốc tịch'; // 482-486
        }
        if ($this->khongCo($hs->out_time)) {
            return 'Hồ sơ chưa có thời gian kết thúc điều trị'; // 487-491
        }
        if (($ly = $this->kiemKhoaKetQuaBenh($hs)) !== null) { // 492-511
            return $ly;
        }
        if (($ly = $this->kiemDienDieuTri($d, $dm, $dienCuoi, $idBhyt)) !== null) { // 512-525
            return $ly;
        }
        if ((int) $dienCuoi->patient_type_id === $idBhyt) { // 526-539
            if (($ly = $this->kiemIcdBhyt($hs, $dm)) !== null) {
                return $ly;
            }
        }
        // 540-548
        foreach ($d->soSinh as $be) {
            if ($this->trong($be->hein_card_number_tmp, false)) {
                return 'Thiếu thông tin thẻ tạm của trẻ sơ sinh';
            }
        }
        if (count($d->dichVu) === 0) {
            return 'Hồ sơ không có dịch vụ thanh toán'; // 549-553
        }
        // 554-589
        $dichVuLoc = [];
        foreach ($d->dichVu as $dv) {
            if ($this->dongThongTuyenHopLe($dv, $idBhyt)) {
                $dichVuLoc[] = $dv;
            }
        }
        if (count($dichVuLoc) === 0) {
            return 'Hồ sơ không có dịch vụ thanh toán'; // 585-589
        }

        return $this->kiemChiTiet($d, $dm, $dichVuLoc); // 590-594
    }

    /** Dong 554-584: bo loc dich vu cua ban thong tuyen */
    private function dongThongTuyenHopLe($dv, $idBhyt)
    {
        $coLoai = $this->coGiaTri($dv->tdl_hein_service_type_id)
            || ((int) $dv->tdl_service_type_id === 1 && (int) $dv->tdl_is_main_exam === 1);
        if (!$coLoai || $this->trong($dv->tdl_hein_service_bhyt_name, false)) {
            return false;
        }
        if (!($this->duong($dv->vir_price) && (float) $dv->amount > 0)) {
            return false;
        }
        if ((int) $dv->patient_type_id !== $idBhyt) {
            return true;
        }
        if ($this->duong($dv->vir_total_hein_price)) {
            return true;
        }
        if ($this->coGiaTri($dv->vir_total_hein_price) && (float) $dv->vir_total_hein_price == 0.0) {
            return $this->duong($dv->other_source_price);
        }

        return false;
    }

    /** Dong 281-300 (Check) / 492-511 (CheckCollinearXml) */
    private function kiemKhoaKetQuaBenh($hs)
    {
        if ($this->khongCo($hs->end_department_id)) {
            return 'Hồ sơ điều trị không có khoa kết thúc điều trị';
        }
        if ($this->khongCo($hs->treatment_end_type_id)) {
            return 'Hồ sơ điều trị không có loại ra viện';
        }
        if ($this->khongCo($hs->treatment_result_id)) {
            return 'Hồ sơ điều trị không có kết quả';
        }
        if ($this->trong($hs->icd_code)) {
            return 'Hồ sơ không có thông tin bệnh';
        }

        return null;
    }

    /**
     * Dong 301-315 / 512-527: dien dieu tri moi nhat, noi tru thieu gio vao, id doi tuong BHYT.
     * Thieu danh muc doi tuong BHYT => tra ly do (DLL ném NullReference, Review Focus 1).
     */
    private function kiemDienDieuTri(DuLieuHoSo $d, HisDanhMuc $dm, &$dienCuoi, &$idBhyt)
    {
        if (count($d->dienDieuTri) === 0) {
            return 'Không xác định được thông tin diện điều trị';
        }
        $dienCuoi = reset($d->dienDieuTri);
        if ((string) $dienCuoi->hein_treatment_type_code === 'DT' && $this->khongCo($d->hoSo->clinical_in_time)) {
            return 'Hồ sơ điều trị nội trú nhưng không có thời gian vào điều trị';
        }
        $idBhyt = $dm->idDoiTuongBhyt();
        if ($idBhyt === null) {
            return 'Không xác định được đối tượng BHYT trong danh mục';
        }

        return null;
    }

    /** Dong 327-337 / 528-538: ma benh khong duoc BHYT thanh toan, lao tiem an thieu giay */
    private function kiemIcdBhyt($hs, HisDanhMuc $dm)
    {
        $icd = $dm->icd($hs->icd_code);
        if ($icd !== null && (int) $icd->do_not_use_hein === 1) {
            return 'Mã bệnh ' . $hs->icd_code . ' không được bảo hiểm y tế thanh toán';
        }
        // GlobalStore.IsLatentTuberculosis (dong 34-55)
        if ($icd !== null && (int) $icd->is_latent_tuberculosis === 1
            && ($this->trong($hs->tuberculosis_issued_org_name) || $this->khongCo($hs->tuberculosis_issued_date))) {
            return 'Thiếu thông tin cấp giấy xác nhận điều trị Lao';
        }

        return null;
    }

    /** checkHoso (dong 445-460) */
    private function coDiaChi($hs)
    {
        $thuongTru = !$this->trong($hs->tdl_patient_commune_code, false) && !$this->trong($hs->tdl_patient_province_code, false);
        $hoKhau = !$this->trong($hs->ht_commune_code, false) && !$this->trong($hs->ht_province_code, false);

        return $thuongTru || $hoKhau;
    }

    /**
     * CheckDetailData (dong 700-808) tren cac dong da loc. Tra thong bao dau tien hoac null.
     */
    private function kiemChiTiet(DuLieuHoSo $d, HisDanhMuc $dm, array $dichVu)
    {
        $hs = $d->hoSo;
        $khongLieuDung = [];
        $text = $dm->config(self::KEY_KHONG_LIEU_DUNG);
        if ($text !== null && trim($text) !== '') {
            $khongLieuDung = explode('|', $text);
        }

        // 719-736
        $thieuIcd = [];
        foreach ($dichVu as $dv) {
            $khongTh = (int) $dv->is_no_execute === 1;
            if ((!$khongTh || (int) $dv->tdl_hein_service_type_id !== self::LOAI_KHONG_THUC_HIEN)
                && $this->trong($dv->icd_code)) {
                if (!$this->trong($dv->tdl_service_req_code, false)) {
                    $thieuIcd[(string) $dv->tdl_service_req_code] = true;
                }
            }
        }
        if (count($thieuIcd) > 0) {
            return 'thiếu thông tin mã bệnh(Mã y lệnh: ' . implode(',', array_keys($thieuIcd)) . ')';
        }

        // 737-773
        $thucHien = [];
        foreach ($dichVu as $dv) {
            if ((int) $dv->is_no_execute !== 1) {
                $thucHien[] = $dv;
            }
        }
        $gioVao = (float) $hs->in_time;
        $gioKhoa = $this->khongCo($hs->fee_lock_time) ? INF : (float) $hs->fee_lock_time;
        $truocVao = [];
        $sauKhoa = [];
        foreach ($thucHien as $dv) {
            $gio = (float) $dv->tdl_intruction_time;
            if ($gio < $gioVao) {
                if (!$this->trong($dv->tdl_service_req_code, false)) {
                    $truocVao[(string) $dv->tdl_service_req_code] = true;
                }
            } elseif ($gio > $gioKhoa) {
                if (!$this->trong($dv->tdl_service_req_code, false)) {
                    $sauKhoa[(string) $dv->tdl_service_req_code] = true;
                }
            }
        }
        if (count($truocVao) > 0) {
            return 'thời gian chỉ định trước thời gian vào viện(Mã y lệnh: ' . implode(',', array_keys($truocVao)) . ')';
        }
        if (count($sauKhoa) > 0) {
            return 'thời gian chỉ định sau thời gian thanh toán(Mã y lệnh: ' . implode(',', array_keys($sauKhoa)) . ')';
        }

        // 774-796
        $thieuChungChi = [];
        $thieuLieu = [];
        foreach ($thucHien as $dv) {
            $loai = $this->coGiaTri($dv->tdl_hein_service_type_id) ? (int) $dv->tdl_hein_service_type_id : 0;
            if (in_array($loai, self::LOAI_CAN_LIEU_DUNG, true)) {
                if (!in_array($dv->active_ingr_bhyt_code, $khongLieuDung, true)
                    && !in_array($dv->tdl_hein_service_bhyt_code, $khongLieuDung, true)
                    && $this->trong($dv->tutorial)
                    && $loai !== 6 && $loai !== 21) {
                    $thieuLieu[(string) $dv->tdl_service_req_code] = true;
                }
                continue;
            }
            if ($this->trong($dv->execute_loginname) && $this->trong($dv->request_loginname)) {
                $thieuChungChi[(string) $dv->tdl_service_req_code] = true;
                continue;
            }
            if ($this->trong($this->maBacSi($dm, $dv->execute_loginname))
                && $this->trong($this->maBacSi($dm, $dv->request_loginname))) {
                $thieuChungChi[(string) $dv->tdl_service_req_code] = true;
            }
        }
        if (count($thieuChungChi) > 0) {
            return 'thiếu thông tin chứng chỉ hành nghề (Mã y lệnh: ' . implode(',', array_keys($thieuChungChi)) . ')';
        }
        if (count($thieuLieu) > 0) {
            return 'thiếu thông tin liều dùng(Mã y lệnh: ' . implode(',', array_keys($thieuLieu)) . ')';
        }

        return null;
    }

    /** GlobalStore.GetMaBacSi: DIPLOMA cua nhan vien; khong co => '' */
    private function maBacSi(HisDanhMuc $dm, $loginname)
    {
        if ($this->trong($loginname, false)) {
            return '';
        }
        $nv = $dm->nhanVien($loginname);

        return $nv === null ? '' : (string) $nv->diploma;
    }

    /** string.IsNullOrEmpty / IsNullOrWhiteSpace. */
    private function trong($v, $boKhoangTrang = true)
    {
        if ($v === null) {
            return true;
        }

        return ($boKhoangTrang ? trim((string) $v) : (string) $v) === '';
    }

    /** Nullable<long>.HasValue: Oracle tra null, SQLite co the tra chuoi rong. */
    private function coGiaTri($v)
    {
        return $v !== null && $v !== '';
    }

    private function khongCo($v)
    {
        return !$this->coGiaTri($v);
    }

    /** (v > 0) & v.HasValue */
    private function duong($v)
    {
        return $this->coGiaTri($v) && (float) $v > 0;
    }
}

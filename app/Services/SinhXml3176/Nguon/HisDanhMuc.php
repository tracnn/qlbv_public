<?php

namespace App\Services\SinhXml3176\Nguon;

use Illuminate\Support\Facades\DB;

/**
 * Danh muc HIS va HIS_CONFIG cho mot luot (chi doc). Nap mot lan moi luot, danh chi muc bang mang PHP.
 */
class HisDanhMuc
{
    const KEY_MA_DOI_TUONG_BHYT = 'MOS.HIS_PATIENT_TYPE.PATIENT_TYPE_CODE.BHYT';

    const COT_CAU_HINH = ['id', 'key', 'value', 'default_value'];
    /** XML1 65 MA_TTDV (social_insurance_number, diploma), XML2 32 / XML3 33-34 (diploma, identification_number) */
    const COT_NHAN_VIEN = ['id', 'loginname', 'tdl_username', 'diploma', 'social_insurance_number', 'identification_number'];
    /** XML1 31 MA_DOITUONG_KCB: ICD lao tiem an / COVID */
    const COT_ICD = ['id', 'icd_code', 'icd_name', 'is_latent_tuberculosis', 'is_covid', 'is_traditional', 'do_not_use_hein'];
    /** XML2 17 / XML3 18: so PATIENT_TYPE_CODE voi ma BHYT */
    const COT_DOI_TUONG = ['id', 'patient_type_code', 'patient_type_name'];
    /** XML1 31: LEVEL_CODE noi DKBD (HeinMediOrg) */
    const COT_NOI_KCB = ['id', 'medi_org_code', 'medi_org_name', 'level_code', 'province_code'];
    /** XML3 46 TAI_SU_DUNG: HIS_MATERIAL_TYPE(SERVICE_ID).IS_REUSABLE */
    const COT_LOAI_VAT_TU = ['id', 'service_id', 'is_reusable'];
    /** XML3 10 MA_XANG_DAU: V_HIS_SERVICE(ID).PETROLEUM_CODE - chi nap dich vu co ma */
    const COT_DICH_VU = ['id', 'petroleum_code'];
    /**
     * XML4 MA_CHI_SO/TEN_CHI_SO khoi B, C [AX]:668-669: V_HIS_SERVICE.SUIM_INDEX_CODE/NAME (HIS_SERVICE chi co
     * SUIM_INDEX_ID). Chi nap dich vu co suim_index_id: dich vu khong gan chi so co SUIM_INDEX_* null, nhu khong tim thay.
     */
    const COT_DICH_VU_CHI_SO = ['id', 'suim_index_id', 'suim_index_code', 'suim_index_name'];
    /** XML1 57 MA_KHOA (MA_KHOA_OPTION=1): BHYT_CODE cac khoa trong DEPARTMENT_IDS */
    const COT_KHOA = ['id', 'department_code', 'bhyt_code'];

    private $cauHinh = [];
    private $nhanVien = [];
    private $icd = [];
    private $doiTuong = [];
    private $doiTuongTheoMa = [];
    private $noiKcb = [];
    private $loaiVatTu = [];
    private $maXangDau = [];
    private $khoa = [];
    private $dichVuChiSo = [];

    private function __construct()
    {
    }

    /** Bang danh muc: ten => cot select + cot loc (de dung bang gia trong test). */
    public static function cacBang()
    {
        return [
            'his_config'        => ['cot' => self::COT_CAU_HINH, 'cot_loc' => ['is_active']],
            'his_employee'      => ['cot' => self::COT_NHAN_VIEN, 'cot_loc' => []],
            'his_icd'           => ['cot' => self::COT_ICD, 'cot_loc' => []],
            'his_patient_type'  => ['cot' => self::COT_DOI_TUONG, 'cot_loc' => []],
            'his_medi_org'      => ['cot' => self::COT_NOI_KCB, 'cot_loc' => []],
            'his_material_type' => ['cot' => self::COT_LOAI_VAT_TU, 'cot_loc' => []],
            'his_service'       => ['cot' => self::COT_DICH_VU, 'cot_loc' => []],
            'his_department'    => ['cot' => self::COT_KHOA, 'cot_loc' => []],
            'v_his_service'     => ['cot' => self::COT_DICH_VU_CHI_SO, 'cot_loc' => []],
        ];
    }

    /** @param string $ketNoi ten connection HIS */
    public static function napChoLuot($ketNoi)
    {
        $db = DB::connection($ketNoi);
        $dm = new self();

        // Nhieu dong is_active=1 cung key: lay dong id lon nhat (sap id tang, dong sau de len)
        foreach ($db->table('his_config')->select(self::COT_CAU_HINH)->where('is_active', 1)->orderBy('id')->get() as $d) {
            $gt = trim((string) $d->value);
            if ($gt === '') {
                $gt = trim((string) $d->default_value);
            }
            $dm->cauHinh[trim((string) $d->key)] = $gt;
        }

        $dm->nhanVien = self::theoKhoa($db->table('his_employee')->select(self::COT_NHAN_VIEN)->orderBy('id')->get(), 'loginname');
        $dm->icd = self::theoKhoa($db->table('his_icd')->select(self::COT_ICD)->orderBy('id')->get(), 'icd_code');
        $doiTuong = $db->table('his_patient_type')->select(self::COT_DOI_TUONG)->orderBy('id')->get();
        $dm->doiTuong = self::theoKhoa($doiTuong, 'id');
        $dm->doiTuongTheoMa = self::theoKhoa($doiTuong, 'patient_type_code');
        $dm->noiKcb = self::theoKhoa($db->table('his_medi_org')->select(self::COT_NOI_KCB)->orderBy('id')->get(), 'medi_org_code');
        $dm->loaiVatTu = self::theoKhoa($db->table('his_material_type')->select(self::COT_LOAI_VAT_TU)
            ->whereNotNull('service_id')->orderBy('id')->get(), 'service_id');
        foreach ($db->table('his_service')->select(self::COT_DICH_VU)->whereNotNull('petroleum_code')->get() as $d) {
            $dm->maXangDau[(string) (int) $d->id] = (string) $d->petroleum_code;
        }
        $dm->khoa = self::theoKhoa($db->table('his_department')->select(self::COT_KHOA)->orderBy('id')->get(), 'id');
        $dm->dichVuChiSo = self::theoKhoa($db->table('v_his_service')->select(self::COT_DICH_VU_CHI_SO)
            ->whereNotNull('suim_index_id')->orderBy('id')->get(), 'id');

        return $dm;
    }

    /** Gia tri HIS_CONFIG (value, rong thi default_value, da trim); key khong co => null. */
    public function config($key)
    {
        return array_key_exists($key, $this->cauHinh) ? $this->cauHinh[$key] : null;
    }

    public function nhanVien($loginname)
    {
        return $this->lay($this->nhanVien, $loginname);
    }

    public function icd($ma)
    {
        return $this->lay($this->icd, $ma);
    }

    public function doiTuong($id)
    {
        return $this->lay($this->doiTuong, (int) $id);
    }

    public function noiKcb($ma)
    {
        return $this->lay($this->noiKcb, $ma);
    }

    /** Loai vat tu theo SERVICE_ID cua dong dich vu. */
    public function loaiVatTu($serviceId)
    {
        return $this->lay($this->loaiVatTu, (int) $serviceId);
    }

    /** PETROLEUM_CODE cua dich vu; khong co => null. */
    public function maXangDau($serviceId)
    {
        $k = (string) (int) $serviceId;

        return isset($this->maXangDau[$k]) && $this->maXangDau[$k] !== '' ? $this->maXangDau[$k] : null;
    }

    public function khoa($id)
    {
        return $this->lay($this->khoa, (int) $id);
    }

    /**
     * Dong V_HIS_SERVICE (suim_index_code, suim_index_name) theo SERVICE_ID cua dong dich vu;
     * dich vu khong gan chi so hoac khong co => null.
     */
    public function dichVuDanhMuc($serviceId)
    {
        if ($serviceId === null || $serviceId === '') {
            return null;
        }

        return $this->lay($this->dichVuChiSo, (int) $serviceId);
    }

    /**
     * ID doi tuong BHYT: lay MA tu key MOS...PATIENT_TYPE_CODE.BHYT roi doi sang ID qua danh muc
     * (DLL lay ma lam ID - [AX]:586, 606). Khong co key hoac ma khong co trong danh muc => null.
     */
    public function idDoiTuongBhyt()
    {
        $ma = $this->config(self::KEY_MA_DOI_TUONG_BHYT);
        if ($ma === null || $ma === '') {
            return null;
        }
        $dt = $this->lay($this->doiTuongTheoMa, $ma);

        return $dt ? (int) $dt->id : null;
    }

    private function lay(array $mang, $khoa)
    {
        $k = trim((string) $khoa);

        return isset($mang[$k]) ? $mang[$k] : null;
    }

    // Co y dung hai quy tac trung khoa: theoKhoa giu dong DAU (id nho nhat), con cauHinh giu id LON nhat.
    private static function theoKhoa($dong, $cot)
    {
        $ra = [];
        foreach ($dong as $d) {
            $k = trim((string) $d->$cot);
            if ($k !== '' && !isset($ra[$k])) {
                $ra[$k] = $d;
            }
        }

        return $ra;
    }
}

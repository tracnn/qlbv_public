<?php

namespace Tests\Unit\SinhXml3176;

use App\Services\SinhXml3176\Bang\DoiTuongKcbXml1;
use App\Services\SinhXml3176\Bang\MaTtdv;
use App\Services\SinhXml3176\Bang\PtaChinh;
use App\Services\SinhXml3176\Bang\Xml13Dung;
use App\Services\SinhXml3176\Bang\Xml14Dung;
use App\Services\SinhXml3176\Bang\Xml1Dung;
use App\Services\SinhXml3176\DuLieuHoSo;
use App\Services\SinhXml3176\Nguon\HisDanhMuc;
use App\Services\SinhXml3176\Nguon\HisNapHoSo;
use Illuminate\Support\Facades\DB;
use Tests\Support\DungBangHisSinhXml3176Sqlite;
use Tests\TestCase;

class Xml13Xml14DungTest extends TestCase
{
    use DungBangHisSinhXml3176Sqlite;

    const TRUONG_XML13 = [
        'MA_LK', 'SO_HOSO', 'SO_CHUYENTUYEN', 'GIAY_CHUYEN_TUYEN', 'MA_CSKCB', 'MA_NOI_DI', 'MA_NOI_DEN', 'HO_TEN',
        'NGAY_SINH', 'GIOI_TINH', 'MA_QUOCTICH', 'MA_DANTOC', 'MA_NGHE_NGHIEP', 'DIA_CHI', 'MA_THE_BHYT', 'GT_THE_DEN',
        'NGAY_VAO', 'NGAY_VAO_NOI_TRU', 'NGAY_RA', 'DAU_HIEU_LS', 'CHAN_DOAN_RV', 'QT_BENHLY', 'TOMTAT_KQ',
        'PP_DIEUTRI', 'MA_BENH_CHINH', 'MA_BENH_KT', 'MA_BENH_YHCT', 'TEN_DICH_VU', 'TEN_THUOC', 'PP_DIEU_TRI',
        'MA_LOAI_RV', 'MA_LYDO_CT', 'HUONG_DIEU_TRI', 'PHUONGTIEN_VC', 'HOTEN_NGUOI_HT', 'CHUCDANH_NGUOI_HT',
        'MA_BAC_SI', 'MA_TTDV', 'DU_PHONG', 'MA_CSKCB_DI', 'MA_CSKCB_DEN', 'TINH_TRANG_CT',
    ];

    const TRUONG_XML14 = [
        'MA_LK', 'SO_GIAYHEN_KL', 'MA_CSKCB', 'HO_TEN', 'NGAY_SINH', 'GIOI_TINH', 'DIA_CHI', 'MA_THE_BHYT',
        'GT_THE_DEN', 'NGAY_VAO', 'NGAY_VAO_NOI_TRU', 'NGAY_RA', 'NGAY_HEN_KL', 'CHAN_DOAN_RV', 'MA_BENH_CHINH',
        'MA_BENH_KT', 'MA_BENH_YHCT', 'MA_DOITUONG_KCB', 'MA_BAC_SI', 'MA_TTDV', 'NGAY_CT', 'DU_PHONG',
    ];

    protected function setUp()
    {
        parent::setUp();
        $this->dungBangHis();
        $his = DB::connection('HISPro');
        $his->table('his_employee')->insert([
            ['id' => 1, 'loginname' => 'gd', 'diploma' => 'CCHN-GD', 'social_insurance_number' => 'BHXH-GD', 'tdl_username' => 'GD', 'title' => 'Giam doc'],
            ['id' => 2, 'loginname' => 'bs', 'diploma' => 'CCHN-BS', 'social_insurance_number' => 'BHXH-BS', 'tdl_username' => 'BS', 'title' => 'Bac si'],
            ['id' => 3, 'loginname' => 'kt', 'diploma' => 'CCHN-KT', 'social_insurance_number' => null, 'tdl_username' => 'KT', 'title' => 'Ky thuat vien'],
            ['id' => 4, 'loginname' => 'dd', 'diploma' => null, 'social_insurance_number' => null, 'tdl_username' => 'DD', 'title' => 'Dieu duong'],
        ]);
        $his->table('his_config')->insert(['id' => 1, 'key' => PtaChinh::KEY_BHYT, 'value' => 'BHYT', 'is_active' => 1]);
    }

    private function cauHinh(array $kv)
    {
        $id = 100;
        foreach ($kv as $k => $v) {
            DB::connection('HISPro')->table('his_config')->insert(['id' => $id++, 'key' => $k, 'value' => $v, 'is_active' => 1]);
        }
    }

    private function dm()
    {
        return HisDanhMuc::napChoLuot('HISPro');
    }

    private function hoSo(array $ghi = [])
    {
        return (object) array_merge(array_fill_keys(HisNapHoSo::COT_HO_SO, null), [
            'id' => 5, 'treatment_code' => 'HS5', 'treatment_end_type_id' => 2, 'tdl_patient_name' => 'Nguyen Van A',
            'tdl_patient_dob' => 19800102000000, 'tdl_patient_gender_id' => 1, 'in_time' => 20260101080000,
            'hein_medi_org_code' => '01929', 'hosp_subs_director_loginname' => 'gd',
        ], $ghi);
    }

    private function pta(array $ghi = [])
    {
        return (object) array_merge(array_fill_keys(HisNapHoSo::COT_DIEN_DIEU_TRI, null), [
            'id' => 1, 'treatment_id' => 5, 'log_time' => 20260101080000, 'patient_type_code' => 'BHYT',
        ], $ghi);
    }

    private function dv(array $ghi = [])
    {
        return (object) array_merge(array_fill_keys(HisNapHoSo::COT_DICH_VU, null), [
            'id' => 1, 'tdl_treatment_id' => 5, 'price' => 100, 'amount' => 1, 'is_no_execute' => 0,
            'tdl_hein_service_type_id' => 3, 'tdl_hein_service_bhyt_name' => 'Xet nghiem A',
        ], $ghi);
    }

    private function du(array $ghiHoSo = [], array $pta = [], array $dichVu = [])
    {
        $d = new DuLieuHoSo($this->hoSo($ghiHoSo));
        $d->dienDieuTri = $pta;
        $d->dichVu = $dichVu;

        return $d;
    }

    private function x13(array $ghiHoSo = [], array $pta = [], array $dichVu = [])
    {
        $ra = (new Xml13Dung())->dung($this->du($ghiHoSo, $pta, $dichVu), $this->dm(), []);
        $this->assertCount(1, $ra);

        return $ra[0];
    }

    private function x14(array $ghiHoSo = [], array $pta = [])
    {
        $ra = (new Xml14Dung())->dung($this->du(array_merge(['treatment_end_type_id' => 3], $ghiHoSo), $pta), $this->dm(), []);
        $this->assertCount(1, $ra);

        return $ra[0];
    }

    // ---------------- XML13

    public function test_xml13_chi_khi_chuyen_vien()
    {
        $x = new Xml13Dung();
        $this->assertSame('XML13', $x->loai());
        $this->assertTrue($x->coTrongHoSo($this->du(['treatment_end_type_id' => 2])));
        foreach ([1, 3, 7, 8, null] as $loai) {
            $d = $this->du(['treatment_end_type_id' => $loai]);
            $this->assertFalse($x->coTrongHoSo($d), 'loai ' . var_export($loai, true));
            $this->assertSame([], $x->dung($d, $this->dm(), []));
        }
    }

    public function test_xml13_3176_bo_nam_truong_va_them_cskcb()
    {
        $r = $this->x13([
            'transfer_in_medi_org_code' => '01001', 'medi_org_code' => '01002', 'treatment_method' => 'Dieu tri A',
            'used_medicine' => 'Thuoc B',
        ], [], [$this->dv(), $this->dv(['id' => 2, 'tdl_hein_service_type_id' => 9, 'tdl_hein_service_bhyt_name' => 'Thuoc C'])]);
        // DLL gan null (module xuat rong)
        foreach (['MA_NOI_DI', 'MA_NOI_DEN', 'TEN_DICH_VU', 'TEN_THUOC', 'PP_DIEU_TRI'] as $k) {
            $this->assertSame('', $r[$k], $k);
        }
        $this->assertSame('01001', $r['MA_CSKCB_DI']);
        $this->assertSame('01002', $r['MA_CSKCB_DEN']);
        $this->assertSame('Dieu tri A', $r['TINH_TRANG_CT']);
        $this->assertSame('Dieu tri A', $r['PP_DIEUTRI']);
        $r = $this->x13(['transfer_in_medi_org_code' => null, 'medi_org_code' => '', 'treatment_method' => null]);
        $this->assertSame('', $r['MA_CSKCB_DI']);
        $this->assertSame('', $r['MA_CSKCB_DEN']);
        $this->assertSame('', $r['TINH_TRANG_CT']);
        $this->assertSame('', $r['PP_DIEUTRI']);
    }

    public function test_xml13_khong_dich_vu_van_sinh()
    {
        // Review Focus 3: DLL ne ArgumentNullException khi list2 rong va mat ca bang
        $r = $this->x13([], [], []);
        $this->assertSame('HS5', $r['MA_LK']);
        $this->assertSame('', $r['TEN_DICH_VU']);
        $this->assertSame('', $r['TEN_THUOC']);
        // dich vu co dong thieu loai BHYT (DLL .Value nem loi) cung khong lam vo
        $r = $this->x13([], [], [$this->dv(['tdl_hein_service_type_id' => null])]);
        $this->assertSame('', $r['TEN_DICH_VU']);
        $this->assertSame('HS5', $r['SO_HOSO']);
    }

    public function test_xml13_out_code_ngan_khong_vo()
    {
        // Review Focus 5: OUT_CODE < 6 ky tu hoac 6 ky tu dau khong phai so, DLL int.Parse(Substring) nem loi
        $this->cauHinh(['HIS.QD_130_BYT.GIAY_CHUYEN_TUYEN_FORMAT' => '<#OUT_CODE_NUM_000000;>/<#OUT_YEAR;>']);
        $r = $this->x13(['out_code' => '123', 'out_time' => 20260105101500]);
        $this->assertSame('<#OUT_CODE_NUM_000000;>/2026', $r['GIAY_CHUYEN_TUYEN']);
        $r = $this->x13(['out_code' => 'AB12CD34', 'out_time' => 20260105101500]);
        $this->assertSame('<#OUT_CODE_NUM_000000;>/2026', $r['GIAY_CHUYEN_TUYEN']);
        $r = $this->x13(['out_code' => null, 'out_time' => 20260105101500]);
        $this->assertSame('<#OUT_CODE_NUM_000000;>/2026', $r['GIAY_CHUYEN_TUYEN']);
    }

    public function test_xml13_so_chuyen_tuyen_va_giay_chuyen_tuyen()
    {
        $r = $this->x13(['out_code' => '000042xx']);
        $this->assertSame('.', $r['SO_CHUYENTUYEN']);
        $this->assertSame('000042xx', $r['GIAY_CHUYEN_TUYEN']);

        $this->cauHinh([
            'HIS.QD_130_BYT.SO_CHUYENTUYEN_FORMAT' => '<#OUT_CODE;>/<#OUT_MONTH;>/<#OUT_YEAR;>/<#OUT_YEAR_SHORT;>',
            'HIS.QD_130_BYT.GIAY_CHUYEN_TUYEN_FORMAT' => 'CT<#OUT_CODE_NUM_0000;>-<#OUT_YEAR_SHORT;>',
        ]);
        $r = $this->x13(['out_code' => '000042xx', 'out_time' => 20260705101500]);
        $this->assertSame('000042xx/07/2026/26', $r['SO_CHUYENTUYEN']);
        // 6 ky tu dau "000042" -> 42, dem 4 ky tu "0000" -> 0042
        $this->assertSame('CT0042-26', $r['GIAY_CHUYEN_TUYEN']);
        // so dai hon do rong thi giu nguyen
        $r = $this->x13(['out_code' => '123456', 'out_time' => 20260705101500]);
        $this->assertSame('CT123456-26', $r['GIAY_CHUYEN_TUYEN']);
        // khong co OUT_TIME: phan thoi gian rong
        $r = $this->x13(['out_code' => '000042', 'out_time' => null]);
        $this->assertSame('000042///', $r['SO_CHUYENTUYEN']);
        $this->assertSame('CT0042-', $r['GIAY_CHUYEN_TUYEN']);
    }

    public function test_xml13_the_bhyt_khop_vi_tri()
    {
        $r = $this->x13([], [
            $this->pta(['id' => 1, 'hein_card_number' => 'A111', 'hein_medi_org_code' => '01', 'hein_card_from_time' => 20250101000000, 'hein_card_to_time' => 20251231000000]),
            // the thu hai thieu han: DLL loc hai danh sach rieng nen lech vi tri
            $this->pta(['id' => 2, 'hein_card_number' => 'B222', 'hein_medi_org_code' => '02', 'hein_card_from_time' => 20260101000000, 'hein_card_to_time' => null]),
            // khong co so the: khong chiem vi tri du co han
            $this->pta(['id' => 3, 'hein_card_number' => null, 'hein_card_from_time' => 20260301000000, 'hein_card_to_time' => 20261231000000]),
            $this->pta(['id' => 4, 'hein_card_number' => 'C333', 'hein_medi_org_code' => '03', 'hein_card_from_time' => 20260401000000, 'hein_card_to_time' => 20271231000000]),
        ]);
        $this->assertSame('A111;B222;C333', $r['MA_THE_BHYT']);
        $this->assertSame('20251231;;20271231', $r['GT_THE_DEN']);
        // mot the, khong han => rong
        $r = $this->x13([], [$this->pta(['hein_card_number' => 'A111'])]);
        $this->assertSame('A111', $r['MA_THE_BHYT']);
        $this->assertSame('', $r['GT_THE_DEN']);
        // khong PTA
        $r = $this->x13();
        $this->assertSame('', $r['MA_THE_BHYT']);
        $this->assertSame('', $r['GT_THE_DEN']);
    }

    public function test_xml13_the_bhyt_sap_xep_theo_key()
    {
        $ptas = [
            $this->pta(['id' => 1, 'log_time' => 20260101000000, 'hein_card_number' => 'A', 'hein_card_from_time' => 20260201000000, 'hein_card_to_time' => 20260301000000]),
            $this->pta(['id' => 2, 'log_time' => 20260102000000, 'hein_card_number' => 'B', 'hein_card_from_time' => 20250101000000, 'hein_card_to_time' => 20250201000000]),
            // trung ca bon khoa nhom => lay ban dau, khong lap
            $this->pta(['id' => 3, 'log_time' => 20260103000000, 'hein_card_number' => 'B', 'hein_card_from_time' => 20250101000000, 'hein_card_to_time' => 20250201000000]),
        ];
        $r = $this->x13([], $ptas);
        $this->assertSame('B;A', $r['MA_THE_BHYT']);
        $this->assertSame('20250201;20260301', $r['GT_THE_DEN']);
        $ptas[0]->log_time = 20260105000000; // A moi nhat
        $this->cauHinh(['HIS.QD_130_BYT.SORT_MA_THE_BHYT' => '1']);
        $r = $this->x13([], $ptas);
        $this->assertSame('A;B', $r['MA_THE_BHYT']);
    }

    public function test_xml13_cac_truong_hanh_chinh_va_ly_do()
    {
        $r = $this->x13([
            'tdl_patient_is_has_not_day_dob' => 1, 'tdl_patient_gender_id' => 2, 'tdl_patient_mps_national_code' => 'VN',
            'ethnic_code' => '01', 'tdl_patient_career_code' => '12345', 'tdl_patient_address' => 'Dia chi',
            'in_time' => 20260101081530, 'clinical_in_time' => 20260102000000, 'out_time' => 20260105171500,
            'icd_code' => 'A01', 'icd_name' => 'Ten benh', 'icd_text' => 'Ghi chu', 'icd_sub_code' => 'B1;B2;;B3',
            'tran_pati_reason_id' => 2, 'treatment_end_type_id' => 2, 'treatment_direction' => 'Huong', 'transport_vehicle' => 'Xe cuu thuong',
            'transporter' => 'Nguoi A', 'clinical_signs' => 'Dau hieu', 'clinical_note' => 'Ghi chu LS', 'subclinical_result' => 'KQ CLS',
        ]);
        $this->assertSame(self::TRUONG_XML13, array_keys($r));
        $this->assertSame('HS5', $r['MA_LK']);
        $this->assertSame('HS5', $r['SO_HOSO']);
        $this->assertSame('01929', $r['MA_CSKCB']);
        $this->assertSame('Nguyen Van A', $r['HO_TEN']);
        $this->assertSame('198000000000', $r['NGAY_SINH']);
        $this->assertSame('1', $r['GIOI_TINH']);
        $this->assertSame('VN', $r['MA_QUOCTICH']);
        $this->assertSame('01', $r['MA_DANTOC']);
        $this->assertSame('12', $r['MA_NGHE_NGHIEP']);
        $this->assertSame('Dia chi', $r['DIA_CHI']);
        $this->assertSame('202601010815', $r['NGAY_VAO']);
        $this->assertSame('202601020000', $r['NGAY_VAO_NOI_TRU']);
        $this->assertSame('202601051715', $r['NGAY_RA']);
        $this->assertSame('Dau hieu', $r['DAU_HIEU_LS']);
        $this->assertSame('Ghi chu LS', $r['QT_BENHLY']);
        $this->assertSame('KQ CLS', $r['TOMTAT_KQ']);
        $this->assertSame('A01', $r['MA_BENH_CHINH']);
        $this->assertSame('B1;B2;B3', $r['MA_BENH_KT']);
        // loai ra vien chuyen tuyen + ly do 2 => "5"
        $this->assertSame('5', $r['MA_LOAI_RV']);
        $this->assertSame('2', $r['MA_LYDO_CT']);
        $this->assertSame('Huong', $r['HUONG_DIEU_TRI']);
        $this->assertSame('Xe cuu thuong', $r['PHUONGTIEN_VC']);
        $this->assertSame('Nguoi A', $r['HOTEN_NGUOI_HT']);
        $this->assertSame('', $r['DU_PHONG']);
        $r = $this->x13(['tran_pati_reason_id' => 1]);
        $this->assertSame('2', $r['MA_LOAI_RV']);
        $r = $this->x13(['tran_pati_reason_id' => null, 'tdl_patient_gender_id' => 3, 'tdl_patient_career_code' => '7']);
        $this->assertSame('1', $r['MA_LOAI_RV']);
        $this->assertSame('', $r['MA_LYDO_CT']);
        $this->assertSame('3', $r['GIOI_TINH']);
        $this->assertSame('7', $r['MA_NGHE_NGHIEP']);
        $this->assertSame('', $r['NGAY_VAO_NOI_TRU']);
        $this->assertSame('', $r['NGAY_RA']);
        // dau hieu LS rong => ghi chu LS (??)
        $r = $this->x13(['clinical_signs' => null, 'clinical_note' => 'Ghi chu LS']);
        $this->assertSame('Ghi chu LS', $r['DAU_HIEU_LS']);
    }

    public function test_xml13_chan_doan_rv_khong_thua_cham_phay()
    {
        // Sua loi DLL [AX]:1293 giong XML7: ICD_TEXT rong thi khong them ';' thua
        $this->assertSame('Ten benh', $this->x13(['icd_name' => 'Ten benh', 'icd_text' => null])['CHAN_DOAN_RV']);
        $this->assertSame('Ten benh;Them', $this->x13(['icd_name' => 'Ten benh', 'icd_text' => 'Them'])['CHAN_DOAN_RV']);
        $this->assertSame('', $this->x13(['icd_name' => null, 'icd_text' => null])['CHAN_DOAN_RV']);
    }

    public function test_xml13_ma_benh_yhct_loai_ma_trung_icd_chinh_khong_gioi_han()
    {
        $r = $this->x13([
            'icd_code' => 'A01', 'traditional_icd_code' => 'Y1;A01;Y2', 'traditional_icd_sub_code' => 'Y2;Y3',
        ]);
        $this->assertSame('Y1;Y2;Y3', $r['MA_BENH_YHCT']);
        $ma = [];
        for ($i = 1; $i <= 15; $i++) {
            $ma[] = 'Y' . $i;
        }
        $this->assertSame(implode(';', $ma), $this->x13(['traditional_icd_code' => implode(';', $ma)])['MA_BENH_YHCT']);
        // toi da 12 ma benh kem theo
        $kt = [];
        for ($i = 1; $i <= 15; $i++) {
            $kt[] = 'K' . $i;
        }
        $this->assertSame(implode(';', array_slice($kt, 0, 12)), $this->x13(['icd_sub_code' => implode(';', $kt)])['MA_BENH_KT']);
    }

    public function test_xml13_tomtat_kq_cat_khong_dut_giua_ky_tu()
    {
        // Sua loi DLL [AX]:1290: cat 4000 byte giua ky tu UTF-8 nhieu byte sinh ky tu thay the
        $dai = str_repeat('ệ', 1400); // 1400 ky tu, 4200 byte, NFKD 4200 > 2000
        $r = $this->x13(['subclinical_result' => $dai]);
        $this->assertSame(str_repeat('ệ', 1333), $r['TOMTAT_KQ']);
        $this->assertTrue(mb_check_encoding($r['TOMTAT_KQ'], 'UTF-8'));
        // chuoi ngan khong cat
        $this->assertSame(str_repeat('a', 2001), $this->x13(['subclinical_result' => str_repeat('a', 2001)])['TOMTAT_KQ']);
        // NFKD > 2000 nhung chua toi 4000 byte: giu nguyen
        $this->assertSame(str_repeat('ệ', 900), $this->x13(['subclinical_result' => str_repeat('ệ', 900)])['TOMTAT_KQ']);
        // it ky tu nhung nhieu byte, NFKD <= 2000: khong cat du > 4000 byte (giu nhu DLL)
        $x = str_repeat('日', 1400); // 4200 byte, 1400 ky tu
        $this->assertSame($x, $this->x13(['subclinical_result' => $x])['TOMTAT_KQ']);
    }

    public function test_xml13_nguoi_ky_va_nguoi_ho_tong()
    {
        $r = $this->x13(['doctor_loginname' => 'bs', 'end_loginname' => 'kt', 'transporter_loginnames' => 'kt;dd;khongco;kt;bs']);
        $this->assertSame('CCHN-BS', $r['MA_BAC_SI']);
        // theo thu tu danh sach nhan vien (id), moi nguoi mot lan, bo login khong co
        $this->assertSame('Bac si;Ky thuat vien;Dieu duong', $r['CHUCDANH_NGUOI_HT']);
        // DOCTOR_LOGINNAME rong => END_LOGINNAME
        $this->assertSame('CCHN-KT', $this->x13(['doctor_loginname' => null, 'end_loginname' => 'kt'])['MA_BAC_SI']);
        // co DOCTOR_LOGINNAME nhung khong tim thay: khong lui ve END_LOGINNAME (giu DLL)
        $this->assertSame('', $this->x13(['doctor_loginname' => 'khongco', 'end_loginname' => 'kt'])['MA_BAC_SI']);
        $this->assertSame('', $this->x13(['doctor_loginname' => null, 'end_loginname' => null])['MA_BAC_SI']);
        $this->assertSame('', $this->x13(['transporter_loginnames' => null])['CHUCDANH_NGUOI_HT']);
        $this->assertSame('', $this->x13(['transporter_loginnames' => 'khongco'])['CHUCDANH_NGUOI_HT']);
    }

    public function test_xml13_ma_ttdv_chuoi_con_nhu_dll()
    {
        $hs = ['representative_hein_code' => 'REP'];
        $this->assertSame('CCHN-GD', $this->x13($hs)['MA_TTDV']);
        $this->cauHinh([MaTtdv::KEY => '113']);
        // Contains("13"): "113" khop => ma BHXH thu truong
        $this->assertSame('BHXH-GD', $this->x13($hs)['MA_TTDV']);
    }

    public function test_xml13_ma_ttdv_key_khong_chua_13()
    {
        $this->cauHinh([MaTtdv::KEY => '1,7']);
        $this->assertSame('REP', $this->x13(['representative_hein_code' => 'REP'])['MA_TTDV']);
    }

    public function test_xml13_thu_tu_khoa_dung_chi_tieu()
    {
        $this->assertSame(self::TRUONG_XML13, array_keys($this->x13()));
        $this->assertCount(42, self::TRUONG_XML13);
    }

    // ---------------- XML14

    public function test_xml14_chi_khi_end_type_3()
    {
        $x = new Xml14Dung();
        $this->assertSame('XML14', $x->loai());
        $this->assertTrue($x->coTrongHoSo($this->du(['treatment_end_type_id' => 3])));
        foreach ([1, 2, 7, null] as $loai) {
            $d = $this->du(['treatment_end_type_id' => $loai]);
            $this->assertFalse($x->coTrongHoSo($d), 'loai ' . var_export($loai, true));
            $this->assertSame([], $x->dung($d, $this->dm(), []));
        }
    }

    public function test_xml14_ngay_hen_kl()
    {
        // 3176: 8 ky tu (yyyyMMdd)
        $r = $this->x14(['appointment_time' => 20260215083000]);
        $this->assertSame('20260215', $r['NGAY_HEN_KL']);
        $this->assertSame('', $this->x14(['appointment_time' => null])['NGAY_HEN_KL']);
    }

    public function test_xml14_ma_benh_yhct_toi_da_13()
    {
        $ma = [];
        for ($i = 1; $i <= 16; $i++) {
            $ma[] = 'Y' . $i;
        }
        $r = $this->x14(['icd_code' => 'Y2', 'traditional_icd_code' => implode(';', $ma), 'traditional_icd_sub_code' => 'Y1;Z9']);
        $mong = array_values(array_diff($ma, ['Y2']));
        $mong[] = 'Z9';
        $this->assertSame(implode(';', array_slice($mong, 0, 13)), $r['MA_BENH_YHCT']);
        $this->assertCount(13, explode(';', $r['MA_BENH_YHCT']));
    }

    public function test_xml14_ma_doituong_kcb_dung_lai_logic_xml1()
    {
        // co HEIN_PATIENT_TYPE_CODE thi lay nguyen
        $pta = [$this->pta(['hein_card_number' => 'DN4010000000001', 'right_route_code' => 'DT', 'right_route_type_code' => 'CC'])];
        $this->assertSame('1.9', $this->x14(['hein_patient_type_code' => '1.9'], $pta)['MA_DOITUONG_KCB']);
        // tu suy: ket qua trung DoiTuongKcbXml1 voi cung ho so, PTA chinh va ma the
        $hs = $this->hoSo(['treatment_end_type_id' => 3, 'hein_patient_type_code' => null]);
        $this->assertSame('2', DoiTuongKcbXml1::suy($hs, $pta[0], 'DN4010000000001', $this->dm()));
        $this->assertSame('2', $this->x14([], $pta)['MA_DOITUONG_KCB']);
        // GT -> 1.3
        $pta = [$this->pta(['hein_card_number' => 'DN4010000000001', 'right_route_code' => 'DT', 'right_route_type_code' => 'GT'])];
        $this->assertSame('1.3', $this->x14([], $pta)['MA_DOITUONG_KCB']);
        // trai tuyen
        $pta = [$this->pta(['hein_card_number' => 'DN4010000000001', 'right_route_code' => 'TT', 'live_area_code' => 'K2'])];
        $this->assertSame('3.6', $this->x14([], $pta)['MA_DOITUONG_KCB']);
        // khong co so the => 9
        $pta = [$this->pta(['hein_card_number' => null, 'right_route_code' => 'DT', 'right_route_type_code' => 'CC'])];
        $this->assertSame('9', $this->x14([], $pta)['MA_DOITUONG_KCB']);
        // khong co PTA nao => rong (DLL .First() nem loi va mat bang)
        $this->assertSame('', $this->x14([], [])['MA_DOITUONG_KCB']);
        // PTA cua ho so khac khong tinh la PTA chinh (DLL loc TREATMENT_ID == ID ho so)
        $pta = [$this->pta(['treatment_id' => 99, 'hein_card_number' => 'DN4010000000001', 'right_route_code' => 'DT', 'right_route_type_code' => 'CC'])];
        $this->assertSame('', $this->x14([], $pta)['MA_DOITUONG_KCB']);
    }

    public function test_xml14_the_bhyt_khop_vi_tri()
    {
        $r = $this->x14([], [
            $this->pta(['id' => 1, 'hein_card_number' => 'A111', 'hein_card_from_time' => 20250101000000, 'hein_card_to_time' => 20251231000000]),
            $this->pta(['id' => 2, 'hein_card_number' => 'B222', 'hein_card_from_time' => 20260101000000, 'hein_card_to_time' => null]),
            $this->pta(['id' => 3, 'hein_card_number' => 'C333', 'hein_card_from_time' => 20260401000000, 'hein_card_to_time' => 20271231000000]),
        ]);
        $this->assertSame('A111;B222;C333', $r['MA_THE_BHYT']);
        $this->assertSame('20251231;;20271231', $r['GT_THE_DEN']);
    }

    public function test_xml14_cac_truong()
    {
        $r = $this->x14([
            'end_code' => 'KL01', 'tdl_patient_gender_id' => 2, 'tdl_patient_address' => 'Dia chi',
            'in_time' => 20260101081530, 'clinical_in_time' => 20260102000000, 'out_time' => 20260105171500,
            'icd_code' => 'A01', 'icd_name' => 'Ten benh', 'icd_text' => null, 'icd_sub_code' => 'B1;B2',
            'doctor_loginname' => 'bs', 'out_date' => 20260105000000, 'hein_patient_type_code' => '1.1',
        ]);
        $this->assertSame(self::TRUONG_XML14, array_keys($r));
        $this->assertSame('HS5', $r['MA_LK']);
        $this->assertSame('KL01', $r['SO_GIAYHEN_KL']);
        $this->assertSame('01929', $r['MA_CSKCB']);
        $this->assertSame('Nguyen Van A', $r['HO_TEN']);
        $this->assertSame('198001020000', $r['NGAY_SINH']);
        $this->assertSame('1', $r['GIOI_TINH']);
        $this->assertSame('Dia chi', $r['DIA_CHI']);
        $this->assertSame('202601010815', $r['NGAY_VAO']);
        $this->assertSame('202601020000', $r['NGAY_VAO_NOI_TRU']);
        $this->assertSame('202601051715', $r['NGAY_RA']);
        $this->assertSame('Ten benh', $r['CHAN_DOAN_RV']);
        $this->assertSame('A01', $r['MA_BENH_CHINH']);
        $this->assertSame('B1;B2', $r['MA_BENH_KT']);
        $this->assertSame('1.1', $r['MA_DOITUONG_KCB']);
        $this->assertSame('CCHN-BS', $r['MA_BAC_SI']);
        $this->assertSame('CCHN-GD', $r['MA_TTDV']);
        $this->assertSame('20260105', $r['NGAY_CT']);
        $this->assertSame('', $r['DU_PHONG']);
        // MA_BAC_SI: khong co DOCTOR_LOGINNAME thi END_LOGINNAME
        $this->assertSame('CCHN-KT', $this->x14(['end_loginname' => 'kt'])['MA_BAC_SI']);
        $this->assertSame('', $this->x14([])['MA_BAC_SI']);
    }

    public function test_xml14_ngay_vao_noi_tru_theo_key()
    {
        $hs = ['clinical_in_time' => 20260102000000, 'tdl_treatment_type_id' => 2];
        $this->assertSame('202601020000', $this->x14($hs)['NGAY_VAO_NOI_TRU']);
        $this->cauHinh([Xml1Dung::KEY_NGAY_VAO_NT => '1']);
        $this->assertSame('', $this->x14($hs)['NGAY_VAO_NOI_TRU']);
        // loai dieu tri khac 2 giu nguyen
        $this->assertSame('202601020000', $this->x14(['clinical_in_time' => 20260102000000, 'tdl_treatment_type_id' => 3])['NGAY_VAO_NOI_TRU']);
    }

    public function test_xml14_ma_ttdv_chia_se_helper()
    {
        $this->cauHinh([MaTtdv::KEY => '14']);
        $this->assertSame('BHXH-GD', $this->x14(['representative_hein_code' => 'REP'])['MA_TTDV']);
    }

    public function test_xml14_thu_tu_khoa_dung_chi_tieu()
    {
        $this->assertSame(self::TRUONG_XML14, array_keys($this->x14()));
        $this->assertCount(22, self::TRUONG_XML14);
    }
}

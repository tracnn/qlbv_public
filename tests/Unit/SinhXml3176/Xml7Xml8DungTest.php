<?php

namespace Tests\Unit\SinhXml3176;

use App\Services\SinhXml3176\Bang\MaTtdv;
use App\Services\SinhXml3176\Bang\PtaChinh;
use App\Services\SinhXml3176\Bang\Xml7Dung;
use App\Services\SinhXml3176\Bang\Xml8Dung;
use App\Services\SinhXml3176\DuLieuHoSo;
use App\Services\SinhXml3176\Nguon\HisDanhMuc;
use App\Services\SinhXml3176\Nguon\HisNapHoSo;
use Illuminate\Support\Facades\DB;
use Tests\Support\DungBangHisSinhXml3176Sqlite;
use Tests\TestCase;

class Xml7Xml8DungTest extends TestCase
{
    use DungBangHisSinhXml3176Sqlite;

    const TRUONG_XML7 = [
        'MA_LK', 'SO_LUU_TRU', 'MA_YTE', 'MA_KHOA_RV', 'NGAY_VAO', 'NGAY_RA', 'MA_DINH_CHI_THAI', 'NGUYENNHAN_DINHCHI',
        'THOIGIAN_DINHCHI', 'TUOI_THAI', 'CHAN_DOAN_RV', 'PP_DIEUTRI', 'GHI_CHU', 'MA_TTDV', 'MA_BS', 'TEN_BS', 'NGAY_CT',
        'MA_CHA', 'MA_ME', 'MA_THE_TAM', 'HO_TEN_CHA', 'HO_TEN_ME', 'SO_NGAY_NGHI', 'NGOAITRU_TUNGAY', 'NGOAITRU_DENNGAY',
        'DU_PHONG',
    ];

    const TRUONG_XML8 = [
        'MA_LK', 'MA_LOAI_KCB', 'HO_TEN_CHA', 'HO_TEN_ME', 'NGUOI_GIAM_HO', 'DON_VI', 'NGAY_VAO', 'NGAY_RA',
        'CHAN_DOAN_VAO', 'CHAN_DOAN_RV', 'QT_BENHLY', 'TOMTAT_KQ', 'PP_DIEUTRI', 'NGAY_SINHCON', 'NGAY_CONCHET',
        'SO_CONCHET', 'KET_QUA_DTRI', 'GHI_CHU', 'MA_TTDV', 'NGAY_CT', 'MA_THE_TAM', 'DU_PHONG',
    ];

    protected function setUp()
    {
        parent::setUp();
        $this->dungBangHis();
        $his = DB::connection('HISPro');
        $his->table('his_employee')->insert([
            ['id' => 1, 'loginname' => 'gd', 'diploma' => 'CCHN-GD', 'social_insurance_number' => 'BHXH-GD', 'tdl_username' => 'GD'],
            ['id' => 2, 'loginname' => 'tk', 'diploma' => 'CCHN-TK', 'social_insurance_number' => 'BHXH-TK', 'tdl_username' => 'Truong khoa'],
            ['id' => 3, 'loginname' => 'pk', 'diploma' => 'CCHN-PK', 'social_insurance_number' => 'BHXH-PK', 'tdl_username' => 'Pho khoa'],
            ['id' => 4, 'loginname' => 'bs', 'diploma' => 'CCHN-BS', 'social_insurance_number' => 'BHXH-BS', 'tdl_username' => 'Bac si'],
        ]);
        $his->table('his_config')->insert(['id' => 1, 'key' => PtaChinh::KEY_BHYT, 'value' => 'BHYT', 'is_active' => 1]);
    }

    private function dm()
    {
        return HisDanhMuc::napChoLuot('HISPro');
    }

    private function hoSo(array $ghi = [])
    {
        return (object) array_merge(array_fill_keys(HisNapHoSo::COT_HO_SO, null), [
            'id' => 5, 'treatment_code' => 'HS5', 'tdl_patient_code' => 'BN5', 'tdl_treatment_type_id' => 3,
            'in_time' => 20260101080000, 'out_time' => 20260105100000, 'out_date' => 20260105000000,
            'clinical_in_time' => 20260101090000,
            'end_department_bhyt_code' => 'K01', 'icd_name' => 'Viem phoi', 'icd_text' => 'ghi them',
            'hosp_subs_director_loginname' => 'gd', 'tdl_patient_dob' => 19800102000000,
        ], $ghi);
    }

    private function pta(array $ghi = [])
    {
        return (object) array_merge(array_fill_keys(HisNapHoSo::COT_DIEN_DIEU_TRI, null), [
            'id' => 1, 'treatment_id' => 5, 'log_time' => 20260101080000, 'patient_type_code' => 'BHYT',
        ], $ghi);
    }

    private function du(array $ghiHoSo = [], array $pta = [], array $be = [], array $dichVu = [])
    {
        $d = new DuLieuHoSo($this->hoSo($ghiHoSo));
        $d->dienDieuTri = $pta;
        $d->soSinh = $be;
        $d->dichVu = $dichVu;

        return $d;
    }

    private function x7(array $ghiHoSo = [], array $pta = [])
    {
        $ra = (new Xml7Dung())->dung($this->du($ghiHoSo, $pta), $this->dm(), []);
        $this->assertCount(1, $ra);

        return $ra[0];
    }

    private function x8(array $ghiHoSo = [], array $pta = [], array $be = [], array $dichVu = [])
    {
        $ra = (new Xml8Dung())->dung($this->du($ghiHoSo, $pta, $be, $dichVu), $this->dm(), []);
        $this->assertCount(1, $ra);

        return $ra[0];
    }

    public function test_xml7_chi_ngoai_noi_tru_ban_ngay()
    {
        $x = new Xml7Dung();
        $this->assertSame('XML7', $x->loai());
        foreach ([2, 3, 4] as $loai) {
            $d = $this->du(['tdl_treatment_type_id' => $loai]);
            $this->assertTrue($x->coTrongHoSo($d), "loai $loai");
            $this->assertCount(1, $x->dung($d, $this->dm(), []));
        }
        // ngoai tru man tinh van sinh XML7 (rong hon XML8, giu nhu DLL)
        $this->assertTrue($x->coTrongHoSo($this->du(['tdl_treatment_type_id' => 2, 'is_chronic' => 1])));
        foreach ([1, 5, 6, 0, null] as $loai) {
            $d = $this->du(['tdl_treatment_type_id' => $loai]);
            $this->assertFalse($x->coTrongHoSo($d), 'loai ' . var_export($loai, true));
            $this->assertSame([], $x->dung($d, $this->dm(), []));
        }
    }

    public function test_xml7_cac_truong_co_ban()
    {
        $r = $this->x7([
            'end_code' => '', 'is_pregnancy_termination' => 1, 'pregnancy_termination_reason' => 'ly do',
            'pregnancy_termination_time' => 20260103123000, 'gestational_age' => 12,
            'treatment_method' => 'PP', 'advise' => 'loi dan', 'end_head_loginname' => 'bs',
            'father_social_insurance_number' => 'BC', 'mother_social_insurance_number' => 'BM',
            'outpatient_date_from' => 20260106000000, 'outpatient_date_to' => 20260110000000,
        ]);
        $this->assertSame(self::TRUONG_XML7, array_keys($r));
        $this->assertSame('HS5', $r['MA_LK']);
        $this->assertSame('0', $r['SO_LUU_TRU']);
        $this->assertSame('BN5', $r['MA_YTE']);
        $this->assertSame('K01', $r['MA_KHOA_RV']);
        $this->assertSame('202601010800', $r['NGAY_VAO']);
        $this->assertSame('202601051000', $r['NGAY_RA']);
        $this->assertSame('1', $r['MA_DINH_CHI_THAI']);
        $this->assertSame('ly do', $r['NGUYENNHAN_DINHCHI']);
        $this->assertSame('202601031230', $r['THOIGIAN_DINHCHI']);
        $this->assertSame('12', $r['TUOI_THAI']);
        $this->assertSame('Viem phoi;ghi them', $r['CHAN_DOAN_RV']);
        $this->assertSame('PP', $r['PP_DIEUTRI']);
        $this->assertSame('loi dan', $r['GHI_CHU']);
        $this->assertSame('CCHN-GD', $r['MA_TTDV']);
        $this->assertSame('CCHN-BS', $r['MA_BS']);
        $this->assertSame('Bac si', $r['TEN_BS']);
        $this->assertSame('20260105', $r['NGAY_CT']);
        $this->assertSame('BC', $r['MA_CHA']);
        $this->assertSame('BM', $r['MA_ME']);
        $this->assertSame('', $r['MA_THE_TAM']);
        $this->assertSame('20260106', $r['NGOAITRU_TUNGAY']);
        $this->assertSame('20260110', $r['NGOAITRU_DENNGAY']);
        // SO_NGAY_NGHI khong cong 1 (giu nhu DLL, can xac nhan)
        $this->assertSame('4', (string) $r['SO_NGAY_NGHI']);
        $this->assertSame('', $r['DU_PHONG']);
    }

    public function test_xml7_thieu_du_lieu_khong_lam_vo()
    {
        $r = $this->x7(['out_time' => null, 'end_head_loginname' => null, 'outpatient_date_from' => 20260106000000, 'outpatient_date_to' => null]);
        $this->assertSame('', $r['NGAY_RA']);
        $this->assertSame('', $r['NGAY_CT']);
        $this->assertSame('0', $r['MA_DINH_CHI_THAI']);
        $this->assertSame('', $r['TUOI_THAI']);
        $this->assertSame('', $r['MA_BS']);
        $this->assertSame('', $r['TEN_BS']);
        $this->assertSame('0', (string) $r['SO_NGAY_NGHI']);
        // ngay nghi khong hop le: DLL .Value tren null nem loi va mat ca bang, module tra 0
        $r = $this->x7(['outpatient_date_from' => 20260106000000, 'outpatient_date_to' => 123]);
        $this->assertSame('0', (string) $r['SO_NGAY_NGHI']);
    }

    public function test_xml7_ma_bs_ten_bs_theo_chuoi_uu_tien_nhanh_3176()
    {
        $r = $this->x7(['end_dept_subs_head_loginname' => 'pk', 'end_department_head_loginname' => 'tk', 'end_head_loginname' => 'bs']);
        $this->assertSame('CCHN-PK', $r['MA_BS']);
        $this->assertSame('Pho khoa', $r['TEN_BS']);
        $r = $this->x7(['end_dept_subs_head_loginname' => null, 'end_department_head_loginname' => 'tk', 'end_head_loginname' => 'bs']);
        $this->assertSame('CCHN-TK', $r['MA_BS']);
        $this->assertSame('Truong khoa', $r['TEN_BS']);
        $r = $this->x7(['end_dept_subs_head_loginname' => 'khong-co', 'end_head_loginname' => 'bs']);
        $this->assertSame('', $r['MA_BS']);
        $this->assertSame('', $r['TEN_BS']);
    }

    public function test_xml7_pta_uu_tien_bhyt()
    {
        $bhytCu = $this->pta(['id' => 1, 'log_time' => 20260101080000, 'patient_type_code' => 'BHYT', 'has_birth_certificate' => null]);
        $vpMoi = $this->pta(['id' => 2, 'log_time' => 20260102080000, 'patient_type_code' => 'VP', 'has_birth_certificate' => 'C']);
        // BHYT cu thang VP moi => PTA chinh khong co giay khai sinh => MA_THE_TAM rong
        $r = $this->x7(['tdl_hein_card_number' => 'DN123'], [$vpMoi, $bhytCu]);
        $this->assertSame('', $r['MA_THE_TAM']);
        // hai PTA cung BHYT: log_time moi hon thang
        $bhytMoi = $this->pta(['id' => 3, 'log_time' => 20260103080000, 'patient_type_code' => 'BHYT', 'has_birth_certificate' => 'C']);
        $r = $this->x7(['tdl_hein_card_number' => 'DN123'], [$bhytCu, $bhytMoi, $vpMoi]);
        $this->assertSame('DN123', $r['MA_THE_TAM']);
        // cung log_time: id lon hon thang
        $a = $this->pta(['id' => 7, 'log_time' => 20260103080000, 'has_birth_certificate' => null]);
        $b = $this->pta(['id' => 9, 'log_time' => 20260103080000, 'has_birth_certificate' => 'C']);
        $r = $this->x7(['tdl_hein_card_number' => 'DN123'], [$a, $b]);
        $this->assertSame('DN123', $r['MA_THE_TAM']);
        // khong co PTA: rong, khong nem
        $r = $this->x7(['tdl_hein_card_number' => 'DN123'], []);
        $this->assertSame('', $r['MA_THE_TAM']);
    }

    public function test_xml7_ten_cha_me()
    {
        // FATHER_NAME / MOTHER_NAME co gia tri thi uu tien, bat ke quan he
        $r = $this->x7(['father_name' => 'Cha that', 'mother_name' => 'Me that', 'tdl_patient_relative_name' => 'Nguoi than', 'tdl_patient_relative_type' => 'cha']);
        $this->assertSame('Cha that', $r['HO_TEN_CHA']);
        $this->assertSame('Me that', $r['HO_TEN_ME']);
        // rong: lay nguoi than neu quan he dung
        foreach (['cha', 'Bố', 'bo'] as $q) {
            $r = $this->x7(['father_name' => '  ', 'tdl_patient_relative_name' => 'Nguoi than', 'tdl_patient_relative_type' => $q]);
            $this->assertSame('Nguoi than', $r['HO_TEN_CHA'], $q);
            $this->assertSame('', $r['HO_TEN_ME'], $q);
        }
        foreach (['mẹ', 'ME'] as $q) {
            $r = $this->x7(['tdl_patient_relative_name' => 'Nguoi than', 'tdl_patient_relative_type' => $q]);
            $this->assertSame('Nguoi than', $r['HO_TEN_ME'], $q);
            $this->assertSame('', $r['HO_TEN_CHA'], $q);
        }
        $r = $this->x7(['tdl_patient_relative_name' => 'Nguoi than', 'tdl_patient_relative_type' => 'vo']);
        $this->assertSame('', $r['HO_TEN_CHA']);
        $this->assertSame('', $r['HO_TEN_ME']);
        $r = $this->x7(['tdl_patient_relative_name' => 'Nguoi than', 'tdl_patient_relative_type' => null]);
        $this->assertSame('', $r['HO_TEN_CHA']);
    }

    public function test_xml7_ghi_chu_cat_1500_ky_tu()
    {
        $r = $this->x7(['advise' => str_repeat('ệ', 1600)]);
        $this->assertSame(1500, mb_strlen($r['GHI_CHU'], 'UTF-8'));
        $r = $this->x7(['advise' => str_repeat('a', 1500)]);
        $this->assertSame(1500, strlen($r['GHI_CHU']));
    }

    public function test_xml7_ma_ttdv_config_khong_trim_giu_nhu_dll()
    {
        DB::connection('HISPro')->table('his_config')->insert(['id' => 2, 'key' => MaTtdv::KEY, 'value' => '1, 7', 'is_active' => 1]);
        $r = $this->x7(['representative_hein_code' => 'RHC']);
        // "1, 7" khong khop "7" (khong trim) => REPRESENTATIVE_HEIN_CODE (can xac nhan)
        $this->assertSame('RHC', $r['MA_TTDV']);
        DB::connection('HISPro')->table('his_config')->where('id', 2)->update(['value' => '1,7']);
        $this->assertSame('BHXH-GD', $this->x7(['representative_hein_code' => 'RHC'])['MA_TTDV']);
    }

    public function test_xml8_loai_ngoai_tru_man_tinh()
    {
        $x = new Xml8Dung();
        $this->assertSame('XML8', $x->loai());
        // ngoai tru khong man tinh (null hoac 0): co XML8, MA_LOAI_KCB 02
        foreach ([null, 0] as $mt) {
            $d = $this->du(['tdl_treatment_type_id' => 2, 'is_chronic' => $mt]);
            $this->assertTrue($x->coTrongHoSo($d));
            $this->assertSame('02', $x->dung($d, $this->dm(), [])[0]['MA_LOAI_KCB']);
        }
        // ngoai tru man tinh bi loai
        $d = $this->du(['tdl_treatment_type_id' => 2, 'is_chronic' => 1]);
        $this->assertFalse($x->coTrongHoSo($d));
        $this->assertSame([], $x->dung($d, $this->dm(), []));
        // cac loai khac 2, 3, 4 bi loai
        foreach ([1, 5, 6, 0, null] as $loai) {
            $d = $this->du(['tdl_treatment_type_id' => $loai]);
            $this->assertFalse($x->coTrongHoSo($d), 'loai ' . var_export($loai, true));
            $this->assertSame([], $x->dung($d, $this->dm(), []));
        }
    }

    public function test_xml8_ma_loai_kcb_theo_bang()
    {
        // 02 ngoai tru
        $this->assertSame('02', $this->x8(['tdl_treatment_type_id' => 2])['MA_LOAI_KCB']);
        // 04 ban ngay, bat ke thoi luong
        $this->assertSame('04', $this->x8(['tdl_treatment_type_id' => 4, 'clinical_in_time' => 20260101090000, 'out_time' => 20260101100000])['MA_LOAI_KCB']);
        // 03 noi tru tu 4 gio tro len
        $this->assertSame('03', $this->x8(['tdl_treatment_type_id' => 3, 'clinical_in_time' => 20260101080000, 'out_time' => 20260101120000])['MA_LOAI_KCB']);
        $this->assertSame('03', $this->x8(['tdl_treatment_type_id' => 3, 'clinical_in_time' => 20260101080000, 'out_time' => 20260105120000])['MA_LOAI_KCB']);
        // 09 noi tru duoi 4 gio (tinh tu CLINICAL_IN_TIME)
        $this->assertSame('09', $this->x8(['tdl_treatment_type_id' => 3, 'clinical_in_time' => 20260101080000, 'out_time' => 20260101115900])['MA_LOAI_KCB']);
        $this->assertSame('09', $this->x8(['tdl_treatment_type_id' => 3, 'in_time' => 20260101000000, 'clinical_in_time' => 20260101090000, 'out_time' => 20260101100000])['MA_LOAI_KCB']);
        // thieu CLINICAL_IN_TIME, thieu OUT_TIME, ra truoc vao: 03
        $this->assertSame('03', $this->x8(['tdl_treatment_type_id' => 3, 'clinical_in_time' => null])['MA_LOAI_KCB']);
        $this->assertSame('03', $this->x8(['tdl_treatment_type_id' => 3, 'out_time' => null])['MA_LOAI_KCB']);
        $this->assertSame('03', $this->x8(['tdl_treatment_type_id' => 3, 'clinical_in_time' => 20260101100000, 'out_time' => 20260101080000])['MA_LOAI_KCB']);
    }

    public function test_xml8_ten_cha_me_khong_bi_dao()
    {
        // FATHER_NAME co gia tri va quan he "cha": DLL ghi de bang nguoi than; module giu FATHER_NAME
        $r = $this->x8(['father_name' => 'Cha that', 'mother_name' => 'Me that', 'tdl_patient_relative_name' => 'Nguoi than', 'tdl_patient_relative_type' => 'cha']);
        $this->assertSame('Cha that', $r['HO_TEN_CHA']);
        $this->assertSame('Me that', $r['HO_TEN_ME']);
        $this->assertSame('Nguoi than', $r['NGUOI_GIAM_HO']);
        // FATHER_NAME rong va quan he cha/bo: DLL khong dien (hoac "bo" luon ghi de); module dien nguoi than
        foreach (['cha', 'bố', 'bo'] as $q) {
            $r = $this->x8(['tdl_patient_relative_name' => 'Nguoi than', 'tdl_patient_relative_type' => $q]);
            $this->assertSame('Nguoi than', $r['HO_TEN_CHA'], $q);
            $this->assertSame('', $r['HO_TEN_ME'], $q);
        }
        foreach (['mẹ', 'me'] as $q) {
            $r = $this->x8(['tdl_patient_relative_name' => 'Nguoi than', 'tdl_patient_relative_type' => $q]);
            $this->assertSame('Nguoi than', $r['HO_TEN_ME'], $q);
            $this->assertSame('', $r['HO_TEN_CHA'], $q);
        }
        // quan he "bo" nhung da co FATHER_NAME: DLL ghi de, module giu FATHER_NAME
        $r = $this->x8(['father_name' => 'Cha that', 'tdl_patient_relative_name' => 'Nguoi than', 'tdl_patient_relative_type' => 'bo']);
        $this->assertSame('Cha that', $r['HO_TEN_CHA']);
    }

    public function test_xml8_tomtat_kq_cat_theo_ky_tu()
    {
        $r = $this->x8(['subclinical_result' => str_repeat('ệ', 2500)]);
        $this->assertSame(2000, mb_strlen($r['TOMTAT_KQ'], 'UTF-8'));
        $this->assertSame(str_repeat('ệ', 2000), $r['TOMTAT_KQ']);
        $this->assertTrue(mb_check_encoding($r['TOMTAT_KQ'], 'UTF-8'));
        $this->assertSame(str_repeat('a', 2000), $this->x8(['subclinical_result' => str_repeat('a', 2000)])['TOMTAT_KQ']);
        $this->assertSame('', $this->x8(['subclinical_result' => null])['TOMTAT_KQ']);
        // ky tu khong hop le trong XML bi bo (RemoveXmlCharError) o cac truong CDATA
        $this->assertSame('ab', $this->x8(['subclinical_result' => "a\x01b"])['TOMTAT_KQ']);
    }

    public function test_chan_doan_rv_khong_dau_cham_phay_thua()
    {
        $this->assertSame('Viem phoi', $this->x7(['icd_name' => 'Viem phoi', 'icd_text' => null])['CHAN_DOAN_RV']);
        $this->assertSame('Viem phoi', $this->x8(['icd_name' => 'Viem phoi', 'icd_text' => null])['CHAN_DOAN_RV']);
        $this->assertSame('Viem phoi', $this->x8(['icd_name' => 'Viem phoi', 'icd_text' => ''])['CHAN_DOAN_RV']);
        $this->assertSame('Viem phoi;kem', $this->x8(['icd_name' => 'Viem phoi', 'icd_text' => 'kem'])['CHAN_DOAN_RV']);
        $this->assertSame('kem', $this->x7(['icd_name' => null, 'icd_text' => 'kem'])['CHAN_DOAN_RV']);
        $this->assertSame('', $this->x7(['icd_name' => null, 'icd_text' => null])['CHAN_DOAN_RV']);
    }

    public function test_xml8_cac_truong_con_lai()
    {
        $be = [
            (object) ['id' => 1, 'treatment_id' => 5, 'born_result_id' => 1, 'born_time' => 20260101000000, 'death_date' => null],
            (object) ['id' => 2, 'treatment_id' => 5, 'born_result_id' => 2, 'born_time' => 20260102093000, 'death_date' => 20260103000000],
            (object) ['id' => 3, 'treatment_id' => 5, 'born_result_id' => 2, 'born_time' => 20260104000000, 'death_date' => 20260105000000],
        ];
        $r = $this->x8([
            'tdl_patient_work_place' => null, 'tdl_patient_work_place_name' => 'Cong ty', 'provisional_diagnosis' => null,
            'clinical_note' => 'benh ly', 'treatment_method' => 'PP', 'treatment_result_code' => 'KQ06',
            'tdl_patient_dob' => 20000101000000, 'representative_hein_code' => 'RHC',
        ], [$this->pta(['has_birth_certificate' => 'C'])], $be);
        $this->assertSame(self::TRUONG_XML8, array_keys($r));
        $this->assertSame('Cong ty', $r['DON_VI']);
        $this->assertSame('Viem phoi', $r['CHAN_DOAN_VAO']);
        $this->assertSame('benh ly', $r['QT_BENHLY']);
        $this->assertSame('PP', $r['PP_DIEUTRI']);
        $this->assertSame('20260102', $r['NGAY_SINHCON']);
        $this->assertSame('20260103', $r['NGAY_CONCHET']);
        $this->assertSame('2', $r['SO_CONCHET']);
        $this->assertSame('6', $r['KET_QUA_DTRI']);
        $this->assertSame('CCHN-GD', $r['MA_TTDV']);
        $this->assertSame('20260105', $r['NGAY_CT']);
        $this->assertSame('', $r['DU_PHONG']);
        // khong co be chet
        $r = $this->x8([], [], [$be[0]]);
        $this->assertSame(['', '', ''], [$r['NGAY_SINHCON'], $r['NGAY_CONCHET'], $r['SO_CONCHET']]);
        // be chet thieu ngay: rong, khong nem
        $r = $this->x8([], [], [(object) ['born_result_id' => 2, 'born_time' => null, 'death_date' => null]]);
        $this->assertSame(['', '', '1'], [$r['NGAY_SINHCON'], $r['NGAY_CONCHET'], $r['SO_CONCHET']]);
    }

    public function test_xml8_ma_the_tam_va_ghi_chu()
    {
        $r = $this->x8(['tdl_hein_card_number' => 'DN123'], [$this->pta(['has_birth_certificate' => 'C'])]);
        $this->assertSame('DN123', $r['MA_THE_TAM']);
        $r = $this->x8(['tdl_hein_card_number' => 'DN123'], [$this->pta(['patient_type_code' => 'VP', 'id' => 9, 'log_time' => 20270101000000, 'has_birth_certificate' => 'C']), $this->pta()]);
        $this->assertSame('', $r['MA_THE_TAM']);
        $this->assertSame('', $this->x8(['tdl_hein_card_number' => 'DN123'], [])['MA_THE_TAM']);
        // GHI_CHU: duoi 16 tuoi lay cha, me, nguoi than; tu 16 tuoi tro len rong
        $tre = ['tdl_patient_dob' => (date('Y') - 5) . '0101000000'];
        $this->assertSame('Cha', $this->x8($tre + ['father_name' => 'Cha', 'mother_name' => 'Me'])['GHI_CHU']);
        $this->assertSame('Me', $this->x8($tre + ['mother_name' => 'Me', 'tdl_patient_relative_name' => 'NT'])['GHI_CHU']);
        $this->assertSame('NT', $this->x8($tre + ['tdl_patient_relative_name' => 'NT'])['GHI_CHU']);
        $this->assertSame('', $this->x8(['father_name' => 'Cha', 'tdl_patient_dob' => 19900101000000])['GHI_CHU']);
        // KET_QUA_DTRI: rong khi khong co ma
        $this->assertSame('', $this->x8(['treatment_result_code' => null])['KET_QUA_DTRI']);
    }

    public function test_khong_dieu_kien_tra_rong()
    {
        $d = new DuLieuHoSo($this->hoSo(['tdl_treatment_type_id' => 1]));
        foreach ([new Xml7Dung(), new Xml8Dung()] as $x) {
            $this->assertFalse($x->coTrongHoSo($d));
            $this->assertSame([], $x->dung($d, $this->dm(), []));
        }
    }

    public function test_thu_tu_khoa_dung_chi_tieu()
    {
        $this->assertSame(self::TRUONG_XML7, array_keys($this->x7()));
        $this->assertSame(self::TRUONG_XML8, array_keys($this->x8()));
    }
}

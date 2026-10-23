<?php

namespace Tests\Unit\SinhXml3176;

use App\Services\SinhXml3176\Bang\MaTtdv;
use App\Services\SinhXml3176\Bang\PtaChinh;
use App\Services\SinhXml3176\Bang\Xml10Dung;
use App\Services\SinhXml3176\Bang\Xml11Dung;
use App\Services\SinhXml3176\Bang\Xml9Dung;
use App\Services\SinhXml3176\DuLieuHoSo;
use App\Services\SinhXml3176\Nguon\HisDanhMuc;
use App\Services\SinhXml3176\Nguon\HisNapHoSo;
use Illuminate\Support\Facades\DB;
use Tests\Support\DungBangHisSinhXml3176Sqlite;
use Tests\TestCase;

class Xml9Xml10Xml11DungTest extends TestCase
{
    use DungBangHisSinhXml3176Sqlite;

    const TRUONG_XML9 = [
        'MA_LK', 'MA_BHXH_NND', 'MA_THE_NND', 'HO_TEN_NND', 'NGAYSINH_NND', 'MA_DANTOC_NND', 'SO_CCCD_NND',
        'NGAYCAP_CCCD_NND', 'NOICAP_CCCD_NND', 'NOI_CU_TRU_NND', 'MA_QUOCTICH', 'MATINH_CU_TRU', 'MAHUYEN_CU_TRU',
        'MAXA_CU_TRU', 'HO_TEN_CHA', 'MA_THE_TAM', 'HO_TEN_CON', 'GIOI_TINH_CON', 'SO_CON', 'LAN_SINH', 'SO_CON_SONG',
        'CAN_NANG_CON', 'NGAY_SINH_CON', 'NOI_SINH_CON', 'TINH_TRANG_CON', 'SINHCON_PHAUTHUAT', 'SINHCON_DUOI32TUAN',
        'GHI_CHU', 'NGUOI_DO_DE', 'NGUOI_GHI_PHIEU', 'NGAY_CT', 'SO', 'QUYEN_SO', 'MA_TTDV', 'DU_PHONG',
    ];

    const TRUONG_XML10 = [
        'MA_LK', 'SO_SERI', 'SO_CT', 'SO_NGAY', 'DON_VI', 'CHAN_DOAN_RV', 'TU_NGAY', 'DEN_NGAY', 'MA_TTDV', 'TEN_BS',
        'MA_BS', 'NGAY_CT', 'DU_PHONG',
    ];

    const TRUONG_XML11 = [
        'MA_LK', 'SO_SERI', 'SO_CT', 'SO_KCB', 'DON_VI', 'MA_BHXH', 'MA_THE_BHYT', 'CHAN_DOAN_RV', 'PP_DIEUTRI',
        'MA_DINH_CHI_THAI', 'NGUYENNHAN_DINHCHI', 'TUOI_THAI', 'SO_NGAY_NGHI', 'TU_NGAY', 'DEN_NGAY', 'HO_TEN_CHA',
        'HO_TEN_ME', 'MA_TTDV', 'MA_BS', 'MA_THE_TAM', 'NGAY_CT', 'MAU_SO', 'DU_PHONG',
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
            'id' => 5, 'treatment_code' => 'HS5', 'in_date' => 20260101000000, 'out_date' => 20260105000000,
            'treatment_end_type_ext_id' => 1, 'hosp_subs_director_loginname' => 'gd',
        ], $ghi);
    }

    private function beSinh(array $ghi = [])
    {
        return (object) array_merge(array_fill_keys(HisNapHoSo::COT_SO_SINH, null), ['id' => 1, 'treatment_id' => 5], $ghi);
    }

    private function pta(array $ghi = [])
    {
        return (object) array_merge(array_fill_keys(HisNapHoSo::COT_DIEN_DIEU_TRI, null), [
            'id' => 1, 'treatment_id' => 5, 'log_time' => 20260101080000, 'patient_type_code' => 'BHYT',
        ], $ghi);
    }

    private function du(array $ghiHoSo = [], array $be = [], array $pta = [])
    {
        $d = new DuLieuHoSo($this->hoSo($ghiHoSo));
        $d->soSinh = $be;
        $d->dienDieuTri = $pta;

        return $d;
    }

    private function x9(array $ghiHoSo = [], array $be = null)
    {
        return (new Xml9Dung())->dung($this->du($ghiHoSo, $be === null ? [$this->beSinh()] : $be), $this->dm(), []);
    }

    private function x10(array $ghiHoSo = [])
    {
        $ra = (new Xml10Dung())->dung($this->du(array_merge(['treatment_end_type_ext_id' => 2], $ghiHoSo)), $this->dm(), []);
        $this->assertCount(1, $ra);

        return $ra[0];
    }

    private function x11(array $ghiHoSo = [], array $pta = [])
    {
        $ra = (new Xml11Dung())->dung($this->du(array_merge(['treatment_end_type_ext_id' => 1], $ghiHoSo), [], $pta), $this->dm(), []);
        $this->assertCount(1, $ra);

        return $ra[0];
    }

    // ---------------- XML9

    public function test_xml9_moi_so_sinh_mot_dong()
    {
        $x = new Xml9Dung();
        $this->assertSame('XML9', $x->loai());
        $ra = $this->x9([], [$this->beSinh(['id' => 1, 'baby_name' => 'Be 1']), $this->beSinh(['id' => 2, 'baby_name' => 'Be 2'])]);
        $this->assertCount(2, $ra);
        $this->assertSame('Be 1', $ra[0]['HO_TEN_CON']);
        $this->assertSame('Be 2', $ra[1]['HO_TEN_CON']);
        // be cua ho so khac bi bo (DLL loc TREATMENT_ID == ID ho so)
        $ra = $this->x9([], [$this->beSinh(['baby_name' => 'A']), $this->beSinh(['id' => 2, 'treatment_id' => 6, 'baby_name' => 'B'])]);
        $this->assertCount(1, $ra);
        $this->assertSame('A', $ra[0]['HO_TEN_CON']);
        // khong co be => khong sinh
        $d = $this->du([], []);
        $this->assertFalse($x->coTrongHoSo($d));
        $this->assertSame([], $x->dung($d, $this->dm(), []));
        $this->assertTrue($x->coTrongHoSo($this->du([], [$this->beSinh()])));
    }

    public function test_xml9_gioi_tinh_doi_ma()
    {
        $this->assertSame('2', $this->x9([], [$this->beSinh(['gender_id' => 1])])[0]['GIOI_TINH_CON']);
        $this->assertSame('1', $this->x9([], [$this->beSinh(['gender_id' => 2])])[0]['GIOI_TINH_CON']);
        $this->assertSame('3', $this->x9([], [$this->beSinh(['gender_id' => 3])])[0]['GIOI_TINH_CON']);
        $this->assertSame('3', $this->x9([], [$this->beSinh(['gender_id' => null])])[0]['GIOI_TINH_CON']);
    }

    public function test_xml9_cac_truong_tu_be_va_me()
    {
        $ra = $this->x9([
            'tdl_patient_mps_national_code' => 'VN', 'tdl_social_insurance_number' => '', 'tdl_hein_card_number' => 'DN4010000123456',
            'in_date' => 20260102000000, 'hosp_subs_director_loginname' => '',
        ], [$this->beSinh([
            'tdl_hein_card_number' => 'THE-ME', 'tdl_patient_name' => 'Nguyen Thi Me', 'tdl_patient_dob' => 19950304000000,
            'tdl_patient_ethnic_code' => '01', 'tdl_patient_cccd_number' => '', 'tdl_patient_cmnd_number' => 'CMND1',
            'tdl_patient_cccd_date' => null, 'tdl_patient_cmnd_date' => 20100506000000, 'tdl_patient_passport_date' => 20200101000000,
            'tdl_patient_cccd_place' => null, 'tdl_patient_cmnd_place' => 'CA HN', 'tdl_patient_address' => 'So 1',
            'tdl_patient_province_code' => '01', 'tdl_patient_district_code' => '001', 'tdl_patient_commune_code' => '00001',
            'father_name' => 'Cha', 'hein_card_number_tmp' => 'TAM1', 'baby_name' => 'Be', 'gender_id' => 1,
            'number_children_birth' => 2, 'number_of_birth' => 1, 'current_alive' => 2, 'weight' => 3250.9,
            'born_time' => 20260102133045, 'born_result_name' => 'Song', 'is_surgery' => null, 'week_count' => 31,
            'note' => null, 'midwife' => 'Ba mu', 'issuer_username' => 'Nguoi ghi', 'issued_date' => 20260103000000,
            'birth_cert_num' => 123, 'birth_cert_book_name' => 'Quyen 1', 'birthplace_type' => 1, 'birth_hospital_name' => 'BV A',
        ])]);
        $this->assertCount(1, $ra);
        $r = $ra[0];
        $this->assertSame(self::TRUONG_XML9, array_keys($r));
        $this->assertSame('HS5', $r['MA_LK']);
        // TDL_SOCIAL_INSURANCE_NUMBER rong => 10 ky tu cuoi the cua me
        $this->assertSame('0000123456', $r['MA_BHXH_NND']);
        $this->assertSame('THE-ME', $r['MA_THE_NND']);
        $this->assertSame('Nguyen Thi Me', $r['HO_TEN_NND']);
        $this->assertSame('19950304', $r['NGAYSINH_NND']);
        $this->assertSame('01', $r['MA_DANTOC_NND']);
        $this->assertSame('CMND1', $r['SO_CCCD_NND']);
        // ngay cap chon doc lap voi so giay: cmnd_date (cccd_date null)
        $this->assertSame('20100506', $r['NGAYCAP_CCCD_NND']);
        $this->assertSame('CA HN', $r['NOICAP_CCCD_NND']);
        $this->assertSame('So 1', $r['NOI_CU_TRU_NND']);
        $this->assertSame('VN', $r['MA_QUOCTICH']);
        $this->assertSame('01', $r['MATINH_CU_TRU']);
        $this->assertSame('001', $r['MAHUYEN_CU_TRU']);
        $this->assertSame('00001', $r['MAXA_CU_TRU']);
        $this->assertSame('Cha', $r['HO_TEN_CHA']);
        $this->assertSame('TAM1', $r['MA_THE_TAM']);
        $this->assertSame('Be', $r['HO_TEN_CON']);
        $this->assertSame('2', $r['GIOI_TINH_CON']);
        $this->assertSame('2', $r['SO_CON']);
        $this->assertSame('1', $r['LAN_SINH']);
        $this->assertSame('2', $r['SO_CON_SONG']);
        $this->assertSame('3250', $r['CAN_NANG_CON']);
        $this->assertSame('202601021330', $r['NGAY_SINH_CON']);
        $this->assertSame('BV A', $r['NOI_SINH_CON']);
        $this->assertSame('Song', $r['TINH_TRANG_CON']);
        $this->assertSame('0', $r['SINHCON_PHAUTHUAT']);
        $this->assertSame('1', $r['SINHCON_DUOI32TUAN']);
        $this->assertSame('', $r['GHI_CHU']);
        $this->assertSame('Ba mu', $r['NGUOI_DO_DE']);
        $this->assertSame('Nguoi ghi', $r['NGUOI_GHI_PHIEU']);
        // giu nhu DLL: NGAY_CT = IN_DATE cua me (de len ISSUED_DATE)
        $this->assertSame('20260102', $r['NGAY_CT']);
        $this->assertSame('123', $r['SO']);
        $this->assertSame('Quyen 1', $r['QUYEN_SO']);
        $this->assertSame('', $r['MA_TTDV']);
        $this->assertSame('', $r['DU_PHONG']);
        // tuan thai >= 32 va co phau thuat
        $r2 = $this->x9([], [$this->beSinh(['week_count' => 32, 'is_surgery' => 1])])[0];
        $this->assertSame('0', $r2['SINHCON_DUOI32TUAN']);
        $this->assertSame('1', $r2['SINHCON_PHAUTHUAT']);
        $this->assertSame('0', $this->x9([], [$this->beSinh(['week_count' => null])])[0]['SINHCON_DUOI32TUAN']);
    }

    public function test_xml9_noi_sinh_con_theo_loai()
    {
        $b = ['birth_hospital_name' => 'BV A', 'birthplace' => 'Thon 1', 'birth_commune_name' => 'Xa X', 'birth_district_name' => 'Huyen H', 'birth_province_name' => 'Tinh T'];
        $noi = function (array $ghi) use ($b) {
            return $this->x9([], [$this->beSinh(array_merge($b, $ghi))])[0]['NOI_SINH_CON'];
        };
        $this->assertSame('BV A', $noi(['birthplace_type' => 1]));
        $this->assertSame('BV A, Thon 1, Xa X, Huyen H, Tinh T', $noi(['birthplace_type' => 2]));
        $this->assertSame('Sinh tại nhà, Thon 1, Xa X, Huyen H, Tinh T', $noi(['birthplace_type' => 3]));
        $this->assertSame('Đẻ trên đường đi, Thon 1, Xa X, Huyen H, Tinh T', $noi(['birthplace_type' => 4]));
        $this->assertSame('Trẻ bị bỏ rơi, Thon 1, Xa X, Huyen H, Tinh T', $noi(['birthplace_type' => 5]));
        $this->assertSame('', $noi(['birthplace_type' => 6]));
        $this->assertSame('', $noi(['birthplace_type' => null]));
        // don ", , " va dau phay cuoi
        $this->assertSame('BV A, Xa X', $noi(['birthplace_type' => 2, 'birthplace' => '', 'birth_district_name' => null, 'birth_province_name' => null]));
        $this->assertSame('Sinh tại nhà, Thon 1', $noi(['birthplace_type' => 3, 'birth_commune_name' => null, 'birth_district_name' => null, 'birth_province_name' => null]));
        // Sua loi DLL [AX]:1079: loai 1 ma ten BV null thi DLL goi Replace tren null (NullReference, mat ca bang) => rong
        $this->assertSame('', $noi(['birthplace_type' => 1, 'birth_hospital_name' => null]));
    }

    public function test_xml9_ma_bhxh_the_ngan_khong_vo()
    {
        // Sua loi DLL [AX]:1094: the ngan hon 10 ky tu (Substring nem loi, mat ca XML9) => lay het chuoi
        $this->assertSame('ABC', $this->x9(['tdl_hein_card_number' => 'ABC'])[0]['MA_BHXH_NND']);
        $this->assertSame('', $this->x9(['tdl_hein_card_number' => null])[0]['MA_BHXH_NND']);
        $this->assertSame('SOBHXH', $this->x9(['tdl_social_insurance_number' => 'SOBHXH', 'tdl_hein_card_number' => 'XYZ'])[0]['MA_BHXH_NND']);
    }

    public function test_xml9_thieu_du_lieu_khong_lam_vo()
    {
        $r = $this->x9([], [$this->beSinh()])[0];
        $this->assertSame(self::TRUONG_XML9, array_keys($r));
        $this->assertSame('', $r['NGAYSINH_NND']);
        $this->assertSame('', $r['CAN_NANG_CON']);
        $this->assertSame('', $r['NGAY_SINH_CON']);
        $this->assertSame('', $r['SO']);
        $this->assertSame('3', $r['GIOI_TINH_CON']);
        $this->assertSame('0', $r['SINHCON_PHAUTHUAT']);
        $this->assertSame('', $this->x9(['in_date' => null])[0]['NGAY_CT']);
    }

    // ---------------- XML10

    public function test_xml10_chi_khi_end_type_ext_2()
    {
        $x = new Xml10Dung();
        $this->assertSame('XML10', $x->loai());
        $d = $this->du(['treatment_end_type_ext_id' => 2]);
        $this->assertTrue($x->coTrongHoSo($d));
        $this->assertCount(1, $x->dung($d, $this->dm(), []));
        foreach ([1, 3, 0, null] as $v) {
            $d = $this->du(['treatment_end_type_ext_id' => $v]);
            $this->assertFalse($x->coTrongHoSo($d), var_export($v, true));
            $this->assertSame([], $x->dung($d, $this->dm(), []));
        }
    }

    public function test_xml10_chan_doan_duong_thai_giu_nhu_dll()
    {
        // IS_3176 gan cung, bo ICD_NAME / ICD_TEXT (giu nhu DLL, can xac nhan)
        $this->assertSame('dưỡng thai', $this->x10(['icd_name' => 'Viem', 'icd_text' => 'abc'])['CHAN_DOAN_RV']);
        $this->assertSame('dưỡng thai', $this->x10()['CHAN_DOAN_RV']);
    }

    public function test_xml10_cac_truong()
    {
        $r = $this->x10([
            'extra_end_code' => 'S1', 'sick_leave_day' => 7.9, 'tdl_patient_work_place' => '  ', 'tdl_patient_work_place_name' => 'Cty A',
            'sick_leave_from' => 20260106000000, 'sick_leave_to' => 20260112000000, 'sick_loginname' => 'bs', 'sick_username' => 'Bac si ky',
            'out_date' => 20260105000000, 'end_dept_subs_head_loginname' => 'pk', 'end_department_head_loginname' => 'tk',
        ]);
        $this->assertSame(self::TRUONG_XML10, array_keys($r));
        $this->assertSame('HS5', $r['MA_LK']);
        $this->assertSame('S1', $r['SO_SERI']);
        $this->assertSame('S1', $r['SO_CT']);
        $this->assertSame('7', $r['SO_NGAY']);
        $this->assertSame('Cty A', $r['DON_VI']);
        $this->assertSame('20260106', $r['TU_NGAY']);
        $this->assertSame('20260112', $r['DEN_NGAY']);
        $this->assertSame('CCHN-GD', $r['MA_TTDV']);
        // MA_BS va TEN_BS co the la hai nguoi khac nhau (giu nhu DLL, can xac nhan)
        $this->assertSame('CCHN-PK', $r['MA_BS']);
        $this->assertSame('Bac si ky', $r['TEN_BS']);
        $this->assertSame('20260105', $r['NGAY_CT']);
        $this->assertSame('', $r['DU_PHONG']);
        // chuoi ?? : khong co pho/truong khoa => SICK_LOGINNAME
        $this->assertSame('CCHN-BS', $this->x10(['sick_loginname' => 'bs'])['MA_BS']);
        $this->assertSame('CCHN-TK', $this->x10(['end_department_head_loginname' => 'tk', 'sick_loginname' => 'bs'])['MA_BS']);
        $this->assertSame('Co quan', $this->x10(['tdl_patient_work_place' => 'Co quan', 'tdl_patient_work_place_name' => 'Cty A'])['DON_VI']);
    }

    public function test_xml10_thieu_du_lieu_khong_lam_vo()
    {
        $r = $this->x10(['sick_leave_day' => null, 'out_date' => null, 'sick_leave_from' => null, 'extra_end_code' => null]);
        $this->assertSame('', $r['SO_NGAY']);
        $this->assertSame('', $r['NGAY_CT']);
        $this->assertSame('', $r['TU_NGAY']);
        $this->assertSame('', $r['SO_SERI']);
        $this->assertSame('', $r['MA_BS']);
        $this->assertSame('', $r['TEN_BS']);
    }

    // ---------------- XML11

    public function test_xml11_chi_khi_end_type_ext_1_mau_ct07()
    {
        $x = new Xml11Dung();
        $this->assertSame('XML11', $x->loai());
        $d = $this->du(['treatment_end_type_ext_id' => 1]);
        $this->assertTrue($x->coTrongHoSo($d));
        $ra = $x->dung($d, $this->dm(), []);
        $this->assertCount(1, $ra);
        $this->assertSame('CT07', $ra[0]['MAU_SO']);
        foreach ([2, 0, null] as $v) {
            $d = $this->du(['treatment_end_type_ext_id' => $v]);
            $this->assertFalse($x->coTrongHoSo($d), var_export($v, true));
            $this->assertSame([], $x->dung($d, $this->dm(), []));
        }
    }

    public function test_xml11_cac_truong()
    {
        $r = $this->x11([
            'extra_end_code' => 'S2', 'tdl_patient_work_place' => '', 'tdl_patient_work_place_name' => 'Cty B',
            'tdl_social_insurance_number' => null, 'sick_hein_card_number' => 'DN4010000999999', 'tdl_hein_card_number' => 'XX',
            'icd_code' => 'J18', 'icd_name' => 'Viem phoi', 'treatment_method' => 'PP',
            'is_pregnancy_termination' => null, 'pregnancy_termination_reason' => 'LD', 'gestational_age' => 12,
            'sick_leave_from' => 20260106080000, 'sick_leave_to' => 20260110170000,
            'sick_loginname' => 'bs', 'in_date' => 20260101000000,
            'end_dept_subs_head_loginname' => null, 'end_department_head_loginname' => 'tk',
        ]);
        $this->assertSame(self::TRUONG_XML11, array_keys($r));
        $this->assertSame('S2', $r['SO_SERI']);
        $this->assertSame('S2', $r['SO_CT']);
        $this->assertSame('HS5', $r['SO_KCB']);
        // chi kiem != null: chuoi rong van la noi lam viec (giu nhu DLL)
        $this->assertSame('', $r['DON_VI']);
        $this->assertSame('0000999999', $r['MA_BHXH']);
        $this->assertSame('DN4010000999999', $r['MA_THE_BHYT']);
        $this->assertSame('J18:Viem phoi', $r['CHAN_DOAN_RV']);
        $this->assertSame('PP', $r['PP_DIEUTRI']);
        $this->assertSame('0', $r['MA_DINH_CHI_THAI']);
        $this->assertSame('LD', $r['NGUYENNHAN_DINHCHI']);
        $this->assertSame('12', $r['TUOI_THAI']);
        $this->assertSame('5', $r['SO_NGAY_NGHI']);
        $this->assertSame('20260106', $r['TU_NGAY']);
        $this->assertSame('20260110', $r['DEN_NGAY']);
        $this->assertSame('CCHN-GD', $r['MA_TTDV']);
        $this->assertSame('CCHN-TK', $r['MA_BS']);
        // giu nhu DLL: NGAY_CT la ngay vao vien
        $this->assertSame('20260101', $r['NGAY_CT']);
        $this->assertSame('', $r['MA_THE_TAM']);
        $this->assertSame('', $r['DU_PHONG']);
        // work place null => ten
        $this->assertSame('Cty B', $this->x11(['tdl_patient_work_place' => null, 'tdl_patient_work_place_name' => 'Cty B'])['DON_VI']);
        // MA_BHXH uu tien TDL_SOCIAL_INSURANCE_NUMBER; roi the nghi; roi the benh nhan
        $this->assertSame('BX', $this->x11(['tdl_social_insurance_number' => 'BX'])['MA_BHXH']);
        $this->assertSame('1234567890', $this->x11(['sick_hein_card_number' => ' ', 'tdl_hein_card_number' => 'AB1234567890'])['MA_BHXH']);
        $this->assertSame('AB1234567890', $this->x11(['sick_hein_card_number' => ' ', 'tdl_hein_card_number' => 'AB1234567890'])['MA_THE_BHYT']);
        $this->assertSame('', $this->x11()['MA_BHXH']);
        $this->assertSame('1', $this->x11(['is_pregnancy_termination' => 1])['MA_DINH_CHI_THAI']);
    }

    public function test_xml11_ma_bhxh_the_ngan_khong_vo()
    {
        // Sua loi DLL [AX]:1094: the ngan hon 10 ky tu (Substring nem loi, mat ca XML11) => lay het chuoi
        $this->assertSame('ABC', $this->x11(['sick_hein_card_number' => 'ABC'])['MA_BHXH']);
        $this->assertSame('XYZ', $this->x11(['tdl_hein_card_number' => 'XYZ'])['MA_BHXH']);
    }

    public function test_xml11_so_ngay_nghi()
    {
        // (den.Date - tu.Date) + 1 ngay; thieu mot dau => rong
        $this->assertSame('1', $this->x11(['sick_leave_from' => 20260106230000, 'sick_leave_to' => 20260106010000])['SO_NGAY_NGHI']);
        $this->assertSame('', $this->x11(['sick_leave_from' => null, 'sick_leave_to' => 20260106010000])['SO_NGAY_NGHI']);
        $this->assertSame('', $this->x11(['sick_leave_from' => 20260106010000, 'sick_leave_to' => null])['SO_NGAY_NGHI']);
        $this->assertSame('', $this->x11()['SO_NGAY_NGHI']);
    }

    public function test_xml11_icd_text_thieu_khong_vo()
    {
        $base = ['icd_code' => 'J18', 'icd_name' => 'Viem phoi'];
        // du cap
        $this->assertSame('J18:Viem phoi;A00:Ta;B00:Cum', $this->x11($base + ['icd_sub_code' => 'A00;B00', 'icd_text' => 'Ta;Cum'])['CHAN_DOAN_RV']);
        // Review Focus 2: ICD_TEXT it phan tu hon ICD_SUB_CODE: DLL IndexOutOfRange; module ghep den dau duoc den do, thieu ten de trong
        $this->assertSame('J18:Viem phoi;A00:Ta;B00:', $this->x11($base + ['icd_sub_code' => 'A00;B00', 'icd_text' => 'Ta'])['CHAN_DOAN_RV']);
        $this->assertSame('J18:Viem phoi;A00:Ta;B00:;C00:', $this->x11($base + ['icd_sub_code' => 'A00;B00;C00', 'icd_text' => 'Ta;;'])['CHAN_DOAN_RV']);
        // ICD_TEXT nhieu hon: phan du bi bo nhu DLL
        $this->assertSame('J18:Viem phoi;A00:Ta', $this->x11($base + ['icd_sub_code' => 'A00', 'icd_text' => 'Ta;Thua'])['CHAN_DOAN_RV']);
        // cat ';' dau/cuoi
        $this->assertSame('J18:Viem phoi;A00:Ta;B00:Cum', $this->x11($base + ['icd_sub_code' => 'A00;B00;', 'icd_text' => 'Ta;Cum;'])['CHAN_DOAN_RV']);
        // chi co mot trong hai: them ';' nhung khong co noi dung (giu nhu DLL)
        $this->assertSame('J18:Viem phoi;', $this->x11($base + ['icd_sub_code' => 'A00'])['CHAN_DOAN_RV']);
        $this->assertSame('J18:Viem phoi;', $this->x11($base + ['icd_text' => 'Ta'])['CHAN_DOAN_RV']);
        // khong co ICD phu
        $this->assertSame('J18:Viem phoi', $this->x11($base)['CHAN_DOAN_RV']);
        // khong co ma ICD chinh: khong ':' dau
        $this->assertSame('Viem phoi', $this->x11(['icd_name' => 'Viem phoi'])['CHAN_DOAN_RV']);
        // ICD_NAME da ket thuc bang ';' khong them ';'
        $this->assertSame('J18:Viem;A00:Ta', $this->x11(['icd_code' => 'J18', 'icd_name' => 'Viem;', 'icd_sub_code' => 'A00', 'icd_text' => 'Ta'])['CHAN_DOAN_RV']);
    }

    public function test_xml11_ten_cha_me()
    {
        $r = $this->x11(['father_name' => 'Cha that', 'mother_name' => ' ', 'tdl_patient_relative_name' => 'NT', 'tdl_patient_relative_type' => 'Mẹ']);
        $this->assertSame('Cha that', $r['HO_TEN_CHA']);
        $this->assertSame('NT', $r['HO_TEN_ME']);
        $r = $this->x11(['tdl_patient_relative_name' => 'NT', 'tdl_patient_relative_type' => 'Bố']);
        $this->assertSame('NT', $r['HO_TEN_CHA']);
        $this->assertSame('', $r['HO_TEN_ME']);
        $r = $this->x11();
        $this->assertSame('', $r['HO_TEN_CHA']);
        $this->assertSame('', $r['HO_TEN_ME']);
    }

    public function test_xml11_ma_the_tam_va_pta_rong_khong_vo()
    {
        $ho = ['tdl_hein_card_number' => 'THE-ME'];
        $this->assertSame('THE-ME', $this->x11($ho, [$this->pta(['has_birth_certificate' => 'C'])])['MA_THE_TAM']);
        $this->assertSame('', $this->x11($ho, [$this->pta(['has_birth_certificate' => 'K'])])['MA_THE_TAM']);
        // PTA BHYT duoc uu tien dau tien du LOG_TIME cu hon
        $ptas = [
            $this->pta(['id' => 1, 'log_time' => 20260101000000, 'has_birth_certificate' => 'K']),
            $this->pta(['id' => 2, 'log_time' => 20260102000000, 'has_birth_certificate' => 'C', 'patient_type_code' => 'KHAC']),
        ];
        $this->assertSame('', $this->x11($ho, $ptas)['MA_THE_TAM']);
        // Sua loi DLL: khong co PTA nao thuoc ho so thi .First() nem loi (mat XML11) => rong
        $this->assertSame('', $this->x11($ho, [$this->pta(['treatment_id' => 99, 'has_birth_certificate' => 'C'])])['MA_THE_TAM']);
        $this->assertSame('', $this->x11($ho, [])['MA_THE_TAM']);
    }

    // ---------------- chung

    public function test_ma_ttdv_dung_helper()
    {
        $cac = [9 => new Xml9Dung(), 10 => new Xml10Dung(), 11 => new Xml11Dung()];
        $mo = function ($so, $x) {
            $ghi = ['treatment_end_type_ext_id' => $so === 10 ? 2 : 1, 'representative_hein_code' => 'RHC', 'hosp_subs_director_loginname' => 'gd'];
            $d = $this->du($ghi, [$this->beSinh()]);

            return [$d, $x->dung($d, $this->dm(), [])[0]['MA_TTDV']];
        };
        // key rong => DIPLOMA (CCHN) cua (pho) giam doc
        foreach ($cac as $so => $x) {
            list($d, $kq) = $mo($so, $x);
            $this->assertSame('CCHN-GD', $kq, "XML$so");
            $this->assertSame(MaTtdv::cua($d, $this->dm(), $so), $kq, "XML$so");
        }
        // key co gia tri: chua so bang => ma BHXH thu truong; khong chua => REPRESENTATIVE_HEIN_CODE
        DB::connection('HISPro')->table('his_config')->insert(['id' => 2, 'key' => MaTtdv::KEY, 'value' => '1, 9,10', 'is_active' => 1]);
        foreach ($cac as $so => $x) {
            list($d, $kq) = $mo($so, $x);
            $this->assertSame(MaTtdv::cua($d, $this->dm(), $so), $kq, "XML$so");
        }
        $this->assertSame('BHXH-GD', $mo(9, $cac[9])[1]);
        $this->assertSame('BHXH-GD', $mo(10, $cac[10])[1]);
        $this->assertSame('RHC', $mo(11, $cac[11])[1]);
    }

    public function test_thu_tu_khoa_dung_chi_tieu()
    {
        $this->assertSame(self::TRUONG_XML9, array_keys($this->x9()[0]));
        $this->assertSame(self::TRUONG_XML10, array_keys($this->x10()));
        $this->assertSame(self::TRUONG_XML11, array_keys($this->x11()));
    }
}

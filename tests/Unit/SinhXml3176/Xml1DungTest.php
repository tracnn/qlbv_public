<?php

namespace Tests\Unit\SinhXml3176;

use App\Services\SinhXml3176\Bang\LoiDungBang;
use App\Services\SinhXml3176\Bang\Xml1Dung;
use App\Services\SinhXml3176\DuLieuHoSo;
use App\Services\SinhXml3176\Nguon\HisDanhMuc;
use App\Services\SinhXml3176\Nguon\HisNapHoSo;
use Illuminate\Support\Facades\DB;
use Tests\Support\DungBangHisSinhXml3176Sqlite;
use Tests\TestCase;

class Xml1DungTest extends TestCase
{
    use DungBangHisSinhXml3176Sqlite;

    const TRUONG = [
        'MA_LK', 'STT', 'MA_BN', 'HO_TEN', 'SO_CCCD', 'NGAY_SINH', 'GIOI_TINH', 'NHOM_MAU', 'MA_QUOCTICH',
        'MA_DANTOC', 'MA_NGHE_NGHIEP', 'DIA_CHI', 'MATINH_CU_TRU', 'MAHUYEN_CU_TRU', 'MAXA_CU_TRU', 'DIEN_THOAI',
        'MA_THE_BHYT', 'MA_DKBD', 'GT_THE_TU', 'GT_THE_DEN', 'NGAY_MIEN_CCT', 'LY_DO_VV', 'LY_DO_VNT',
        'MA_LY_DO_VNT', 'CHAN_DOAN_VAO', 'CHAN_DOAN_RV', 'MA_BENH_CHINH', 'MA_BENH_KT', 'MA_BENH_YHCT',
        'MA_PTTT_QT', 'MA_DOITUONG_KCB', 'MA_NOI_DI', 'MA_NOI_DEN', 'MA_TAI_NAN', 'NGAY_VAO', 'NGAY_VAO_NOI_TRU',
        'NGAY_RA', 'GIAY_CHUYEN_TUYEN', 'SO_NGAY_DTRI', 'PP_DIEU_TRI', 'KET_QUA_DTRI', 'MA_LOAI_RV', 'GHI_CHU',
        'NGAY_TTOAN', 'T_THUOC', 'T_VTYT', 'T_TONGCHI_BV', 'T_TONGCHI_BH', 'T_BNTT', 'T_BNCCT', 'T_BHTT',
        'T_NGUONKHAC', 'T_BHTT_GDV', 'NAM_QT', 'THANG_QT', 'MA_LOAI_KCB', 'MA_KHOA', 'MA_CSKCB', 'MA_KHUVUC',
        'CAN_NANG', 'CAN_NANG_CON', 'NAM_NAM_LIEN_TUC', 'NGAY_TAI_KHAM', 'MA_HSBA', 'MA_TTDV', 'DU_PHONG',
    ];

    protected function setUp()
    {
        parent::setUp();
        $this->dungBangHis();
        $his = DB::connection('HISPro');
        $his->table('his_patient_type')->insert([
            ['id' => 1, 'patient_type_code' => 'BHYT', 'patient_type_name' => 'BHYT'],
            ['id' => 2, 'patient_type_code' => 'VP', 'patient_type_name' => 'VP'],
        ]);
        $his->table('his_employee')->insert([
            ['id' => 1, 'loginname' => 'gd', 'diploma' => 'CCHN-GD', 'social_insurance_number' => 'BHXH-GD'],
            ['id' => 2, 'loginname' => 'pgd', 'diploma' => 'CCHN-PGD', 'social_insurance_number' => 'BHXH-PGD'],
        ]);
        $his->table('his_icd')->insert([
            ['id' => 1, 'icd_code' => 'J18', 'icd_name' => 'Viem phoi', 'is_latent_tuberculosis' => null],
            ['id' => 2, 'icd_code' => 'A15', 'icd_name' => 'Lao', 'is_latent_tuberculosis' => 1],
        ]);
        $his->table('his_medi_org')->insert([
            ['id' => 1, 'medi_org_code' => '01929', 'medi_org_name' => 'BV', 'level_code' => '2'],
        ]);
    }

    private function dm(array $cauHinh = [])
    {
        $cauHinh = array_merge([
            HisDanhMuc::KEY_MA_DOI_TUONG_BHYT => 'BHYT',
            Xml1Dung::KEY_BHYT => 'BHYT',
        ], $cauHinh);
        $i = 1;
        foreach ($cauHinh as $k => $v) {
            DB::connection('HISPro')->table('his_config')->insert(['id' => $i++, 'key' => $k, 'value' => $v, 'is_active' => 1]);
        }

        return HisDanhMuc::napChoLuot('HISPro');
    }

    private function hoSo(array $ghi = [])
    {
        return (object) array_merge(array_fill_keys(HisNapHoSo::COT_HO_SO, null), [
            'id' => 5, 'treatment_code' => 'HS5', 'tdl_patient_code' => 'BN5', 'tdl_patient_name' => 'Nguyen Van A',
            'tdl_patient_dob' => 19800102000000, 'tdl_patient_gender_id' => 2, 'tdl_patient_mps_national_code' => '000',
            'ethnic_code' => '01', 'tdl_patient_address' => 'Dien Bien', 'tdl_patient_province_code' => '11',
            'tdl_patient_commune_code' => '03199', 'hein_medi_org_code' => '01929', 'tdl_treatment_type_id' => 1,
            'tdl_patient_type_id' => 1, 'icd_code' => 'J18', 'icd_name' => 'Viem phoi',
            'in_time' => 20261001080000, 'out_time' => 20261001100000, 'fee_lock_time' => 20261001110000,
            'hein_lock_time' => 20261002000000, 'treatment_result_code' => '01', 'treatment_end_type_id' => 1,
            'hein_patient_type_code' => '1.1',
        ], $ghi);
    }

    private function the(array $ghi = [])
    {
        return (object) array_merge(array_fill_keys(HisNapHoSo::COT_DIEN_DIEU_TRI, null), [
            'id' => 1, 'treatment_id' => 5, 'log_time' => 20261001080000, 'patient_type_id' => 1,
            'patient_type_code' => 'BHYT', 'hein_card_number' => 'DN4010000000001', 'hein_medi_org_code' => '01929',
            'hein_card_from_time' => 20260101000000, 'hein_card_to_time' => 20261231000000, 'right_route_code' => 'DT',
        ], $ghi);
    }

    private function du(array $ghiHoSo = [], array $the = null)
    {
        $d = new DuLieuHoSo($this->hoSo($ghiHoSo));
        $d->dienDieuTri = $the === null ? [$this->the()] : $the;

        return $d;
    }

    private function dung(DuLieuHoSo $d, HisDanhMuc $dm = null, array $daDung = [])
    {
        $ra = (new Xml1Dung(new \DateTime('2026-10-22 09:00:00')))->dung($d, $dm ?: $this->dm(), $daDung);
        $this->assertCount(1, $ra);

        return $ra[0];
    }

    private function dongTien($bv, $bh, $bhtt, $bncct, $bntt, $nk, $maVatTu = null)
    {
        $r = [
            'THANH_TIEN_BV' => $bv, 'THANH_TIEN_BH' => $bh, 'T_BHTT' => $bhtt, 'T_BNCCT' => $bncct,
            'T_BNTT' => $bntt, 'T_NGUONKHAC' => $nk, 'MA_PTTT' => '1',
        ];
        if ($maVatTu !== null) {
            $r['MA_VAT_TU'] = $maVatTu;
        }

        return $r;
    }

    public function test_tong_tien_bang_tong_xml2_xml3()
    {
        $daDung = [
            'XML2' => [
                $this->dongTien('15000.00', '15000.00', '12000.00', '3000.00', '0.00', '0.00'),
                $this->dongTien('0.10', '0.10', '0.08', '0.02', '0.00', '0.00'),
            ],
            'XML3' => [
                $this->dongTien('100000.00', '100000.00', '80000.00', '20000.00', '0.00', '0.00', ''),
                $this->dongTien('0.20', '0.20', '0.16', '0.04', '0.00', '0.00', 'VT01'),
                $this->dongTien('5000.00', '0.00', '0.00', '0.00', '4000.00', '1000.00', 'VT02'),
            ],
        ];
        $r = $this->dung($this->du(), null, $daDung);

        $this->assertSame('15000.10', $r['T_THUOC']);
        $this->assertSame('5000.20', $r['T_VTYT']);
        $this->assertSame('120000.30', $r['T_TONGCHI_BV']);
        $this->assertSame('115000.30', $r['T_TONGCHI_BH']);
        $this->assertSame('92000.24', $r['T_BHTT']);
        $this->assertSame('23000.06', $r['T_BNCCT']);
        $this->assertSame('4000.00', $r['T_BNTT']);
        $this->assertSame('1000.00', $r['T_NGUONKHAC']);
        $this->assertSame('92000.24', $r['T_BHTT_GDV']);

        // Khong co XML2/XML3 => 0.00
        $r = $this->dung($this->du(), null, ['XML2' => [], 'XML3' => []]);
        $this->assertSame('0.00', $r['T_THUOC']);
        $this->assertSame('0.00', $r['T_TONGCHI_BV']);

        // The quan doi/cong an: T_BHTT_GDV = 0
        $r = $this->dung($this->du([], [$this->the(['hein_card_number' => 'QN4010000000001'])]), null, $daDung);
        $this->assertSame('0.00', $r['T_BHTT_GDV']);
    }

    public function test_chan_doan_rv_khong_dau_cham_phay_thua()
    {
        $this->assertSame('Viem phoi', $this->dung($this->du(['icd_text' => null]))['CHAN_DOAN_RV']);
        $this->assertSame('Viem phoi', $this->dung($this->du(['icd_text' => '']))['CHAN_DOAN_RV']);
        $this->assertSame('Viem phoi;Tang huyet ap', $this->dung($this->du(['icd_text' => 'Tang huyet ap']))['CHAN_DOAN_RV']);
        $this->assertSame('Viem phoi;Tang huyet ap;Dai thao duong',
            $this->dung($this->du(['icd_text' => ';Tang huyet ap;Dai thao duong;']))['CHAN_DOAN_RV']);
        $this->assertSame('Tang huyet ap', $this->dung($this->du(['icd_name' => null, 'icd_text' => 'Tang huyet ap']))['CHAN_DOAN_RV']);
    }

    public function test_the_bhyt_nhieu_the_giu_dung_vi_tri()
    {
        $the = [
            $this->the(['id' => 2, 'log_time' => 20261001090000, 'hein_card_number' => 'HN4010000000002',
                'hein_medi_org_code' => null, 'hein_card_from_time' => 20260601000000, 'hein_card_to_time' => 20261130000000]),
            $this->the(['id' => 1]),
            // dien dieu tri vien phi khong co the: khong chiem vi tri
            $this->the(['id' => 3, 'patient_type_id' => 2, 'patient_type_code' => 'VP', 'hein_card_number' => null,
                'hein_medi_org_code' => null, 'hein_card_from_time' => null, 'hein_card_to_time' => null]),
        ];
        $r = $this->dung($this->du([], $the));

        $this->assertSame('DN4010000000001;HN4010000000002', $r['MA_THE_BHYT']);
        $this->assertSame('01929;', $r['MA_DKBD']);
        $this->assertSame('20260101;20260601', $r['GT_THE_TU']);
        $this->assertSame('20261231;20261130', $r['GT_THE_DEN']);

        // SORT_MA_THE_BHYT = 1: LOG_TIME giam dan
        $r = $this->dung($this->du([], $the), $this->dm([Xml1Dung::KEY_SAP_THE => '1']));
        $this->assertSame('HN4010000000002;DN4010000000001', $r['MA_THE_BHYT']);
        $this->assertSame(';01929', $r['MA_DKBD']);
        $this->assertSame('20260601;20260101', $r['GT_THE_TU']);
    }

    public function test_ma_benh_yhct_tach_va_bo_trung()
    {
        $r = $this->dung($this->du([
            'traditional_icd_code' => 'U50.001;U50.002', 'traditional_icd_sub_code' => 'U50.002;U51.1;;U50.001',
        ]));
        $this->assertSame('U50.001;U50.002;U51.1', $r['MA_BENH_YHCT']);

        $ma = [];
        for ($i = 1; $i <= 15; $i++) {
            $ma[] = sprintf('U%02d', $i);
        }
        $r = $this->dung($this->du(['traditional_icd_code' => 'U01', 'traditional_icd_sub_code' => implode(';', $ma)]));
        $this->assertSame(implode(';', array_slice($ma, 0, 13)), $r['MA_BENH_YHCT']);
    }

    public function test_so_cccd_roi_ve_cmnd_ho_chieu()
    {
        $r = $this->dung($this->du(['tdl_patient_cccd_number' => '', 'tdl_patient_cmnd_number' => '012345678']));
        $this->assertSame('012345678', $r['SO_CCCD']);

        $r = $this->dung($this->du(['tdl_patient_passport_number' => 'B1234567']));
        $this->assertSame('B1234567', $r['SO_CCCD']);

        $r = $this->dung($this->du(['tdl_patient_cccd_number' => '011080000001', 'tdl_patient_cmnd_number' => '012345678']));
        $this->assertSame('011080000001', $r['SO_CCCD']);

        $this->assertSame('', $this->dung($this->du())['SO_CCCD']);
    }

    public function test_thieu_icd_danh_muc_nem_loi_dung_bang_khong_nem_null()
    {
        // ICD chinh khong co trong danh muc: DLL coi nhu null (X1P:285, 293), khong nem, van suy ma
        $d = $this->du(['hein_patient_type_code' => '', 'icd_code' => 'Z99', 'hein_medi_org_code' => '01001'],
            [$this->the(['hein_medi_org_code' => '01929'])]);
        $this->assertSame('9', $this->dung($d)['MA_DOITUONG_KCB']);

        // Danh muc co dong ICD_CODE null khong lam vo
        DB::connection('HISPro')->table('his_icd')->insert(['id' => 3, 'icd_code' => null, 'icd_name' => 'Rong', 'is_latent_tuberculosis' => 1]);
        $d = $this->du(['hein_patient_type_code' => '', 'icd_code' => 'Z99', 'icd_sub_code' => 'Z98', 'hein_medi_org_code' => '01001'],
            [$this->the(['hein_medi_org_code' => '01929'])]);
        $this->assertSame('9', $this->dung($d)['MA_DOITUONG_KCB']);

        // Thieu thoi gian vao vien
        try {
            $this->dung($this->du(['in_time' => null]));
            $this->fail('Phai nem LoiDungBang');
        } catch (LoiDungBang $e) {
            $this->assertSame('XML1', $e->loaiBang());
        }

        // SO_NGAY_DTRI_OPTION=1 ma TREATMENT_DAY_COUNT null
        try {
            $this->dung($this->du(['tdl_treatment_type_id' => 3, 'treatment_day_count' => null]),
                $this->dm([Xml1Dung::KEY_SO_NGAY => '1']));
            $this->fail('Phai nem LoiDungBang');
        } catch (LoiDungBang $e) {
            $this->assertSame('XML1', $e->loaiBang());
        }
    }

    public function test_khong_co_dien_dieu_tri_khong_vo()
    {
        $r = $this->dung($this->du(['hein_patient_type_code' => null], []));
        $this->assertSame('', $r['MA_THE_BHYT']);
        $this->assertSame('9', $r['MA_DOITUONG_KCB']);
    }

    public function test_thu_tu_khoa_dung_chi_tieu()
    {
        $r = $this->dung($this->du());
        $this->assertSame(self::TRUONG, array_keys($r));
        foreach ($r as $k => $v) {
            $this->assertInternalType('string', $v, $k);
        }
        $this->assertSame('HS5', $r['MA_LK']);
        $this->assertSame('1', $r['STT']);
        $this->assertSame('198001020000', $r['NGAY_SINH']);
        $this->assertSame('1', $r['GIOI_TINH']);
        $this->assertSame('202610010800', $r['NGAY_VAO']);
        $this->assertSame('202610011000', $r['NGAY_RA']);
        $this->assertSame('202610011100', $r['NGAY_TTOAN']);
        $this->assertSame('2026', $r['NAM_QT']);
        $this->assertSame('10', $r['THANG_QT']);
        $this->assertSame('01', $r['MA_LOAI_KCB']);
        $this->assertSame('0', $r['SO_NGAY_DTRI']);
        $this->assertSame('1', $r['KET_QUA_DTRI']);
        $this->assertSame('0', $r['MA_TAI_NAN']);
        $this->assertSame('HS5', $r['MA_HSBA']);
    }

    public function test_ma_ttdv_cchn_giam_doc_hoac_theo_key()
    {
        $d = $this->du(['hosp_subs_director_loginname' => 'pgd', 'hospital_director_loginname' => 'gd',
            'representative_hein_code' => 'RHC']);
        $this->assertSame('CCHN-PGD', $this->dung($d)['MA_TTDV']);

        // Pho giam doc rong => giam doc
        $d2 = $this->du(['hosp_subs_director_loginname' => '', 'hospital_director_loginname' => 'gd']);
        $this->assertSame('CCHN-GD', $this->dung($d2)['MA_TTDV']);

        // Key MA_TTDV_FROM_PROFILE_XML chua "1": ma BHXH nhu nhanh 130
        $this->assertSame('BHXH-PGD', $this->dung($d, $this->dm([Xml1Dung::KEY_MA_TTDV => '2,1']))['MA_TTDV']);
    }

    public function test_ma_ttdv_key_khac_1_lay_representative_hein_code()
    {
        $d = $this->du(['hosp_subs_director_loginname' => 'pgd', 'representative_hein_code' => 'RHC']);
        $this->assertSame('RHC', $this->dung($d, $this->dm([Xml1Dung::KEY_MA_TTDV => '2']))['MA_TTDV']);

        // Key chua "1" ma khong co loginname giam doc => REPRESENTATIVE_HEIN_CODE
        DB::connection('HISPro')->table('his_config')->delete();
        $d = $this->du(['representative_hein_code' => 'RHC']);
        $this->assertSame('RHC', $this->dung($d, $this->dm([Xml1Dung::KEY_MA_TTDV => '1']))['MA_TTDV']);
    }

    public function test_doi_tuong_kcb_suy_khi_ma_rong()
    {
        // HEIN_PATIENT_TYPE_CODE rong => suy; dung noi DKBD => 1.1
        $r = $this->dung($this->du(['hein_patient_type_code' => '']));
        $this->assertSame('1.1', $r['MA_DOITUONG_KCB']);

        // Lao tiem an qua ICD phu
        $r = $this->dung($this->du(['hein_patient_type_code' => null, 'hein_medi_org_code' => '01001',
            'icd_sub_code' => 'A15'], [$this->the(['hein_medi_org_code' => '0'])]));
        $this->assertSame('1.1', $r['MA_DOITUONG_KCB']);

        // Trai tuyen, the HN
        $r = $this->dung($this->du(['hein_patient_type_code' => null],
            [$this->the(['right_route_code' => 'TT', 'hein_card_number' => 'HN4010000000002'])]));
        $this->assertSame('3.6', $r['MA_DOITUONG_KCB']);
    }

    public function test_doi_tuong_kcb_cac_nhanh_con_lai()
    {
        $suy = function (array $hs, array $the) {
            $hs = array_merge(['hein_patient_type_code' => null, 'hein_medi_org_code' => '01001'], $hs);

            return $this->dung($this->du($hs, [$this->the($the)]))['MA_DOITUONG_KCB'];
        };
        $this->assertSame('2', $suy([], ['right_route_type_code' => 'CC']));
        $this->assertSame('1.2', $suy([], ['has_birth_certificate' => 'C']));
        $this->assertSame('1.4', $suy([], ['has_working_letter' => 1]));
        $this->assertSame('9', $suy([], []));
        $this->assertSame('3.1', $suy(['hein_level_code' => '1', 'tdl_treatment_type_id' => 3], ['right_route_code' => 'TT']));
        $this->assertSame('1.15', $suy(['hein_level_code' => '2', 'tdl_treatment_type_id' => 3], ['right_route_code' => 'TT']));
        $this->assertSame('3.3', $suy(['hein_level_code' => '3', 'hein_medi_org_code' => '02001'], ['right_route_code' => 'TT']));
        $this->assertSame('7.1', $suy(['tdl_treatment_type_id' => 6], ['right_route_code' => null]));
        // Nhu DLL: DKBD "" (khac null) == ma co so "" => dung tuyen 1.1
        $this->assertSame('1.1', $suy(['hein_medi_org_code' => ''], ['hein_medi_org_code' => '']));
    }

    public function test_chan_doan_vao_so_bo_rong_lay_icd_name()
    {
        $this->assertSame('Viem phoi', $this->dung($this->du(['provisional_diagnosis' => '']))['CHAN_DOAN_VAO']);
        $this->assertSame('Sot', $this->dung($this->du(['provisional_diagnosis' => 'Sot']))['CHAN_DOAN_VAO']);
    }

    public function test_loai_kcb_noi_tru_va_so_ngay()
    {
        $r = $this->dung($this->du(['tdl_treatment_type_id' => 3, 'clinical_in_time' => 20261001090000,
            'out_time' => 20261005100000]));
        $this->assertSame('03', $r['MA_LOAI_KCB']);
        $this->assertSame('5', $r['SO_NGAY_DTRI']);
        $this->assertSame('202610010900', $r['NGAY_VAO_NOI_TRU']);

        $r = $this->dung($this->du(['tdl_treatment_type_id' => 3, 'clinical_in_time' => 20261001090000,
            'out_time' => 20261001120000]));
        $this->assertSame('09', $r['MA_LOAI_KCB']);
        $this->assertSame('0', $r['SO_NGAY_DTRI']);

        $r = $this->dung($this->du(['tdl_treatment_type_id' => 3, 'treatment_day_count' => 7.6]),
            $this->dm([Xml1Dung::KEY_SO_NGAY => '1']));
        $this->assertSame('7', $r['SO_NGAY_DTRI']);
    }

    public function test_ma_khoa_man_tinh_va_ksk()
    {
        DB::connection('HISPro')->table('his_department')->insert([
            ['id' => 7, 'department_code' => 'NOI', 'bhyt_code' => 'K02'],
            ['id' => 8, 'department_code' => 'NGOAI', 'bhyt_code' => 'K19'],
        ]);
        $d = $this->du(['department_ids' => '7,8', 'co_department_ids' => '8;9', 'end_room_bhyt_code' => '',
            'exit_bhyt_code' => 'K03']);
        $this->assertSame('K03', $this->dung($d)['MA_KHOA']);
        $this->assertSame('K02;K19', $this->dung($d, $this->dm([Xml1Dung::KEY_MA_KHOA => '1']))['MA_KHOA']);

        // Ngoai tru man tinh: co dich vu ngoai kham/don => 08, chi kham => 05
        $dv = function ($loaiYc) {
            return (object) array_merge(array_fill_keys(HisNapHoSo::COT_DICH_VU, null), [
                'id' => $loaiYc, 'patient_type_id' => 2, 'tdl_hein_service_type_id' => 1, 'amount' => 1, 'price' => 100,
                'tdl_service_req_type_id' => $loaiYc,
            ]);
        };
        $d = $this->du(['tdl_treatment_type_id' => 2, 'is_chronic' => 1]);
        $d->dichVu = [$dv(1)];
        $this->assertSame('05', $this->dung($d)['MA_LOAI_KCB']);
        $d->dichVu = [$dv(1), $dv(2)];
        $this->assertSame('08', $this->dung($d)['MA_LOAI_KCB']);

        // KSK
        $d = $this->du(['tdl_patient_type_id' => 3], [$this->the(['patient_type_id' => 3, 'patient_type_code' => 'KSK'])]);
        $this->assertSame('100', $this->dung($d, $this->dm([Xml1Dung::KEY_KSK => 'KSK']))['MA_LOAI_KCB']);
    }

    public function test_can_nang_chieu_cao_va_cat_do_dai()
    {
        $d = $this->du(['tdl_patient_name' => str_repeat('Ă', 300), 'icd_sub_code' => implode(';', array_fill(0, 14, 'K29.123456789'))]);
        $d->dhst = [
            (object) ['id' => 1, 'treatment_id' => 5, 'execute_time' => 20261001080000, 'weight' => 60.456, 'height' => 165.555],
            (object) ['id' => 2, 'treatment_id' => 5, 'execute_time' => 20261001090000, 'weight' => 0, 'height' => null],
        ];
        $d->soSinh = [(object) ['id' => 1, 'treatment_id' => 5, 'weight' => 3200, 'hein_card_number_tmp' => 'X']];
        $r = $this->dung($d);

        $this->assertSame('60.46', $r['CAN_NANG']);
        $this->assertSame('165.56', $r['DU_PHONG']);
        $this->assertSame('3200', $r['CAN_NANG_CON']);
        $this->assertSame(255, mb_strlen($r['HO_TEN'], 'UTF-8'));
        // MA_BENH_KT: toi da 12 ma va 100 ky tu, cat o ranh gioi ma
        $this->assertLessThanOrEqual(100, strlen($r['MA_BENH_KT']));
        $this->assertSame(7, count(explode(';', $r['MA_BENH_KT'])));
    }
}

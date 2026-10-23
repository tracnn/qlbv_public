<?php

namespace Tests\Unit\SinhXml3176;

use App\Services\SinhXml3176\CauHinhSinhXml3176;
use App\Services\SinhXml3176\DuLieuHoSo;
use App\Services\SinhXml3176\Nguon\HisDanhMuc;
use App\Services\SinhXml3176\Nguon\HisNapHoSo;
use App\Services\SinhXml3176\Nguon\HisUngVien;
use Illuminate\Support\Facades\DB;
use Tests\Support\DungBangHisSinhXml3176Sqlite;
use Tests\TestCase;

class HisNapHoSoTest extends TestCase
{
    use DungBangHisSinhXml3176Sqlite;

    private $idDichVu = 0;

    protected function setUp()
    {
        parent::setUp();
        $this->dungBangHis();
        DB::connection('HISPro')->table('his_patient_type')->insert([
            ['id' => 1, 'patient_type_code' => 'BHYT', 'patient_type_name' => 'Bao hiem y te'],
            ['id' => 2, 'patient_type_code' => 'VP', 'patient_type_name' => 'Vien phi'],
        ]);
    }

    private function napHoSo(array $khoi = [])
    {
        config(['organization.sinh_xml3176' => array_merge(['ket_noi_his' => 'HISPro'], $khoi)]);
        $cauHinh = CauHinhSinhXml3176::tuConfig();
        $ungVien = new HisUngVien($cauHinh);
        $ungVien->doiMa();

        return new HisNapHoSo($cauHinh, $ungVien);
    }

    /** Them mot ho so day du cac bang con; tra ve treatment id. */
    private function themHoSo($id, $soDichVu = 3, $doiTuong = 1)
    {
        $his = DB::connection('HISPro');
        $his->table('v_his_treatment_12')->insert([
            'id' => $id, 'treatment_code' => 'HS' . $id, 'tdl_patient_name' => 'BN ' . $id,
        ]);
        $his->table('v_his_patient_type_alter')->insert([
            ['id' => $id * 10 + 1, 'treatment_id' => $id, 'log_time' => 20261001080000, 'is_delete' => 0],
            ['id' => $id * 10 + 2, 'treatment_id' => $id, 'log_time' => 20261002080000, 'is_delete' => 0],
        ]);
        for ($i = 0; $i < $soDichVu; $i++) {
            $this->idDichVu++;
            $his->table('v_his_sere_serv_2')->insert([
                'id' => $this->idDichVu, 'tdl_treatment_id' => $id, 'patient_type_id' => $doiTuong,
                'ekip_id' => $id, 'is_delete' => 0, 'tdl_hein_service_type_id' => 1, 'amount' => 1, 'price' => 1000,
            ]);
        }
        $his->table('his_ekip_user')->insert(['id' => $id, 'ekip_id' => $id, 'loginname' => 'bs' . $id, 'is_delete' => 0]);
        $his->table('v_his_bed_log')->insert(['id' => $id, 'treatment_id' => $id, 'is_delete' => 0]);
        $his->table('v_his_sere_serv_tein')->insert(['id' => $id, 'tdl_treatment_id' => $id, 'sere_serv_id' => $this->idDichVu, 'is_delete' => 0]);
        $his->table('v_his_sere_serv_pttt')->insert(['id' => $id, 'tdl_treatment_id' => $id, 'sere_serv_id' => $this->idDichVu, 'is_delete' => 0]);
        $his->table('his_dhst')->insert(['id' => $id, 'treatment_id' => $id, 'weight' => 50, 'is_delete' => 0]);
        $his->table('v_his_baby')->insert(['id' => $id, 'treatment_id' => $id, 'weight' => 3, 'is_delete' => 0]);
        $his->table('his_exp_medimate_used')->insert(['id' => $id, 'tdl_treatment_id' => $id, 'amount' => 1, 'is_delete' => 0]);

        return $id;
    }

    private function idCac(array $dong, $cot = 'id')
    {
        return array_map(function ($d) use ($cot) {
            return (int) $d->$cot;
        }, $dong);
    }

    public function test_nap_gom_dung_ho_so()
    {
        $this->themHoSo(101);
        $this->themHoSo(102);

        $kq = $this->napHoSo()->nap([101, 102]);

        $this->assertSame([101, 102], array_keys($kq));
        foreach ([101, 102] as $id) {
            $this->assertInstanceOf(DuLieuHoSo::class, $kq[$id]);
            $this->assertSame('HS' . $id, $kq[$id]->hoSo->treatment_code);
            $this->assertCount(3, $kq[$id]->dichVu);
            foreach ($kq[$id]->dichVu as $dv) {
                $this->assertEquals($id, $dv->tdl_treatment_id);
            }
            // PTA sap log_time desc, id desc
            $this->assertSame([$id * 10 + 2, $id * 10 + 1], $this->idCac($kq[$id]->dienDieuTri));
            $this->assertSame(['bs' . $id], array_map(function ($e) {
                return $e->loginname;
            }, $kq[$id]->ekip));
            $this->assertCount(1, $kq[$id]->giuong);
            $this->assertCount(1, $kq[$id]->ketQuaCls);
            $this->assertCount(1, $kq[$id]->pttt);
            $this->assertCount(1, $kq[$id]->dhst);
            $this->assertCount(1, $kq[$id]->soSinh);
            $this->assertCount(1, $kq[$id]->suDungThuoc);
            $this->assertNull($kq[$id]->hivTreatment);
            $this->assertNull($kq[$id]->dieuTriLao);
        }
    }

    public function test_bang_con_mac_dinh_mang_rong()
    {
        DB::connection('HISPro')->table('v_his_treatment_12')->insert(['id' => 7, 'treatment_code' => 'HS7']);

        $kq = $this->napHoSo()->nap([7]);

        $this->assertSame([], $kq[7]->dienDieuTri);
        $this->assertSame([], $kq[7]->dichVu);
        $this->assertSame([], $kq[7]->ekip);
        $this->assertSame([], $kq[7]->suDungThuoc);
        $this->assertSame([], $this->napHoSo()->nap([]));
    }

    public function test_loc_dich_vu_theo_doi_tuong_tt()
    {
        $this->themHoSo(201, 2, 1);
        $this->themHoSo(202, 2, 2);
        DB::connection('HISPro')->table('v_his_sere_serv_2')->insert([
            'id' => 999, 'tdl_treatment_id' => 201, 'patient_type_id' => 1, 'is_delete' => 1,
        ]);

        $kq = $this->napHoSo(['doi_tuong_tt' => ['BHYT']])->nap([201, 202]);

        $this->assertCount(2, $kq[201]->dichVu);
        $this->assertNotContains(999, $this->idCac($kq[201]->dichVu));
        $this->assertArrayHasKey(202, $kq);
        $this->assertSame([], $kq[202]->dichVu);

        // Khong cau hinh doi_tuong_tt => khong loc (nhung van bo dong is_delete=1)
        $tatCa = $this->napHoSo()->nap([201, 202]);
        $this->assertCount(2, $tatCa[201]->dichVu);
        $this->assertCount(2, $tatCa[202]->dichVu);
    }

    public function test_loc_dau_vao_nhu_plugin_his()
    {
        $this->themHoSo(301, 1, 1);
        $his = DB::connection('HISPro');
        $mau = ['tdl_treatment_id' => 301, 'patient_type_id' => 1, 'is_delete' => 0, 'tdl_hein_service_type_id' => 1,
            'amount' => 1, 'price' => 1000, 'ekip_id' => 777];
        $his->table('v_his_sere_serv_2')->insert([
            array_merge($mau, ['id' => 901, 'is_no_execute' => 1]),
            array_merge($mau, ['id' => 902, 'is_expend' => 1]),
            array_merge($mau, ['id' => 903, 'price' => 0]),
            array_merge($mau, ['id' => 904, 'amount' => 0]),
            array_merge($mau, ['id' => 905, 'tdl_hein_service_type_id' => null]),
        ]);
        $his->table('his_ekip_user')->insert(['id' => 777, 'ekip_id' => 777, 'loginname' => 'bs777', 'is_delete' => 0]);

        $kq = $this->napHoSo()->nap([301]);

        $this->assertSame([$this->idDichVu], $this->idCac($kq[301]->dichVu)); // chi con dong hop le cua themHoSo
        $loginEkip = array_map(function ($e) {
            return $e->loginname;
        }, $kq[301]->ekip);
        $this->assertContains('bs777', $loginEkip); // ekip gan tu danh sach chua loc, nhu plugin HIS
    }

    public function test_ma_doi_tuong_bhyt_khong_phai_so()
    {
        DB::connection('HISPro')->table('his_config')->insert([
            ['id' => 1, 'key' => 'MOS.HIS_PATIENT_TYPE.PATIENT_TYPE_CODE.BHYT', 'value' => 'VP', 'default_value' => null, 'is_active' => 0],
            ['id' => 2, 'key' => 'MOS.HIS_PATIENT_TYPE.PATIENT_TYPE_CODE.BHYT', 'value' => ' BHYT ', 'default_value' => null, 'is_active' => 1],
            ['id' => 3, 'key' => 'HIS.QD_130_BYT.XML3.NGAY_KQ_OPTION', 'value' => '', 'default_value' => '1', 'is_active' => 1],
        ]);

        $dm = HisDanhMuc::napChoLuot('HISPro');

        $this->assertSame(1, $dm->idDoiTuongBhyt());
        $this->assertSame('BHYT', $dm->config('MOS.HIS_PATIENT_TYPE.PATIENT_TYPE_CODE.BHYT'));
        $this->assertSame('1', $dm->config('HIS.QD_130_BYT.XML3.NGAY_KQ_OPTION'));
        $this->assertNull($dm->config('KHONG.CO'));
        $this->assertSame('VP', $dm->doiTuong(2)->patient_type_code);
        $this->assertNull($dm->doiTuong(3));
    }

    public function test_config_nhieu_dong_lay_id_lon_nhat()
    {
        DB::connection('HISPro')->table('his_config')->insert([
            ['id' => 5, 'key' => 'K', 'value' => 'moi', 'default_value' => null, 'is_active' => 1],
            ['id' => 4, 'key' => 'K', 'value' => 'cu', 'default_value' => null, 'is_active' => 1],
        ]);

        $this->assertSame('moi', HisDanhMuc::napChoLuot('HISPro')->config('K'));
    }

    public function test_ma_bhyt_khong_co_trong_danh_muc_tra_null()
    {
        DB::connection('HISPro')->table('his_config')->insert([
            ['id' => 1, 'key' => 'MOS.HIS_PATIENT_TYPE.PATIENT_TYPE_CODE.BHYT', 'value' => 'XX', 'default_value' => null, 'is_active' => 1],
        ]);

        $this->assertNull(HisDanhMuc::napChoLuot('HISPro')->idDoiTuongBhyt());
    }

    public function test_tra_danh_muc()
    {
        $his = DB::connection('HISPro');
        $his->table('his_employee')->insert(['id' => 1, 'loginname' => 'bs1', 'diploma' => 'CCHN1']);
        $his->table('his_icd')->insert(['id' => 1, 'icd_code' => 'A15', 'icd_name' => 'Lao']);
        $his->table('his_medi_org')->insert(['id' => 1, 'medi_org_code' => '01001', 'level_code' => '1']);
        $his->table('his_material_type')->insert(['id' => 1, 'service_id' => 55, 'is_reusable' => 1]);
        $his->table('his_service')->insert([
            ['id' => 66, 'petroleum_code' => 'XD1'],
            ['id' => 67, 'petroleum_code' => null],
        ]);
        $his->table('his_department')->insert(['id' => 3, 'bhyt_code' => 'K01']);

        $dm = HisDanhMuc::napChoLuot('HISPro');

        $this->assertSame('CCHN1', $dm->nhanVien('bs1')->diploma);
        $this->assertNull($dm->nhanVien('khong'));
        $this->assertSame('Lao', $dm->icd('A15')->icd_name);
        $this->assertSame('1', (string) $dm->noiKcb('01001')->level_code);
        $this->assertEquals(1, $dm->loaiVatTu(55)->is_reusable);
        $this->assertNull($dm->loaiVatTu(56));
        $this->assertSame('XD1', $dm->maXangDau(66));
        $this->assertNull($dm->maXangDau(67));
        $this->assertSame('K01', $dm->khoa(3)->bhyt_code);
    }

    public function test_nap_theo_doi_hoi_chan_chi_so_cdha()
    {
        $this->themHoSo(401, 1);
        $this->themHoSo(402, 1);
        $his = DB::connection('HISPro');
        // Chen tung dong: insert nhieu dong lay cot theo dong dau, dong thieu cot se lech gia tri
        foreach ([
            ['id' => 41, 'treatment_id' => 401, 'tracking_time' => 20261001080000, 'content' => 'DB 401', 'care_instruction' => 'CS',
                'disease_stage' => 'GD', 'creator' => 'dd1', 'modifier' => 'bs1', 'is_delete' => 0],
            ['id' => 42, 'treatment_id' => 402, 'tracking_time' => 20261001080000, 'content' => 'DB 402', 'is_delete' => 0],
            ['id' => 43, 'treatment_id' => 401, 'tracking_time' => 20261002080000, 'content' => 'xoa', 'is_delete' => 1],
        ] as $d) {
            $his->table('his_tracking')->insert($d);
        }
        $his->table('his_debate')->insert([
            ['id' => 51, 'treatment_id' => 401, 'tracking_id' => 41, 'conclusion' => 'KL 401', 'is_delete' => 0],
            ['id' => 52, 'treatment_id' => 402, 'tracking_id' => 42, 'conclusion' => 'KL 402', 'is_delete' => 0],
        ]);
        foreach ([
            ['id' => 61, 'tdl_treatment_id' => 401, 'sere_serv_id' => 7, 'suim_index_code' => 'S1', 'suim_index_name' => 'Chi so 1',
                'suim_index_unit_name' => 'mm', 'value' => '12', 'description' => 'MT', 'is_delete' => 0],
            ['id' => 62, 'tdl_treatment_id' => 402, 'sere_serv_id' => 8, 'suim_index_code' => 'S2', 'is_delete' => 0],
        ] as $d) {
            $his->table('v_his_sere_serv_suin')->insert($d);
        }

        $kq = $this->napHoSo()->nap([401, 402]);

        $this->assertSame([41], $this->idCac($kq[401]->theoDoi));
        $this->assertSame([42], $this->idCac($kq[402]->theoDoi));
        $td = $kq[401]->theoDoi[0];
        $this->assertSame('DB 401', $td->content);
        $this->assertSame('CS', $td->care_instruction);
        $this->assertSame('GD', $td->disease_stage);
        $this->assertSame('dd1', $td->creator);
        $this->assertSame('bs1', $td->modifier);

        $this->assertSame([51], $this->idCac($kq[401]->hoiChan));
        $this->assertSame([52], $this->idCac($kq[402]->hoiChan));
        $this->assertSame('KL 401', $kq[401]->hoiChan[0]->conclusion);
        $this->assertEquals(41, $kq[401]->hoiChan[0]->tracking_id);

        $this->assertSame([61], $this->idCac($kq[401]->chiSoCdha));
        $this->assertSame([62], $this->idCac($kq[402]->chiSoCdha));
        $cs = $kq[401]->chiSoCdha[0];
        $this->assertEquals(7, $cs->sere_serv_id);
        $this->assertSame(['S1', 'Chi so 1', 'mm', '12', 'MT'], [
            $cs->suim_index_code, $cs->suim_index_name, $cs->suim_index_unit_name, $cs->value, $cs->description,
        ]);

        // Cot moi cho XML4/XML5 co tren dong dich vu, ket qua CLS, DHST
        foreach (['description', 'conclude', 'result_read_time', 'tracking_id', 'hospitalization_reason',
            'pathological_process', 'pathological_history', 'pathological_history_family', 'full_exam', 'part_exam',
            'subclinical', 'treatment_instruction'] as $c) {
            $this->assertObjectHasAttribute($c, $kq[401]->dichVu[0]);
        }
        foreach (['bhyt_code', 'bhyt_name', 'antibiotic_resistance_code', 'antibiotic_resistance_name', 'value',
            'test_index_unit_name', 'result_description'] as $c) {
            $this->assertObjectHasAttribute($c, $kq[401]->ketQuaCls[0]);
        }
        foreach (['pulse', 'temperature', 'breath_rate', 'spo2', 'weight', 'height', 'belly', 'chest', 'urine',
            'blood_pressure_max', 'blood_pressure_min', 'capillary_blood_glucose', 'tracking_id'] as $c) {
            $this->assertObjectHasAttribute($c, $kq[401]->dhst[0]);
        }

        // Mac dinh mang rong
        $his->table('v_his_treatment_12')->insert(['id' => 403, 'treatment_code' => 'HS403']);
        $rong = $this->napHoSo()->nap([403]);
        $this->assertSame([], $rong[403]->theoDoi);
        $this->assertSame([], $rong[403]->hoiChan);
        $this->assertSame([], $rong[403]->chiSoCdha);
    }

    public function test_theo_doi_sap_theo_tracking_time()
    {
        $this->themHoSo(501, 1);
        DB::connection('HISPro')->table('his_tracking')->insert([
            ['id' => 3, 'treatment_id' => 501, 'tracking_time' => 20261003080000, 'content' => 'c', 'is_delete' => 0],
            ['id' => 1, 'treatment_id' => 501, 'tracking_time' => 20261005080000, 'content' => 'e', 'is_delete' => 0],
            ['id' => 5, 'treatment_id' => 501, 'tracking_time' => 20261001080000, 'content' => 'a', 'is_delete' => 0],
            ['id' => 4, 'treatment_id' => 501, 'tracking_time' => 20261003080000, 'content' => 'd', 'is_delete' => 0],
        ]);

        $kq = $this->napHoSo()->nap([501]);

        $this->assertSame([5, 3, 4, 1], $this->idCac($kq[501]->theoDoi));
    }

    public function test_dich_vu_danh_muc_co_suim_index()
    {
        DB::connection('HISPro')->table('v_his_service')->insert([
            ['id' => 70, 'suim_index_id' => 9, 'suim_index_code' => 'SI9', 'suim_index_name' => 'Chi so 9'],
            ['id' => 71, 'suim_index_id' => null, 'suim_index_code' => null, 'suim_index_name' => null],
        ]);

        $dm = HisDanhMuc::napChoLuot('HISPro');

        $dv = $dm->dichVuDanhMuc(70);
        $this->assertSame('SI9', $dv->suim_index_code);
        $this->assertSame('Chi so 9', $dv->suim_index_name);
        $this->assertSame('SI9', $dm->dichVuDanhMuc('70')->suim_index_code);
        // Dich vu khong gan chi so (hoac khong co) => null, giong DLL lay SUIM_INDEX_* null
        $this->assertNull($dm->dichVuDanhMuc(71));
        $this->assertNull($dm->dichVuDanhMuc(72));
        $this->assertNull($dm->dichVuDanhMuc(null));
    }

    public function test_moi_bang_con_mot_truy_van()
    {
        // 10 nguon Dot 1 + theoDoi, hoiChan, chiSoCdha + hivTreatment, dieuTriLao (Dot 3)
        $this->assertCount(15, HisNapHoSo::cacNguon());

        foreach ([1, 2, 3, 4, 5] as $id) {
            $this->themHoSo($id);
        }
        $nap = $this->napHoSo();
        $his = DB::connection('HISPro');

        $his->flushQueryLog();
        $his->enableQueryLog();
        $nap->nap([1, 2]);
        $hai = count($his->getQueryLog());

        $his->flushQueryLog();
        $nap->nap([1, 2, 3, 4, 5]);
        $nam = count($his->getQueryLog());
        $his->disableQueryLog();

        // 1 truy van ho so + 1 truy van moi bang con
        $this->assertSame(count(HisNapHoSo::cacNguon()), $hai);
        $this->assertSame($hai, $nam);
    }

    public function test_chia_nhom_id()
    {
        $this->assertSame(1000, HisNapHoSo::NHOM_ID);

        // SQLite 3.31 chi cho 999 tham so => thu bang lop con co nhom 2 ID
        config(['organization.sinh_xml3176' => ['ket_noi_his' => 'HISPro']]);
        $cauHinh = CauHinhSinhXml3176::tuConfig();
        $ungVien = new HisUngVien($cauHinh);
        $ungVien->doiMa();
        $nap = new class($cauHinh, $ungVien) extends HisNapHoSo {
            protected $nhomId = 2;
        };
        foreach ([1, 2, 3] as $id) {
            $this->themHoSo($id);
        }

        $his = DB::connection('HISPro');
        $his->flushQueryLog();
        $his->enableQueryLog();
        $kq = $nap->nap([1, 2, 3]);
        $soTruyVan = count($his->getQueryLog());
        $his->disableQueryLog();

        $this->assertSame([1, 2, 3], array_keys($kq));
        $this->assertCount(3, $kq[3]->dichVu);
        $this->assertCount(1, $kq[3]->ekip);
        // 3 ID chia 2 nhom => moi nguon 2 truy van (ekip: 3 ekip_id cung 2 nhom)
        $this->assertSame(count(HisNapHoSo::cacNguon()) * 2, $soTruyVan);
    }

    public function test_chi_select_cot_khai_bao()
    {
        $this->themHoSo(301);
        $his = DB::connection('HISPro');
        $his->flushQueryLog();
        $his->enableQueryLog();
        $this->napHoSo()->nap([301]);
        $log = $his->getQueryLog();
        $his->disableQueryLog();

        foreach ($log as $q) {
            $this->assertNotRegExp('/select \*/i', $q['query']);
        }
    }

    public function test_hiv_lay_id_lon_nhat()
    {
        $this->themHoSo(601, 1);
        $this->themHoSo(602, 1);
        $his = DB::connection('HISPro');
        // Chen khong theo thu tu id; ban ghi is_delete=1 (id lon nhat) bi bo
        foreach ([
            ['id' => 12, 'treatment_id' => 601, 'hiv_treatment_code' => 'B', 'is_delete' => 0],
            ['id' => 31, 'treatment_id' => 601, 'hiv_treatment_code' => 'XOA', 'is_delete' => 1],
            ['id' => 20, 'treatment_id' => 601, 'hiv_treatment_code' => 'C', 'is_delete' => 0],
            ['id' => 7, 'treatment_id' => 601, 'hiv_treatment_code' => 'A', 'is_delete' => 0],
            ['id' => 8, 'treatment_id' => 602, 'hiv_treatment_code' => 'Z', 'is_delete' => 0],
        ] as $d) {
            $his->table('his_hiv_treatment')->insert($d);
        }

        $kq = $this->napHoSo()->nap([601, 602]);

        $this->assertInstanceOf(\stdClass::class, $kq[601]->hivTreatment);
        $this->assertEquals(20, $kq[601]->hivTreatment->id);
        $this->assertSame('C', $kq[601]->hivTreatment->hiv_treatment_code);
        $this->assertEquals(8, $kq[602]->hivTreatment->id);
        $this->assertNull($kq[601]->dieuTriLao);
    }

    public function test_lao_lay_ban_cuoi()
    {
        $this->themHoSo(701, 1);
        $his = DB::connection('HISPro');
        foreach ([
            ['id' => 5, 'treatment_id' => 701, 'regimen_tuberculosis_code' => 'R5', 'is_delete' => 0],
            ['id' => 3, 'treatment_id' => 701, 'regimen_tuberculosis_code' => 'R3', 'is_delete' => 0],
            ['id' => 9, 'treatment_id' => 701, 'regimen_tuberculosis_code' => 'R9', 'is_delete' => 0],
            ['id' => 11, 'treatment_id' => 701, 'regimen_tuberculosis_code' => 'XOA', 'is_delete' => 1],
        ] as $d) {
            $his->table('his_tuberculosis_treat')->insert($d);
        }

        $kq = $this->napHoSo()->nap([701]);

        $this->assertEquals(9, $kq[701]->dieuTriLao->id);
        $this->assertSame('R9', $kq[701]->dieuTriLao->regimen_tuberculosis_code);
        $this->assertNull($kq[701]->hivTreatment);
    }

    public function test_khong_co_hiv_lao_la_null()
    {
        DB::connection('HISPro')->table('v_his_treatment_12')->insert(['id' => 801, 'treatment_code' => 'HS801']);

        $kq = $this->napHoSo()->nap([801]);

        $this->assertNull($kq[801]->hivTreatment);
        $this->assertNull($kq[801]->dieuTriLao);
        $this->assertNull((new DuLieuHoSo((object) []))->hivTreatment);
        $this->assertNull((new DuLieuHoSo((object) []))->dieuTriLao);
    }

    public function test_cot_dot_3_co_tren_hang_so()
    {
        foreach (['father_name', 'mother_name', 'end_code', 'extra_end_code', 'treatment_end_type_ext_id', 'sick_leave_from',
            'end_dept_subs_head_loginname', 'transporter_loginnames', 'used_medicine', 'clinical_note'] as $c) {
            $this->assertContains($c, HisNapHoSo::COT_HO_SO);
        }
        foreach (['baby_name', 'born_result_id', 'birthplace_type', 'tdl_patient_passport_place'] as $c) {
            $this->assertContains($c, HisNapHoSo::COT_SO_SINH);
        }
        $this->assertContains('treatment_id', HisNapHoSo::COT_HIV);
        $this->assertContains('tuberculosis_treatmen_result', HisNapHoSo::COT_LAO);
        $this->assertContains('title', HisDanhMuc::COT_NHAN_VIEN);
    }
}

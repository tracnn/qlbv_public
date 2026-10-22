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

    public function test_moi_bang_con_mot_truy_van()
    {
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
}

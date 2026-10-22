<?php

namespace Tests\Unit\SinhXml3176;

use App\Services\SinhXml3176\Bang\Xml4Dung;
use App\Services\SinhXml3176\DuLieuHoSo;
use App\Services\SinhXml3176\Nguon\HisDanhMuc;
use App\Services\SinhXml3176\Nguon\HisNapHoSo;
use Illuminate\Support\Facades\DB;
use Tests\Support\DungBangHisSinhXml3176Sqlite;
use Tests\TestCase;

class Xml4DungTest extends TestCase
{
    use DungBangHisSinhXml3176Sqlite;

    const TRUONG = [
        'MA_LK', 'STT', 'MA_DICH_VU', 'MA_CHI_SO', 'TEN_CHI_SO', 'GIA_TRI', 'DON_VI_DO', 'MO_TA', 'KET_LUAN',
        'NGAY_KQ', 'MA_BS_DOC_KQ', 'DU_PHONG',
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
            ['id' => 1, 'loginname' => 'bs1', 'diploma' => 'CCHN1', 'identification_number' => '001100000001'],
            ['id' => 2, 'loginname' => 'ktv1', 'diploma' => null, 'identification_number' => '001200000002'],
            ['id' => 3, 'loginname' => 'bs2', 'diploma' => 'CCHN2', 'identification_number' => null],
        ]);
        $his->table('v_his_service')->insert([
            ['id' => 501, 'suim_index_id' => 9, 'suim_index_code' => 'CS_DM', 'suim_index_name' => 'Chi so danh muc'],
        ]);
    }

    private function dm(array $cauHinh = [])
    {
        $cauHinh = array_merge([HisDanhMuc::KEY_MA_DOI_TUONG_BHYT => 'BHYT'], $cauHinh);
        DB::connection('HISPro')->table('his_config')->delete();
        $i = 1;
        foreach ($cauHinh as $k => $v) {
            DB::connection('HISPro')->table('his_config')->insert(['id' => $i++, 'key' => $k, 'value' => $v, 'is_active' => 1]);
        }

        return HisDanhMuc::napChoLuot('HISPro');
    }

    /** Xet nghiem BHYT (loai 18). */
    private function dv(array $ghi = [])
    {
        return (object) array_merge(array_fill_keys(HisNapHoSo::COT_DICH_VU, null), [
            'id' => 21, 'service_id' => 301, 'patient_type_id' => 1,
            'tdl_hein_service_type_id' => 18, 'tdl_hein_service_bhyt_code' => '23.0001.1000',
            'is_no_execute' => null, 'amount' => 1, 'price' => 100000, 'vir_total_hein_price' => 80000,
            'intruction_time' => 20261002083000, 'start_time' => 20261002090000,
            'finish_time' => 20261002100000, 'end_time' => 20261002110000,
            'execute_loginname' => 'bs2', 'conclude' => 'Binh thuong',
        ], $ghi);
    }

    private function tein(array $ghi = [])
    {
        return (object) array_merge(array_fill_keys(HisNapHoSo::COT_KET_QUA_CLS, null), [
            'id' => 1, 'sere_serv_id' => 21, 'bhyt_code' => 'GLU', 'bhyt_name' => 'Glucose', 'value' => '5.5',
            'test_index_unit_name' => 'mmol/L', 'result_description' => 'MT',
        ], $ghi);
    }

    private function suin(array $ghi = [])
    {
        return (object) array_merge(array_fill_keys(HisNapHoSo::COT_CHI_SO_CDHA, null), [
            'id' => 1, 'sere_serv_id' => 41, 'suim_index_code' => 'EF', 'suim_index_name' => 'Phan suat tong mau',
            'suim_index_unit_name' => '%', 'value' => '60', 'description' => 'Mo ta suin',
        ], $ghi);
    }

    /** CDHA (loai 1). */
    private function cdha(array $ghi = [])
    {
        return $this->dv(array_merge([
            'id' => 41, 'service_id' => 501, 'tdl_hein_service_type_id' => 1, 'tdl_hein_service_bhyt_code' => '18.0001.0001',
            'description' => 'Mo ta CDHA', 'conclude' => 'Ket luan CDHA',
        ], $ghi));
    }

    private function du(array $dichVu, array $tein = [], array $suin = [])
    {
        $d = new DuLieuHoSo((object) ['id' => 5, 'treatment_code' => 'HS5']);
        $d->dichVu = $dichVu;
        $d->ketQuaCls = $tein;
        $d->chiSoCdha = $suin;

        return $d;
    }

    private function dung(DuLieuHoSo $d, HisDanhMuc $dm = null)
    {
        return (new Xml4Dung())->dung($d, $dm ?: $this->dm(), []);
    }

    private function cot(array $dong, $truong)
    {
        return array_map(function ($r) use ($truong) {
            return $r[$truong];
        }, $dong);
    }

    public function test_khoi_a_mot_dong_moi_tein_sap_theo_id()
    {
        $this->assertSame('XML4', (new Xml4Dung())->loai());
        $ra = $this->dung($this->du([$this->dv()], [
            $this->tein(['id' => 7, 'bhyt_code' => 'URE', 'bhyt_name' => 'Ure', 'value' => '4']),
            $this->tein(['id' => 3]),
            $this->tein(['id' => 5, 'sere_serv_id' => 999, 'bhyt_code' => 'KHAC']),
        ]));

        $this->assertCount(2, $ra);
        $this->assertSame(['GLU', 'URE'], $this->cot($ra, 'MA_CHI_SO'));
        $r = $ra[0];
        $this->assertSame('HS5', $r['MA_LK']);
        $this->assertSame('1', $r['STT']);
        $this->assertSame('23.0001.1000', $r['MA_DICH_VU']);
        $this->assertSame('Glucose', $r['TEN_CHI_SO']);
        $this->assertSame('5.5', $r['GIA_TRI']);
        $this->assertSame('mmol/L', $r['DON_VI_DO']);
        $this->assertSame('MT', $r['MO_TA']);
        $this->assertSame('Binh thuong', $r['KET_LUAN']);
        $this->assertSame('Binh thuong', $ra[1]['KET_LUAN']);
        $this->assertSame('202610021000', $r['NGAY_KQ']); // XN: FINISH_TIME
        $this->assertSame('CCHN2', $r['MA_BS_DOC_KQ']);
        $this->assertSame('', $r['DU_PHONG']);
    }

    public function test_ma_chi_so_roi_ve_khang_sinh_do()
    {
        $ra = $this->dung($this->du([$this->dv()], [
            $this->tein(['id' => 1, 'bhyt_code' => null, 'bhyt_name' => 'Ten BHYT',
                'antibiotic_resistance_code' => 'AMP', 'antibiotic_resistance_name' => 'Ampicillin']),
            $this->tein(['id' => 2, 'bhyt_code' => '', 'bhyt_name' => '',
                'antibiotic_resistance_code' => 'CIP', 'antibiotic_resistance_name' => 'Ciprofloxacin']),
            $this->tein(['id' => 3, 'bhyt_code' => null, 'bhyt_name' => null]),
        ]));

        // Giu nhu DLL [AX]:696: ma va ten chon doc lap (lech cap o dong 1)
        $this->assertSame('AMP', $ra[0]['MA_CHI_SO']);
        $this->assertSame('Ten BHYT', $ra[0]['TEN_CHI_SO']);
        $this->assertSame('CIP', $ra[1]['MA_CHI_SO']);
        $this->assertSame('Ciprofloxacin', $ra[1]['TEN_CHI_SO']);
        $this->assertSame('', $ra[2]['MA_CHI_SO']);
        $this->assertSame('', $ra[2]['TEN_CHI_SO']);
    }

    public function test_tein_value_rong_van_sinh_dong()
    {
        $ra = $this->dung($this->du([$this->dv()], [
            $this->tein(['value' => null, 'test_index_unit_name' => null, 'result_description' => null]),
            $this->tein(['id' => 2, 'value' => '']),
        ]));

        $this->assertCount(2, $ra);
        $this->assertSame('', $ra[0]['GIA_TRI']);
        $this->assertSame('', $ra[0]['DON_VI_DO']);
        $this->assertSame('', $ra[0]['MO_TA']);
        $this->assertSame('', $ra[1]['GIA_TRI']);
    }

    public function test_khoi_b_dich_vu_khong_chi_so_lay_suim_index_danh_muc()
    {
        $ra = $this->dung($this->du([
            $this->cdha(['intruction_time' => 20261002100000]),
            $this->dv(['id' => 22, 'service_id' => 302, 'intruction_time' => 20261002070000,
                'description' => 'Mo ta XN', 'conclude' => null]),
        ]));

        $this->assertCount(2, $ra);
        // sap theo INTRUCTION_TIME
        $this->assertSame(['23.0001.1000', '18.0001.0001'], $this->cot($ra, 'MA_DICH_VU'));
        // dich vu khong co chi so danh muc => ""
        $this->assertSame('', $ra[0]['MA_CHI_SO']);
        $this->assertSame('', $ra[0]['TEN_CHI_SO']);
        $this->assertSame('Mo ta XN', $ra[0]['MO_TA']);
        $this->assertSame('', $ra[0]['KET_LUAN']);
        $this->assertSame('202610021100', $ra[0]['NGAY_KQ']); // XN khoi B: END_TIME (giu nhu DLL [AX]:697)
        $this->assertSame('CS_DM', $ra[1]['MA_CHI_SO']);
        $this->assertSame('Chi so danh muc', $ra[1]['TEN_CHI_SO']);
        $this->assertSame('', $ra[1]['GIA_TRI']);
        $this->assertSame('', $ra[1]['DON_VI_DO']);
        $this->assertSame('Mo ta CDHA', $ra[1]['MO_TA']);
        $this->assertSame('Ket luan CDHA', $ra[1]['KET_LUAN']);
    }

    public function test_co_suin_khong_sinh_khoi_b()
    {
        $ra = $this->dung($this->du([$this->cdha()], [], [
            $this->suin(),
            $this->suin(['id' => 2, 'suim_index_code' => null, 'suim_index_name' => 'Ten rieng', 'value' => '3']),
        ]));

        $this->assertCount(2, $ra);
        $this->assertSame('EF', $ra[0]['MA_CHI_SO']);
        $this->assertSame('Phan suat tong mau', $ra[0]['TEN_CHI_SO']);
        $this->assertSame('60', $ra[0]['GIA_TRI']);
        $this->assertSame('%', $ra[0]['DON_VI_DO']);
        $this->assertSame('Mo ta suin', $ra[0]['MO_TA']);
        $this->assertSame('Ket luan CDHA', $ra[0]['KET_LUAN']);
        // ma hoac ten rong => ca hai lay tu danh muc dich vu
        $this->assertSame('CS_DM', $ra[1]['MA_CHI_SO']);
        $this->assertSame('Chi so danh muc', $ra[1]['TEN_CHI_SO']);
        $this->assertSame('3', $ra[1]['GIA_TRI']);
    }

    public function test_stt_lien_tuc_qua_ba_khoi()
    {
        $ra = $this->dung($this->du([
            $this->cdha(),
            $this->dv(['id' => 22, 'tdl_hein_service_bhyt_code' => 'XN_KHONG_CS']),
            $this->dv(),
        ], [$this->tein(), $this->tein(['id' => 2, 'bhyt_code' => 'URE'])], [$this->suin()]));

        $this->assertSame(['1', '2', '3', '4'], $this->cot($ra, 'STT'));
        $this->assertSame(['GLU', 'URE', '', 'EF'], $this->cot($ra, 'MA_CHI_SO'));
        $this->assertSame(['23.0001.1000', '23.0001.1000', 'XN_KHONG_CS', '18.0001.0001'], $this->cot($ra, 'MA_DICH_VU'));
    }

    public function test_ngay_kq_cdha_uu_tien_result_read_time()
    {
        $dv = [
            $this->cdha(['result_read_time' => 20261002123000]),
            $this->cdha(['id' => 42, 'result_read_time' => null, 'end_time' => null, 'finish_time' => null,
                'start_time' => null, 'intruction_time' => 20261002080000]),
            $this->cdha(['id' => 43, 'tdl_hein_service_type_id' => 8, 'result_read_time' => null]),
        ];
        $ra = $this->dung($this->du($dv));
        $this->assertSame(['202610020800', '202610021230', '202610021100'], $this->cot($ra, 'NGAY_KQ'));

        // khoi C cung theo RESULT_READ_TIME voi CDHA
        $ra = $this->dung($this->du([$this->cdha(['result_read_time' => 20261002123000])], [], [$this->suin()]));
        $this->assertSame('202610021230', $ra[0]['NGAY_KQ']);

        // XN khong co FINISH_TIME => ""
        $ra = $this->dung($this->du([$this->dv(['finish_time' => null, 'result_read_time' => 20261002123000])], [$this->tein()]));
        $this->assertSame('', $ra[0]['NGAY_KQ']);
    }

    public function test_lay_dvu_pttt_0_1_2()
    {
        $dv = [
            $this->dv(),
            $this->dv(['id' => 23, 'tdl_hein_service_type_id' => 7, 'tdl_hein_service_bhyt_code' => 'PT1', 'tracking_id' => null]),
            $this->dv(['id' => 24, 'tdl_hein_service_type_id' => 22, 'tdl_hein_service_bhyt_code' => 'TT1', 'tracking_id' => 77]),
            $this->dv(['id' => 25, 'tdl_hein_service_type_id' => 2, 'tdl_hein_service_bhyt_code' => 'KHAC']),
        ];
        $key = Xml4Dung::KEY_LAY_DVU_PTTT;

        $this->assertSame(['23.0001.1000'], $this->cot($this->dung($this->du($dv)), 'MA_DICH_VU'));
        $this->assertSame(['23.0001.1000'], $this->cot($this->dung($this->du($dv), $this->dm([$key => '0'])), 'MA_DICH_VU'));
        $this->assertSame(['23.0001.1000', 'PT1'], $this->cot($this->dung($this->du($dv), $this->dm([$key => '1'])), 'MA_DICH_VU'));
        $this->assertSame(['23.0001.1000', 'PT1', 'TT1'], $this->cot($this->dung($this->du($dv), $this->dm([$key => '2'])), 'MA_DICH_VU'));

        // PTTT/TT: NGAY_KQ theo thoi diem truyen vao (khoi A: FINISH_TIME, khoi B: END_TIME)
        $ra = $this->dung($this->du([$dv[1]], [$this->tein(['sere_serv_id' => 23])]), $this->dm([$key => '1']));
        $this->assertSame('202610021000', $ra[0]['NGAY_KQ']);
        $ra = $this->dung($this->du([$dv[1]]), $this->dm([$key => '1']));
        $this->assertSame('202610021100', $ra[0]['NGAY_KQ']);
    }

    public function test_ma_bs_doc_kq_lay_diploma_nguoi_dau_tien_co()
    {
        $ra = $this->dung($this->du([
            $this->dv(['subclinical_result_loginname' => 'khong_co;ktv1;bs1;bs2']),
            $this->dv(['id' => 22, 'subclinical_result_loginname' => null, 'execute_loginname' => 'ktv1']),
            $this->dv(['id' => 26, 'subclinical_result_loginname' => 'ktv1', 'execute_loginname' => 'bs2']),
        ]));

        $this->assertSame('CCHN1', $ra[0]['MA_BS_DOC_KQ']);
        // Giu nhu DLL [AX]:694: khong ai co DIPLOMA => login name tho EXECUTE_LOGINNAME
        $this->assertSame('ktv1', $ra[1]['MA_BS_DOC_KQ']);
        $this->assertSame('bs2', $ra[2]['MA_BS_DOC_KQ']);
    }

    public function test_ma_bs_doc_kq_rong_roi_ve_execute()
    {
        $ra = $this->dung($this->du([
            $this->dv(['subclinical_result_loginname' => '', 'execute_loginname' => 'bs1']),
            $this->dv(['id' => 22, 'subclinical_result_loginname' => '', 'execute_loginname' => null]),
        ]));

        // Sua loi DLL [AX]:695: chuoi rong coi nhu null => tra DIPLOMA theo EXECUTE_LOGINNAME
        $this->assertSame('CCHN1', $ra[0]['MA_BS_DOC_KQ']);
        $this->assertSame('', $ra[1]['MA_BS_DOC_KQ']);
    }

    public function test_mo_ta_ket_luan_cat_1024_ky_tu()
    {
        $dai = str_repeat('ă', 1030);
        $ra = $this->dung($this->du([$this->cdha(['description' => $dai, 'conclude' => $dai])], [$this->tein([
            'sere_serv_id' => 41, 'result_description' => $dai])]));
        $ra2 = $this->dung($this->du([$this->cdha(['description' => $dai, 'conclude' => $dai])]));

        $this->assertSame(1024, mb_strlen($ra[0]['MO_TA'], 'UTF-8'));
        $this->assertSame(1024, mb_strlen($ra[0]['KET_LUAN'], 'UTF-8'));
        $this->assertSame(str_repeat('ă', 1024), $ra2[0]['MO_TA']);
        $this->assertSame(str_repeat('ă', 1024), $ra2[0]['KET_LUAN']);
    }

    public function test_bhyt_khong_tien_khong_vao_xml4()
    {
        $ra = $this->dung($this->du([
            $this->dv(['vir_total_hein_price' => 0, 'other_source_price' => 0]),
            $this->dv(['id' => 22, 'tdl_hein_service_bhyt_code' => 'VP', 'patient_type_id' => 2, 'vir_total_hein_price' => 0]),
        ], [$this->tein(), $this->tein(['id' => 2, 'sere_serv_id' => 22])]));

        $this->assertSame(['VP'], $this->cot($ra, 'MA_DICH_VU'));
    }

    public function test_khong_co_dich_vu_cls_tra_rong()
    {
        $x = new Xml4Dung();
        $this->assertSame([], $this->dung($this->du([])));
        $this->assertFalse($x->coTrongHoSo($this->du([])));
        $kham = $this->dv(['tdl_hein_service_type_id' => 5]);
        $this->assertFalse($x->coTrongHoSo($this->du([$kham])));
        $this->assertSame([], $this->dung($this->du([$kham], [$this->tein()])));
        $khongTh = $this->dv(['is_no_execute' => 1]);
        $this->assertSame([], $this->dung($this->du([$khongTh], [$this->tein()])));
        $this->assertTrue($x->coTrongHoSo($this->du([$this->dv()])));
    }

    public function test_thu_tu_khoa_dung_chi_tieu()
    {
        $ra = $this->dung($this->du([$this->cdha(), $this->dv(['id' => 22]), $this->dv()], [$this->tein()], [
            $this->suin()]));

        $this->assertCount(3, $ra);
        foreach ($ra as $r) {
            $this->assertSame(self::TRUONG, array_keys($r));
            foreach ($r as $v) {
                $this->assertInternalType('string', $v);
            }
        }
    }
}

<?php

namespace Tests\Unit\SinhXml3176;

use App\Services\SinhXml3176\Bang\Xml5Dung;
use App\Services\SinhXml3176\DuLieuHoSo;
use App\Services\SinhXml3176\Nguon\HisDanhMuc;
use App\Services\SinhXml3176\Nguon\HisNapHoSo;
use Illuminate\Support\Facades\DB;
use Tests\Support\DungBangHisSinhXml3176Sqlite;
use Tests\TestCase;

class Xml5DungTest extends TestCase
{
    use DungBangHisSinhXml3176Sqlite;

    const TRUONG = [
        'MA_LK', 'STT', 'DIEN_BIEN_LS', 'GIAI_DOAN_BENH', 'HOI_CHAN', 'PHAU_THUAT', 'THOI_DIEM_DBLS',
        'NGUOI_THUC_HIEN', 'DU_PHONG',
    ];

    protected function setUp()
    {
        parent::setUp();
        $this->dungBangHis();
        $his = DB::connection('HISPro');
        $his->table('his_patient_type')->insert([
            ['id' => 1, 'patient_type_code' => 'BHYT', 'patient_type_name' => 'BHYT'],
        ]);
        $his->table('his_employee')->insert([
            // Review Focus 2: nhan vien LOGINNAME null dung truoc trong danh muc
            ['id' => 1, 'loginname' => null, 'diploma' => 'KHONG_DUNG', 'identification_number' => 'X'],
            ['id' => 2, 'loginname' => 'bs1', 'diploma' => 'CCHN1', 'identification_number' => '001100000001'],
            ['id' => 3, 'loginname' => 'dd1', 'diploma' => 'CCHN_DD', 'identification_number' => null],
            ['id' => 4, 'loginname' => 'nv0', 'diploma' => null, 'identification_number' => '001200000004'],
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

    /** Dich vu kham chinh (loai dich vu 1). */
    private function kham(array $ghi = [])
    {
        return (object) array_merge(array_fill_keys(HisNapHoSo::COT_DICH_VU, null), [
            'id' => 11, 'service_id' => 101, 'patient_type_id' => 1, 'tdl_service_type_id' => 1,
            'tdl_hein_service_type_id' => 1, 'tdl_is_main_exam' => 1, 'amount' => 1, 'price' => 30000,
            'vir_total_hein_price' => 30000, 'tdl_intruction_time' => 20261001080000,
            'start_time' => 20261001083000, 'execute_loginname' => 'bs1',
            'hospitalization_reason' => 'Dau bung', 'part_exam' => 'Bung mem',
        ], $ghi);
    }

    /** Dich vu PTTT (loai BHYT 7). */
    private function pt(array $ghi = [])
    {
        return $this->kham(array_merge([
            'id' => 31, 'service_id' => 301, 'tdl_service_type_id' => 4, 'tdl_hein_service_type_id' => 7,
            'tdl_is_main_exam' => null, 'tracking_id' => 1, 'description' => 'Mo ruot thua',
        ], $ghi));
    }

    private function to(array $ghi = [])
    {
        return (object) array_merge(array_fill_keys(HisNapHoSo::COT_THEO_DOI, null), [
            'id' => 1, 'treatment_id' => 5, 'tracking_time' => 20261002070000, 'content' => '  Tinh, tiep xuc tot  ',
            'creator' => 'bs1',
        ], $ghi);
    }

    private function dhst(array $ghi = [])
    {
        return (object) array_merge(array_fill_keys(HisNapHoSo::COT_DHST, null), ['id' => 1, 'treatment_id' => 5], $ghi);
    }

    private function hc(array $ghi = [])
    {
        return (object) array_merge(array_fill_keys(HisNapHoSo::COT_HOI_CHAN, null), [
            'id' => 1, 'treatment_id' => 5, 'tracking_id' => 1, 'conclusion' => 'Mo cap cuu',
        ], $ghi);
    }

    private function pttt(array $ghi = [])
    {
        return (object) array_merge(array_fill_keys(HisNapHoSo::COT_PTTT, null), ['id' => 1, 'sere_serv_id' => 31], $ghi);
    }

    private function du(array $dichVu, array $theoDoi = [], array $dhst = [], array $hoiChan = [], array $pttt = [])
    {
        $d = new DuLieuHoSo((object) ['id' => 5, 'treatment_code' => 'HS5']);
        $d->dichVu = $dichVu;
        $d->theoDoi = $theoDoi;
        $d->dhst = $dhst;
        $d->hoiChan = $hoiChan;
        $d->pttt = $pttt;

        return $d;
    }

    private function dung(DuLieuHoSo $d, HisDanhMuc $dm = null)
    {
        return (new Xml5Dung())->dung($d, $dm ?: $this->dm(), []);
    }

    private function cot(array $dong, $truong)
    {
        return array_map(function ($r) use ($truong) {
            return $r[$truong];
        }, $dong);
    }

    public function test_nhanh_theo_doi_mot_dong_moi_to_co_content_sap_theo_thoi_gian()
    {
        $this->assertSame('XML5', (new Xml5Dung())->loai());
        $ra = $this->dung($this->du([$this->kham()], [
            $this->to(['id' => 2, 'tracking_time' => 20261003070000, 'content' => 'Ngay 2', 'disease_stage' => 'On dinh']),
            $this->to(['id' => 1]),
            $this->to(['id' => 3, 'tracking_time' => 20261002060000, 'content' => '']),
            $this->to(['id' => 4, 'tracking_time' => 20261002060000, 'content' => null, 'care_instruction' => 'CS']),
        ]));

        $this->assertCount(2, $ra);
        $this->assertSame(['1', '2'], $this->cot($ra, 'STT'));
        $this->assertSame(['202610020700', '202610030700'], $this->cot($ra, 'THOI_DIEM_DBLS'));
        $r = $ra[0];
        $this->assertSame('HS5', $r['MA_LK']);
        $this->assertSame('Diễn biến: Tinh, tiep xuc tot', $r['DIEN_BIEN_LS']);
        $this->assertSame('', $r['GIAI_DOAN_BENH']);
        $this->assertSame('', $r['HOI_CHAN']);
        $this->assertSame('', $r['PHAU_THUAT']);
        $this->assertSame('CCHN1', $r['NGUOI_THUC_HIEN']);
        $this->assertSame('', $r['DU_PHONG']);
        $this->assertSame('On dinh', $ra[1]['GIAI_DOAN_BENH']);
    }

    public function test_csst_dung_thu_tu_va_tien_to()
    {
        $day = $this->dhst([
            'id' => 7, 'tracking_id' => 1, 'pulse' => 80, 'temperature' => '37.5', 'breath_rate' => 18, 'spo2' => 98,
            'weight' => '55', 'height' => 160, 'belly' => 70, 'chest' => 80, 'urine' => 1500,
            'blood_pressure_max' => 120, 'blood_pressure_min' => 80, 'capillary_blood_glucose' => '.5',
        ]);
        $ra = $this->dung($this->du([$this->kham()], [
            $this->to(['care_instruction' => ' Cham soc cap 2 ']),
            $this->to(['id' => 2, 'tracking_time' => 20261003070000, 'content' => 'Ngay 2']),
            $this->to(['id' => 3, 'tracking_time' => 20261004070000, 'content' => 'Ngay 3']),
        ], [
            $day,
            $this->dhst(['id' => 8, 'tracking_id' => 1, 'pulse' => 99]), // cung to: lay ban ghi dau
            $this->dhst(['id' => 9, 'tracking_id' => 2, 'pulse' => 80, 'temperature' => 37, 'blood_pressure_max' => 120,
                'blood_pressure_min' => 80]),
            $this->dhst(['id' => 10, 'tracking_id' => 3, 'blood_pressure_max' => 130]),
        ]));

        $this->assertSame('Diễn biến: Tinh, tiep xuc tot; CSST<M80_T37.5_NT18_SP98_W55_H160_B70_C80_U1500_HA120/80_CBG0.5>'
            . '; Theo dõi - chăm sóc: Cham soc cap 2', $ra[0]['DIEN_BIEN_LS']);
        $this->assertSame('Diễn biến: Ngay 2; CSST<M80_T37_HA120/80>', $ra[1]['DIEN_BIEN_LS']);
        // HA chi co MAX: "HA" + MAX + "/" + "" (C# noi null = "")
        $this->assertSame('Diễn biến: Ngay 3; CSST<HA130/>', $ra[2]['DIEN_BIEN_LS']);
    }

    public function test_hoi_chan_distinct_noi_cham_phay()
    {
        $ra = $this->dung($this->du([$this->kham()], [$this->to(), $this->to(['id' => 2, 'tracking_time' => 20261003070000])], [], [
            $this->hc(),
            $this->hc(['id' => 2, 'conclusion' => 'Chuyen tuyen']),
            $this->hc(['id' => 3, 'conclusion' => 'Mo cap cuu']),
            $this->hc(['id' => 4, 'tracking_id' => 9, 'conclusion' => 'To khac']),
            $this->hc(['id' => 5, 'tracking_id' => 2, 'conclusion' => '  ']),
        ]));

        $this->assertSame('Mo cap cuu; Chuyen tuyen', $ra[0]['HOI_CHAN']);
        $this->assertSame('', $ra[1]['HOI_CHAN']); // IsNullOrWhiteSpace => ""
    }

    public function test_hoi_chan_bo_ket_luan_null_rong()
    {
        $ra = $this->dung($this->du([$this->kham()], [$this->to()], [], [
            $this->hc(['conclusion' => null]),
            $this->hc(['id' => 2, 'conclusion' => 'X']),
            $this->hc(['id' => 3, 'conclusion' => '']),
        ]));

        // Sua loi DLL [AX]:725: DLL ra "; X"
        $this->assertSame('X', $ra[0]['HOI_CHAN']);
    }

    public function test_phau_thuat_chi_dich_vu_co_pttt_bo_mo_ta_null()
    {
        $ra = $this->dung($this->du([
            $this->kham(),
            $this->pt(['id' => 30, 'description' => null]),
            $this->pt(),
            $this->pt(['id' => 32, 'tdl_hein_service_type_id' => 22, 'description' => 'Thay bang']),
            $this->pt(['id' => 33, 'tdl_hein_service_type_id' => 2, 'description' => 'Loai 2']),
            $this->pt(['id' => 34, 'description' => 'Khong co pttt']),
            $this->pt(['id' => 35, 'tdl_hein_service_type_id' => 18, 'description' => 'Xet nghiem']),
            $this->pt(['id' => 36, 'tracking_id' => 2, 'description' => 'To khac']),
        ], [$this->to()], [], [], [
            $this->pttt(['id' => 1, 'sere_serv_id' => 30]),
            $this->pttt(['id' => 2, 'sere_serv_id' => 31]),
            $this->pttt(['id' => 3, 'sere_serv_id' => 32]),
            $this->pttt(['id' => 4, 'sere_serv_id' => 33]),
            $this->pttt(['id' => 5, 'sere_serv_id' => 35]),
            $this->pttt(['id' => 6, 'sere_serv_id' => 36]),
        ]));

        // Sua loi DLL [AX]:761: DESCRIPTION null dung dau khong sinh ';' thua
        $this->assertSame('Mo ruot thua;Thay bang;Loai 2', $ra[0]['PHAU_THUAT']);
    }

    public function test_nguoi_thuc_hien_uu_tien_modifier()
    {
        $ra = $this->dung($this->du([$this->kham()], [
            $this->to(['id' => 1, 'modifier' => 'dd1', 'creator' => 'bs1']),
            $this->to(['id' => 2, 'tracking_time' => 20261003070000, 'modifier' => 'nv0', 'creator' => 'bs1']),
            $this->to(['id' => 3, 'tracking_time' => 20261004070000, 'modifier' => 'khongco', 'creator' => 'nv0']),
        ]));

        $this->assertSame(['CCHN_DD', 'CCHN1', ''], $this->cot($ra, 'NGUOI_THUC_HIEN'));
    }

    public function test_moi_to_rong_content_ra_0_dong()
    {
        $x = new Xml5Dung();
        $d = $this->du([$this->kham()], [
            $this->to(['content' => null, 'care_instruction' => 'Cham soc']),
            $this->to(['id' => 2, 'content' => '']),
        ], [$this->dhst(['tracking_id' => 1, 'pulse' => 80])]);

        // Giu nhu DLL [AX]:755: co to dieu tri thi khong roi ve du lieu kham
        $this->assertSame([], $this->dung($d));
        $this->assertFalse($x->coTrongHoSo($d));
    }

    public function test_nhanh_kham_khi_khong_co_to_dieu_tri()
    {
        $ra = $this->dung($this->du([$this->kham([
            'dhst_id' => 7, 'pathological_process' => 'Dau 2 ngay', 'pathological_history' => 'Khoe',
            'pathological_history_family' => 'Chua ghi nhan', 'full_exam' => 'Tinh', 'subclinical' => 'BT',
            'treatment_instruction' => 'Noi khoa', 'hospitalization_reason' => ' Dau bung ',
        ])], [], [
            $this->dhst(['id' => 6, 'pulse' => 70]),
            $this->dhst(['id' => 7, 'pulse' => 80, 'temperature' => 37]),
        ]));

        $this->assertCount(1, $ra);
        $r = $ra[0];
        $this->assertSame('1', $r['STT']);
        $this->assertSame('Lý do khám:  Dau bung ; Quá trình bệnh lý: Dau 2 ngay; Tiểu sử bệnh của bệnh nhân: Khoe; '
            . 'Tiểu sử bệnh của gia đình: Chua ghi nhan; Khám toàn thân: Tinh; Khám bộ phận: Bung mem; CSST<M80_T37>; '
            . 'Tóm tắt kết quả cls: BT; Phương pháp điều trị: Noi khoa', $r['DIEN_BIEN_LS']);
        $this->assertSame('', $r['GIAI_DOAN_BENH']);
        $this->assertSame('', $r['HOI_CHAN']);
        $this->assertSame('', $r['PHAU_THUAT']);
        $this->assertSame('202610010800', $r['THOI_DIEM_DBLS']);
        $this->assertSame('CCHN1', $r['NGUOI_THUC_HIEN']);
        $this->assertSame('', $r['DU_PHONG']);
    }

    public function test_nhanh_kham_chon_kham_chinh_roi_ve_kham_som_nhat()
    {
        $ra = $this->dung($this->du([
            $this->kham(['id' => 12, 'tdl_is_main_exam' => null, 'tdl_intruction_time' => 20261001090000,
                'hospitalization_reason' => 'Muon']),
            $this->kham(['id' => 13, 'tdl_is_main_exam' => null, 'tdl_intruction_time' => 20261001070000,
                'hospitalization_reason' => 'Som', 'part_exam' => null]),
        ]));
        $this->assertSame('Lý do khám: Som', $ra[0]['DIEN_BIEN_LS']);
        $this->assertSame('202610010700', $ra[0]['THOI_DIEM_DBLS']);

        $ra = $this->dung($this->du([
            $this->kham(['id' => 13, 'tdl_is_main_exam' => null, 'tdl_intruction_time' => 20261001070000]),
            $this->kham(['id' => 12, 'hospitalization_reason' => 'Chinh', 'part_exam' => null]),
        ]));
        $this->assertSame('Lý do khám: Chinh', $ra[0]['DIEN_BIEN_LS']);
    }

    public function test_nhanh_kham_3176_diploma_roi_ve_cccd()
    {
        $ra = $this->dung($this->du([$this->kham(['execute_loginname' => 'nv0'])]));
        $this->assertSame('001200000004', $ra[0]['NGUOI_THUC_HIEN']);

        $ra = $this->dung($this->du([$this->kham(['execute_loginname' => 'khongco'])]));
        $this->assertSame('', $ra[0]['NGUOI_THUC_HIEN']);

        $ra = $this->dung($this->du([$this->kham(['execute_loginname' => null])]));
        $this->assertSame('', $ra[0]['NGUOI_THUC_HIEN']);
    }

    public function test_thoi_diem_dbls_nhanh_kham_theo_option()
    {
        $key = Xml5Dung::KEY_NGAY_YL_OPTION;
        $coBd = $this->du([$this->kham()]);
        $khongBd = $this->du([$this->kham(['start_time' => null])]);

        $this->assertSame('202610010800', $this->dung($coBd, $this->dm([$key => '1']))[0]['THOI_DIEM_DBLS']);
        $this->assertSame('202610010830', $this->dung($coBd, $this->dm([$key => '2']))[0]['THOI_DIEM_DBLS']);
        // Giu nhu DLL [AX]:760: option 2, START_TIME null => rong
        $this->assertSame('', $this->dung($khongBd, $this->dm([$key => '2']))[0]['THOI_DIEM_DBLS']);
        $this->assertSame('202610010830', $this->dung($coBd, $this->dm([$key => ' 3 ']))[0]['THOI_DIEM_DBLS']);
        $this->assertSame('202610010800', $this->dung($khongBd, $this->dm([$key => '3']))[0]['THOI_DIEM_DBLS']);
    }

    public function test_nhan_vien_loginname_null_khong_vo()
    {
        // Sua loi DLL [AX]:759: DLL nem NullReference o o.LOGINNAME.Equals(...) => mat ca XML5
        $ra = $this->dung($this->du([$this->kham()]));
        $this->assertCount(1, $ra);
        $this->assertSame('CCHN1', $ra[0]['NGUOI_THUC_HIEN']);

        $ra = $this->dung($this->du([$this->kham()], [$this->to(['creator' => null, 'modifier' => null])]));
        $this->assertSame('', $ra[0]['NGUOI_THUC_HIEN']);
    }

    public function test_khong_kham_khong_to_tra_rong()
    {
        $x = new Xml5Dung();
        $this->assertSame([], $this->dung($this->du([])));
        $this->assertFalse($x->coTrongHoSo($this->du([])));
        $xn = $this->kham(['tdl_is_main_exam' => null, 'tdl_service_type_id' => 2, 'tdl_hein_service_type_id' => 18]);
        $this->assertSame([], $this->dung($this->du([$xn])));
        $this->assertFalse($x->coTrongHoSo($this->du([$xn])));
        // Kham BHYT khong tien BH, khong nguon khac => ngoai list2
        $this->assertSame([], $this->dung($this->du([$this->kham(['vir_total_hein_price' => 0])])));
        $this->assertTrue($x->coTrongHoSo($this->du([$this->kham()])));
        $this->assertTrue($x->coTrongHoSo($this->du([], [$this->to()])));
    }

    public function test_dien_bien_cat_1024()
    {
        $dai = str_repeat('ă', 1030);
        $ra = $this->dung($this->du([
            $this->kham(),
            $this->pt(['description' => $dai]),
        ], [$this->to(['content' => $dai])], [], [$this->hc(['conclusion' => $dai])], [$this->pttt()]));
        $ra2 = $this->dung($this->du([$this->kham(['hospitalization_reason' => $dai])]));

        $this->assertSame('Diễn biến: ' . str_repeat('ă', 1024 - mb_strlen('Diễn biến: ', 'UTF-8')), $ra[0]['DIEN_BIEN_LS']);
        $this->assertSame(str_repeat('ă', 1024), $ra[0]['HOI_CHAN']);
        $this->assertSame(str_repeat('ă', 1024), $ra[0]['PHAU_THUAT']);
        $this->assertSame(1024, mb_strlen($ra2[0]['DIEN_BIEN_LS'], 'UTF-8'));
    }

    public function test_thu_tu_khoa_dung_chi_tieu()
    {
        $ra = array_merge(
            $this->dung($this->du([$this->kham()], [$this->to()])),
            $this->dung($this->du([$this->kham(['start_time' => null])]), $this->dm([Xml5Dung::KEY_NGAY_YL_OPTION => '2']))
        );

        $this->assertCount(2, $ra);
        foreach ($ra as $r) {
            $this->assertSame(self::TRUONG, array_keys($r));
            foreach ($r as $v) {
                $this->assertInternalType('string', $v);
            }
        }
    }
}

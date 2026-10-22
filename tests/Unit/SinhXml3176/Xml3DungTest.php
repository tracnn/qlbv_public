<?php

namespace Tests\Unit\SinhXml3176;

use App\Services\SinhXml3176\Bang\LoiDungBang;
use App\Services\SinhXml3176\Bang\Xml3Dung;
use App\Services\SinhXml3176\DuLieuHoSo;
use App\Services\SinhXml3176\Nguon\HisDanhMuc;
use App\Services\SinhXml3176\Nguon\HisNapHoSo;
use Illuminate\Support\Facades\DB;
use Tests\Support\DungBangHisSinhXml3176Sqlite;
use Tests\TestCase;

class Xml3DungTest extends TestCase
{
    use DungBangHisSinhXml3176Sqlite;

    const TRUONG = [
        'MA_LK', 'STT', 'MA_DICH_VU', 'MA_PTTT_QT', 'MA_VAT_TU', 'MA_NHOM', 'GOI_VTYT', 'TEN_VAT_TU',
        'TEN_DICH_VU', 'MA_XANG_DAU', 'DON_VI_TINH', 'PHAM_VI', 'SO_LUONG', 'DON_GIA_BV', 'DON_GIA_BH',
        'TT_THAU', 'TYLE_TT_DV', 'TYLE_TT_BH', 'THANH_TIEN_BV', 'THANH_TIEN_BH', 'T_TRANTT', 'MUC_HUONG',
        'T_NGUONKHAC_NSNN', 'T_NGUONKHAC_VTNN', 'T_NGUONKHAC_VTTN', 'T_NGUONKHAC_CL', 'T_NGUONKHAC',
        'T_BHTT', 'T_BNTT', 'T_BNCCT', 'MA_KHOA', 'MA_GIUONG', 'MA_BAC_SI', 'NGUOI_THUC_HIEN', 'MA_BENH',
        'MA_BENH_YHCT', 'NGAY_YL', 'NGAY_TH_YL', 'NGAY_KQ', 'MA_PTTT', 'VET_THUONG_TP', 'PP_VO_CAM',
        'VI_TRI_TH_DVKT', 'MA_MAY', 'MA_HIEU_SP', 'TAI_SU_DUNG', 'DU_PHONG',
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
    }

    private function dm(array $cauHinh = [])
    {
        $cauHinh = array_merge([HisDanhMuc::KEY_MA_DOI_TUONG_BHYT => 'BHYT'], $cauHinh);
        $i = 1;
        foreach ($cauHinh as $k => $v) {
            DB::connection('HISPro')->table('his_config')->insert(['id' => $i++, 'key' => $k, 'value' => $v, 'is_active' => 1]);
        }

        return HisDanhMuc::napChoLuot('HISPro');
    }

    /** DVKT BHYT co ban: xet nghiem 100.000, muc huong 80%. */
    private function dv(array $ghi = [])
    {
        return (object) array_merge(array_fill_keys(HisNapHoSo::COT_DICH_VU, null), [
            'id' => 21, 'service_id' => 301, 'service_req_id' => 401, 'patient_type_id' => 1,
            'tdl_hein_service_type_id' => 1, 'tdl_hein_service_bhyt_code' => '23.0001.1000',
            'tdl_hein_service_bhyt_name' => 'Xet nghiem', 'service_unit_name' => 'Lan', 'hst_bhyt_code' => '1',
            'is_no_execute' => null, 'amount' => 1, 'price' => 100000, 'original_price' => 100000, 'vir_price' => 100000,
            'vat_ratio' => 0,
            'hein_ratio' => 0.8, 'vir_total_hein_price' => 80000, 'vir_total_patient_price_bhyt' => 20000,
            'intruction_time' => 20261002083000, 'tdl_intruction_time' => 20261002083000,
            'request_loginname' => 'bs1', 'execute_loginname' => 'bs2', 'request_bhyt_code' => 'K01',
            'start_time' => 20261002090000, 'finish_time' => 20261002100000,
        ], $ghi);
    }

    /** VTYT (loai 14). */
    private function vt(array $ghi = [])
    {
        return $this->dv(array_merge([
            'id' => 31, 'service_id' => 311, 'tdl_hein_service_type_id' => 14, 'tdl_hein_service_bhyt_code' => 'N03.01',
            'tdl_hein_service_bhyt_name' => 'Bom tiem', 'service_unit_name' => 'Cai', 'hst_bhyt_code' => '10',
            'start_time' => null, 'finish_time' => null,
        ], $ghi));
    }

    /** Giuong (loai 3; giuong la 3/4/19/20). */
    private function giuong(array $ghi = [])
    {
        return $this->dv(array_merge([
            'tdl_hein_service_type_id' => 3, 'tdl_hein_service_bhyt_code' => 'K01.1918', 'service_unit_name' => 'Ngay',
            'hst_bhyt_code' => '15', 'price' => 200000, 'original_price' => 200000, 'vir_price' => 200000,
            'vir_total_hein_price' => 160000,
            'start_time' => null, 'finish_time' => null,
        ], $ghi));
    }

    private function du(array $dichVu, array $ghiHoSo = [])
    {
        $d = new DuLieuHoSo((object) array_merge([
            'id' => 5, 'treatment_code' => 'HS5', 'in_time' => 20261001070000, 'out_time' => null,
            'icd_code' => 'J18', 'traditional_icd_code' => null, 'hein_medi_org_code' => '79001',
        ], $ghiHoSo));
        $d->dichVu = $dichVu;

        return $d;
    }

    private function dung(DuLieuHoSo $d, HisDanhMuc $dm = null)
    {
        return (new Xml3Dung())->dung($d, $dm ?: $this->dm(), []);
    }

    private function tong(array $r)
    {
        return round((float) $r['T_BHTT'] + (float) $r['T_BNCCT'] + (float) $r['T_BNTT'] + (float) $r['T_NGUONKHAC'], 2);
    }

    public function test_loai_xml3()
    {
        $this->assertSame('XML3', (new Xml3Dung())->loai());
        $this->assertTrue((new Xml3Dung())->coTrongHoSo($this->du([$this->dv()])));
        $this->assertFalse((new Xml3Dung())->coTrongHoSo($this->du([$this->dv(['tdl_hein_service_type_id' => 9])])));
    }

    public function test_dvkt_co_ban_can_doi_tong()
    {
        $r = $this->dung($this->du([$this->dv()]))[0];

        $this->assertSame('HS5', $r['MA_LK']);
        $this->assertSame('1', $r['STT']);
        $this->assertSame('23.0001.1000', $r['MA_DICH_VU']);
        $this->assertSame('Xet nghiem', $r['TEN_DICH_VU']);
        $this->assertSame('', $r['MA_VAT_TU']);
        $this->assertSame('100', $r['TYLE_TT_DV']);
        $this->assertSame('100', $r['TYLE_TT_BH']);
        $this->assertSame('1', $r['PHAM_VI']);
        $this->assertSame('100000', $r['DON_GIA_BV']);
        $this->assertSame('100000', $r['DON_GIA_BH']);
        $this->assertSame('100000.00', $r['THANH_TIEN_BV']);
        $this->assertSame('100000.00', $r['THANH_TIEN_BH']);
        $this->assertSame('80000.00', $r['T_BHTT']);
        $this->assertSame('20000.00', $r['T_BNCCT']);
        $this->assertSame('0.00', $r['T_BNTT']);
        $this->assertSame('0.00', $r['T_NGUONKHAC']);
        $this->assertSame('', $r['T_TRANTT']);
        $this->assertSame('80', $r['MUC_HUONG']);
        $this->assertSame('CCHN1', $r['MA_BAC_SI']);
        $this->assertSame('CCHN2', $r['NGUOI_THUC_HIEN']);
        $this->assertSame('202610020830', $r['NGAY_YL']);
        $this->assertSame('202610020900', $r['NGAY_TH_YL']);
        $this->assertSame('202610021000', $r['NGAY_KQ']);
        $this->assertSame('1', $r['MA_PTTT']);
        $this->assertSame('', $r['MA_GIUONG']);

        // TYLE_TT_BH 50%, NK 15.000: BV-BH-NK = 35.000 > 0 => DLL ghi BNTT = BV-BH = 50.000 (tong 105.000 > BV).
        // Can doi: BNTT = BV - BHTT - BNCCT - NK = 45.000.
        $r = $this->dung($this->du([$this->dv(['hein_limit_ratio' => 50, 'other_source_price' => 15000])]))[0];
        $this->assertSame('50', $r['TYLE_TT_BH']);
        $this->assertSame('100000.00', $r['THANH_TIEN_BV']);
        $this->assertSame('50000.00', $r['THANH_TIEN_BH']);
        $this->assertSame('40000.00', $r['T_BHTT']);
        $this->assertSame('0.00', $r['T_BNCCT']);
        $this->assertSame('15000.00', $r['T_NGUONKHAC']);
        $this->assertSame('45000.00', $r['T_BNTT']);
        $this->assertSame((float) $r['THANH_TIEN_BV'], $this->tong($r));
    }

    public function test_ty_le_dv_lam_tron_half_up_va_tien_dung_ty_le_xuat()
    {
        // HEIN_LIMIT_PRICE/ORIGINAL = 32,5% -> 33 (DLL banker ra 32); tien tinh theo 33
        $r = $this->dung($this->du([$this->dv(['hein_limit_price' => 32500])]))[0];

        $this->assertSame('33', $r['TYLE_TT_DV']);
        $this->assertSame('33000.00', $r['THANH_TIEN_BV']);
        $this->assertSame('33000.00', $r['THANH_TIEN_BH']);
        $this->assertSame('26400.00', $r['T_BHTT']);
        $this->assertSame((float) $r['THANH_TIEN_BV'], $this->tong($r));

        // HEIN_LIMIT_RATIO 12,5 -> 13
        $r = $this->dung($this->du([$this->dv(['hein_limit_ratio' => 12.5])]))[0];
        $this->assertSame('13', $r['TYLE_TT_BH']);
        $this->assertSame('13000.00', $r['THANH_TIEN_BH']);
    }

    public function test_doi_tuong_khong_bhyt_ty_le_bh_0()
    {
        $r = $this->dung($this->du([$this->dv(['patient_type_id' => 2, 'hein_ratio' => null])]))[0];

        $this->assertSame('0', $r['TYLE_TT_BH']);
        $this->assertSame('2', $r['PHAM_VI']);
        $this->assertSame('0.00', $r['THANH_TIEN_BH']);
        $this->assertSame('0.00', $r['T_BHTT']);
        $this->assertSame('100000.00', $r['T_BNTT']);
        $this->assertSame('0', $r['MUC_HUONG']);
    }

    public function test_vtyt_ty_le_bh_50_phan_tram()
    {
        $r = $this->dung($this->du([$this->vt(['amount' => 2, 'hein_limit_ratio' => 50, 'vir_total_hein_price' => 80000])]))[0];

        $this->assertSame('', $r['MA_DICH_VU']);
        $this->assertSame('N03.01', $r['MA_VAT_TU']);
        $this->assertSame('Bom tiem', $r['TEN_VAT_TU']);
        $this->assertSame('', $r['TEN_DICH_VU']);
        $this->assertSame('100', $r['TYLE_TT_DV']);
        $this->assertSame('50', $r['TYLE_TT_BH']);
        $this->assertSame('200000.00', $r['THANH_TIEN_BV']);
        $this->assertSame('100000.00', $r['THANH_TIEN_BH']);
        $this->assertSame('80000.00', $r['T_BHTT']);
        $this->assertSame('20000.00', $r['T_BNCCT']);
        $this->assertSame('100000.00', $r['T_BNTT']);
        $this->assertSame('', $r['NGUOI_THUC_HIEN']);
        $this->assertSame((float) $r['THANH_TIEN_BV'], $this->tong($r));
    }

    public function test_vtyt_co_tran_bhtt_theo_vir_thap_hon()
    {
        // HIS da ap tran: VIR_TOTAL_HEIN_PRICE 400.000 < BH*HEIN_RATIO 800.000 => T_BHTT = VIR, BNCCT = VIR BN BHYT
        $r = $this->dung($this->du([$this->vt(['price' => 1000000, 'original_price' => 1000000, 'vir_price' => 1000000,
            'hein_limit_price' => 500000, 'vir_total_hein_price' => 400000, 'vir_total_patient_price_bhyt' => 100000])]))[0];

        $this->assertSame('500000.00', $r['T_TRANTT']);
        $this->assertSame('1000000.00', $r['THANH_TIEN_BH']);
        $this->assertSame('400000.00', $r['T_BHTT']);
        $this->assertSame('100000.00', $r['T_BNCCT']);
        $this->assertSame('500000.00', $r['T_BNTT']);
        $this->assertSame((float) $r['THANH_TIEN_BV'], $this->tong($r));
    }

    public function test_vtyt_vir_cao_hon_giu_gia_tri_tinh()
    {
        $r = $this->dung($this->du([$this->vt(['vir_total_hein_price' => 200000, 'vir_total_patient_price_bhyt' => 50000])]))[0];

        $this->assertSame('80000.00', $r['T_BHTT']);
        $this->assertSame('20000.00', $r['T_BNCCT']);
        $this->assertSame('0.00', $r['T_BNTT']);
    }

    public function test_bhtt_lon_hon_bv_nem_loi_dung_bang()
    {
        // DG_BV = VIR_PRICE 50.000 < DG_BH 100.000 => BHTT 80.000 > BV 50.000
        $this->expectException(LoiDungBang::class);

        $this->dung($this->du([$this->dv(['vir_price' => 50000])]));
    }

    public function test_gay_te_khong_thuc_hien_khong_ra_gt_tb_gt()
    {
        $d = $this->du([$this->dv(['tdl_hein_service_type_id' => 7, 'is_no_execute' => 1])]);
        $d->pttt = [(object) ['id' => 1, 'sere_serv_id' => 21, 'icd_cm_code' => null, 'icd_cm_sub_code' => null,
            'is_anaesthesia' => 1, 'emme_hein_code' => null, 'emme_second_hein_code' => null]];
        $r = $this->dung($d, $this->dm(['XML.EXPORT.4210.GAY_TE_OPTION' => '1']))[0];

        $this->assertSame('23.0001.1000_GT_TB', $r['MA_DICH_VU']);
        $this->assertSame('4', $r['PP_VO_CAM']);
    }

    public function test_gia_am_khong_vao_xml3()
    {
        $dm = $this->dm(['HIS.QD_130_BYT.LAY_CA_DVU_0_DONG' => '1']);

        $this->assertSame([], $this->dung($this->du([$this->dv(['price' => -1000])]), $dm));
    }

    public function test_hao_phi_va_so_luong_0_khong_vao_xml3()
    {
        $chiTiet = $this->dung($this->du([
            $this->vt(['id' => 31, 'is_expend' => 1]),
            $this->dv(['id' => 32, 'is_expend' => 1]),
            $this->vt(['id' => 33, 'amount' => 0]),
            $this->vt(['id' => 34]),
        ]));

        $this->assertCount(1, $chiTiet);
        $this->assertFalse((new Xml3Dung())->coTrongHoSo($this->du([$this->vt(['is_expend' => 1])])));
    }

    public function test_vtyt_kem_dich_vu_cha_goi_va_ma_khoa()
    {
        $cha = $this->dv(['id' => 21, 'tdl_hein_service_type_id' => 8, 'request_bhyt_code' => 'K02',
            'tdl_hein_service_bhyt_code' => '10.0001.0001']);
        $vt = $this->vt(['parent_id' => 21, 'request_bhyt_code' => 'K09', 'intruction_time' => 20261002090000,
            'material_bid_extra_code' => 'G1', 'material_bid_package_code' => 'P1', 'material_bid_group_code' => 'N1',
            'model_code' => 'MD01']);
        $dong = $this->dung($this->du([$cha, $vt]));

        $this->assertCount(2, $dong);
        $this->assertSame('10.0001.0001', $dong[1]['MA_DICH_VU']);
        $this->assertSame('G1', $dong[1]['GOI_VTYT']);
        $this->assertSame('K02', $dong[1]['MA_KHOA']);
        $this->assertSame('G1;P1;N1', $dong[1]['TT_THAU']); // [AX]:623 khong du ';' cuoi
        $this->assertSame('MD01', $dong[1]['MA_HIEU_SP']);
    }

    public function test_bo_dong_gia_0_mac_dinh()
    {
        $dong = $this->dung($this->du([$this->dv(['id' => 21, 'price' => 0, 'original_price' => 0]), $this->dv(['id' => 22])]));

        $this->assertCount(1, $dong);
        $this->assertSame('1', $dong[0]['STT']);
        $this->assertSame('100000.00', $dong[0]['THANH_TIEN_BV']);
    }

    public function test_lay_dong_gia_0_khi_bat_key()
    {
        $dm = $this->dm(['HIS.QD_130_BYT.LAY_CA_DVU_0_DONG' => '1']);
        $dong = $this->dung($this->du([$this->dv(['id' => 21, 'price' => 0, 'original_price' => 0, 'vir_price' => 0, 'vir_total_hein_price' => 0]),
            $this->dv(['id' => 22])]), $dm);

        $this->assertCount(2, $dong);
        $this->assertSame('0.00', $dong[0]['THANH_TIEN_BV']);
        $this->assertSame('2', $dong[1]['STT']);
    }

    public function test_khong_thuc_hien_hau_to_tb_khong_nguon_khac()
    {
        $r = $this->dung($this->du([$this->dv(['is_no_execute' => 1, 'other_source_price' => 5000])]))[0];

        $this->assertSame('23.0001.1000_TB', $r['MA_DICH_VU']);
        $this->assertSame('0', $r['TYLE_TT_DV']);
        $this->assertSame('0', $r['TYLE_TT_BH']);
        $this->assertSame('0.00', $r['THANH_TIEN_BV']);
        $this->assertSame('0.00', $r['T_NGUONKHAC']); // [AX]:613
        $this->assertSame('0.00', $r['T_NGUONKHAC_CL']);

        $dm = $this->dm(['HIS.QD_130_BYT.XML3.KHONG_LAY_DVU_KHONG_THUC_HIEN' => '1']);
        $this->assertSame([], $this->dung($this->du([$this->dv(['is_no_execute' => 1])]), $dm));
    }

    public function test_nguon_khac_con_bang_tong()
    {
        // VTYT: NK 150.000 > BV - BHTT => chan am: NK = BV - BHTT, cac truong con theo NK moi ([AX]:612)
        $r = $this->dung($this->du([$this->vt(['other_source_price' => 150000, 'hein_pay_source_type_id' => 1])]))[0];

        $this->assertSame('20000.00', $r['T_NGUONKHAC']);
        $this->assertSame('20000.00', $r['T_NGUONKHAC_NSNN']);
        $this->assertSame('0.00', $r['T_NGUONKHAC_CL']);
        $this->assertSame('0.00', $r['T_BNTT']);
        $this->assertSame('0.00', $r['T_BNCCT']);
        $this->assertSame((float) $r['THANH_TIEN_BV'], $this->tong($r));
    }

    public function test_giuong_cuoi_khong_gio_ra_dung_out_time()
    {
        $a = $this->giuong(['id' => 41, 'use_time' => 20261001080000, 'intruction_time' => 20261001080000,
            'tdl_intruction_time' => 20261001080000]);
        $b = $this->giuong(['id' => 42, 'use_time' => 20261002080000, 'intruction_time' => 20261002080000,
            'tdl_intruction_time' => 20261002080000]);
        $dong = $this->dung($this->du([$a, $b], ['out_time' => 20261003070000]));

        $this->assertSame('202610020800', $dong[0]['NGAY_KQ']);
        $this->assertSame('202610030700', $dong[1]['NGAY_KQ']);

        // BED_TIME_OPTION=1: BED_LOG khong co FINISH_TIME, khong co dong ke tiep => DLL long.Parse("" + "00") vo ca XML3
        $d = $this->du([$this->giuong(['id' => 42, 'bed_log_id' => 9, 'use_time' => 20261002080000,
            'intruction_time' => 20261002080000, 'tdl_intruction_time' => 20261002080000])], ['out_time' => 20261003070000]);
        $d->giuong = [(object) ['id' => 9, 'bed_code' => 'G01', 'bed_service_type_id' => 301, 'service_req_id' => 401,
            'share_count' => null, 'start_time' => 20261002080000, 'finish_time' => null]];
        $r = $this->dung($d, $this->dm(['HIS.QD_130_BYT.BED_TIME_OPTION' => '1']))[0];
        $this->assertSame('202610030700', $r['NGAY_KQ']);
        $this->assertSame('G01', $r['MA_GIUONG']);
    }

    public function test_giuong_cuoi_khong_co_gio_nao_nem_loi_dung_bang()
    {
        $this->expectException(LoiDungBang::class);

        $this->dung($this->du([$this->giuong(['use_time' => 20261002080000])]));
    }

    public function test_ma_may_dang_3176_source_3_co_ten()
    {
        $d = $this->du([$this->dv(), $this->dv(['id' => 22, 'machine_group_code' => 'MG', 'source_code' => '1',
            'source_name' => 'Ten khac', 'serial_number' => 'SN02'])]);
        $d->ketQuaCls = [
            (object) ['id' => 1, 'sere_serv_id' => 21, 'machine_group_code' => 'MG', 'source_code' => '3',
                'source_name' => null, 'serial_number' => null],
            (object) ['id' => 2, 'sere_serv_id' => 21, 'machine_group_code' => 'MG', 'source_code' => '3',
                'source_name' => 'Ten nguon', 'serial_number' => 'SN01'],
        ];
        $dong = $this->dung($d);

        $this->assertSame('MG.3[Ten nguon].SN01', $dong[0]['MA_MAY']);
        $this->assertSame('MG.1.SN02', $dong[1]['MA_MAY']);
    }

    public function test_nguoi_thuc_hien_khong_cchn_dung_cccd()
    {
        $r = $this->dung($this->du([$this->dv(['execute_loginname' => 'ktv1'])]))[0];
        $this->assertSame('001200000002', $r['NGUOI_THUC_HIEN']);

        // ekip: nguoi co CCHN dung CCHN, nguoi khong co dung CCCD
        $d = $this->du([$this->dv(['execute_loginname' => 'bs2', 'ekip_id' => 7])]);
        $d->ekip = [
            (object) ['id' => 1, 'ekip_id' => 7, 'loginname' => 'bs1'],
            (object) ['id' => 2, 'ekip_id' => 7, 'loginname' => 'ktv1'],
        ];
        $this->assertSame('CCHN2;CCHN1;001200000002', $this->dung($d)[0]['NGUOI_THUC_HIEN']);

        // giuong: nguoi thuc hien khong co CCHN => CCCD (DLL chi lay DIPLOMA)
        $r = $this->dung($this->du([$this->giuong(['execute_loginname' => null, 'assigned_execute_loginname' => 'ktv1', 'request_loginname' => null,
            'use_time' => 20261002080000])], ['out_time' => 20261003070000]))[0];
        $this->assertSame('001200000002', $r['NGUOI_THUC_HIEN']);
    }

    public function test_dong_trung_id_chi_mot_lan()
    {
        $dong = $this->dung($this->du([$this->dv(), $this->dv()]));

        $this->assertCount(1, $dong);
    }

    public function test_thieu_danh_muc_doi_tuong_nem_loi_dung_bang()
    {
        try {
            $this->dung($this->du([$this->dv(['patient_type_id' => 99])]));
            $this->fail('Phai nem LoiDungBang');
        } catch (LoiDungBang $e) {
            $this->assertSame('XML3', $e->loaiBang());
        }

        $this->expectException(LoiDungBang::class);
        $this->dung($this->du([$this->dv()]), $this->dm([HisDanhMuc::KEY_MA_DOI_TUONG_BHYT => 'KHONGCO']));
    }

    public function test_thu_tu_khoa_dung_chi_tieu()
    {
        $r = $this->dung($this->du([$this->dv()]))[0];

        $this->assertCount(47, self::TRUONG);
        $this->assertSame(self::TRUONG, array_keys($r));
        foreach ($r as $k => $v) {
            $this->assertInternalType('string', $v, $k);
        }
    }
}

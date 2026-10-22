<?php

namespace Tests\Unit\SinhXml3176;

use App\Services\SinhXml3176\Bang\LoiDungBang;
use App\Services\SinhXml3176\Bang\Xml2Dung;
use App\Services\SinhXml3176\DuLieuHoSo;
use App\Services\SinhXml3176\Nguon\HisDanhMuc;
use App\Services\SinhXml3176\Nguon\HisNapHoSo;
use Illuminate\Support\Facades\DB;
use Tests\Support\DungBangHisSinhXml3176Sqlite;
use Tests\TestCase;

class Xml2DungTest extends TestCase
{
    use DungBangHisSinhXml3176Sqlite;

    const TRUONG = [
        'MA_LK', 'STT', 'MA_THUOC', 'MA_PP_CHEBIEN', 'MA_CSKCB_THUOC', 'MA_NHOM', 'TEN_THUOC', 'DON_VI_TINH',
        'HAM_LUONG', 'DUONG_DUNG', 'DANG_BAO_CHE', 'LIEU_DUNG', 'CACH_DUNG', 'SO_DANG_KY', 'TT_THAU', 'PHAM_VI',
        'TYLE_TT_BH', 'SO_LUONG', 'DON_GIA', 'THANH_TIEN_BV', 'THANH_TIEN_BH', 'T_NGUONKHAC_NSNN',
        'T_NGUONKHAC_VTNN', 'T_NGUONKHAC_VTTN', 'T_NGUONKHAC_CL', 'T_NGUONKHAC', 'MUC_HUONG', 'T_BHTT',
        'T_BNCCT', 'T_BNTT', 'MA_KHOA', 'MA_BAC_SI', 'MA_DICH_VU', 'NGAY_YL', 'NGAY_TH_YL', 'MA_PTTT',
        'NGUON_CTRA', 'VET_THUONG_TP', 'DU_PHONG',
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
        $his->table('his_employee')->insert([['id' => 1, 'loginname' => 'bs1', 'diploma' => 'CCHN1']]);
    }

    /** Danh muc that (SQLite) voi cac key cau hinh them. */
    private function dm(array $cauHinh = [])
    {
        $cauHinh = array_merge([HisDanhMuc::KEY_MA_DOI_TUONG_BHYT => 'BHYT'], $cauHinh);
        $i = 1;
        foreach ($cauHinh as $k => $v) {
            DB::connection('HISPro')->table('his_config')->insert(['id' => $i++, 'key' => $k, 'value' => $v, 'is_active' => 1]);
        }

        return HisDanhMuc::napChoLuot('HISPro');
    }

    private function thuoc(array $ghi = [])
    {
        return (object) array_merge(array_fill_keys(HisNapHoSo::COT_DICH_VU, null), [
            'id' => 11, 'patient_type_id' => 1, 'tdl_hein_service_type_id' => 9, 'medicine_id' => 501,
            'exp_mest_medicine_id' => 701, 'active_ingr_bhyt_code' => '40.17', 'tdl_hein_service_bhyt_code' => 'TH1',
            'tdl_hein_service_bhyt_name' => 'Paracetamol', 'service_unit_name' => 'Vien', 'hst_bhyt_code' => '4',
            'amount' => 10, 'price' => 1500, 'original_price' => 1500, 'vat_ratio' => 0, 'hein_ratio' => 0.8,
            'vir_total_hein_price' => 12000, 'vir_total_patient_price_bhyt' => 3000,
            'intruction_time' => 20261002083000, 'tdl_intruction_date' => 20261002000000,
            'request_loginname' => 'bs1', 'request_bhyt_code' => 'K01', 'hein_card_number' => 'DN4010000000001',
        ], $ghi);
    }

    private function du(array $dichVu, array $suDung = [])
    {
        $d = new DuLieuHoSo((object) ['id' => 5, 'treatment_code' => 'HS5', 'tdl_treatment_type_id' => 1]);
        $d->dichVu = $dichVu;
        $d->suDungThuoc = $suDung;

        return $d;
    }

    private function dung(DuLieuHoSo $d, HisDanhMuc $dm = null)
    {
        return (new Xml2Dung())->dung($d, $dm ?: $this->dm(), []);
    }

    public function test_dong_thuoc_bhyt_co_ban()
    {
        $dong = $this->dung($this->du([$this->thuoc()]));

        $this->assertCount(1, $dong);
        $r = $dong[0];
        $this->assertSame('15000.00', $r['THANH_TIEN_BV']);
        $this->assertSame('15000.00', $r['THANH_TIEN_BH']);
        $this->assertSame('12000.00', $r['T_BHTT']);
        $this->assertSame('3000.00', $r['T_BNCCT']);
        $this->assertSame('0.00', $r['T_BNTT']);
        $this->assertSame('0.00', $r['T_NGUONKHAC']);
        $this->assertSame('100', $r['TYLE_TT_BH']);
        $this->assertSame('80', $r['MUC_HUONG']);
        $this->assertSame('10', $r['SO_LUONG']);
        $this->assertSame('1500', $r['DON_GIA']);
        $this->assertSame('1', $r['PHAM_VI']);
        $this->assertSame('1', $r['NGUON_CTRA']);
        $this->assertSame('HS5', $r['MA_LK']);
        $this->assertSame('1', $r['STT']);
        $this->assertSame('40.17', $r['MA_THUOC']);
        $this->assertSame('CCHN1', $r['MA_BAC_SI']);
        $this->assertSame('202610020830', $r['NGAY_YL']);
        $this->assertSame('202610020830', $r['NGAY_TH_YL']);
    }

    public function test_ty_le_le_lam_tron_half_up_va_tien_dung_ty_le_xuat()
    {
        // PRICE/ORIGINAL_PRICE = 33,33% -> TYLE_TT_BH 33, tien tinh dung theo 33
        $r = $this->dung($this->du([$this->thuoc(['price' => 499.95])]))[0];

        $this->assertSame('33', $r['TYLE_TT_BH']);
        $this->assertSame('15000.00', $r['THANH_TIEN_BV']);
        $this->assertSame(sprintf('%.2f', round(15000 * 0.33, 2)), $r['THANH_TIEN_BH']);
        $this->assertSame('4950.00', $r['THANH_TIEN_BH']);
        $this->assertSame('3960.00', $r['T_BHTT']);
        $this->assertSame('990.00', $r['T_BNCCT']);
        $this->assertSame('10050.00', $r['T_BNTT']);

        // 32,5% -> half-up 33 (DLL round banker ra 32)
        $r = $this->dung($this->du([$this->thuoc(['price' => 487.5])]))[0];
        $this->assertSame('33', $r['TYLE_TT_BH']);
        $this->assertSame('4950.00', $r['THANH_TIEN_BH']);

        // 12,5% qua HEIN_LIMIT_PRICE -> 13
        $r = $this->dung($this->du([$this->thuoc(['hein_limit_price' => 187.5])]))[0];
        $this->assertSame('13', $r['TYLE_TT_BH']);
        $this->assertSame('1950.00', $r['THANH_TIEN_BH']);
    }

    public function test_ty_le_duoi_0_5_lam_tron_ve_0_pham_vi_2()
    {
        $r = $this->dung($this->du([$this->thuoc(['price' => 6])]))[0]; // 0,4%

        $this->assertSame('0', $r['TYLE_TT_BH']);
        $this->assertSame('2', $r['PHAM_VI']);
        $this->assertSame('0.00', $r['THANH_TIEN_BH']);
    }

    public function test_quy_doi_don_vi_lam_tron()
    {
        $r = $this->dung($this->du([$this->thuoc([
            'convert_ratio' => 3, 'convert_unit_name' => 'Goi', 'original_price' => 1001, 'price' => 1001,
        ])]))[0];

        $this->assertSame('Goi', $r['DON_VI_TINH']);
        $this->assertSame('30', $r['SO_LUONG']);
        $this->assertSame('333.667', $r['DON_GIA']);
        $this->assertRegExp('/^\d+\.\d{3}$/', $r['DON_GIA']);
        $this->assertSame('10010.01', $r['THANH_TIEN_BV']);
    }

    public function test_tach_dong_theo_lan_dung_nhan_ty_le_quy_doi()
    {
        $dm = $this->dm(['HIS.QD_130_BYT.XML2.NGAY_TH_YL_CUA_THUOC_OPTION' => '1']);
        $d = $this->du([$this->thuoc(['convert_ratio' => 3, 'convert_unit_name' => 'Goi', 'amount' => 2])], [
            (object) ['id' => 2, 'exp_mest_medicine_id' => 701, 'exp_mest_material_id' => null,
                'use_time' => 20261003000000, 'amount' => 1, 'medication_session' => 3],
            (object) ['id' => 1, 'exp_mest_medicine_id' => 701, 'exp_mest_material_id' => null,
                'use_time' => 20261002000000, 'amount' => 1, 'medication_session' => 1],
            (object) ['id' => 3, 'exp_mest_medicine_id' => 999, 'exp_mest_material_id' => null,
                'use_time' => 20261002000000, 'amount' => 5, 'medication_session' => 1],
        ]);

        $dong = $this->dung($d, $dm);

        $this->assertCount(2, $dong);
        $this->assertSame(['1', '2'], [$dong[0]['STT'], $dong[1]['STT']]);
        $this->assertSame('3', $dong[0]['SO_LUONG']);
        $this->assertSame('500', $dong[0]['DON_GIA']);
        $this->assertSame('202610020700', $dong[0]['NGAY_TH_YL']);
        $this->assertSame('202610031500', $dong[1]['NGAY_TH_YL']);
    }

    public function test_nguon_khac_khong_am()
    {
        // TYLE 120% (tran > gia) lam BH > BV: DLL can doi ra T_NGUONKHAC = -10
        $r = $this->dung($this->du([$this->thuoc([
            'amount' => 1, 'price' => 100, 'original_price' => 100, 'hein_limit_price' => 120,
            'other_source_price' => 10, 'hein_pay_source_type_id' => 1, 'vir_total_patient_price_bhyt' => 0,
        ])]))[0];

        $this->assertSame('100.00', $r['THANH_TIEN_BV']);
        $this->assertSame('0.00', $r['T_NGUONKHAC']);
        $this->assertSame('0.00', $r['T_NGUONKHAC_NSNN']);
        $tong = (float) $r['T_BHTT'] + (float) $r['T_BNCCT'] + (float) $r['T_BNTT'] + (float) $r['T_NGUONKHAC'];
        $this->assertEquals(100.0, $tong, '', 0.001);
    }

    public function test_nguon_khac_can_doi_tong()
    {
        $r = $this->dung($this->du([$this->thuoc([
            'other_source_price' => 100, 'hein_pay_source_type_id' => 2,
        ])]))[0];

        $this->assertSame('1000.00', $r['T_NGUONKHAC']);
        $this->assertSame('1000.00', $r['T_NGUONKHAC_VTNN']);
        $this->assertSame('0.00', $r['T_NGUONKHAC_CL']);
        $this->assertSame('2000.00', $r['T_BNCCT']);
    }

    public function test_tinh_lai_bncct_bntt_theo_nguon_khac_con_lai()
    {
        $thuoc = $this->thuoc(['price' => 750, 'other_source_price' => 100]);

        $r = $this->dung($this->du([$thuoc]))[0];
        $this->assertSame(['500.00', '7500.00', '1000.00'], [$r['T_BNCCT'], $r['T_BNTT'], $r['T_NGUONKHAC_CL']]);

        $r = $this->dung($this->du([$thuoc]), $this->dm([Xml2Dung::KEY_TINH_LAI_NK => '1']))[0];
        $this->assertSame(['1500.00', '6500.00'], [$r['T_BNCCT'], $r['T_BNTT']]);
    }

    public function test_an_ngay_th_yl_ngoai_noi_tru()
    {
        $r = $this->dung($this->du([$this->thuoc()]), $this->dm([Xml2Dung::KEY_AN_NGAY_TH_YL => '1']))[0];
        $this->assertSame('', $r['NGAY_TH_YL']);
    }

    public function test_thieu_danh_muc_doi_tuong_nem_loi_dung_bang()
    {
        try {
            $this->dung($this->du([$this->thuoc(['patient_type_id' => 99, 'vir_total_hein_price' => 0])]));
            $this->fail('Phai nem LoiDungBang');
        } catch (LoiDungBang $e) {
            $this->assertSame('XML2', $e->loaiBang());
            $this->assertContains('99', $e->getMessage());
        }
    }

    public function test_khong_co_doi_tuong_bhyt_trong_danh_muc_nem_loi()
    {
        $this->expectException(LoiDungBang::class);
        $this->dung($this->du([$this->thuoc()]), $this->dm([HisDanhMuc::KEY_MA_DOI_TUONG_BHYT => 'KHONGCO']));
    }

    public function test_thu_tu_khoa_dung_chi_tieu()
    {
        $r = $this->dung($this->du([$this->thuoc()]))[0];

        $this->assertSame(self::TRUONG, array_keys($r));
        $this->assertCount(39, $r);
    }

    public function test_chon_dong()
    {
        $dong = $this->dung($this->du([
            $this->thuoc(['id' => 1, 'intruction_time' => 20261003080000]),
            $this->thuoc(['id' => 2, 'tdl_hein_service_type_id' => 14]),            // VTYT
            $this->thuoc(['id' => 3, 'is_no_execute' => 1]),
            $this->thuoc(['id' => 4, 'price' => 0]),
            $this->thuoc(['id' => 5, 'vir_total_hein_price' => 0]),                 // BHYT 0 dong, khong NK
            $this->thuoc(['id' => 6, 'vir_total_hein_price' => 0, 'other_source_price' => 5]),
            $this->thuoc(['id' => 7, 'patient_type_id' => 2, 'vir_total_hein_price' => null]), // vien phi
            $this->thuoc(['id' => 8, 'tdl_hein_service_type_id' => 21, 'medicine_id' => null]),
        ]));

        // con lai id 6, 7, 8, 1 (sap on dinh theo INTRUCTION_TIME)
        $this->assertSame(['40.17', '40.17', 'TH1', '40.17'], array_column($dong, 'MA_THUOC'));
        $this->assertSame(['1', '2', '3', '4'], array_column($dong, 'STT'));
        $this->assertSame('202610030800', $dong[3]['NGAY_YL']); // id 1 sap cuoi theo INTRUCTION_TIME
    }

    public function test_doi_tuong_khac_tyle_0_pham_vi_2()
    {
        $r = $this->dung($this->du([$this->thuoc(['patient_type_id' => 2, 'vir_total_hein_price' => null])]))[0];

        $this->assertSame('0', $r['TYLE_TT_BH']);
        $this->assertSame('2', $r['PHAM_VI']);
        $this->assertSame('0.00', $r['THANH_TIEN_BH']);
        $this->assertSame('15000.00', $r['T_BNTT']);
        $this->assertSame('4', $r['NGUON_CTRA']);
    }

    public function test_lieu_dung_va_tt_thau()
    {
        $r = $this->dung($this->du([$this->thuoc([
            'tutorial' => 'Ngay uong 2 lan (sang, chieu)', 'use_time' => 20261002000000, 'use_time_to' => 20261006000000,
            'medicine_tt_thau' => '', 'medicine_bid_extra_code' => 'G1', 'medicine_bid_year' => '2026',
        ])]), $this->dm(['HIS.Desktop.Plugins.AssignPrescription.TutorialFormat' => '1']))[0];

        $this->assertSame('Ngay uong 2 lan  * 5 ngày [2 Vien/ngày]', $r['LIEU_DUNG']);
        $this->assertSame('G1;2026', $r['TT_THAU']);
    }

    public function test_bo_thuoc_co_dich_vu_cha_khong_thuc_hien()
    {
        $cha = $this->thuoc(['id' => 90, 'tdl_hein_service_type_id' => 8, 'is_no_execute' => 1, 'tdl_hein_service_bhyt_code' => 'DV90']);
        $con = $this->thuoc(['parent_id' => 90]);

        $this->assertSame('DV90', $this->dung($this->du([$cha, $con]))[0]['MA_DICH_VU']);
        $this->assertSame([], $this->dung($this->du([$cha, $con]),
            $this->dm(['HIS.QD_130_BYT.XML3.KHONG_LAY_DVU_KHONG_THUC_HIEN' => '1'])));
    }

    public function test_hao_phi_va_so_luong_0_khong_vao_xml2()
    {
        $chiTiet = $this->dung($this->du([
            $this->thuoc(['id' => 11, 'is_expend' => 1]),
            $this->thuoc(['id' => 12, 'amount' => 0]),
            $this->thuoc(['id' => 13]),
        ]));

        $this->assertCount(1, $chiTiet);
        $this->assertFalse((new Xml2Dung())->coTrongHoSo($this->du([$this->thuoc(['is_expend' => 1])])));
    }

    public function test_co_trong_ho_so()
    {
        $x = new Xml2Dung();
        $this->assertSame('XML2', $x->loai());
        $this->assertTrue($x->coTrongHoSo($this->du([$this->thuoc()])));
        $this->assertFalse($x->coTrongHoSo($this->du([$this->thuoc(['tdl_hein_service_type_id' => 14])])));
        $this->assertFalse($x->coTrongHoSo($this->du([])));
    }
}

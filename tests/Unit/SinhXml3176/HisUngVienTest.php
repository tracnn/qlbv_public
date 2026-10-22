<?php

namespace Tests\Unit\SinhXml3176;

use App\Services\SinhXml3176\CauHinhSinhXml3176;
use App\Services\SinhXml3176\Nguon\HisUngVien;
use Illuminate\Support\Facades\DB;
use Tests\Support\DungBangHisSinhXml3176Sqlite;
use Tests\TestCase;

class HisUngVienTest extends TestCase
{
    use DungBangHisSinhXml3176Sqlite;

    protected function setUp()
    {
        parent::setUp();
        $this->dungBangHis();
        DB::connection('HISPro')->table('his_branch')->insert([
            ['id' => 1, 'hein_medi_org_code' => '01001'],
            ['id' => 2, 'hein_medi_org_code' => '01002'],
        ]);
        DB::connection('HISPro')->table('his_patient_type')->insert([
            ['id' => 11, 'patient_type_code' => 'BHYT'],
            ['id' => 12, 'patient_type_code' => 'VP'],
        ]);
        DB::connection('HISPro')->table('his_treatment_type')->insert([
            ['id' => 21, 'treatment_type_code' => 'NOITRU'],
            ['id' => 22, 'treatment_type_code' => 'NGOAITRU'],
        ]);
    }

    private function datCauHinh(array $khoi)
    {
        config(['organization.sinh_xml3176' => array_merge(['ket_noi_his' => 'HISPro'], $khoi)]);

        return new HisUngVien(CauHinhSinhXml3176::tuConfig());
    }

    private function them(array $ghiDe)
    {
        static $n = 0;
        $n++;
        DB::connection('HISPro')->table('his_treatment')->insert(array_merge([
            'treatment_code'        => 'HS' . $n,
            'branch_id'             => 1,
            'tdl_patient_type_id'   => 11,
            'tdl_treatment_type_id' => 21,
            'fee_lock_time'         => null,
            'out_time'              => null,
            'is_delete'             => 0,
        ], $ghiDe));
    }

    private function maLo($lo)
    {
        return array_map(function ($r) {
            return $r->treatment_code;
        }, $lo);
    }

    public function test_chinh_thuc_lay_theo_fee_lock_va_sap_xep()
    {
        $this->them(['treatment_code' => 'C', 'fee_lock_time' => 20260105000000, 'out_time' => 20260105000000]);
        $this->them(['treatment_code' => 'A', 'fee_lock_time' => 20260103000000, 'out_time' => 20260103000000]);
        $this->them(['treatment_code' => 'B', 'fee_lock_time' => 20260103000000, 'out_time' => 20260103000000]);
        $this->them(['treatment_code' => 'CU', 'fee_lock_time' => 20260101000000, 'out_time' => 20260101000000]);
        $this->them(['treatment_code' => 'CHUARA', 'fee_lock_time' => 20260106000000, 'out_time' => null]);

        $h = $this->datCauHinh([]);
        $h->doiMa();
        $lo = $h->layLo(CauHinhSinhXml3176::CHINH_THUC, 20260102000000, 10);

        $this->assertSame(['A', 'B', 'C'], $this->maLo($lo));
        $this->assertSame('20260103000000', (string) $lo[0]->fee_lock_time);
        $this->assertObjectHasAttribute('id', $lo[0]);
        $this->assertObjectHasAttribute('out_time', $lo[0]);

        $this->assertSame(['A'], $this->maLo($h->layLo(CauHinhSinhXml3176::CHINH_THUC, 20260102000000, 1)));
    }

    public function test_thong_tuyen_chi_lay_chua_thanh_toan()
    {
        $this->them(['treatment_code' => 'TT2', 'fee_lock_time' => null, 'out_time' => 20260105000000]);
        $this->them(['treatment_code' => 'TT1', 'fee_lock_time' => null, 'out_time' => 20260103000000]);
        $this->them(['treatment_code' => 'DATT', 'fee_lock_time' => 20260104000000, 'out_time' => 20260104000000]);
        $this->them(['treatment_code' => 'CU', 'fee_lock_time' => null, 'out_time' => 20260101000000]);

        $h = $this->datCauHinh([]);
        $h->doiMa();

        $this->assertSame(['TT1', 'TT2'], $this->maLo($h->layLo(CauHinhSinhXml3176::THONG_TUYEN, 20260102000000, 10)));
    }

    public function test_loc_co_so_doi_tuong_dien()
    {
        $t = 20260103000000;
        $this->them(['treatment_code' => 'OK', 'fee_lock_time' => $t, 'out_time' => $t]);
        $this->them(['treatment_code' => 'SAICS', 'branch_id' => 2, 'fee_lock_time' => $t, 'out_time' => $t]);
        $this->them(['treatment_code' => 'SAIDT', 'tdl_patient_type_id' => 12, 'fee_lock_time' => $t, 'out_time' => $t]);
        $this->them(['treatment_code' => 'SAIDIEN', 'tdl_treatment_type_id' => 22, 'fee_lock_time' => $t, 'out_time' => $t]);

        $h = $this->datCauHinh([
            'co_so'         => ['01001'],
            'doi_tuong_bn'  => ['BHYT'],
            'dien_dieu_tri' => ['NOITRU'],
        ]);
        $h->doiMa();

        $this->assertSame(['OK'], $this->maLo($h->layLo(CauHinhSinhXml3176::CHINH_THUC, 0, 10)));
    }

    public function test_bo_loc_rong_la_khong_loc()
    {
        $t = 20260103000000;
        $this->them(['treatment_code' => 'A', 'fee_lock_time' => $t, 'out_time' => $t]);
        $this->them(['treatment_code' => 'B', 'branch_id' => 2, 'tdl_patient_type_id' => 12, 'tdl_treatment_type_id' => 22, 'fee_lock_time' => $t, 'out_time' => $t]);

        $h = $this->datCauHinh([]);
        $h->doiMa();

        $this->assertSame(['A', 'B'], $this->maLo($h->layLo(CauHinhSinhXml3176::CHINH_THUC, 0, 10)));
        $this->assertSame([], $h->idDoiTuongTt());
    }

    public function test_id_doi_tuong_tt()
    {
        $h = $this->datCauHinh(['doi_tuong_tt' => ['VP']]);
        $h->doiMa();

        $this->assertSame([12], $h->idDoiTuongTt());
    }

    public function test_ma_sai_thi_nem_loi_cau_hinh()
    {
        $h = $this->datCauHinh(['doi_tuong_bn' => ['BHYT', 'GOSAI']]);

        try {
            $h->doiMa();
            $this->fail('Phai nem InvalidArgumentException');
        } catch (\InvalidArgumentException $e) {
            $this->assertContains('doi_tuong_bn', $e->getMessage());
            $this->assertContains('GOSAI', $e->getMessage());
        }
    }

    public function test_goi_lay_lo_truoc_doi_ma_thi_nem()
    {
        $h = $this->datCauHinh([]);

        $this->expectException(\LogicException::class);
        $h->layLo(CauHinhSinhXml3176::CHINH_THUC, 0, 10);
    }

    public function test_bo_ho_so_da_xoa()
    {
        $t = 20260103000000;
        $this->them(['treatment_code' => 'XOA', 'fee_lock_time' => $t, 'out_time' => $t, 'is_delete' => 1]);
        $this->them(['treatment_code' => 'XOATT', 'fee_lock_time' => null, 'out_time' => $t, 'is_delete' => 1]);
        $this->them(['treatment_code' => 'CON', 'fee_lock_time' => $t, 'out_time' => $t]);

        $h = $this->datCauHinh([]);
        $h->doiMa();

        $this->assertSame(['CON'], $this->maLo($h->layLo(CauHinhSinhXml3176::CHINH_THUC, 0, 10)));
        $this->assertSame([], $this->maLo($h->layLo(CauHinhSinhXml3176::THONG_TUYEN, 0, 10)));
    }
}

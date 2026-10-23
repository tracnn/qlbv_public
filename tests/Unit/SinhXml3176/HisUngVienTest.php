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
        $lo = $h->layLo(CauHinhSinhXml3176::CHINH_THUC, 20260102000000, 10, 0, null, true);

        $this->assertSame(['A', 'B', 'C'], $this->maLo($lo));
        $this->assertSame('20260103000000', (string) $lo[0]->fee_lock_time);
        $this->assertObjectHasAttribute('id', $lo[0]);
        $this->assertObjectHasAttribute('out_time', $lo[0]);

        $this->assertSame(['A'], $this->maLo($h->layLo(CauHinhSinhXml3176::CHINH_THUC, 20260102000000, 1, 0, null, true)));
    }

    public function test_thong_tuyen_chi_lay_chua_thanh_toan()
    {
        $this->them(['treatment_code' => 'TT2', 'fee_lock_time' => null, 'out_time' => 20260105000000]);
        $this->them(['treatment_code' => 'TT1', 'fee_lock_time' => null, 'out_time' => 20260103000000]);
        $this->them(['treatment_code' => 'DATT', 'fee_lock_time' => 20260104000000, 'out_time' => 20260104000000]);
        $this->them(['treatment_code' => 'CU', 'fee_lock_time' => null, 'out_time' => 20260101000000]);

        $h = $this->datCauHinh([]);
        $h->doiMa();

        $this->assertSame(['TT1', 'TT2'], $this->maLo($h->layLo(CauHinhSinhXml3176::THONG_TUYEN, 20260102000000, 10, 0, null, true)));
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

        $this->assertSame(['OK'], $this->maLo($h->layLo(CauHinhSinhXml3176::CHINH_THUC, 0, 10, 0, null, true)));
    }

    public function test_bo_loc_rong_la_khong_loc()
    {
        $t = 20260103000000;
        $this->them(['treatment_code' => 'A', 'fee_lock_time' => $t, 'out_time' => $t]);
        $this->them(['treatment_code' => 'B', 'branch_id' => 2, 'tdl_patient_type_id' => 12, 'tdl_treatment_type_id' => 22, 'fee_lock_time' => $t, 'out_time' => $t]);

        $h = $this->datCauHinh([]);
        $h->doiMa();

        $this->assertSame(['A', 'B'], $this->maLo($h->layLo(CauHinhSinhXml3176::CHINH_THUC, 0, 10, 0, null, true)));
        $this->assertSame([], $h->idDoiTuongTt());
    }

    public function test_id_doi_tuong_tt()
    {
        $h = $this->datCauHinh(['doi_tuong_tt' => ['VP']]);
        $h->doiMa();

        $this->assertSame([12], $h->idDoiTuongTt());
    }

    public function test_mot_ma_nhieu_id_lay_hop()
    {
        // Hai his_branch cung HEIN_MEDI_ORG_CODE: ca hai ID deu phai vao bo loc
        DB::connection('HISPro')->table('his_branch')->insert(['id' => 3, 'hein_medi_org_code' => '01001']);
        $t = 20260103000000;
        $this->them(['treatment_code' => 'B1', 'branch_id' => 1, 'fee_lock_time' => $t, 'out_time' => $t]);
        $this->them(['treatment_code' => 'B2', 'branch_id' => 2, 'fee_lock_time' => $t, 'out_time' => $t]);
        $this->them(['treatment_code' => 'B3', 'branch_id' => 3, 'fee_lock_time' => $t, 'out_time' => $t]);

        $h = $this->datCauHinh(['co_so' => ['01001']]);
        $h->doiMa();

        $this->assertSame(['B1', 'B3'], $this->maLo($h->layLo(CauHinhSinhXml3176::CHINH_THUC, 0, 10, 0, null, true)));
        $this->assertSame('01001', $h->maCskcb(3));
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
        $h->layLo(CauHinhSinhXml3176::CHINH_THUC, 0, 10, 0, null, true);
    }

    public function test_bo_ho_so_da_xoa()
    {
        $t = 20260103000000;
        $this->them(['treatment_code' => 'XOA', 'fee_lock_time' => $t, 'out_time' => $t, 'is_delete' => 1]);
        $this->them(['treatment_code' => 'XOATT', 'fee_lock_time' => null, 'out_time' => $t, 'is_delete' => 1]);
        $this->them(['treatment_code' => 'CON', 'fee_lock_time' => $t, 'out_time' => $t]);

        $h = $this->datCauHinh([]);
        $h->doiMa();

        $this->assertSame(['CON'], $this->maLo($h->layLo(CauHinhSinhXml3176::CHINH_THUC, 0, 10, 0, null, true)));
        $this->assertSame([], $this->maLo($h->layLo(CauHinhSinhXml3176::THONG_TUYEN, 0, 10, 0, null, true)));
    }
    public function test_lay_lo_keyset_cung_moc_theo_id()
    {
        $t = 20260103000000;
        foreach (['K1', 'K2', 'K3', 'K4', 'K5'] as $ma) {
            $this->them(['treatment_code' => $ma, 'fee_lock_time' => $t, 'out_time' => $t]);
        }
        $this->them(['treatment_code' => 'SAU', 'fee_lock_time' => $t + 1, 'out_time' => $t]);
        $this->them(['treatment_code' => 'TRUOC', 'fee_lock_time' => $t - 1, 'out_time' => $t]);

        $h = $this->datCauHinh([]);
        $h->doiMa();

        // sauId = 0: lay ca dong dung bang moc
        $trang1 = $h->layLo(CauHinhSinhXml3176::CHINH_THUC, $t, 2, 0, null, true);
        $this->assertSame(['K1', 'K2'], $this->maLo($trang1));

        $trang2 = $h->layLo(CauHinhSinhXml3176::CHINH_THUC, $t, 2, (int) $trang1[1]->id, null, true);
        $this->assertSame(['K3', 'K4'], $this->maLo($trang2));

        $trang3 = $h->layLo(CauHinhSinhXml3176::CHINH_THUC, $t, 2, (int) $trang2[1]->id, null, true);
        $this->assertSame(['K5', 'SAU'], $this->maLo($trang3));

        $trang4 = $h->layLo(CauHinhSinhXml3176::CHINH_THUC, (int) $trang3[1]->fee_lock_time, 2, (int) $trang3[1]->id, null, true);
        $this->assertSame([], $this->maLo($trang4));
    }

    public function test_lay_lo_keyset_thong_tuyen_va_den_moc()
    {
        $this->them(['treatment_code' => 'T1', 'out_time' => 20260103000000]);
        $this->them(['treatment_code' => 'T2', 'out_time' => 20260103000000]);
        $this->them(['treatment_code' => 'T3', 'out_time' => 20260104000000]);
        $this->them(['treatment_code' => 'T4', 'out_time' => 20260105000000]);

        $h = $this->datCauHinh([]);
        $h->doiMa();

        $tt = CauHinhSinhXml3176::THONG_TUYEN;
        $dau = $h->layLo($tt, 20260103000000, 1, 0, null, true);
        $this->assertSame(['T1'], $this->maLo($dau));
        $this->assertSame(['T2', 'T3'], $this->maLo($h->layLo($tt, 20260103000000, 10, (int) $dau[0]->id, 20260104000000, true)));
    }

    private function homNay($ngay)
    {
        return new \DateTime($ngay . ' 15:30:00');
    }

    private function sapTheoMa(array $lo, array $thuTu)
    {
        usort($lo, function ($a, $b) use ($thuTu) {
            return array_search($a->treatment_code, $thuTu) - array_search($b->treatment_code, $thuTu);
        });

        return $lo;
    }

    public function test_lay_lo_theo_modify_time_va_keyset()
    {
        $ra = 20260101000000;
        $this->them(['treatment_code' => 'M3', 'fee_lock_time' => $ra, 'out_time' => $ra, 'modify_time' => 20260110000000]);
        $this->them(['treatment_code' => 'M1', 'fee_lock_time' => $ra, 'out_time' => $ra, 'modify_time' => 20260105000000]);
        $this->them(['treatment_code' => 'M2', 'fee_lock_time' => $ra, 'out_time' => $ra, 'modify_time' => 20260105000000]);
        $this->them(['treatment_code' => 'CU', 'fee_lock_time' => $ra, 'out_time' => $ra, 'modify_time' => 20260101000000]);
        $this->them(['treatment_code' => 'TT', 'fee_lock_time' => null, 'out_time' => $ra, 'modify_time' => 20260106000000]);

        $h = $this->datCauHinh([]);
        $h->datHomNay($this->homNay('2026-01-10'));
        $h->doiMa();

        $this->assertSame('modify_time', HisUngVien::COT_QUET);
        $trang1 = $h->layLo(CauHinhSinhXml3176::CHINH_THUC, 20260102000000, 2);
        $this->assertSame(['M1', 'M2'], $this->maLo($trang1));
        $this->assertSame('20260105000000', (string) $trang1[0]->modify_time);
        $trang2 = $h->layLo(CauHinhSinhXml3176::CHINH_THUC, 20260105000000, 2, (int) $trang1[1]->id);
        $this->assertSame(['M3'], $this->maLo($trang2));
        $this->assertSame(['TT'], $this->maLo($h->layLo(CauHinhSinhXml3176::THONG_TUYEN, 0, 10)));
        $theoMa = $h->layTheoMa(CauHinhSinhXml3176::CHINH_THUC, ['M1']);
        $this->assertObjectHasAttribute('modify_time', $theoMa[0]);
    }

    public function test_cua_so_ra_vien_loai_ho_so_ra_vien_cu()
    {
        // Hom nay 2026-03-10, cua so 60 ngay => out_time >= 2026-01-09 00:00:00
        $this->them(['treatment_code' => 'TRONG', 'fee_lock_time' => 20260301000000, 'out_time' => 20260109000000, 'modify_time' => 20260309000000]);
        $this->them(['treatment_code' => 'NGOAI', 'fee_lock_time' => 20260301000000, 'out_time' => 20260108235959, 'modify_time' => 20260309000000]);
        $this->them(['treatment_code' => 'TTNGOAI', 'fee_lock_time' => null, 'out_time' => 20251201000000, 'modify_time' => 20260309000000]);

        $h = $this->datCauHinh([]);
        $h->datHomNay($this->homNay('2026-03-10'));
        $h->doiMa();

        $this->assertSame(['TRONG'], $this->maLo($h->layLo(CauHinhSinhXml3176::CHINH_THUC, 0, 10)));
        $this->assertSame([], $this->maLo($h->layLo(CauHinhSinhXml3176::THONG_TUYEN, 0, 10)));

        // Cua so rong hon (cau hinh) thi lay them ho so cu
        $h2 = $this->datCauHinh([]);
        config(['sinh_xml3176.cua_so_ra_vien_ngay' => 1000]);
        $h2 = new HisUngVien(CauHinhSinhXml3176::tuConfig());
        $h2->datHomNay($this->homNay('2026-03-10'));
        $h2->doiMa();
        $this->assertSame(['TRONG', 'NGOAI'], $this->maLo($h2->layLo(CauHinhSinhXml3176::CHINH_THUC, 0, 10)));
    }

    public function test_chay_bu_theo_ngay_van_dung_fee_lock_time()
    {
        // modify_time va cua so khong anh huong chay bu theo ngay
        $this->them(['treatment_code' => 'CT', 'fee_lock_time' => 20200105000000, 'out_time' => 20200101000000, 'modify_time' => 20260301000000]);
        $this->them(['treatment_code' => 'CT2', 'fee_lock_time' => 20200109000000, 'out_time' => 20200101000000, 'modify_time' => 20200101000000]);
        $this->them(['treatment_code' => 'TT', 'fee_lock_time' => null, 'out_time' => 20200106000000, 'modify_time' => 20260301000000]);

        $h = $this->datCauHinh([]);
        $h->datHomNay($this->homNay('2026-03-10'));
        $h->doiMa();

        $this->assertSame(['CT'], $this->maLo($h->layLo(CauHinhSinhXml3176::CHINH_THUC, 20200104000000, 10, 0, 20200106000000, true)));
        $this->assertSame(['TT'], $this->maLo($h->layLo(CauHinhSinhXml3176::THONG_TUYEN, 20200104000000, 10, 0, 20200107000000, true)));
        $this->assertSame('fee_lock_time', HisUngVien::cotMoc(CauHinhSinhXml3176::CHINH_THUC));
        $this->assertSame('out_time', HisUngVien::cotMoc(CauHinhSinhXml3176::THONG_TUYEN));
    }

    public function test_dieu_kien_loai_ban_giu_nguyen()
    {
        $t = 20260301000000;
        $this->them(['treatment_code' => 'CTH', 'fee_lock_time' => $t, 'out_time' => $t, 'modify_time' => $t]);
        $this->them(['treatment_code' => 'CHUARA', 'fee_lock_time' => $t, 'out_time' => null, 'modify_time' => $t]);
        $this->them(['treatment_code' => 'TTUYEN', 'fee_lock_time' => null, 'out_time' => $t, 'modify_time' => $t]);
        $this->them(['treatment_code' => 'XOA', 'fee_lock_time' => $t, 'out_time' => $t, 'modify_time' => $t, 'is_delete' => 1]);
        $this->them(['treatment_code' => 'SAICS', 'branch_id' => 2, 'fee_lock_time' => $t, 'out_time' => $t, 'modify_time' => $t]);

        $h = $this->datCauHinh(['co_so' => ['01001']]);
        $h->datHomNay($this->homNay('2026-03-10'));
        $h->doiMa();

        $this->assertSame(['CTH'], $this->maLo($h->layLo(CauHinhSinhXml3176::CHINH_THUC, 0, 10)));
        $this->assertSame(['TTUYEN'], $this->maLo($h->layLo(CauHinhSinhXml3176::THONG_TUYEN, 0, 10)));
    }

    public function test_lay_theo_ma_va_ma_cskcb()
    {
        $t = 20260103000000;
        $this->them(['treatment_code' => 'M1', 'fee_lock_time' => $t, 'out_time' => $t]);
        $this->them(['treatment_code' => 'M2', 'branch_id' => 2, 'fee_lock_time' => $t, 'out_time' => $t]);
        $this->them(['treatment_code' => 'M3', 'fee_lock_time' => $t, 'out_time' => $t, 'is_delete' => 1]);

        $h = $this->datCauHinh(['co_so' => ['01001']]);
        $h->doiMa();

        $this->assertSame(['M1'], $this->maLo($h->layTheoMa(CauHinhSinhXml3176::CHINH_THUC, ['M1', 'M2', 'M3', 'KHONG'])));
        $this->assertSame([], $h->layTheoMa(CauHinhSinhXml3176::CHINH_THUC, []));
        $this->assertSame('01002', $h->maCskcb(2));
        $this->assertNull($h->maCskcb(99));
        $this->assertNull($h->maCskcb(null));
    }
}

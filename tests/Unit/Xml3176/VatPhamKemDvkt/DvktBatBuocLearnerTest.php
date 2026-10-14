<?php

namespace Tests\Unit\Xml3176\VatPhamKemDvkt;

use App\Models\BHYT\Xml3176VatPhamKemDvkt;
use App\Models\BHYT\Xml3176VpkdDvktBatBuoc;
use App\Services\Xml3176\VatPhamKemDvktLearner;
use Illuminate\Support\Facades\DB;
use Tests\Support\Xml3176RuleTestSupport;
use Tests\TestCase;

class DvktBatBuocLearnerTest extends TestCase
{
    use Xml3176RuleTestSupport;

    private $stt = 0;

    protected function setUp()
    {
        parent::setUp();
        $this->bootXml3176Sqlite([
            '2026_01_09_152826_create_xml3176_xml2s_table.php',
            '2026_01_09_152832_create_xml3176_xml3s_table.php',
            '2026_10_13_090000_create_xml3176_vat_pham_kem_dvkt_table.php',
            '2026_10_14_090000_them_nguoi_sua_vao_xml3176_vat_pham_kem_dvkt.php',
            '2026_01_09_161708_create_xml3176_error_catalogs_table.php',
            '2026_10_15_090000_create_xml3176_vpkd_dvkt_bat_buoc_table.php',
        ]);
        // Bang tra: Omnipaque -> CLVT co can quang, MRI co tuong phan; buong tiem truyen (ma nhom chung) -> dat buong
        foreach ([['THUOC', '40.644', '', '18.0219.0041'], ['THUOC', '40.644', '', '18.0320.0065'],
                  ['VTYT', 'N08.00.150', 'buồng tiêm truyền', '12.0448.1187']] as $d) {
            Xml3176VatPhamKemDvkt::create(['loai' => $d[0], 'ma_vat_pham' => $d[1], 'ten_khop' => $d[2],
                'ma_dvkt' => $d[3], 'ten_dvkt' => 'DV ' . $d[3], 'nguon' => 'HOC']);
        }
    }

    private function dv(string $lk, string $ma, $bhtt = 1000): void
    {
        DB::table('xml3176_xml3s')->insert(['ma_lk' => $lk, 'stt' => ++$this->stt, 'ma_vat_tu' => '',
            'ma_dich_vu' => $ma, 'ten_dich_vu' => 'DV ' . $ma, 't_bhtt' => $bhtt, 'so_luong' => 1, 'ngay_yl' => '202609010800']);
    }

    private function thuoc(string $lk, string $ma, $bhtt = 1000): void
    {
        DB::table('xml3176_xml2s')->insert(['ma_lk' => $lk, 'stt' => ++$this->stt, 'ma_thuoc' => $ma,
            'ten_thuoc' => 'Omnipaque', 't_bhtt' => $bhtt, 'ngay_yl' => '202609010800']);
    }

    private function vt(string $lk, string $ma, string $ten, $bhtt = 1000): void
    {
        DB::table('xml3176_xml3s')->insert(['ma_lk' => $lk, 'stt' => ++$this->stt, 'ma_vat_tu' => $ma,
            'ten_vat_tu' => $ten, 't_bhtt' => $bhtt, 'so_luong' => 1, 'ngay_yl' => '202609010800']);
    }

    private function l(): VatPhamKemDvktLearner
    {
        return new VatPhamKemDvktLearner();
    }

    /** @test */
    public function chon_dvkt_dat_ty_le_va_so_ho_so()
    {
        for ($i = 0; $i < 20; $i++) {          // CLVT: 19/20 co thuoc BHYT (95%)
            $this->dv("CT$i", '18.0219.0041');
            if ($i < 19) {
                $this->thuoc("CT$i", '40.644');
            }
        }
        for ($i = 0; $i < 20; $i++) {          // MRI: 7/20 co thuoc (35%) -> loai
            $this->dv("MR$i", '18.0320.0065');
            if ($i < 7) {
                $this->thuoc("MR$i", '40.644');
            }
        }

        $kq = $this->l()->hocDvktBatBuoc(0.9, 20);

        $this->assertCount(1, $kq);
        $this->assertSame('18.0219.0041', $kq[0]['ma_dvkt']);
        $this->assertSame(20, $kq[0]['so_ho_so']);
        $this->assertSame(19, $kq[0]['so_ho_so_co_kem']);
        $this->assertSame(0.95, $kq[0]['ty_le']);
        $this->assertSame('DV 18.0219.0041', $kq[0]['ten_dvkt']);
    }

    /** @test */
    public function duoi_so_ho_so_toi_thieu_bi_loai()
    {
        for ($i = 0; $i < 19; $i++) {
            $this->dv("CT$i", '18.0219.0041');
            $this->thuoc("CT$i", '40.644');
        }

        $this->assertSame([], $this->l()->hocDvktBatBuoc(0.9, 20));
    }

    /** @test */
    public function chi_dem_dong_bhyt_tra_ca_hai_phia()
    {
        for ($i = 0; $i < 20; $i++) {
            $this->dv("CT$i", '18.0219.0041');
            $this->thuoc("CT$i", '40.644', $i < 10 ? 1000 : 0);   // 10 HS thuoc BN tu tra
        }
        $this->dv('TU_TRA', '18.0219.0041', 0);                 // DVKT quy khong tra: khong dem

        $kq = $this->l()->hocDvktBatBuoc(0.4, 20);

        $this->assertSame(20, $kq[0]['so_ho_so']);
        $this->assertSame(10, $kq[0]['so_ho_so_co_kem']);
    }

    /** @test */
    public function dvkt_gt_chuan_hoa_va_vtyt_ma_nhom_chung_khop_ma_ten()
    {
        for ($i = 0; $i < 20; $i++) {
            $this->dv("B$i", '12.0448.1187_GT');
            $this->vt("B$i", 'N08.00.150', $i < 18 ? 'Buồng tiêm truyền' : 'Catheter khác');
        }

        $kq = $this->l()->hocDvktBatBuoc(0.9, 20);

        $this->assertSame('12.0448.1187', $kq[0]['ma_dvkt']);
        $this->assertSame(18, $kq[0]['so_ho_so_co_kem']);
    }

    /** @test */
    public function bang_tra_dong_tat_khong_vao_nhom()
    {
        Xml3176VatPhamKemDvkt::query()->update(['hieu_luc' => false]);
        for ($i = 0; $i < 20; $i++) {
            $this->dv("CT$i", '18.0219.0041');
            $this->thuoc("CT$i", '40.644');
        }

        $this->assertSame([], $this->l()->hocDvktBatBuoc(0.9, 20));
    }

    /** @test */
    public function ghi_them_cap_nhat_tat_bat_lai_giu_tay()
    {
        $l = $this->l();
        $d = ['ma_dvkt' => '18.0219.0041', 'ten_dvkt' => 'CLVT', 'so_ho_so' => 20, 'so_ho_so_co_kem' => 19, 'ty_le' => 0.95];
        Xml3176VpkdDvktBatBuoc::create(['ma_dvkt' => '99.9999.9999', 'nguon' => 'TAY']);

        $this->assertSame(['them' => 1, 'cap_nhat' => 0, 'tat' => 0, 'bat_lai' => 0], $l->ghiDvktBatBuoc([$d]));
        $this->assertSame(['them' => 0, 'cap_nhat' => 1, 'tat' => 0, 'bat_lai' => 0], $l->ghiDvktBatBuoc([['so_ho_so' => 30] + $d]));
        $this->assertSame(30, Xml3176VpkdDvktBatBuoc::where('ma_dvkt', '18.0219.0041')->first()->so_ho_so);

        $this->assertSame(['them' => 0, 'cap_nhat' => 0, 'tat' => 1, 'bat_lai' => 0], $l->ghiDvktBatBuoc([]));
        $m = Xml3176VpkdDvktBatBuoc::where('ma_dvkt', '18.0219.0041')->first();
        $this->assertFalse($m->hieu_luc);
        $this->assertSame(Xml3176VatPhamKemDvkt::GHI_CHU_TU_TAT, $m->ghi_chu);

        $this->assertSame(1, $l->ghiDvktBatBuoc([$d])['bat_lai']);
        $this->assertTrue(Xml3176VpkdDvktBatBuoc::where('ma_dvkt', '18.0219.0041')->first()->hieu_luc);

        Xml3176VpkdDvktBatBuoc::where('ma_dvkt', '18.0219.0041')->update(['hieu_luc' => false, 'ghi_chu' => 'Người dùng tắt']);
        $l->ghiDvktBatBuoc([$d]);
        $this->assertFalse(Xml3176VpkdDvktBatBuoc::where('ma_dvkt', '18.0219.0041')->first()->hieu_luc);
        $this->assertTrue(Xml3176VpkdDvktBatBuoc::where('ma_dvkt', '99.9999.9999')->first()->hieu_luc, 'Dong TAY khong bi dung');
    }
}

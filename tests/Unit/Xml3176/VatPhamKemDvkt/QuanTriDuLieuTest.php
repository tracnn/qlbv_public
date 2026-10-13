<?php

namespace Tests\Unit\Xml3176\VatPhamKemDvkt;

use App\Models\BHYT\Xml3176VatPhamKemDvkt;
use App\Models\BHYT\Xml3176VpkdLanHoc;
use App\Models\BHYT\Xml3176VpkdUngVien;
use Carbon\Carbon;
use Tests\Support\Xml3176RuleTestSupport;
use Tests\TestCase;

class QuanTriDuLieuTest extends TestCase
{
    use Xml3176RuleTestSupport;

    protected function setUp()
    {
        parent::setUp();
        $this->bootXml3176Sqlite([
            '2026_10_13_090000_create_xml3176_vat_pham_kem_dvkt_table.php',
            '2026_10_14_090000_them_nguoi_sua_vao_xml3176_vat_pham_kem_dvkt.php',
            '2026_10_14_090100_create_xml3176_vpkd_lan_hoc_table.php',
            '2026_10_14_090200_create_xml3176_vpkd_ung_vien_table.php',
        ]);
    }

    /** @test */
    public function bang_tra_co_cot_ghi_vet()
    {
        $m = Xml3176VatPhamKemDvkt::create([
            'loai' => 'VTYT', 'ma_vat_pham' => 'N08.00.150', 'ten_khop' => 'buồng', 'ma_dvkt' => '12.0448.1187',
            'nguon' => 'TAY', 'nguoi_sua_id' => '7', 'sua_luc' => '2026-10-14 08:00:00',
        ])->fresh();

        $this->assertSame(7, $m->nguoi_sua_id);
        $this->assertInstanceOf(Carbon::class, $m->sua_luc);
    }

    /** @test */
    public function lan_hoc_luu_ket_qua_dang_mang()
    {
        $l = Xml3176VpkdLanHoc::create([
            'user_id' => 7, 'trang_thai' => Xml3176VpkdLanHoc::XONG, 'ket_qua' => ['them' => 3],
            'bat_dau_luc' => '2026-10-14 08:00:00',
        ])->fresh();

        $this->assertSame(['them' => 3], $l->ket_qua);
        $this->assertInstanceOf(Carbon::class, $l->bat_dau_luc);
        $this->assertSame('CHO', Xml3176VpkdLanHoc::CHO);
        $this->assertSame('DANG_CHAY', Xml3176VpkdLanHoc::DANG_CHAY);
        $this->assertSame('LOI', Xml3176VpkdLanHoc::LOI);
    }

    /** @test */
    public function ung_vien_luu_dvkt_dang_mang_va_ep_kieu()
    {
        $u = Xml3176VpkdUngVien::create([
            'lan_hoc_id' => 1, 'loai' => 'THUOC', 'ma_vat_pham' => '40.644', 'ten_vat_pham' => 'Omnipaque',
            'ten_khop' => '', 'so_dong' => '10', 'so_lien_ket' => '6', 'ty_le_gan' => '0.6',
            'dvkt' => [['ma' => '18.0150.0041', 'ten' => 'CLVT', 'so_lan' => 6]],
        ])->fresh();

        $this->assertSame(10, $u->so_dong);
        $this->assertSame(0.6, $u->ty_le_gan);
        $this->assertSame('18.0150.0041', $u->dvkt[0]['ma']);
    }

    /** @test */
    public function config_co_nguong_ung_vien()
    {
        $this->assertEquals(0.5, config('xml3176.vat_pham_kem_dvkt.nguong_ung_vien'));
    }
}

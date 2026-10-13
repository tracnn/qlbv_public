<?php

namespace Tests\Unit\Xml3176\VatPhamKemDvkt;

use App\Models\BHYT\Xml3176VatPhamKemDvkt;
use App\Models\BHYT\Xml3176VpkdUngVien;
use App\Services\Xml3176\VatPhamKemDvktLearner;
use Illuminate\Support\Facades\DB;
use Tests\Support\Xml3176RuleTestSupport;
use Tests\TestCase;

class UngVienLearnerTest extends TestCase
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
            '2026_10_14_090100_create_xml3176_vpkd_lan_hoc_table.php',
            '2026_10_14_090200_create_xml3176_vpkd_ung_vien_table.php',
        ]);
    }

    /** $n dong VTYT BHYT tra; $dsGan = [ma_dvkt => so dong gan]. */
    private function vtyt(string $ma, string $ten, int $n, array $dsGan): void
    {
        $gan = [];
        foreach ($dsGan as $dv => $k) {
            for ($i = 0; $i < $k; $i++) {
                $gan[] = $dv;
            }
        }
        for ($i = 0; $i < $n; $i++) {
            DB::table('xml3176_xml3s')->insert([
                'ma_lk' => 'HS' . $i, 'stt' => ++$this->stt, 'ma_vat_tu' => $ma, 'ten_vat_tu' => $ten,
                'ma_dich_vu' => $gan[$i] ?? null, 't_bhtt' => 1000, 'so_luong' => 1, 'ngay_yl' => '202609010800',
            ]);
        }
    }

    private function hoc(): array
    {
        return (new VatPhamKemDvktLearner())->hoc(0.8, 5, null, 0.5);
    }

    /** @test */
    public function chon_ung_vien_theo_hai_nhanh_tieu_chi()
    {
        $this->vtyt('N01.01.001.0001.000.0001', 'A ty le 60%', 10, ['01.0001.0001' => 4, '01.0002.0001_GT' => 2]);
        $this->vtyt('N01.01.001.0001.000.0002', 'B du ty le thieu dong', 3, ['01.0003.0001' => 3]);
        $this->vtyt('N01.01.001.0001.000.0003', 'C ty le 30%', 10, ['01.0004.0001' => 3]);
        $this->vtyt('N01.01.001.0001.000.0004', 'D da vao bang tra', 10, ['01.0005.0001' => 9]);
        $this->vtyt('N01.01.001.0001.000.0005', 'E khong lien ket', 10, []);

        $kq = $this->hoc();
        $ma = array_column($kq['ung_vien'], 'ma_vat_pham');
        sort($ma);

        $this->assertSame(['N01.01.001.0001.000.0001', 'N01.01.001.0001.000.0002'], $ma);
        $this->assertSame(['VTYT' => 1, 'THUOC' => 0], $kq['so_vat_pham']);
        $this->assertCount(1, $kq['dong']);
    }

    /** @test */
    public function dvkt_ung_vien_chuan_hoa_va_sap_giam_dan()
    {
        $this->vtyt('N01.01.001.0001.000.0001', 'A', 10, ['01.0002.0001_GT' => 2, '01.0001.0001' => 4]);

        $u = $this->hoc()['ung_vien'][0];

        $this->assertSame(10, $u['so_dong']);
        $this->assertSame(6, $u['so_lien_ket']);
        $this->assertSame(0.6, $u['ty_le_gan']);
        $this->assertSame(['01.0001.0001', '01.0002.0001'], array_column($u['dvkt'], 'ma'));
        $this->assertSame([4, 2], array_column($u['dvkt'], 'so_lan'));
    }

    /** @test */
    public function ghi_ung_vien_thay_toan_bo()
    {
        $l = new VatPhamKemDvktLearner();
        $mau = ['loai' => 'VTYT', 'ma_vat_pham' => 'X', 'ten_vat_pham' => 'X', 'ten_khop' => '',
            'so_dong' => 10, 'so_lien_ket' => 6, 'ty_le_gan' => 0.6, 'dvkt' => []];

        $this->assertSame(2, $l->ghiUngVien([$mau, ['ma_vat_pham' => 'Y'] + $mau], 5));
        $this->assertSame(1, $l->ghiUngVien([['ma_vat_pham' => 'Z'] + $mau], 6));

        $this->assertSame(['Z'], Xml3176VpkdUngVien::pluck('ma_vat_pham')->all());
        $this->assertSame(6, Xml3176VpkdUngVien::first()->lan_hoc_id);
    }

    /** @test */
    public function nap_ghi_nguoi_sua_khi_duoc_truyen()
    {
        $l = new VatPhamKemDvktLearner();
        $hang = [['loai' => 'THUOC', 'ma_vat_pham' => '40.644', 'ten_vat_pham' => 'Omnipaque', 'ma_dvkt' => '18.0150.0041', 'hieu_luc' => '1']];

        $l->nap($hang);
        $this->assertNull(Xml3176VatPhamKemDvkt::first()->nguoi_sua_id);

        $l->nap([['hieu_luc' => '0', 'ghi_chu' => 'sai'] + $hang[0]], 7);
        $m = Xml3176VatPhamKemDvkt::first();
        $this->assertSame(7, $m->nguoi_sua_id);
        $this->assertNotNull($m->sua_luc);
        $this->assertFalse($m->hieu_luc);
    }
}

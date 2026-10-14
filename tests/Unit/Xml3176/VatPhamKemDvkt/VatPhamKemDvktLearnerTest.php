<?php

namespace Tests\Unit\Xml3176\VatPhamKemDvkt;

use App\Models\BHYT\Xml3176VatPhamKemDvkt;
use App\Services\Xml3176\VatPhamKemDvktLearner;
use Illuminate\Support\Facades\DB;
use Tests\Support\Xml3176RuleTestSupport;
use Tests\TestCase;

class VatPhamKemDvktLearnerTest extends TestCase
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
        ]);
    }

    /** Them $n dong VTYT BHYT tra, $lk dong trong do gan ma_dich_vu = $dv. */
    private function vtyt(string $ma, string $ten, int $n, int $lk, string $dv): void
    {
        for ($i = 0; $i < $n; $i++) {
            DB::table('xml3176_xml3s')->insert([
                'ma_lk' => 'HS' . $i, 'stt' => ++$this->stt, 'ma_vat_tu' => $ma, 'ten_vat_tu' => $ten,
                'ma_dich_vu' => $i < $lk ? $dv : null, 'goi_vtyt' => $i < $lk ? 'G1' : null,
                't_bhtt' => 1000, 'so_luong' => 1, 'ngay_yl' => '202609010800',
            ]);
        }
    }

    private function dvkt(string $ma, string $ten): void
    {
        DB::table('xml3176_xml3s')->insert([
            'ma_lk' => 'DV', 'stt' => ++$this->stt, 'ma_vat_tu' => '', 'ma_dich_vu' => $ma, 'ten_dich_vu' => $ten,
            't_bhtt' => 1000, 'so_luong' => 1, 'ngay_yl' => '202609010800',
        ]);
    }

    private function thuoc(string $ma, string $ten, int $n, int $lk, string $dv): void
    {
        for ($i = 0; $i < $n; $i++) {
            DB::table('xml3176_xml2s')->insert([
                'ma_lk' => 'HS' . $i, 'stt' => ++$this->stt, 'ma_thuoc' => $ma, 'ten_thuoc' => $ten,
                'ma_dich_vu' => $i < $lk ? $dv : null, 't_bhtt' => 1000, 'ngay_yl' => '202609010800',
            ]);
        }
    }

    private function learner(): VatPhamKemDvktLearner
    {
        return new VatPhamKemDvktLearner();
    }

    /** @test */
    public function chon_vat_pham_dat_nguong_va_toi_thieu()
    {
        $this->vtyt('N08.00.150', 'Buồng tiêm truyền', 10, 9, '12.0448.1187_GT'); // 90% -> chon
        $this->vtyt('N03.01.070.5484.000.0004', 'Bơm tiêm 10ml', 10, 2, '01.0175.0196'); // 20% -> bo
        $this->vtyt('N03.03.030.2239.279.0001', 'Kim chọc tủy', 3, 3, '22.0126.0092'); // 3 < 5 -> bo
        $this->dvkt('12.0448.1187', 'Đặt buồng tiêm truyền dưới da');

        $kq = $this->learner()->hoc(0.8, 5);

        $this->assertSame(['VTYT' => 1, 'THUOC' => 0], $kq['so_vat_pham']);
        $this->assertCount(1, $kq['dong']);
        $d = $kq['dong'][0];
        $this->assertSame('VTYT', $d['loai']);
        $this->assertSame('N08.00.150', $d['ma_vat_pham']);
        $this->assertSame('buồng tiêm truyền', $d['ten_khop']);
        $this->assertSame('12.0448.1187', $d['ma_dvkt']);
        $this->assertSame('Đặt buồng tiêm truyền dưới da', $d['ten_dvkt']);
        $this->assertSame(9, $d['so_lan_gan']);
        $this->assertSame(0.9, $d['ty_le_gan']);
    }

    /** @test */
    public function bo_dvkt_me_khong_phai_ma_ky_thuat_hoac_trung_ma_vat_pham()
    {
        // Prod 29/08-03/09/2026: HIS ghi MA_DICH_VU = 40.642 (chinh ma thuoc Xenetix) tren 20 dong XML2.
        $this->thuoc('40.642', 'Xenetix 350', 5, 3, '40.642');
        $this->thuoc('40.642', 'Xenetix 350', 5, 5, '18.0192.0041');
        $this->thuoc('40.642', 'Xenetix 350', 1, 1, 'N03.01.070');
        $this->dvkt('18.0192.0041', 'Chụp cắt lớp vi tính lồng ngực có tiêm thuốc cản quang');

        $ma = collect($this->learner()->hoc(0.8, 5)['dong'])->pluck('ma_dvkt')->all();

        $this->assertSame(['18.0192.0041'], $ma);
    }

    /** @test */
    public function bo_dvkt_me_khong_dung_thuoc_can_quang()
    {
        // Prod: Iopamiro gan vao "CLVT long nguc khong tiem thuoc can quang" 42 lan - hoc vao bang tra
        // thi R1 bo lot ho so co thuoc can quang ma chi chup khong can quang.
        $this->thuoc('40.645', 'Iopamiro', 10, 9, '18.0192.0041');
        $this->thuoc('40.645', 'Iopamiro', 10, 9, '18.0191.0040');
        $this->dvkt('18.0192.0041', 'Chụp cắt lớp vi tính lồng ngực có tiêm thuốc cản quang (từ 1- 32 dãy)');
        $this->dvkt('18.0191.0040', 'Chụp cắt lớp vi tính lồng ngực KHÔNG tiêm thuốc cản quang (từ 1- 32 dãy)');
        $this->dvkt('18.0220.0040', 'Chụp cắt lớp vi tính bụng - tiểu khung thường quy [không có thuốc cản quang]');
        $this->thuoc('40.645', 'Iopamiro', 1, 1, '18.0220.0040');

        $kq = $this->learner()->hoc(0.8, 5);

        $this->assertSame(['18.0192.0041'], collect($kq['dong'])->pluck('ma_dvkt')->all());
    }

    /** @test */
    public function xet_moi_ten_cua_dvkt_me_khi_loai_tru()
    {
        // Prod: 18.0219.0040 co ca ten "(PACS)" lan ten "[khong co thuoc can quang]". Ten (PACS) lon
        // hon theo thu tu chu nen MAX(ten) chon no - chi xet mot ten thi lot.
        $this->thuoc('40.645', 'Iopamiro', 10, 9, '18.0192.0041');
        $this->thuoc('40.645', 'Iopamiro', 10, 9, '18.0219.0040');
        $this->dvkt('18.0192.0041', 'CT lồng ngực có tiêm thuốc cản quang');
        $this->dvkt('18.0219.0040', 'CT bụng thường quy (PACS)');
        $this->dvkt('18.0219.0040', 'CT bụng [không có thuốc cản quang]');

        $this->assertSame(['18.0192.0041'], collect($this->learner()->hoc(0.8, 5)['dong'])->pluck('ma_dvkt')->all());
    }

    /** @test */
    public function moi_dvkt_tung_gan_deu_vao_danh_sach_me()
    {
        $this->thuoc('40.644', 'Omnipaque', 5, 3, '18.0150.0041');
        $this->thuoc('40.644', 'Omnipaque', 5, 5, '18.0156.0041');

        $kq = $this->learner()->hoc(0.8, 5);

        $this->assertSame(['18.0150.0041', '18.0156.0041'], collect($kq['dong'])->pluck('ma_dvkt')->sort()->values()->all());
        $this->assertSame('', $kq['dong'][0]['ten_khop']);
    }

    /** @test */
    public function ghi_them_moi_roi_hoc_lai_cap_nhat()
    {
        $this->vtyt('N08.00.150', 'Buồng tiêm truyền', 10, 9, '12.0448.1187');
        $l = $this->learner();

        $this->assertSame(['them' => 1, 'cap_nhat' => 0, 'tat' => 0, 'bat_lai' => 0], $l->ghi($l->hoc(0.8, 5)['dong']));
        $this->assertSame(['them' => 0, 'cap_nhat' => 1, 'tat' => 0, 'bat_lai' => 0], $l->ghi($l->hoc(0.8, 5)['dong']));
        $this->assertSame(1, Xml3176VatPhamKemDvkt::count());
    }

    /** @test */
    public function khong_sua_dong_tay_va_giu_dong_nguoi_dung_tat()
    {
        $this->vtyt('N08.00.150', 'Buồng tiêm truyền', 10, 9, '12.0448.1187');
        $l = $this->learner();
        $l->ghi($l->hoc(0.8, 5)['dong']);
        Xml3176VatPhamKemDvkt::query()->update(['hieu_luc' => false, 'ghi_chu' => 'Người dùng tắt']);
        Xml3176VatPhamKemDvkt::create(['loai' => 'VTYT', 'ma_vat_pham' => 'N06.02.100', 'ten_khop' => 'stent đại tràng',
            'ma_dvkt' => '20.0053.0105', 'nguon' => 'TAY', 'so_lan_gan' => 0]);

        $l->ghi($l->hoc(0.8, 5)['dong']);

        $hoc = Xml3176VatPhamKemDvkt::where('nguon', 'HOC')->first();
        $this->assertFalse($hoc->hieu_luc);
        $this->assertSame('Người dùng tắt', $hoc->ghi_chu);
        $this->assertTrue(Xml3176VatPhamKemDvkt::where('nguon', 'TAY')->first()->hieu_luc);
    }

    /** @test */
    public function dong_hoc_roi_duoi_nguong_bi_tat_va_tu_bat_lai()
    {
        $this->vtyt('N08.00.150', 'Buồng tiêm truyền', 10, 9, '12.0448.1187');
        $l = $this->learner();
        $l->ghi($l->hoc(0.8, 5)['dong']);

        $kq = $l->ghi($l->hoc(0.95, 5)['dong']); // nguong cao hon -> roi khoi bang
        $this->assertSame(1, $kq['tat']);
        $m = Xml3176VatPhamKemDvkt::first();
        $this->assertFalse($m->hieu_luc);
        $this->assertSame(Xml3176VatPhamKemDvkt::GHI_CHU_TU_TAT, $m->ghi_chu);

        $kq = $l->ghi($l->hoc(0.8, 5)['dong']);
        $this->assertSame(1, $kq['bat_lai']);
        $this->assertTrue(Xml3176VatPhamKemDvkt::first()->hieu_luc);
    }

    /** @test */
    public function loc_theo_tu_ngay()
    {
        $this->vtyt('N08.00.150', 'Buồng tiêm truyền', 10, 9, '12.0448.1187');

        $this->assertSame([], $this->learner()->hoc(0.8, 5, '202610')['dong']);
    }

    /** @test */
    public function ghi_cap_nhat_khong_ghi_de_ten_dvkt_bang_null()
    {
        $this->vtyt('N08.00.150', 'Buồng tiêm truyền', 10, 9, '12.0448.1187');
        $this->dvkt('12.0448.1187', 'Đặt buồng tiêm truyền');
        $l = $this->learner();
        $l->ghi($l->hoc(0.8, 5)['dong']);
        $this->assertSame('Đặt buồng tiêm truyền', Xml3176VatPhamKemDvkt::first()->ten_dvkt);

        DB::table('xml3176_xml3s')->where('ma_vat_tu', '')->delete();
        $l->ghi($l->hoc(0.8, 5)['dong']);

        $this->assertSame('Đặt buồng tiêm truyền', Xml3176VatPhamKemDvkt::first()->ten_dvkt);
    }
}

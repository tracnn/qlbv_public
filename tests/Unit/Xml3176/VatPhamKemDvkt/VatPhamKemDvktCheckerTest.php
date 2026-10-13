<?php

namespace Tests\Unit\Xml3176\VatPhamKemDvkt;

use App\Models\BHYT\Xml3176VatPhamKemDvkt;
use App\Services\Xml3176\VatPhamKemDvktLookup;
use App\Services\Xml3176CompleteChecker;
use Illuminate\Support\Facades\DB;
use Tests\Support\FakeXml3176ErrorService;
use Tests\Support\Xml3176RuleTestSupport;
use Tests\TestCase;

class VatPhamKemDvktCheckerTest extends TestCase
{
    use Xml3176RuleTestSupport;

    protected function setUp()
    {
        parent::setUp();
        $this->bootXml3176Sqlite([
            '2026_01_09_152826_create_xml3176_xml2s_table.php',
            '2026_01_09_152832_create_xml3176_xml3s_table.php',
            '2026_10_13_090000_create_xml3176_vat_pham_kem_dvkt_table.php',
        ]);
        VatPhamKemDvktLookup::xoaBoNho();

        Xml3176VatPhamKemDvkt::create([
            'loai' => 'VTYT', 'ma_vat_pham' => 'N08.00.150', 'ten_khop' => 'buồng tiêm truyền cấy dưới da',
            'ma_dvkt' => '12.0448.1187', 'ten_dvkt' => 'Đặt buồng tiêm truyền dưới da', 'nguon' => 'HOC',
        ]);
        Xml3176VatPhamKemDvkt::create([
            'loai' => 'THUOC', 'ma_vat_pham' => '40.644', 'ten_khop' => '',
            'ma_dvkt' => '18.0156.0041', 'ten_dvkt' => 'CLVT có cản quang', 'nguon' => 'HOC', 'hieu_luc' => false,
        ]);
    }

    protected function tearDown()
    {
        VatPhamKemDvktLookup::xoaBoNho();
        parent::tearDown();
    }

    private function xml3(string $maLk, int $stt, array $f): void
    {
        DB::table('xml3176_xml3s')->insert(array_merge(['ma_lk' => $maLk, 'stt' => $stt], $f));
    }

    private function codes(string $maLk): array
    {
        $checker = new Xml3176CompleteChecker(new FakeXml3176ErrorService());

        return $this->errorCodes($this->invokePrivate($checker, 'checkVatPhamKemDvkt', $maLk));
    }

    /** @test */
    public function bat_buong_tiem_truyen_khong_co_dvkt_me()
    {
        $this->xml3('000007339564', 1, ['ma_dich_vu' => 'K33.NO1', 'ma_vat_tu' => '', 'ten_dich_vu' => 'Giường', 't_bhtt' => 340200, 'so_luong' => 1, 'ngay_yl' => '202610050000']);
        $this->xml3('000007339564', 2, ['ma_dich_vu' => '', 'ma_vat_tu' => 'N08.00.150', 'ten_vat_tu' => 'Buồng tiêm truyền cấy dưới da', 't_bhtt' => 4188000, 'so_luong' => 1, 'ngay_yl' => '202610050900']);

        $this->assertSame(['XMLComplete_VTYT_KHONG_CO_DVKT_ME'], $this->codes('000007339564'));
    }

    /** @test */
    public function co_dvkt_me_gay_te_bhyt_tra_thi_khong_loi()
    {
        $this->xml3('000007342941', 1, ['ma_dich_vu' => '12.0448.1187_GT', 'ma_vat_tu' => '', 't_bhtt' => 1070800, 'so_luong' => 1, 'ngay_yl' => '202610020800']);
        $this->xml3('000007342941', 2, ['ma_dich_vu' => null, 'ma_vat_tu' => 'N08.00.150', 'ten_vat_tu' => 'Buồng tiêm truyền cấy dưới da', 't_bhtt' => 4188000, 'so_luong' => 1, 'ngay_yl' => '202610020900']);

        $this->assertSame([], $this->codes('000007342941'));
    }

    /** @test */
    public function dong_bang_tra_bi_tat_thi_im_lang()
    {
        DB::table('xml3176_xml2s')->insert(['ma_lk' => '000007403605', 'stt' => 1, 'ma_thuoc' => '40.644', 'ten_thuoc' => 'Omnipaque', 't_bhtt' => 363632, 'ngay_yl' => '202610070900']);

        $this->assertSame([], $this->codes('000007403605'));
    }

    /** @test */
    public function bang_tra_chua_migrate_thi_r1_im_lang()
    {
        DB::statement('DROP TABLE xml3176_vat_pham_kem_dvkt');
        VatPhamKemDvktLookup::xoaBoNho();
        $this->xml3('X', 1, ['ma_dich_vu' => '', 'ma_vat_tu' => 'N08.00.150', 'ten_vat_tu' => 'Buồng tiêm truyền cấy dưới da', 't_bhtt' => 4188000, 'so_luong' => 1, 'ngay_yl' => '202610050900']);

        $this->assertSame([], $this->codes('X'));
    }

    /** @test */
    public function checkErrors_goi_quy_tac_moi()
    {
        $nguon = file_get_contents(app_path('Services/Xml3176CompleteChecker.php'));

        $this->assertContains('$this->checkVatPhamKemDvkt($ma_lk)', $nguon);
    }
}

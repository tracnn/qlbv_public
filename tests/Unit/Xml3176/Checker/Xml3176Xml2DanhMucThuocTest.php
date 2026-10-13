<?php

namespace Tests\Unit\Xml3176\Checker;

use App\Models\BHYT\Xml3176Xml1;
use App\Models\BHYT\Xml3176Xml2;
use App\Services\Xml3176Xml2Checker;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Support\Xml3176RuleTestSupport;
use Tests\TestCase;

/**
 * Doi chieu dong thuoc XML2 voi danh muc thuoc cua co so.
 *
 * So lieu lay tu danh sach giam tru tu dong cua cong thang 10/2026 (BM 01929): Medivernol
 * 40.183 thau 51/QD-TTMS duoc duyet "1000mg" nhung XML2 khai "1g". "1g" van co o dong
 * danh muc khac cung SDK (thau 795) nen quy tac cu bao nham "sai thong tin thau"
 * (Canh bao) - cong thi giam tru "Thuoc sai ham luong" 4.288 dong.
 */
class Xml3176Xml2DanhMucThuocTest extends TestCase
{
    use Xml3176RuleTestSupport;

    protected function setUp()
    {
        parent::setUp();
        $this->bootXml3176Sqlite([
            '2026_01_09_152817_create_xml3176_xml1s_table.php',
            '2026_01_09_152826_create_xml3176_xml2s_table.php',
        ]);

        // Bang medicine_catalogs khong co migration tao bang (dung tu ban dump) - dung tay
        // dung cac cot checkDrugCatalog doc.
        Schema::connection('mysql')->create('medicine_catalogs', function ($t) {
            $t->increments('id');
            foreach (['ma_thuoc', 'ten_thuoc', 'ham_luong', 'so_dang_ky', 'tt_thau', 'don_vi_tinh',
                      'duong_dung', 'ma_duong_dung', 'dang_bao_che', 'ma_cskcb', 'tu_ngay', 'den_ngay'] as $c) {
                $t->string($c)->nullable();
            }
            $t->decimal('don_gia', 15, 2)->nullable();
            $t->timestamps();
        });

        $this->themDanhMuc('1000mg', '893710197523', '51/QĐ-TTMS;G1;N1;2025');
        $this->themDanhMuc('1g', '893710197523', '795/QĐ-BM;G1;N1;2025');

        Xml3176Xml1::create(['ma_lk' => 'LK', 'stt' => 1, 'ma_cskcb' => '01929']);
    }

    private function themDanhMuc(string $hamLuong, string $sdk, string $ttThau): void
    {
        DB::table('medicine_catalogs')->insert([
            'ma_thuoc' => '40.183', 'ten_thuoc' => 'Medivernol 1g', 'ham_luong' => $hamLuong,
            'so_dang_ky' => $sdk, 'tt_thau' => $ttThau, 'don_vi_tinh' => 'Lọ',
            'duong_dung' => 'Tiêm', 'ma_duong_dung' => '2.10', 'dang_bao_che' => 'Bột pha tiêm',
            'ma_cskcb' => '01929', 'don_gia' => 9380,
        ]);
    }

    private function chay(array $attrs)
    {
        $d = new Xml3176Xml2();
        foreach (array_merge([
            'ma_lk' => 'LK', 'stt' => 1, 'ma_nhom' => 4, 'ma_khoa' => 'K26',
            'ma_thuoc' => '40.183', 'ten_thuoc' => 'Medivernol 1g', 'ham_luong' => '1000mg',
            'so_dang_ky' => '893710197523', 'tt_thau' => '51/QĐ-TTMS;G1;N1;2025',
            'don_vi_tinh' => 'Lọ', 'duong_dung' => '2.10', 'dang_bao_che' => 'Bột pha tiêm',
            'don_gia' => 9380,
        ], $attrs) as $k => $v) {
            $d->{$k} = $v;
        }
        $checker = $this->makeChecker(Xml3176Xml2Checker::class);
        return collect($this->invokePrivate($checker, 'checkDrugCatalog', $d));
    }

    private function maLoi(array $attrs): array
    {
        return $this->errorCodes($this->chay($attrs));
    }

    /** @test */
    public function sai_ham_luong_so_voi_dong_dung_sdk_va_thau_thi_bao_sai_ham_luong()
    {
        $codes = $this->maLoi(['ham_luong' => '1g']);

        $this->assertContains('XML2_INVALID_DRUG_CONCENTRATION', $codes);
        $this->assertNotContains('XML2_INVALID_TT_THAU', $codes);
    }

    /** @test */
    public function mo_ta_loi_sai_ham_luong_neu_ham_luong_duoc_duyet()
    {
        $loi = $this->chay(['ham_luong' => '1g'])
            ->firstWhere('error_code', 'XML2_INVALID_DRUG_CONCENTRATION');

        $this->assertNotNull($loi);
        $this->assertContains('1000mg', $loi->description);
    }

    /** @test */
    public function khop_du_bon_truong_thi_khong_bao_loi_danh_muc()
    {
        $codes = $this->maLoi([]);

        foreach (['XML2_INVALID_DRUG_CODE', 'XML2_INVALID_DRUG_CONCENTRATION',
                  'XML2_INVALID_REGISTRATION_NUMBER', 'XML2_INVALID_TT_THAU'] as $c) {
            $this->assertNotContains($c, $codes);
        }
    }

    /** @test */
    public function dung_sdk_va_ham_luong_nhung_thau_khong_co_thi_bao_sai_thau()
    {
        $codes = $this->maLoi(['tt_thau' => '999/QĐ-BM;G1;N1;2026']);

        $this->assertContains('XML2_INVALID_TT_THAU', $codes);
        $this->assertNotContains('XML2_INVALID_DRUG_CONCENTRATION', $codes);
    }

    /** @test */
    public function sai_sdk_thi_bao_sai_so_dang_ky()
    {
        $codes = $this->maLoi(['so_dang_ky' => 'VD-00000-00']);

        $this->assertContains('XML2_INVALID_REGISTRATION_NUMBER', $codes);
    }

    /** @test */
    public function ma_thuoc_khong_co_trong_danh_muc()
    {
        $this->assertContains('XML2_INVALID_DRUG_CODE', $this->maLoi(['ma_thuoc' => '40.999']));
    }
}

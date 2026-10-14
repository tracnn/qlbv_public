<?php

namespace Tests\Unit\Xml3176\Checker;

use App\Models\BHYT\Xml3176Xml1;
use App\Models\BHYT\Xml3176Xml3;
use App\Services\Xml3176Xml3Checker;
use Illuminate\Support\Facades\DB;
use Tests\Support\Xml3176RuleTestSupport;
use Tests\TestCase;

/**
 * Doi chieu ma DVKT XML3 voi danh muc DVKT (checkMedicalService) khi ma co hau to _XX.
 *
 * Do tren prod 01-13/10/2026: 438 dong _TB bi bao XML3_INVALID_MA_DICH_VU vi danh muc BHXH
 * khong liet ke ma _TB (chi co ma goc), trong khi ma _GT/_BS co san trong danh muc.
 */
class Xml3176Xml3DanhMucDvktTest extends TestCase
{
    use Xml3176RuleTestSupport;

    protected function setUp()
    {
        parent::setUp();
        $this->bootXml3176Sqlite([
            '2024_06_12_145200_create_danh_muc_dich_vu_table.php',
            '2026_07_28_130000_add_ma_cskcb_to_service_catalogs.php',
            '2026_01_09_152832_create_xml3176_xml3s_table.php',
            '2024_07_05_113054_create_medical_organizations_table.php',
        ]);
        DB::table('service_catalogs')->insert([
            ['ma_dich_vu' => '18.0659.0054', 'ten_dich_vu' => 'Chụp, nong và đặt stent động mạch vành',
             'don_gia' => 7402900, 'quy_trinh' => '', 'tu_ngay' => '20240101', 'den_ngay' => null],
            ['ma_dich_vu' => '10.0772.0548', 'ten_dich_vu' => 'Phẫu thuật kết hợp xương gãy bánh chè',
             'don_gia' => 5000000, 'quy_trinh' => '', 'tu_ngay' => '20240101', 'den_ngay' => null],
            ['ma_dich_vu' => '10.0772.0548_GT', 'ten_dich_vu' => 'Phẫu thuật kết hợp xương gãy bánh chè [gây tê]',
             'don_gia' => 3000000, 'quy_trinh' => '', 'tu_ngay' => '20240101', 'den_ngay' => null],
            ['ma_dich_vu' => '28.0161.0576', 'ten_dich_vu' => 'Phẫu thuật khâu đơn giản vết thương vùng mặt cổ',
             'don_gia' => 2000000, 'quy_trinh' => '', 'tu_ngay' => '20240101', 'den_ngay' => '20241231'],
        ]);
    }

    private function codes(array $thuocTinh): array
    {
        $x1 = new Xml3176Xml1(['ma_lk' => 'A', 'ma_cskcb' => null, 'ngay_vao' => '202610050800']);
        $dong = new Xml3176Xml3(array_merge(['ma_lk' => 'A', 'stt' => 1, 'ma_nhom' => 8], $thuocTinh));
        $dong->setRelation('Xml3176Xml1', $x1);

        return $this->errorCodes($this->invokePrivate(
            $this->makeChecker(Xml3176Xml3Checker::class),
            'checkMedicalService',
            $dong
        ));
    }

    /** @test */
    public function hau_to_tb_co_ma_goc_trong_danh_muc_thi_khong_bao_khong_ton_tai()
    {
        $codes = $this->codes([
            'ma_dich_vu' => '18.0659.0054_TB', 'ten_dich_vu' => 'Chụp, nong và đặt stent động mạch vành [Áp dụng trường hợp thất bại]',
            'don_gia_bh' => 0,
        ]);

        $this->assertNotContains('XML3_INVALID_MA_DICH_VU', $codes);
        $this->assertNotContains('XML3_INVALID_TEN_DICH_VU', $codes, 'Ten co phan hau to hop le khac ten ma goc');
        $this->assertNotContains('XML3_INVALID_APPROVED_DON_GIA_BH', $codes);
    }

    /** @test */
    public function hau_to_bat_ky_hai_chu_cai_cung_bo_khi_tra_danh_muc()
    {
        $this->assertNotContains('XML3_INVALID_MA_DICH_VU', $this->codes(['ma_dich_vu' => '18.0659.0054_XX', 'ten_dich_vu' => 'Bất kỳ']));
    }

    /** @test */
    public function ma_gt_co_san_trong_danh_muc_van_so_gia_va_ten_theo_dong_gt()
    {
        // Gia gay te 3.000.000: khai 4.000.000 la vuot gia dong _GT (du thap hon gia ma goc).
        $codes = $this->codes([
            'ma_dich_vu' => '10.0772.0548_GT', 'ten_dich_vu' => 'Phẫu thuật kết hợp xương gãy bánh chè',
            'don_gia_bh' => 4000000,
        ]);

        $this->assertContains('XML3_INVALID_APPROVED_DON_GIA_BH', $codes);
        $this->assertContains('XML3_INVALID_TEN_DICH_VU', $codes);

        $this->assertSame([], $this->codes([
            'ma_dich_vu' => '10.0772.0548_GT', 'ten_dich_vu' => 'Phẫu thuật kết hợp xương gãy bánh chè [gây tê]',
            'don_gia_bh' => 3000000,
        ]));
    }

    /** @test */
    public function ma_goc_cung_khong_co_thi_van_bao_khong_ton_tai()
    {
        $this->assertContains('XML3_INVALID_MA_DICH_VU', $this->codes(['ma_dich_vu' => '99.9999.9999_TB']));
        $this->assertContains('XML3_INVALID_MA_DICH_VU', $this->codes(['ma_dich_vu' => '99.9999.9999']));
    }

    /** @test */
    public function ma_goc_het_hieu_luc_thi_bao_ngay_vao()
    {
        $codes = $this->codes(['ma_dich_vu' => '28.0161.0576_GT', 'ten_dich_vu' => 'Khâu [gây tê]']);

        $this->assertNotContains('XML3_INVALID_MA_DICH_VU', $codes);
        $this->assertContains('XML3_INVALID_MA_DICH_VU_NGAY_VAO', $codes);
    }

    /** @test */
    public function ma_khong_hau_to_giu_nguyen_hanh_vi()
    {
        $this->assertSame([], $this->codes([
            'ma_dich_vu' => '18.0659.0054', 'ten_dich_vu' => 'Chụp, nong và đặt stent động mạch vành', 'don_gia_bh' => 7402900,
        ]));
        $this->assertContains('XML3_INVALID_APPROVED_DON_GIA_BH', $this->codes([
            'ma_dich_vu' => '18.0659.0054', 'ten_dich_vu' => 'Chụp, nong và đặt stent động mạch vành', 'don_gia_bh' => 9000000,
        ]));
    }
}

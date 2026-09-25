<?php

namespace Tests\Unit\Xml3176\Checker;

use App\Models\BHYT\Xml3176Xml1;
use App\Models\BHYT\Xml3176Xml3;
use App\Services\Xml3176CompleteChecker;
use Tests\Support\FakeXml3176ErrorService;
use Tests\Support\Xml3176RuleTestSupport;
use Tests\TestCase;

/**
 * Hai luật ngày giường tính số ngày điều trị nội trú từ NGAY_VAO_NOI_TRU (không phải NGAY_VAO
 * - thời điểm đến khám), và tách "trường hợp đặc biệt chưa khai ngày cộng thêm" ra cảnh báo
 * riêng.
 *
 * 000007255189: đến khám 17/09, vào nội trú 18/09 09:09, ra 24/09, khai 6 ngày - đúng. Luật cũ
 * đếm từ 17/09 nên đòi 7 và báo thiếu. Trên 1.061 hồ sơ nội trú thật: 210 -> 163 báo thiếu;
 * 111/163 còn lại chỉ là trường hợp đặc biệt chưa khai ngày được cộng thêm (quyền, không
 * phải lỗi).
 */
class Xml3176CompleteNgayGiuongNoiTruTest extends TestCase
{
    use Xml3176RuleTestSupport;

    const THIEU = 'XMLComplete_BED_DAYS_BELOW_TT39';
    const DB_CHUA_KHAI = 'XMLComplete_BED_DAYS_SPECIAL_NOT_CLAIMED';
    const THUA = 'XMLComplete_INVALID_BED_DAYS';

    protected function setUp()
    {
        parent::setUp();
        config([
            'xml3176.treatment_type_inpatient' => ['03', '04', '09'],
            'xml3176.xml1.ma_loai_kcb_khong_tinh_ngay_dieu_tri' => ['01', '07', '09'],
            'xml3176.invalid_treatment_result' => [3, 4, 5, 6],
            'xml3176.invalid_end_type_treatment' => [2, 3, 4],
            'xml3176.bed_group_code' => [14, 15, 16],
            'xml3176.bed_days_tt39.tolerance' => 0.5,
        ]);
        $this->bootXml3176Sqlite([
            '2026_01_09_152817_create_xml3176_xml1s_table.php',
            '2026_01_09_152832_create_xml3176_xml3s_table.php',
        ]);
    }

    /** @return string[] mã lỗi của hai luật ngày giường */
    private function loi(array $xml1, array $giuong): array
    {
        $x = Xml3176Xml1::create(array_merge([
            'ma_lk' => 'HS', 'stt' => 1, 'ma_loai_kcb' => '03', 'ket_qua_dtri' => 2, 'ma_loai_rv' => 1,
            'so_ngay_dtri' => 8,
        ], $xml1));
        foreach ($giuong as $i => $sl) {
            Xml3176Xml3::create(['ma_lk' => 'HS', 'stt' => $i + 1, 'ma_nhom' => 15, 'ma_dich_vu' => 'K33.NO1', 'so_luong' => $sl]);
        }

        $c = new Xml3176CompleteChecker(new FakeXml3176ErrorService());

        return array_merge(
            $this->errorCodes($this->invokePrivate($c, 'checkInvalidBedDays', $x)),
            $this->errorCodes($this->invokePrivate($c, 'checkBedDaysBelowTT39', $x))
        );
    }

    private function hs255189(array $ghiDe = [])
    {
        return array_merge(['ngay_vao' => '202609170638', 'ngay_vao_noi_tru' => '202609180909', 'ngay_ra' => '202609241542'], $ghiDe);
    }

    /** @test */
    public function tinh_tu_ngay_vao_noi_tru_khai_du_khong_bao()
    {
        $this->assertSame([], $this->loi($this->hs255189(), [0.5, 1, 1, 1, 1, 1, 0.5]));
    }

    /** @test */
    public function tinh_tu_ngay_vao_noi_tru_thieu_that_van_bao()
    {
        $codes = $this->loi($this->hs255189(), [1, 1, 1, 1, 1]);   // 5 < 6

        $this->assertContains(self::THIEU, $codes);
        $this->assertNotContains(self::DB_CHUA_KHAI, $codes);
    }

    /** @test */
    public function tinh_tu_ngay_vao_noi_tru_bat_thua_ma_luat_cu_bo_sot()
    {
        // Khai 7: đếm từ 17/09 thì vừa đủ, đếm đúng từ 18/09 thì thừa 1.
        $this->assertContains(self::THUA, $this->loi($this->hs255189(), [1, 1, 1, 1, 1, 1, 1]));
    }

    /** @test */
    public function khong_co_ngay_vao_noi_tru_thi_dung_ngay_vao_nhu_cu()
    {
        $this->assertSame([], $this->loi(
            ['ngay_vao' => '202609180909', 'ngay_vao_noi_tru' => null, 'ngay_ra' => '202609241542'],
            [0.5, 1, 1, 1, 1, 1, 0.5]
        ));
    }

    /** @test */
    public function dac_biet_chi_thieu_ngay_cong_them_la_canh_bao_rieng()
    {
        // Nặng xin về (kết quả 4): 18/09 -> 24/09 là 6 ngày, được cộng 1 = 7. Khai 6 = chưa khai ngày cộng thêm.
        $codes = $this->loi($this->hs255189(['ket_qua_dtri' => 4]), [0.5, 1, 1, 1, 1, 1, 0.5]);

        $this->assertContains(self::DB_CHUA_KHAI, $codes);
        $this->assertNotContains(self::THIEU, $codes);
    }

    /** @test */
    public function dac_biet_thieu_duoi_muc_thuong_van_la_thieu_that()
    {
        $codes = $this->loi($this->hs255189(['ket_qua_dtri' => 4]), [1, 1, 1, 1, 1]);   // 5 < 6

        $this->assertContains(self::THIEU, $codes);
        $this->assertNotContains(self::DB_CHUA_KHAI, $codes);
    }

    /** @test */
    public function ho_so_moc_000007093453_van_bao_thua_1_ngay()
    {
        // BHXH đã trừ 1 ngày: vào 24/08 02:01, vào nội trú 24/08 05:09, ra 01/09 11:57, khai 9, đúng 8.
        $codes = $this->loi(
            ['ngay_vao' => '202608240201', 'ngay_vao_noi_tru' => '202608240509', 'ngay_ra' => '202609011157'],
            [1, 1, 1, 1, 1, 1, 1, 1, 1]
        );

        $this->assertContains(self::THUA, $codes);
    }
}

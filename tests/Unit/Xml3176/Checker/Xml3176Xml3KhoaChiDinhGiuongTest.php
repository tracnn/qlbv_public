<?php

namespace Tests\Unit\Xml3176\Checker;

use App\Models\BHYT\Xml3176Xml3;
use App\Services\Xml3176Xml3Checker;
use Tests\Support\Xml3176RuleTestSupport;
use Tests\TestCase;

/**
 * XML3_INVALID_DEPARTMENT_CODE "Khoa chi dinh giuong khong dung quy dinh".
 *
 * Quy tac cu doi MA_KHOA BAT DAU bang khoa cua giuong, nen ma lien chuyen khoa K103436 chi
 * hop le voi giuong K10 - giuong K34, K36 bi bao nham. Tren du lieu that 125/134 loi la bao
 * nham kieu nay; 9 loi con lai la sai that (vd K103436 dung giuong K33.GBN.1).
 */
class Xml3176Xml3KhoaChiDinhGiuongTest extends TestCase
{
    use Xml3176RuleTestSupport;

    const MA = 'XML3_INVALID_DEPARTMENT_CODE';

    protected function setUp()
    {
        parent::setUp();
        config([
            'organization.exclude_department' => ['K02'],
            'xml3176.bed_group_code' => [14, 15, 16],
            'xml3176.bed_code_pattern' => '/^[HTCK]\d{3}$/',
        ]);
    }

    /** Khong co ngay_th_yl/ngay_kq nen nhanh kiem trung giuong (can CSDL) khong chay. */
    private function loi($maKhoa, $maDichVu)
    {
        $dong = new Xml3176Xml3([
            'ma_lk' => 'A', 'stt' => 1, 'ma_nhom' => 15, 'ma_giuong' => 'K001',
            'ma_khoa' => $maKhoa, 'ma_dich_vu' => $maDichVu,
        ]);

        return collect($this->invokePrivate(
            $this->makeChecker(Xml3176Xml3Checker::class), 'checkBedGroupCodeConditions', $dong
        ))->where('error_code', self::MA)->values();
    }

    /** @test */
    public function lien_khoa_dung_giuong_cua_mot_khoa_trong_nhom_khong_bao()
    {
        $this->assertCount(0, $this->loi('K103436', 'K36.NO1'));   // ca nguoi dung neu
        $this->assertCount(0, $this->loi('K103436', 'K34.NO1'));
        $this->assertCount(0, $this->loi('K3233', 'K33.NO1'));
        $this->assertCount(0, $this->loi('K024849', 'K49.HSCC'));
    }

    /** @test */
    public function lien_khoa_dung_giuong_ngoai_nhom_van_bao_va_neu_ro_cac_khoa()
    {
        $loi = $this->loi('K103436', 'K33.GBN.1');

        $this->assertCount(1, $loi);
        $this->assertContains('K103436 (liên khoa K10, K34, K36)', $loi[0]->description);
        $this->assertContains('K33.GBN.1', $loi[0]->description);
    }

    /** @test */
    public function mot_khoa_giu_nguyen_hanh_vi()
    {
        $this->assertCount(0, $this->loi('K36', 'K36.NO1'));
        $this->assertCount(1, $this->loi('K30', 'K02.HSCC'));
    }

    /** @test */
    public function ma_khoa_sai_dang_giu_cach_so_dau_chuoi_cu()
    {
        // K123 khong tach duoc: van so "bat dau bang" nhu truoc.
        $this->assertCount(0, $this->loi('K123', 'K12.NO1'));
        $this->assertCount(1, $this->loi('K123', 'K23.NO1'));
    }

    /** @test */
    public function khoa_loai_tru_van_khong_kiem()
    {
        $this->assertCount(0, $this->loi('K02', 'K33.NO1'));
    }
}

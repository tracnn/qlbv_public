<?php

namespace Tests\Unit\Xml3176\Checker;

use App\Models\BHYT\Xml3176Xml1;
use App\Models\BHYT\Xml3176Xml3;
use App\Services\Xml3176Xml3Checker;
use Tests\Support\Xml3176RuleTestSupport;
use Tests\TestCase;

/**
 * Ra soat 15/10/2026 tren prod: quy tac moi 04/09 bao trung quy tac khac tren CUNG dong -
 *   - UNDER_3MIN + NGAY_TH_YL_EQUALS_NGAY_KQ: 41.747 dong (bang gio => 0 phut)
 *   - NGAY_KQ_GREATER_NGAY_RA nam trong INVALID_NGAY_KQ: 420/424 dong
 * Moi sai sot chi con mot dong loi. Xml1 luon gan san de khong cham CSDL.
 */
class ThoiGianTrungLapTest extends TestCase
{
    use Xml3176RuleTestSupport;

    private function dong(array $attrs, array $xml1 = []): Xml3176Xml3
    {
        $d = new Xml3176Xml3();
        foreach ($attrs as $k => $v) {
            $d->{$k} = $v;
        }
        $x1 = new Xml3176Xml1();
        foreach ($xml1 + ['ngay_vao' => '202607010700', 'ngay_ra' => '202607050800'] as $k => $v) {
            $x1->{$k} = $v;
        }
        $d->setRelation('Xml3176Xml1', $x1);

        return $d;
    }

    private function ma(Xml3176Xml3 $d): array
    {
        $k = $this->makeChecker(Xml3176Xml3Checker::class);

        return array_merge(
            $this->errorCodes($this->invokePrivate($k, 'checkNgayKq', $d)),
            $this->errorCodes($this->invokePrivate($k, 'checkTimingAndExecutor', $d))
        );
    }

    /** @test */
    public function trung_gio_chi_bao_equals_khong_bao_duoi_3_phut()
    {
        $ma = $this->ma($this->dong(['ma_nhom' => '1', 'ngay_th_yl' => '202607010900', 'ngay_kq' => '202607010900']));

        $this->assertContains('XML3_NGAY_TH_YL_EQUALS_NGAY_KQ', $ma);
        $this->assertNotContains('XML3_EXECUTION_TIME_UNDER_3MIN', $ma);
    }

    /** @test */
    public function mot_phut_van_bao_duoi_3_phut()
    {
        $ma = $this->ma($this->dong(['ma_nhom' => '1', 'ngay_th_yl' => '202607010900', 'ngay_kq' => '202607010901']));

        $this->assertContains('XML3_EXECUTION_TIME_UNDER_3MIN', $ma);
        $this->assertNotContains('XML3_NGAY_TH_YL_EQUALS_NGAY_KQ', $ma);
    }

    /** @test */
    public function kq_truoc_th_yl_van_bao_duoi_3_phut()
    {
        $ma = $this->ma($this->dong(['ma_nhom' => '3', 'ngay_th_yl' => '202607010900', 'ngay_kq' => '202607010850']));

        $this->assertContains('XML3_EXECUTION_TIME_UNDER_3MIN', $ma);
    }

    /** @test */
    public function kq_sau_ngay_ra_chi_bao_invalid_ngay_kq()
    {
        $ma = $this->ma($this->dong(['ma_nhom' => '1', 'ngay_yl' => '202607040800', 'ngay_kq' => '202607060800']));

        $this->assertContains('XML3_INVALID_NGAY_KQ', $ma);
        $this->assertNotContains('XML3_NGAY_KQ_GREATER_NGAY_RA', $ma);
    }

    /** @test */
    public function vat_tu_khong_qua_invalid_ngay_kq_thi_van_bao_greater_ngay_ra()
    {
        $nhomVatTu = (string) config('xml3176.material_group_code')[0];
        $ma = $this->ma($this->dong(['ma_nhom' => $nhomVatTu, 'ngay_yl' => '202607040800', 'ngay_kq' => '202607060800']));

        $this->assertNotContains('XML3_INVALID_NGAY_KQ', $ma);
        $this->assertContains('XML3_NGAY_KQ_GREATER_NGAY_RA', $ma);
    }

    /** @test */
    public function kq_trong_dot_khong_bao_gi()
    {
        $ma = $this->ma($this->dong(['ma_nhom' => '1', 'ngay_yl' => '202607040800', 'ngay_kq' => '202607050700']));

        $this->assertNotContains('XML3_INVALID_NGAY_KQ', $ma);
        $this->assertNotContains('XML3_NGAY_KQ_GREATER_NGAY_RA', $ma);
    }
}

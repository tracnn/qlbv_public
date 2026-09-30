<?php

namespace Tests\Unit\Xml3176\Checker;

use App\Models\BHYT\Xml3176Xml11;
use App\Models\BHYT\Xml3176Xml9;
use App\Services\Xml3176Xml11Checker;
use App\Services\Xml3176Xml9Checker;
use Tests\Support\Xml3176RuleTestSupport;
use Tests\TestCase;

/**
 * Ma so BHXH nay co the la so dinh danh ca nhan 12 so (vd 001191036820), khong chi ma 10 so
 * cu. Do 30/09/2026: XML11 co 115 dong dai 10 va 8 dong dai 12 (deu la chu so) - ca 8 dong
 * 12 so bi bao sai la "Ma BHXH khong hop le". Nguoi dung chot: tu 10 den 12 ky tu, ap cho ca
 * ma BHXH o XML11 va ma BHXH nguoi nuoi duong o XML9.
 */
class Xml3176MaBhxhDoDaiTest extends TestCase
{
    use Xml3176RuleTestSupport;

    private function loiXml11($maBhxh)
    {
        $loi = $this->invokePrivate($this->makeChecker(Xml3176Xml11Checker::class), 'infoChecker',
            new Xml3176Xml11(['ma_lk' => 'A', 'stt' => 1, 'ma_bhxh' => $maBhxh]));

        return collect($loi)->firstWhere('error_code', 'XML11_INFO_ERROR_MA_BHXH_LENGTH');
    }

    private function loiXml9($maBhxhNnd)
    {
        $loi = $this->invokePrivate($this->makeChecker(Xml3176Xml9Checker::class), 'infoChecker',
            new Xml3176Xml9(['ma_lk' => 'A', 'stt' => 1, 'ma_bhxh_nnd' => $maBhxhNnd]));

        return collect($loi)->firstWhere('error_code', 'XML9_INFO_ERROR_MA_BHXH_NND_LENGTH');
    }

    public function doDai()
    {
        return [
            '9 ky tu'  => ['123456789', true],
            '10 ky tu' => ['0123456789', false],
            '11 ky tu' => ['01234567890', false],
            '12 ky tu' => ['001191036820', false],
            '13 ky tu' => ['0011910368201', true],
        ];
    }

    /**
     * @test
     * @dataProvider doDai
     */
    public function xml11_ma_bhxh_tu_10_den_12_ky_tu($ma, $baoLoi)
    {
        $loi = $this->loiXml11($ma);

        $this->assertSame($baoLoi, $loi !== null, "MA_BHXH '$ma'");
        if ($baoLoi) {
            $this->assertSame('Mã BHXH phải có độ dài từ 10 đến 12 ký tự: ' . $ma, $loi->description);
        }
    }

    /**
     * @test
     * @dataProvider doDai
     */
    public function xml9_ma_bhxh_nguoi_nuoi_duong_tu_10_den_12_ky_tu($ma, $baoLoi)
    {
        $loi = $this->loiXml9($ma);

        $this->assertSame($baoLoi, $loi !== null, "MA_BHXH_NND '$ma'");
        if ($baoLoi) {
            $this->assertSame('Mã BHXH Người nuôi dưỡng phải có độ dài từ 10 đến 12 ký tự: ' . $ma, $loi->description);
        }
    }

    /** @test */
    public function ma_trong_van_bao_thieu_chu_khong_bao_do_dai()
    {
        $this->assertNull($this->loiXml11(''));
        $this->assertNull($this->loiXml9(''));
    }
}

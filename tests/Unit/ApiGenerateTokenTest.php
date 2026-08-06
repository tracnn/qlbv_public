<?php

namespace Tests\Unit;

use App\Console\Commands\ApiGenerateToken;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Tests\TestCase;

/** Ghi de duong dan de test KHONG cham config/organization.php that. */
class ApiGenerateTokenGiaLap extends ApiGenerateToken
{
    public $tepGiaLap;

    protected function duongDanConfig()
    {
        return $this->tepGiaLap;
    }

    protected function duongDanCacheConfig()
    {
        return '/duong-dan-khong-ton-tai';
    }
}

class ApiGenerateTokenTest extends TestCase
{
    protected $tep;

    protected function setUp()
    {
        parent::setUp();

        $this->tep = tempnam(sys_get_temp_dir(), 'cfg');
    }

    protected function tearDown()
    {
        if ($this->tep && is_file($this->tep)) {
            unlink($this->tep);
        }

        parent::tearDown();
    }

    protected function noiDungMau($hashCu = '')
    {
        return "<?php\n\nreturn [\n"
            . "    // Chu thich phai con nguyen sau khi ghi\n"
            . "    'api' => [\n"
            . "        'access_token' => '" . $hashCu . "',\n"
            . "        'organization' => '01013',\n"
            . "    ],\n"
            . "];\n";
    }

    /** @return array ['ma_thoat' => int, 'ra' => string] */
    protected function chay(array $thamSo = ['--force' => true])
    {
        $lenh = new ApiGenerateTokenGiaLap();
        $lenh->tepGiaLap = $this->tep;
        $lenh->setLaravel($this->app);

        $ra = new BufferedOutput();
        $maThoat = $lenh->run(new ArrayInput($thamSo), $ra);

        return ['ma_thoat' => $maThoat, 'ra' => $ra->fetch()];
    }

    protected function tokenTrongDauRa($ra)
    {
        preg_match('/\b([0-9a-f]{64})\b/', $ra, $khop);

        return isset($khop[1]) ? $khop[1] : null;
    }

    /** @test */
    public function sinh_token_64_ky_tu_hex()
    {
        file_put_contents($this->tep, $this->noiDungMau());

        $kq = $this->chay();

        $token = $this->tokenTrongDauRa($kq['ra']);

        $this->assertNotNull($token);
        $this->assertSame(64, strlen($token));
        $this->assertEquals(0, $kq['ma_thoat']);
    }

    /** @test */
    public function hai_lan_chay_cho_hai_token_khac_nhau()
    {
        file_put_contents($this->tep, $this->noiDungMau());
        $mot = $this->tokenTrongDauRa($this->chay()['ra']);

        file_put_contents($this->tep, $this->noiDungMau());
        $hai = $this->tokenTrongDauRa($this->chay()['ra']);

        $this->assertNotEquals($mot, $hai);
    }

    /** @test */
    public function hash_ghi_vao_tep_dung_bang_sha256_cua_token_in_ra()
    {
        file_put_contents($this->tep, $this->noiDungMau());

        $token = $this->tokenTrongDauRa($this->chay()['ra']);

        $this->assertContains(
            "'access_token' => '" . hash('sha256', $token) . "'",
            file_get_contents($this->tep)
        );
    }

    /**
     * Tep nay con chua cau hinh co so KCB va tai khoan cong BHXH - ghi lai ca tep la
     * cach nhanh nhat lam mat chung.
     *
     * @test
     */
    public function cac_dong_khac_giu_nguyen_tung_ky_tu()
    {
        file_put_contents($this->tep, $this->noiDungMau());

        $this->chay();

        $sau = file_get_contents($this->tep);

        $this->assertContains('// Chu thich phai con nguyen sau khi ghi', $sau);
        $this->assertContains("'organization' => '01013',", $sau);
    }

    /** @test */
    public function tep_thieu_khoa_thi_khong_ghi_gi_va_bao_loi()
    {
        $truoc = "<?php\n\nreturn [\n    'api' => [\n        'organization' => '01013',\n    ],\n];\n";
        file_put_contents($this->tep, $truoc);

        $kq = $this->chay();

        $this->assertNotEquals(0, $kq['ma_thoat']);
        $this->assertEquals($truoc, file_get_contents($this->tep));
        $this->assertContains('access_token', $kq['ra']);
    }
}

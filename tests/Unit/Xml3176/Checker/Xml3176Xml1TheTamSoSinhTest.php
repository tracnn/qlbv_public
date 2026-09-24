<?php

namespace Tests\Unit\Xml3176\Checker;

use App\Models\BHYT\Xml3176Xml1;
use App\Services\Xml3176Xml1Checker;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Support\Xml3176RuleTestSupport;
use Tests\TestCase;

/**
 * Luat MA_DKBD_NOT_FOUND bao gia cho the tam tre so sinh (DKBD XX000 khong co trong danh
 * muc CSKCB): ca 3/3 ho so XX000 tren CSDL that deu dinh (000006850061, 000006923430,
 * 000007100950).
 */
class Xml3176Xml1TheTamSoSinhTest extends TestCase
{
    use Xml3176RuleTestSupport;

    const MA = 'XML1_ADMIN_INFO_ERROR_MA_DKBD_NOT_FOUND';

    protected function setUp()
    {
        parent::setUp();
        // Ghi de CHINH ket noi 'mysql' - khong dung RefreshDatabase (.env tro CSDL that).
        config(['database.connections.mysql' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '']]);
        DB::purge('mysql');
        Schema::create('medical_organizations', function ($t) {
            $t->increments('id');
            $t->string('ma_cskcb', 20);
            $t->timestamps();
        });
        DB::table('medical_organizations')->insert([['ma_cskcb' => '01001'], ['ma_cskcb' => '01929']]);
    }

    private function chay($maThe, $maDkbd): array
    {
        $d = new Xml3176Xml1();
        $d->ma_the_bhyt = $maThe;
        $d->ma_dkbd = $maDkbd;

        return $this->errorCodes($this->invokePrivate(
            $this->makeChecker(Xml3176Xml1Checker::class), 'checkMaDkbdKhongCoTrongDanhMuc', $d));
    }

    /** @test */
    public function the_tam_so_sinh_khong_bi_bao()
    {
        $this->assertNotContains(self::MA, $this->chay('TE1373700012345', '37000'));
    }

    /** @test */
    public function the_thuong_co_dkbd_khong_co_trong_danh_muc_van_bi_bao()
    {
        $this->assertContains(self::MA, $this->chay('DN4010112345678', '99999'));
    }

    /** @test */
    public function the_khac_te1_co_dkbd_xx000_van_bi_bao()
    {
        $this->assertContains(self::MA, $this->chay('TR1010000012345', '01000'));
    }

    /** @test */
    public function dkbd_co_trong_danh_muc_khong_bi_bao()
    {
        $this->assertNotContains(self::MA, $this->chay('DN4010112345678', '01001'));
    }

    /** @test */
    public function ho_so_hai_the_chi_bao_the_thuong_sai()
    {
        // Ghep DKBD voi the cung vi tri: the tam bo qua, the thuong co DKBD sai van bao.
        $loi = $this->invokePrivate($this->makeChecker(Xml3176Xml1Checker::class),
            'checkMaDkbdKhongCoTrongDanhMuc', $this->xml1('TE1010000012345;DN4010112345678', '01000;99999'));

        $this->assertCount(1, $loi);
        $this->assertContains('99999', $loi->first()->description);
    }

    /** @test */
    public function ho_so_hai_the_deu_la_the_tam_khong_bao()
    {
        $this->assertNotContains(self::MA, $this->chay('TE1010000012345;TE1010000054321', '01000;01000'));
    }

    private function xml1($maThe, $maDkbd)
    {
        $d = new Xml3176Xml1();
        $d->ma_the_bhyt = $maThe;
        $d->ma_dkbd = $maDkbd;

        return $d;
    }
}

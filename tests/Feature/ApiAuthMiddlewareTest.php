<?php

namespace Tests\Feature;

use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Event;
use Tests\Support\DungBangLoiDotDieuTriSqlite;
use Tests\TestCase;

class ApiAuthMiddlewareTest extends TestCase
{
    use DungBangLoiDotDieuTriSqlite;

    const TOKEN = 'token-thu-nghiem-64-ky-tu';

    protected function setUp()
    {
        parent::setUp();

        $this->chuanBiBangLoi();

        config(['organization.api.access_token' => hash('sha256', self::TOKEN)]);
    }

    protected function goi($header = null)
    {
        $tuyChon = $header === null ? [] : ['Authorization' => $header];

        return $this->getJson('/api/order-check/violations?treatment_code=X', $tuyChon);
    }

    /**
     * Bat log qua su kien MessageLogged: su kien nay duoc ban TRUOC bo loc muc cua
     * Monolog, nen van bat duoc du phpunit.xml dat APP_LOG_LEVEL=emergency.
     *
     * @return array danh sach ['level' => ..., 'context' => [...]]
     */
    protected function batLog(callable $viec)
    {
        $ghi = [];

        Event::listen(MessageLogged::class, function ($e) use (&$ghi) {
            $ghi[] = ['level' => $e->level, 'context' => $e->context];
        });

        $viec();

        return $ghi;
    }

    /** @test */
    public function token_dung_thi_qua_duoc()
    {
        $this->goi('Bearer ' . self::TOKEN)->assertStatus(200);
    }

    /** @test */
    public function token_sai_thi_401()
    {
        $this->goi('Bearer token-bay-ba')
            ->assertStatus(401)
            ->assertJson(['success' => false, 'error' => ['code' => 'UNAUTHORIZED']]);
    }

    /** @test */
    public function thieu_header_thi_401()
    {
        $this->goi(null)->assertStatus(401);
    }

    /** @test */
    public function sai_dinh_dang_header_thi_401()
    {
        $this->goi('Token ' . self::TOKEN)->assertStatus(401);
        $this->goi('Bearer')->assertStatus(401);
    }

    /**
     * config/organization.php khong nam trong git nen ban cai chua cap nhat se THIEU
     * khoa nay. Trang thai an toan duy nhat la tu choi - khong phai 500, va tuyet doi
     * khong phai cho qua.
     *
     * @test
     */
    public function chua_cau_hinh_hash_thi_401_chu_khong_cho_qua()
    {
        config(['organization.api.access_token' => '']);

        $this->goi('Bearer ' . self::TOKEN)->assertStatus(401);
        $this->goi('Bearer ')->assertStatus(401);
    }

    /**
     * Chan duong so sanh truc tiep: neu con doan code nao so token voi gia tri cau hinh
     * ma khong bam, ca nay se do.
     *
     * @test
     */
    public function cau_hinh_luu_token_tho_thi_khong_qua_duoc()
    {
        config(['organization.api.access_token' => self::TOKEN]);

        $this->goi('Bearer ' . self::TOKEN)->assertStatus(401);
    }

    /** @test */
    public function that_bai_ghi_warning_kem_ly_do_va_khong_kem_token()
    {
        $ghi = $this->batLog(function () {
            $this->goi('Bearer token-bay-ba');
        });

        $warning = array_values(array_filter($ghi, function ($d) {
            return $d['level'] === 'warning';
        }));

        $this->assertCount(1, $warning);
        $this->assertEquals('sai_token', $warning[0]['context']['ly_do']);
        $this->assertNotContains('token-bay-ba', json_encode($warning[0]['context']));
    }

    /**
     * Truoc day moi request thanh cong deu ghi Log::info, lam ngap log that.
     *
     * @test
     */
    public function thanh_cong_ghi_debug_chu_khong_phai_info()
    {
        $ghi = $this->batLog(function () {
            $this->goi('Bearer ' . self::TOKEN);
        });

        $muc = array_column($ghi, 'level');

        $this->assertContains('debug', $muc);
        $this->assertNotContains('info', $muc);
    }
}

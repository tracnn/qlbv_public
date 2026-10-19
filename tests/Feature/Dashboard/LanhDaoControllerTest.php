<?php

namespace Tests\Feature\Dashboard;

use App\Services\Dashboard\LanhDao\KyLoc;
use App\Services\Dashboard\LanhDao\LanhDaoService;
use Tests\Support\UserGiaCoQuyen;
use Tests\TestCase;

/**
 * Lop gia ke thua service that (Mockery vo voi kieu tra ve ": array").
 */
class FakeLanhDaoService extends LanhDaoService
{
    /** @var array[] mỗi lần gọi: ['phan' => , 'ky' => KyLoc] */
    public $cacLanGoi = array();

    public function lay(string $phan, KyLoc $ky): array
    {
        $this->cacLanGoi[] = array('phan' => $phan, 'ky' => $ky);

        return array('phan' => $phan);
    }
}

class LanhDaoControllerTest extends TestCase
{
    /** @var FakeLanhDaoService */
    private $gia;

    protected function setUp()
    {
        parent::setUp();

        $this->gia = new FakeLanhDaoService();
        $this->app->instance(LanhDaoService::class, $this->gia);
    }

    /** @test */
    public function moi_phan_tra_json_va_goi_dung_phan()
    {
        foreach (LanhDaoService::PHAN as $i => $phan) {
            $this->goi("/dashboard/lanh-dao/{$phan}?from=2026-10-01&to=2026-10-19&co_so=nb")
                ->assertStatus(200)
                ->assertJson(array('phan' => $phan));

            $this->assertCount($i + 1, $this->gia->cacLanGoi);
            $lan = $this->gia->cacLanGoi[$i];
            $this->assertSame($phan, $lan['phan']);
            $this->assertSame('20261001000000', $lan['ky']->from);
            $this->assertSame('20261019235959', $lan['ky']->to);
            $this->assertSame('nb', $lan['ky']->coSo);
        }
        $this->assertCount(5, $this->gia->cacLanGoi);
    }

    /** @test */
    public function phan_la_404()
    {
        $this->goi('/dashboard/lanh-dao/xyz?from=2026-10-01&to=2026-10-19')->assertStatus(404);
        $this->assertCount(0, $this->gia->cacLanGoi);
    }

    /** @test */
    public function qua_92_ngay_422()
    {
        $this->goi('/dashboard/lanh-dao/the-so?from=2026-01-01&to=2026-10-19')->assertStatus(422);
        $this->assertCount(0, $this->gia->cacLanGoi, 'Validation trượt mà vẫn chạm vào service');
    }

    /** @test */
    public function thieu_from_422()
    {
        $this->goi('/dashboard/lanh-dao/the-so?to=2026-10-19')->assertStatus(422);
        $this->assertCount(0, $this->gia->cacLanGoi);
    }

    /** @test */
    public function trang_index_200_va_co_cau_hinh_route()
    {
        $res = $this->actingAs(UserGiaCoQuyen::tao())->get('/dashboard/lanh-dao');

        $res->assertStatus(200);
        $this->assertContains('LANH_DAO_CFG', $res->getContent());
        $this->assertContains('/dashboard/lanh-dao', $res->getContent());
    }

    private function goi($duongDan)
    {
        return $this->actingAs(UserGiaCoQuyen::tao())->get($duongDan, array(
            'X-Requested-With' => 'XMLHttpRequest',
            'Accept' => 'application/json',
        ));
    }
}

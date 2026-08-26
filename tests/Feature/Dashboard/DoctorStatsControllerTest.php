<?php

namespace Tests\Feature\Dashboard;

use Tests\TestCase;
use Tests\Support\UserGiaCoQuyen;
use App\Services\Dashboard\DoctorService;

/**
 * Lop gia ke thua service that.
 *
 * KHONG dung Mockery: ban 0.9.11 (composer.json ghim "0.9.*") goi
 * ReflectionType::__toString() khi reflect method co khai bao kieu tra ve, ma ham do da
 * deprecated tu PHP 7.4. phpunit.xml bat convertNoticesToExceptions nen moi Mockery::mock()
 * tren cac service nay deu no ngay dong dau. Ca bay method duoi day deu khai ": array".
 *
 * Ke thua that thi giu dung chu ky, khong reflect gi ca - dung khuon ma
 * Xml3176DashboardControllerTest da dat trong chinh thu muc nay.
 */
class FakeDoctorService extends DoctorService
{
    public $examinationsReturn = array();
    public $revenueReturn      = array();
    public $surgeriesReturn    = array();

    /** So lan goi tung method - thay cho ->once() cua Mockery */
    public $soLanGoi = array('getExaminations' => 0, 'getRevenue' => 0, 'getSurgeries' => 0);

    /** Tham so lan goi gan nhat - thay cho ->with() cua Mockery */
    public $thamSoCuoi = array();

    public function getExaminations(string $from, string $to, ?int $departmentId = null): array
    {
        $this->ghiNhan('getExaminations', func_get_args());

        return $this->examinationsReturn;
    }

    public function getRevenue(string $from, string $to, ?int $departmentId = null): array
    {
        $this->ghiNhan('getRevenue', func_get_args());

        return $this->revenueReturn;
    }

    public function getSurgeries(string $from, string $to): array
    {
        $this->ghiNhan('getSurgeries', func_get_args());

        return $this->surgeriesReturn;
    }

    private function ghiNhan($method, array $thamSo)
    {
        $this->soLanGoi[$method]++;
        $this->thamSoCuoi[$method] = $thamSo;
    }
}

class DoctorStatsControllerTest extends TestCase
{
    /** @var FakeDoctorService */
    private $gia;

    protected function setUp()
    {
        parent::setUp();

        $this->gia = new FakeDoctorService();
        $this->app->instance(DoctorService::class, $this->gia);
    }

    /** @test */
    public function examinations_endpoint_returns_json()
    {
        $this->gia->examinationsReturn = array(
            array('loginname' => 'vck', 'username' => 'VŨ CÔNG KHANH', 'total_exams' => 450, 'total_patients' => 400),
        );

        $response = $this->goi('/dashboard/doctor-stats/examinations?from=2026-03-01&to=2026-03-31');

        $response->assertStatus(200)
                 ->assertJsonStructure(array('data' => array(array('loginname', 'username', 'total_exams'))));

        $this->assertSame(1, $this->gia->soLanGoi['getExaminations'], 'Controller phai goi getExaminations dung mot lan');
        // Hai tham so string lien nhau rat de truyen nguoc thu tu ma van ra 200
        $this->assertSame(array('2026-03-01', '2026-03-31', null), $this->gia->thamSoCuoi['getExaminations']);
    }

    /** @test */
    public function examinations_endpoint_validates_required_params()
    {
        $response = $this->goi('/dashboard/doctor-stats/examinations');

        $response->assertStatus(422);
        $this->assertSame(0, $this->gia->soLanGoi['getExaminations'], 'Validation truot ma van cham vao service');
    }

    /** @test */
    public function revenue_endpoint_returns_json()
    {
        $response = $this->goi('/dashboard/doctor-stats/revenue?from=2026-03-01&to=2026-03-31');

        $response->assertStatus(200)->assertJsonStructure(array('data'));

        $this->assertSame(1, $this->gia->soLanGoi['getRevenue']);
        $this->assertSame(array('2026-03-01', '2026-03-31', null), $this->gia->thamSoCuoi['getRevenue']);
    }

    /** @test */
    public function surgeries_endpoint_returns_json()
    {
        $response = $this->goi('/dashboard/doctor-stats/surgeries?from=2026-03-01&to=2026-03-31');

        $response->assertStatus(200)->assertJsonStructure(array('data'));

        $this->assertSame(1, $this->gia->soLanGoi['getSurgeries']);
        $this->assertSame(array('2026-03-01', '2026-03-31'), $this->gia->thamSoCuoi['getSurgeries']);
    }

    private function goi($duongDan)
    {
        return $this->actingAs(UserGiaCoQuyen::tao())->getJson($duongDan);
    }
}

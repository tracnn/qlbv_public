<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\DB;
use Tests\Support\DungBangLoiDotDieuTriSqlite;
use Tests\TestCase;

class OrderCheckApiTest extends TestCase
{
    use DungBangLoiDotDieuTriSqlite;

    const TOKEN = 'token-thu-nghiem';

    protected function setUp()
    {
        parent::setUp();

        $this->chuanBiBangLoi();

        config(['organization.api.access_token' => self::TOKEN]);
    }

    protected function goi(array $thamSo, $token = self::TOKEN)
    {
        return $this->getJson(
            '/api/order-check/violations?' . http_build_query($thamSo),
            ['Authorization' => 'Bearer ' . $token]
        );
    }

    /** @test */
    public function thieu_token_thi_tra_401()
    {
        $this->getJson('/api/order-check/violations?treatment_code=X')
            ->assertStatus(401)
            ->assertJson(['success' => false]);
    }

    /** @test */
    public function thieu_ca_hai_tham_so_thi_tra_422_dung_khuon()
    {
        $this->goi([])
            ->assertStatus(422)
            ->assertJson([
                'success' => false,
                'error' => ['code' => 'VALIDATION_ERROR'],
            ])
            ->assertJsonStructure([
                'success',
                'error' => ['code', 'message', 'details'],
                'meta' => ['timestamp', 'request_id'],
            ]);
    }

    /** @test */
    public function dot_co_du_ba_nhom_loi_tra_ve_day_du()
    {
        $this->themViPham(['severity' => 'critical']);

        DB::table('check_hein_cards')->insert([
            'ma_lk' => '01013250800123', 'ma_tracuu' => '005', 'ma_kiemtra' => '00',
            'ma_ketqua' => 'The het han', 'ghi_chu' => null, 'ma_the' => 'DN4010112345678',
            'created_at' => '2026-08-05 14:00:00', 'updated_at' => '2026-08-05 14:03:00',
        ]);

        DB::table('xml3176_error_results')->insert([
            'xml' => 'XML1', 'ma_lk' => '01013250800123', 'stt' => 1,
            'ngay_yl' => '20260805', 'ngay_kq' => '20260805', 'error_code' => 'L001',
            'description' => 'Chi tiet loi', 'critical_error' => 1,
            'created_at' => '2026-08-05 15:00:00', 'updated_at' => '2026-08-05 15:00:00',
        ]);

        $phanHoi = $this->goi(['treatment_code' => '01013250800123']);

        $phanHoi->assertStatus(200)
            ->assertJson([
                'success' => true,
                'summary' => [
                    'total' => 3, 'order_check' => 1, 'hein_card' => 1, 'xml3176' => 1,
                    'critical' => 2, 'has_error' => true, 'truncated' => false,
                ],
            ])
            ->assertJsonStructure([
                'success',
                'data' => ['treatment_code', 'order_check', 'hein_card', 'xml3176'],
                'summary',
                'meta' => ['timestamp', 'request_id'],
            ]);

        // Laravel 5.5: TestResponse::json() KHONG nhan tham so khoa (chi co tu 5.6),
        // nen phai lay ca mang roi tu di xuong.
        $than = $phanHoi->json();

        $this->assertEquals('****5678', $than['data']['hein_card'][0]['ma_the_masked']);
    }

    /**
     * Khong dung 404: HIS goi cho MOI dot dieu tri, "khong co loi" la ket qua hop le
     * chu khong phai tai nguyen khong ton tai.
     *
     * @test
     */
    public function dot_sach_tra_200_voi_ba_mang_rong()
    {
        $this->goi(['treatment_code' => 'KHONG-CO-DOT-NAY'])
            ->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => ['order_check' => [], 'hein_card' => [], 'xml3176' => []],
                'summary' => ['total' => 0, 'has_error' => false],
            ]);
    }

    /** @test */
    public function loc_theo_treatment_id_van_hoat_dong()
    {
        $this->themViPham(['treatment_id' => 9001]);

        $this->goi(['treatment_id' => 9001])
            ->assertStatus(200)
            ->assertJson(['summary' => ['order_check' => 1]]);
    }
}

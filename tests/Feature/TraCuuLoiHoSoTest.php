<?php

namespace Tests\Feature;

use Tests\Support\DungBangHoSoHisSqlite;
use Tests\Support\DungBangLoiDotDieuTriSqlite;
use Tests\TestCase;

/** Nguoi dung gia: hasRole() tra true dung cho danh sach role duoc cap. */
class NguoiDungCoRole extends \App\User
{
    public $roles = [];

    public function hasRole($role, $team = null, $requireAll = false)
    {
        return in_array($role, $this->roles);
    }

    public function can($permission, $team = null, $requireAll = false)
    {
        return false;
    }
}

class TraCuuLoiHoSoTest extends TestCase
{
    use DungBangLoiDotDieuTriSqlite;
    use DungBangHoSoHisSqlite;

    const MA = '01013250800123';

    protected function setUp()
    {
        parent::setUp();
        $this->chuanBiBangLoi();
        $this->chuanBiBangHoSo();
    }

    protected function nguoiDung(array $roles)
    {
        $u = new NguoiDungCoRole();
        $u->id = 1;
        $u->roles = $roles;

        return $u;
    }

    protected function traCuu($ma, array $roles = ['tra-cuu-loi-ho-so'])
    {
        return $this->actingAs($this->nguoiDung($roles))
            ->getJson('/khth/tra-cuu-loi-ho-so/tra-cuu?treatment_code=' . urlencode($ma));
    }

    /** @test */
    public function khong_co_role_thi_403()
    {
        $this->actingAs($this->nguoiDung(['xml-man']))
            ->get('/khth/tra-cuu-loi-ho-so')
            ->assertStatus(403);
    }

    /** @test */
    public function co_role_thi_mo_duoc_man_hinh()
    {
        $this->actingAs($this->nguoiDung(['tra-cuu-loi-ho-so']))
            ->get('/khth/tra-cuu-loi-ho-so')
            ->assertStatus(200);
    }

    /** @test */
    public function ho_so_co_loi_tra_du_ho_so_va_ba_nhom()
    {
        $this->themHoSo();
        $this->themViPham(['treatment_code' => self::MA, 'severity' => 'critical']);

        $this->traCuu(self::MA)
            ->assertStatus(200)
            ->assertJson([
                'profile' => ['patient_name' => 'Nguyễn Văn A', 'ma_cskcb' => '01001'],
                'summary' => ['order_check' => 1, 'has_error' => true],
            ])
            ->assertJsonStructure([
                'profile', 'profile_error',
                'data' => ['treatment_code', 'order_check', 'hein_card', 'xml3176'],
                'summary' => ['total', 'critical', 'has_error'],
            ]);
    }

    /** @test */
    public function ho_so_sach_van_tra_200_va_khong_co_loi()
    {
        $this->themHoSo();

        $res = $this->traCuu(self::MA)->assertStatus(200);

        $res->assertJson(['summary' => ['total' => 0, 'has_error' => false]]);
        $this->assertSame([], $res->json()['data']['order_check']);
    }

    /** @test */
    public function khong_co_ho_so_tren_his_nhung_van_tra_loi_cua_mysql()
    {
        $this->themViPham(['treatment_code' => self::MA]);

        $res = $this->traCuu(self::MA)->assertStatus(200);

        $this->assertNull($res->json()['profile']);
        $this->assertCount(1, $res->json()['data']['order_check']);
    }

    /** @test */
    public function ma_rong_tra_422()
    {
        $this->traCuu('')
            ->assertStatus(422)
            ->assertJson(['message' => 'Chưa nhập mã điều trị']);
    }
}

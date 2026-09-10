<?php

namespace Tests\Feature;

use Tests\TestCase;

/**
 * User gia thoa CheckRole middleware ma khong truy van bang roles trong DB.
 * (Theo pattern cua tests/Feature/Dashboard/Xml3176DashboardControllerTest.php)
 */
class FakeCategoryManagerUser extends \App\User
{
    public function hasRole($role, $team = null, $requireAll = false) { return true; }
    public function can($permission, $team = null, $requireAll = false) { return true; }
}

class DanhMucTraCuuControllerTest extends TestCase
{
    private function nguoiDung()
    {
        $u = new FakeCategoryManagerUser();
        $u->id = 1;
        return $u;
    }

    /** @test */
    public function man_hinh_mo_duoc_voi_khoa_hop_le()
    {
        $this->actingAs($this->nguoiDung())
             ->get('/danh-muc-tra-cuu/dvkt_can_ma_may')
             ->assertStatus(200)
             ->assertSee('DVKT cần mã máy');
    }

    /** @test */
    public function khoa_la_tra_404_o_man_hinh()
    {
        $this->actingAs($this->nguoiDung())
             ->get('/danh-muc-tra-cuu/khong-co-that')
             ->assertStatus(404);
    }

    /** @test */
    public function khoa_la_tra_404_o_duong_du_lieu()
    {
        $this->actingAs($this->nguoiDung())
             ->get('/danh-muc-tra-cuu/khong-co-that/du-lieu')
             ->assertStatus(404);
    }

    /** @test */
    public function duong_du_lieu_tra_json_dung_cau_truc()
    {
        $this->actingAs($this->nguoiDung())
             ->getJson('/danh-muc-tra-cuu/dvkt_can_ma_may/du-lieu')
             ->assertStatus(200)
             ->assertJsonStructure(['data']);
    }
}

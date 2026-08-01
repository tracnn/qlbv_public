<?php

namespace Tests\Feature;

use App\Role;
use App\RoleUser;
use App\Services\SuperAdminBootstrap;
use Tests\Support\DungBangPhanQuyenSqlite;
use Tests\TestCase;

class KhoiTaoSuperAdminTest extends TestCase
{
    use DungBangPhanQuyenSqlite;

    protected function setUp()
    {
        parent::setUp();

        $this->chuanBiBangPhanQuyen();
    }

    /** @test */
    public function he_thong_trong_thi_man_khoi_tao_mo()
    {
        $this->actingAs($this->nguoiDungGia())
            ->get('/setup/quan-tri-dau-tien')
            ->assertStatus(200);
    }

    /** @test */
    public function chua_dang_nhap_thi_chuyen_ve_trang_dang_nhap()
    {
        $this->get('/setup/quan-tri-dau-tien')
            ->assertRedirect('/login');
    }

    /**
     * 404 chu khong phai 403: sau khi cong dong, khong de lo su ton tai cua man
     * nay trong suot vong doi con lai cua ban cai.
     *
     * @test
     */
    public function da_khoi_tao_thi_get_tra_404()
    {
        app(SuperAdminBootstrap::class)->gan($this->nguoiDungGia(5005));

        $this->actingAs($this->nguoiDungGia(6006))
            ->get('/setup/quan-tri-dau-tien')
            ->assertStatus(404);
    }

    /** @test */
    public function da_khoi_tao_thi_post_tra_404_va_khong_them_ban_ghi()
    {
        app(SuperAdminBootstrap::class)->gan($this->nguoiDungGia(5005));

        $this->actingAs($this->nguoiDungGia(6006))
            ->post('/setup/quan-tri-dau-tien')
            ->assertStatus(404);

        $this->assertSame(0, RoleUser::where('user_id', 6006)->count());
    }

    /** @test */
    public function post_gan_quyen_cho_nguoi_dang_dang_nhap()
    {
        $this->actingAs($this->nguoiDungGia(7007))
            ->post('/setup/quan-tri-dau-tien')
            ->assertRedirect('/home');

        $roleId = Role::where('name', 'superadministrator')->value('id');

        $this->assertSame(1, RoleUser::where('role_id', $roleId)
            ->where('user_id', 7007)
            ->where('user_type', 'App\CustomUser')
            ->count());
    }

    /** @test */
    public function post_lan_hai_khong_tao_ban_ghi_trung()
    {
        $nguoiDung = $this->nguoiDungGia(8008);

        $this->actingAs($nguoiDung)->post('/setup/quan-tri-dau-tien');
        $this->actingAs($nguoiDung)->post('/setup/quan-tri-dau-tien')
            ->assertStatus(404);

        $this->assertSame(1, RoleUser::where('user_id', 8008)->count());
    }
}

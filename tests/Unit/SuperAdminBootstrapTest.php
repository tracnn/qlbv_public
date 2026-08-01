<?php

namespace Tests\Unit;

use App\Exceptions\DaKhoiTaoException;
use App\Role;
use App\RoleUser;
use App\Services\SuperAdminBootstrap;
use Tests\Support\DungBangPhanQuyenSqlite;
use Tests\TestCase;

class SuperAdminBootstrapTest extends TestCase
{
    use DungBangPhanQuyenSqlite;

    protected $bootstrap;

    protected function setUp()
    {
        parent::setUp();

        $this->chuanBiBangPhanQuyen();
        $this->bootstrap = new SuperAdminBootstrap();
    }

    /**
     * Day chinh la kich ban gay loi cu: bang roles rong vi ban cai moi chua chay
     * laratrust:seeder. Ma cu doc ->first()->id tren null o day.
     *
     * @test
     */
    public function bang_roles_rong_thi_coi_nhu_chua_khoi_tao()
    {
        $this->assertSame(0, Role::count());
        $this->assertTrue($this->bootstrap->chuaKhoiTao());
    }

    /** @test */
    public function co_vai_tro_nhung_chua_gan_ai_thi_van_chua_khoi_tao()
    {
        Role::create([
            'name'         => 'superadministrator',
            'display_name' => 'Super Administrator',
            'description'  => 'Highest level administrator',
        ]);

        $this->assertTrue($this->bootstrap->chuaKhoiTao());
    }

    /** @test */
    public function da_gan_cho_mot_nguoi_thi_da_khoi_tao()
    {
        $this->bootstrap->gan($this->nguoiDungGia());

        $this->assertFalse($this->bootstrap->chuaKhoiTao());
    }

    /** @test */
    public function vai_tro_duoc_tao_neu_bang_roles_chua_co()
    {
        $vaiTro = $this->bootstrap->vaiTro();

        $this->assertSame('superadministrator', $vaiTro->name);
        $this->assertSame(1, Role::where('name', 'superadministrator')->count());
    }

    /** @test */
    public function goi_vai_tro_hai_lan_khong_tao_ban_ghi_trung()
    {
        $this->bootstrap->vaiTro();
        $this->bootstrap->vaiTro();

        $this->assertSame(1, Role::where('name', 'superadministrator')->count());
    }

    /**
     * Ban ghi phai nam tren ket noi mysql voi dung user_type. Day cung la bang
     * chung cho diem "phai kiem chung" trong dac ta: attachRole() tren model ghim
     * Oracle van ghi dung vao bang pivot ben mysql.
     *
     * @test
     */
    public function gan_ghi_dung_ban_ghi_role_user()
    {
        $nguoiDung = $this->nguoiDungGia(2002);

        $this->bootstrap->gan($nguoiDung);

        $roleId = Role::where('name', 'superadministrator')->value('id');

        $this->assertSame(1, RoleUser::where('role_id', $roleId)
            ->where('user_id', 2002)
            ->where('user_type', 'App\CustomUser')
            ->count());
    }

    /** @test */
    public function nguoi_thu_hai_bi_tu_choi()
    {
        $this->bootstrap->gan($this->nguoiDungGia(3003));

        $this->expectException(DaKhoiTaoException::class);

        $this->bootstrap->gan($this->nguoiDungGia(4004));
    }

    /**
     * Khong phai test rollback: gan() nem truoc khi ghi bat cu thu gi, nen khong
     * co gi de rollback. Cai duoc chung minh la lan kiem tra lai chan dung cho -
     * TRUOC attachRole() - nen nguoi thu hai khong de lai ban ghi role_user nao.
     *
     * @test
     */
    public function nguoi_thu_hai_bi_tu_choi_khong_de_lai_ban_ghi()
    {
        $this->bootstrap->gan($this->nguoiDungGia(3003));

        try {
            $this->bootstrap->gan($this->nguoiDungGia(4004));
        } catch (DaKhoiTaoException $e) {
            // mong doi
        }

        $this->assertSame(0, RoleUser::where('user_id', 4004)->count());
    }
}

<?php

namespace Tests\Unit\Ctdt;

use Tests\TestCase;
use Tests\Support\DungBangCtdtSqlite;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BHYT\BHYTCtdtController;
use App\Jobs\SignCtdtJob;
use App\Models\BHYT\Ctdt\CtdtHoSo;
use App\Models\BHYT\Ctdt\CtdtChungTu;

class CtdtKyVaGuiTest extends TestCase
{
    use DungBangCtdtSqlite;

    /** @var BHYTCtdtController */
    private $controller;

    protected function setUp()
    {
        parent::setUp();
        $this->chuanBiBangCtdt();
        Queue::fake();
        $this->controller = new BHYTCtdtController();

        // $errors binh thuong duoc middleware ShareErrorsFromSession cua nhom 'web' bom vao
        // moi view. Goi view(...)->render() thang trong test khong di qua middleware do, nen
        // includes.message (duoc detail.blade.php nhung vao) se gap "Undefined variable:
        // errors". Bom san mot ViewErrorBag rong o day - khong lien quan gi toi logic ky va
        // gui, chi bit mot khoang trong ha tang test.
        \Illuminate\Support\Facades\View::share('errors', new \Illuminate\Support\ViewErrorBag());

        config([
            'organization.chung_tu_dien_tu.sign_enabled'   => true,
            'organization.chung_tu_dien_tu.submit_enabled' => true,
            'organization.chung_tu_dien_tu.sign_queue_name'   => 'JobSignCtdt',
            'organization.chung_tu_dien_tu.submit_queue_name' => 'JobSubmitCtdt',
        ]);
    }

    private function hoSo(array $ghiDe = [])
    {
        $hoSo = CtdtHoSo::create(array_merge([
            'ma_ho_so' => 'YT001', 'dich_vu' => 'CT2025', 'loai_hs' => '39',
            'macskcb' => '01929', 'so_chung_tu' => 1,
            'checked_at' => '2026-08-20 08:00:00', 'so_loi' => 0, 'is_signed' => false,
        ], $ghiDe));

        CtdtChungTu::create([
            'ho_so_id' => $hoSo->id, 'loai_ho_so' => 'CT03', 'ma_chung_tu' => 'YT001',
            'noi_dung_goc' => '<CT03/>',
        ]);

        return $hoSo->fresh();
    }

    private function layJson($response)
    {
        return json_decode($response->getContent(), true);
    }

    /**
     * Gia lap mot nguoi dung dang nhap chi de render duoc man chi tiet.
     *
     * VI SAO CAN: view 'bhyt.ctdt.detail' @extends('adminlte::page'), va
     * AppServiceProvider::boot() dang ky View::composer('adminlte::page', ...) goi thang
     * Auth::user()->hasRole(...) khong kiem null. Khong dang nhap ai thi Auth::user() la
     * null va composer nem "Call to a member function hasRole() on null" - loi nay khong
     * lien quan gi toi tinh nang ky va gui, no la mot dieu kien co san cua man hinh (chua
     * tung co test nao render() toan bo trang truoc task nay de lo ra). DungBangCtdtSqlite
     * chi dung 12 bang rieng cua module, khong co bang roles/permissions, nen khong the
     * dang nhap mot App\User that va goi hasRole() that. Gia lap qua Mockery de tranh dung
     * toi CSDL phan quyen (theo dung tinh than "khong dung RefreshDatabase, khong dung toi
     * qlbv that" cua ca file test nay).
     */
    private function giaLapDangNhap()
    {
        // KHONG DUNG Mockery::mock(App\User::class): tren PHP 7.4 voi Mockery cua du an
        // nay, sinh proxy cho mot lop co phuong thuc khai bao kieu tra ve (return type)
        // kich hoat ReflectionType::__toString() da bi loai bo, va error handler cua
        // Laravel bien canh bao do thanh ErrorException - dung thu "Mockery vo voi
        // return type" da gap o cac task truoc. Dung mot lop con nac danh that thay vi
        // mock de tranh hoan toan sinh ma dong cua Mockery.
        $nguoiDung = new class extends \App\User {
            public function hasRole($name, $team = null, $requireAll = false)
            {
                return true;
            }
        };
        $nguoiDung->id = 1;

        // setUser(), KHONG login(): login() phat su kien 'login' - o du an nay co listener
        // DanhDauCanKhoiTaoSuperAdmin -> SuperAdminBootstrap doc bang 'roles' that, bang
        // nay khong nam trong 12 bang cua DungBangCtdtSqlite nen se nem "no such table:
        // roles". setUser() chi gan nguoi dung vao guard, khong phat su kien nao.
        \Illuminate\Support\Facades\Auth::setUser($nguoiDung);
    }

    /** @test */
    public function ho_so_du_dieu_kien_thi_day_job_ky()
    {
        $this->hoSo();

        $kq = $this->layJson($this->controller->kyVaGui('YT001'));

        $this->assertTrue($kq['thanh_cong']);
        Queue::assertPushed(SignCtdtJob::class);
    }

    /** @test */
    public function job_ky_di_dung_hang_doi_lay_tu_cau_hinh()
    {
        // Day sai hang doi thi worker khong bao gio nhan duoc, va ho so nam mai o "chua ky"
        // ma khong co dau hieu gi.
        config(['organization.chung_tu_dien_tu.sign_queue_name' => 'HangDoiRieng']);
        $this->hoSo();

        $this->controller->kyVaGui('YT001');

        Queue::assertPushed(SignCtdtJob::class, function ($job) {
            return $job->queue === 'HangDoiRieng';
        });
    }

    /** @test */
    public function job_gui_duoc_xau_chuoi_SAU_job_ky()
    {
        // Ky xong moi gui duoc. Day hai job doc lap thi job gui co the chay truoc va luon
        // thay is_signed = false.
        $this->hoSo();

        $this->controller->kyVaGui('YT001');

        Queue::assertPushed(SignCtdtJob::class, function ($job) {
            return !empty($job->chained);
        });
    }

    /** @test */
    public function ho_so_chua_kiem_thi_tu_choi_va_KHONG_day_job()
    {
        $this->hoSo(['checked_at' => null]);

        $kq = $this->layJson($this->controller->kyVaGui('YT001'));

        $this->assertFalse($kq['thanh_cong']);
        $this->assertContains('chưa kiểm', mb_strtolower($kq['thong_diep']));
        Queue::assertNotPushed(SignCtdtJob::class);
    }

    /** @test */
    public function ho_so_con_loi_thi_tu_choi_va_KHONG_day_job()
    {
        $this->hoSo(['so_loi' => 4]);

        $kq = $this->layJson($this->controller->kyVaGui('YT001'));

        $this->assertFalse($kq['thanh_cong']);
        Queue::assertNotPushed(SignCtdtJob::class);
    }

    /** @test */
    public function chuc_nang_gui_dang_tat_thi_tu_choi_va_noi_ro_ly_do()
    {
        // Nguoi bam nut phai biet VI SAO khong co gi xay ra, khong thi ho bam lai mai.
        config(['organization.chung_tu_dien_tu.submit_enabled' => false]);
        $this->hoSo();

        $kq = $this->layJson($this->controller->kyVaGui('YT001'));

        $this->assertFalse($kq['thanh_cong']);
        $this->assertNotEmpty($kq['thong_diep']);
        Queue::assertNotPushed(SignCtdtJob::class);
    }

    /** @test */
    public function ho_so_da_ky_roi_thi_van_day_duoc_de_gui_lai()
    {
        // Gui lai mot ho so da ky la viec hop le (cong tra 500 lan truoc chang han).
        $this->hoSo(['is_signed' => true]);

        $kq = $this->layJson($this->controller->kyVaGui('YT001'));

        $this->assertTrue($kq['thanh_cong']);
        Queue::assertPushed(SignCtdtJob::class);
    }

    /** @test */
    public function ho_so_khong_ton_tai_thi_nem_ModelNotFound()
    {
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        $this->controller->kyVaGui('KHONG_TON_TAI');
    }

    /** @test */
    public function route_ky_va_gui_ton_tai_va_nam_trong_nhom_xml_man()
    {
        // Dat ngoai nhom checkrole la mo mot duong gui len cong BHXH cho bat ky ai dang nhap.
        $route = Route::getRoutes()->getByName('bhyt.ctdt.ky-va-gui');

        $this->assertNotNull($route, 'Thieu route bhyt.ctdt.ky-va-gui');
        $this->assertContains('POST', $route->methods());
        $this->assertContains('checkrole:xml-man', $route->gatherMiddleware());
    }

    /** @test */
    public function man_chi_tiet_render_duoc_va_co_nut_ky_va_gui()
    {
        $this->giaLapDangNhap();
        $hoSo = $this->hoSo();

        $html = view('bhyt.ctdt.detail', [
            'hoSo' => $hoSo->fresh(),
            'tabs' => \App\Services\Ctdt\CtdtDetailTabs::cua($hoSo->fresh()),
        ])->render();

        $this->assertContains('btn-ky-va-gui', $html);
    }

    /** @test */
    public function noi_dung_phan_hoi_cua_cong_duoc_thoat_khi_hien()
    {
        // submitted_message la nguyen van phan hoi cua cong - du lieu ben ngoai. Bo thoat la
        // mot lo hong XSS luu tru, dung lop loi da bi bat ba lan trong module nay.
        $this->giaLapDangNhap();
        $hoSo = $this->hoSo([
            'submit_error'      => 'Lỗi <img src=x onerror=alert(1)>',
            'submitted_message' => '{"ChiTiet":"<script>alert(2)</script>"}',
        ]);

        $html = view('bhyt.ctdt.detail', [
            'hoSo' => $hoSo->fresh(),
            'tabs' => \App\Services\Ctdt\CtdtDetailTabs::cua($hoSo->fresh()),
        ])->render();

        $this->assertNotContains('<img src=x', $html);
        $this->assertNotContains('<script>alert(2)', $html);
        $this->assertContains('&lt;img', $html);
    }
}

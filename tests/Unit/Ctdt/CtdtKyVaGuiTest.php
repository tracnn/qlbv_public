<?php

namespace Tests\Unit\Ctdt;

use Tests\TestCase;
use Tests\Support\DungBangCtdtSqlite;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Cache;
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

        // phpunit.xml dat CACHE_DRIVER=array cho test, nen khoa cache chong bam trung dung
        // ArrayStore chu khong phai FileStore. ArrayStore van ton tai giua cac test TRONG
        // CUNG mot tien trinh PHPUnit - khoa cua test truoc se chan test sau neu khong xoa.
        Cache::flush();
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
    public function job_gui_trong_chain_cung_di_dung_hang_doi_lay_tu_cau_hinh()
    {
        // Chi khoa hang doi cua job KY la khong du: bo onQueue() cua job GUI thi ho so ky
        // xong roi nam mai o "da ky, chua gui" - worker JobSubmitCtdt khong bao gio nhat, va
        // khong co dau hieu gi tren man hinh.
        config([
            'organization.chung_tu_dien_tu.sign_queue_name'   => 'HangDoiKyRieng',
            'organization.chung_tu_dien_tu.submit_queue_name' => 'HangDoiGuiRieng',
        ]);

        $this->hoSo();

        $this->controller->kyVaGui('YT001');

        Queue::assertPushed(SignCtdtJob::class, function ($job) {
            return isset($job->chained[0])
                && unserialize($job->chained[0])->queue === 'HangDoiGuiRieng';
        });
    }

    /** @test */
    public function thieu_cau_hinh_hang_doi_thi_lui_ve_ten_mac_dinh()
    {
        // config/organization.php la tep rieng cua tung may va nam trong .gitignore. Mot ban
        // config:cache cu hay mot lan ghi de thieu khoa se lam ca hai job im lang roi vao
        // hang doi 'default', va khong worker nao nhat chung.
        config([
            'organization.chung_tu_dien_tu.sign_queue_name'   => null,
            'organization.chung_tu_dien_tu.submit_queue_name' => null,
        ]);

        $this->hoSo();

        $this->controller->kyVaGui('YT001');

        Queue::assertPushed(SignCtdtJob::class, function ($job) {
            return $job->queue === 'JobSignCtdt';
        });
    }

    /** @test */
    public function ky_tat_va_ho_so_chua_ky_thi_tu_choi_va_noi_ro_ly_do()
    {
        config(['organization.chung_tu_dien_tu.sign_enabled' => false]);
        $this->hoSo(['is_signed' => false]);

        $kq = $this->layJson($this->controller->kyVaGui('YT001'));

        $this->assertFalse($kq['thanh_cong']);
        $this->assertContains('ký số đang tắt', $kq['thong_diep']);
        Queue::assertNotPushed(SignCtdtJob::class);
    }

    /** @test */
    public function ky_tat_nhung_ho_so_DA_ky_thi_van_gui_lai_duoc()
    {
        // Gui lai mot ho so da ky khong can ky lai, nen co ky tat khong lien quan.
        config(['organization.chung_tu_dien_tu.sign_enabled' => false]);
        $this->hoSo(['is_signed' => true]);

        $kq = $this->layJson($this->controller->kyVaGui('YT001'));

        $this->assertTrue($kq['thanh_cong']);
        Queue::assertPushed(SignCtdtJob::class);
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
    public function nut_ky_va_gui_ma_hoa_ma_ho_so_truoc_khi_ghep_url()
    {
        // ma_ho_so co the chua '#' (nhanh lui GUID). Khong ma hoa thi trinh duyet cat tu dau
        // '#' va yeu cau tro sai ho so - hoac te hon, gui nham mot ho so khac len cong.
        //
        // KHONG the render RIENG partial: no dung @push('after-scripts'), va noi dung day
        // chi hien ra khi co layout cha voi @stack('after-scripts') - render partial mot
        // minh se mat trang script. Ma cung KHONG the kiem tren toan bo $html cua trang: nut
        // "Xoa ho so" co san (Task 1-5) dung dung chuoi 'encodeURIComponent(maHoSo)' cho URL
        // cua no, nen assertContains tren ca trang PASS gia du toi xoa mat encodeURIComponent
        // trong partial cua chinh minh (da phat hien dieu nay khi tu do mutation). Vi vay
        // cat rieng doan HTML NGAY SAU diem gan click-handler cua nut ky-va-gui roi moi kiem.
        $this->giaLapDangNhap();
        $hoSo = $this->hoSo();

        $html = view('bhyt.ctdt.detail', [
            'hoSo' => $hoSo->fresh(),
            'tabs' => \App\Services\Ctdt\CtdtDetailTabs::cua($hoSo->fresh()),
        ])->render();

        $diem = strpos($html, "\$('#btn-ky-va-gui').on('click'");
        $this->assertNotFalse($diem, 'Khong tim thay script gan click cho nut ky-va-gui');

        $doanRieng = substr($html, $diem, 2000);
        $this->assertContains('encodeURIComponent(maHoSo)', $doanRieng);
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

    /** @test */
    public function man_chi_tiet_hien_ly_do_ky_hong_va_thoat_noi_dung()
    {
        // signed_error tung la cot chi-ghi: SignCtdtJob ghi vao ma khong man hinh nao doc.
        // Nguoi van hanh thay "Chua ky so" va di tim nut ky (da bam roi) thay vi di cam lai
        // USB token.
        $this->giaLapDangNhap();
        $hoSo = $this->hoSo(['signed_error' => 'USB token bi rut <img src=x onerror=alert(1)>']);

        $html = view('bhyt.ctdt.detail', [
            'hoSo' => $hoSo->fresh(),
            'tabs' => \App\Services\Ctdt\CtdtDetailTabs::cua($hoSo->fresh()),
        ])->render();

        $this->assertContains('Lỗi ký số', $html);
        $this->assertNotContains('<img src=x', $html, 'signed_error tu dich vu ky, phai thoat');
        $this->assertContains('&lt;img', $html);
    }

    /** @test */
    public function phan_hoi_cua_lan_gui_THANH_CONG_van_hien_tren_man_chi_tiet()
    {
        // submitted_message tung bi long trong @if($hoSo->submit_error), nen phan hoi cua
        // mot lan gui thanh cong khong bao gio hien - dung thu ma buoc nghiem thu tay cua
        // Giai doan 4 can doc.
        $this->giaLapDangNhap();
        $hoSo = $this->hoSo([
            'submit_error'      => null,
            'ma_ket_qua'        => '200',
            'submitted_message' => '{"MaKetQua":"200","MaGD":"GD-001"}',
        ]);

        $html = view('bhyt.ctdt.detail', [
            'hoSo' => $hoSo->fresh(),
            'tabs' => \App\Services\Ctdt\CtdtDetailTabs::cua($hoSo->fresh()),
        ])->render();

        $this->assertContains('GD-001', $html);
    }

    /** @test */
    public function bam_lan_hai_khi_lan_mot_dang_chay_thi_TU_CHOI()
    {
        // Da xay ra that: mot ho so bi bam ba lan, sinh ba chuoi job. Voi ho so ky duoc thi
        // thanh BA lan POST that len cong - va PL02 khong co ma giao dich phia client nen
        // cong khong khu trung duoc.
        $this->hoSo();

        $lan1 = $this->layJson($this->controller->kyVaGui('YT001'));
        $lan2 = $this->layJson($this->controller->kyVaGui('YT001'));

        $this->assertTrue($lan1['thanh_cong']);
        $this->assertFalse($lan2['thanh_cong'], 'Lan bam thu hai phai bi tu choi');
        $this->assertContains('đang xử lý', $lan2['thong_diep']);

        Queue::assertPushed(SignCtdtJob::class, 1);
    }

    /** @test */
    public function ho_so_KHAC_van_bam_duoc_binh_thuong()
    {
        // Khoa phai theo TUNG ho so. Khoa chung se bien mot lan bam thanh mot hang doi mot
        // nguoi - ca phong khong ai gui duoc trong luc mot ho so dang chay.
        $this->hoSo();
        $this->hoSo(['ma_ho_so' => 'YT002']);

        $this->controller->kyVaGui('YT001');
        $kq = $this->layJson($this->controller->kyVaGui('YT002'));

        $this->assertTrue($kq['thanh_cong']);
        Queue::assertPushed(SignCtdtJob::class, 2);
    }

    /** @test */
    public function ho_so_bi_TU_CHOI_thi_KHONG_giu_khoa()
    {
        // Bi tu choi nghia la khong co chuoi job nao chay, nen khong co gi de nha khoa. Giu
        // khoa o day se khoa nguoi dung ra ngoai het thoi han vi mot lan bam khong lam gi ca.
        config(['organization.chung_tu_dien_tu.submit_enabled' => false]);
        $this->hoSo();

        $this->controller->kyVaGui('YT001');

        config(['organization.chung_tu_dien_tu.submit_enabled' => true]);
        $kq = $this->layJson($this->controller->kyVaGui('YT001'));

        $this->assertTrue($kq['thanh_cong'], 'Lan bam bi tu choi khong duoc giu khoa');
    }

    /** @test */
    public function job_gui_nha_khoa_khi_xong()
    {
        // Khong nha thi nguoi dung phai cho het han khoa moi gui lai duoc - ke ca khi lan
        // gui truoc da xong tu lau.
        $this->hoSo();
        $this->controller->kyVaGui('YT001');

        // Dung Cache::has() de tham do, KHONG dung Cache::add(): add() se TU DAT khoa khi
        // no chua ton tai, tuc phep do lam thay doi thu no dang do.
        $this->assertTrue(Cache::has(BHYTCtdtController::KHOA_XU_LY . 'YT001'),
            'Khoa phai dang giu sau khi bam');

        $job = new \App\Jobs\SubmitCtdtJob('YT001', 'tracnn');
        $job->submitServiceGia = new \Tests\Support\FakeCtdtSubmitService();
        $job->handle();

        $this->assertFalse(Cache::has(BHYTCtdtController::KHOA_XU_LY . 'YT001'),
            'Job gui xong phai nha khoa');
    }

    /** @test */
    public function job_gui_nha_khoa_tren_duong_THANH_CONG()
    {
        // Nhanh $quyetDinh === GUI la nhanh DUY NHAT dan toi mot lan POST that len cong.
        // Neu no khong nha khoa, moi lan gui THANH CONG deu khoa ho so lai het thoi han - dung
        // duong di binh thuong nhat lai la duong khong duoc canh.
        $this->hoSo(['is_signed' => true, 'duong_dan_da_ky' => 'da-ky/YT001.xml']);
        \Illuminate\Support\Facades\Storage::fake('exportCtdt');
        \Illuminate\Support\Facades\Storage::disk('exportCtdt')->put('da-ky/YT001.xml', '<x/>');

        $this->controller->kyVaGui('YT001');

        $job = new \App\Jobs\SubmitCtdtJob('YT001', 'tracnn');
        $job->submitServiceGia = new \Tests\Support\FakeCtdtSubmitService();
        $job->handle();

        $this->assertFalse(Cache::has(BHYTCtdtController::KHOA_XU_LY . 'YT001'));
    }

    /** @test */
    public function job_gui_nha_khoa_khi_KHONG_TIM_THAY_tep_da_ky()
    {
        // Tep tren dia co the bi don dep. Job ghi loi roi tra ve som - van phai nha khoa.
        $this->hoSo(['is_signed' => true, 'duong_dan_da_ky' => 'khong-ton-tai.xml']);
        \Illuminate\Support\Facades\Storage::fake('exportCtdt');

        $this->controller->kyVaGui('YT001');

        (new \App\Jobs\SubmitCtdtJob('YT001', 'tracnn'))->handle();

        $this->assertFalse(Cache::has(BHYTCtdtController::KHOA_XU_LY . 'YT001'));
    }

    /** @test */
    public function ca_hai_job_nha_khoa_trong_failed()
    {
        // failed() la luoi cuoi: het luot thu ma khong nha thi ho so bi khoa het thoi han
        // du lan gui do da chet tu lau.
        foreach ([\App\Jobs\SignCtdtJob::class, \App\Jobs\SubmitCtdtJob::class] as $lop) {
            $this->hoSo();
            Cache::add(BHYTCtdtController::KHOA_XU_LY . 'YT001', true, BHYTCtdtController::KHOA_XU_LY_PHUT);

            (new $lop('YT001'))->failed(new \RuntimeException('mang chap'));

            $this->assertFalse(Cache::has(BHYTCtdtController::KHOA_XU_LY . 'YT001'),
                $lop . '::failed() phai nha khoa');

            CtdtHoSo::where('ma_ho_so', 'YT001')->delete();
        }
    }

    /** @test */
    public function thoi_han_khoa_phai_lon_hon_ngan_sach_thu_lai_cua_ca_chuoi()
    {
        // Khoa het han GIUA CHUNG thi nguoi dung bam lai va sinh chuoi thu hai - hai chuoi
        // song song cho mot ho so la hai lan POST that len cong.
        //
        // Ngan sach chay thuan cua chuoi: SignCtdtJob tries x timeout + SubmitCtdtJob
        // tries x timeout. Chua tinh thoi gian nam cho trong hang doi, nen thoi han khoa
        // phai co bien du.
        $nganSach = 0;

        foreach ([\App\Jobs\SignCtdtJob::class, \App\Jobs\SubmitCtdtJob::class] as $lop) {
            $mac = (new \ReflectionClass($lop))->getDefaultProperties();
            $nganSach += (int) $mac['tries'] * (int) $mac['timeout'];
        }

        $khoaGiay = BHYTCtdtController::KHOA_XU_LY_PHUT * 60;

        $this->assertGreaterThan($nganSach, $khoaGiay,
            'Thoi han khoa (' . $khoaGiay . 's) phai lon hon ngan sach chuoi (' . $nganSach . 's)');
    }

    /** @test */
    public function ho_so_tung_gui_ma_dau_vet_bi_nap_lai_xoa_thi_doi_XAC_NHAN()
    {
        // CtdtLuuHoSo::ghiHoSo() reset ma_gd/ma_ket_qua/is_signed khi nap de - dung thiet
        // ke, vi noi dung da doi. Nhung hau qua: mot ho so DA duoc cong nhan that se hien
        // "Chua ky so" tren man danh sach va gui lai duoc ma khong co gi canh bao. Bang
        // chung da gui chi con o lich_su_gui, von chi hien o man CHI TIET.
        $this->hoSo([
            'ma_gd'        => null,
            'ma_ket_qua'   => null,
            'lich_su_gui'  => '2026-08-19 10:00:00 | MaGD GD-001 | MaKetQua 200',
        ]);

        $kq = $this->layJson($this->controller->kyVaGui('YT001'));

        $this->assertFalse($kq['thanh_cong'], 'Lan bam dau phai bi tu choi de nguoi bam nhin thay');
        $this->assertTrue($kq['can_xac_nhan']);
        $this->assertContains('đã từng được gửi lên cổng BHXH', $kq['thong_diep']);

        Queue::assertNotPushed(SignCtdtJob::class);
    }

    /** @test */
    public function bam_lai_kem_xac_nhan_gui_lai_thi_day_job_binh_thuong()
    {
        // Gui lai sau khi sua noi dung la viec HOP LE - canh bao chu khong chan cung.
        $this->hoSo([
            'ma_gd'        => null,
            'ma_ket_qua'   => null,
            'lich_su_gui'  => '2026-08-19 10:00:00 | MaGD GD-001 | MaKetQua 200',
        ]);

        request()->merge(['xac_nhan_gui_lai' => 1]);

        $kq = $this->layJson($this->controller->kyVaGui('YT001'));

        $this->assertTrue($kq['thanh_cong']);
        $this->assertArrayNotHasKey('can_xac_nhan', $kq);
        Queue::assertPushed(SignCtdtJob::class);
    }

    /** @test */
    public function ho_so_con_giu_ma_gd_thi_KHONG_doi_xac_nhan_them()
    {
        // Nhanh canh bao chi danh cho ca dau vet DA BI XOA. Ho so con ma_gd thi hop xac
        // nhan dau tien cua nut da noi ro "da gui (MaGD ...)" roi - hoi lan hai la thua.
        $this->hoSo([
            'ma_gd'       => 'GD-001',
            'ma_ket_qua'  => '200',
            'lich_su_gui' => '2026-08-19 10:00:00 | MaGD GD-001 | MaKetQua 200',
            'is_signed'   => true,
        ]);

        $kq = $this->layJson($this->controller->kyVaGui('YT001'));

        $this->assertTrue($kq['thanh_cong']);
        Queue::assertPushed(SignCtdtJob::class);
    }

    /** @test */
    public function ho_so_tung_bi_cong_TU_CHOI_cung_doi_xac_nhan_nhung_KHONG_noi_la_da_tiep_nhan()
    {
        // noiLichSu() ghi dong lich su khi ma_gd HOAC ma_ket_qua khac rong. Mot ho so tung bi
        // cong tu choi chi co ma_ket_qua - no van thoa dieu kien "tung gui", nhung noi voi
        // nguoi van hanh rang cong "da tiep nhan" la noi sai.
        $this->hoSo([
            'ma_gd'       => null,
            'ma_ket_qua'  => null,
            'lich_su_gui' => '[2026-08-20 08:00:00] MaGD= MaKetQua=205',
        ]);

        $kq = $this->layJson($this->controller->kyVaGui('YT001'));

        $this->assertFalse($kq['thanh_cong']);
        $this->assertTrue($kq['can_xac_nhan']);
        $this->assertNotContains('tiếp nhận', $kq['thong_diep'],
            'Loi van khong duoc khang dinh cong da tiep nhan');
    }
}

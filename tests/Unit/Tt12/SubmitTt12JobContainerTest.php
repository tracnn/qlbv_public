<?php

namespace Tests\Unit\Tt12;

use Tests\TestCase;
use ReflectionMethod;
use ReflectionProperty;
use App\Jobs\SubmitTt12Job;
use App\Jobs\SubmitCtdtJob;
use App\Services\Tt12\Tt12SubmitService;
use App\Services\Ctdt\CtdtSubmitService;

/**
 * Canh cai bay tiem phu thuoc cua container Laravel 5.5 tren SubmitTt12Job.
 *
 * VAN DE GOC (do duoc 2026-08-26, chan TOAN BO duong gui TT12 - chua tung chay duoc lan nao):
 *
 *   SubmitTt12Job::handle(Tt12SubmitService $submitService)
 *
 * Container tiem theo getClass() TRUOC khi xet gia tri mac dinh
 * (Illuminate\Container\BoundMethod::addDependencyForCallParameter - dong getClass() dung
 * truoc isDefaultValueAvailable()). Nen constructor
 *
 *   Tt12SubmitService::__construct(BHYTLoginService $loginService = null)
 *
 * van bi tiem mot BHYTLoginService RONG ma co so, du no khai `= null`. Roi trong gui():
 *
 *   $login = $this->loginService ?: $this->taoLogin($maCskcb);
 *
 * nhanh taoLogin($maCskcb) KHONG BAO GIO chay vi ve trai da truthy - token bi lay voi ma co
 * so rong va CauHinhCoSo::cua('') nem 'Thieu ma co so KCB' o MOI lan gui.
 *
 * VI SAO BO TEST CU KHONG BAT DUOC: ca 8 test trong SubmitTt12JobTest goi handle($gia) voi
 * service gia truyen tay, nen chung chua bao gio di qua duong container - dung cai duong ma
 * san pham dung.
 *
 * SubmitCtdtJob da tranh dung bay nay tu truoc va ghi lai ly do; SubmitXml3176Job:70-73 thi
 * dinh mot lan roi. Tep nay bien bai hoc do thanh mot bat bien kiem duoc.
 */
class SubmitTt12JobContainerTest extends TestCase
{
    /** @test */
    public function handle_khong_nhan_tham_so_co_type_hint()
    {
        // Day la bat bien chinh. Mot tham so co type-hint tren handle() la du de container
        // dung lai ban service hong - khong can biet ben trong service lam gi.
        $ham = new ReflectionMethod(SubmitTt12Job::class, 'handle');

        $this->assertSame(0, $ham->getNumberOfParameters(),
            'SubmitTt12Job::handle() phai tu dung service, khong nhan qua tham so co '
            . 'type-hint: container Laravel 5.5 tiem ke ca khi tham so khai "= null"');
    }

    /** @test */
    public function job_dung_chung_khuon_voi_SubmitCtdtJob()
    {
        // SubmitCtdtJob la ban da chay THAT tren cong BHXH (maKetQua 200). Giu hai job cung
        // khuon de bai hoc khong bi quen lai lan nua.
        $this->assertSame(
            0,
            (new ReflectionMethod(SubmitCtdtJob::class, 'handle'))->getNumberOfParameters(),
            'SubmitCtdtJob doi khuon roi - xem lai ca hai job'
        );

        $this->assertTrue(property_exists(SubmitTt12Job::class, 'submitServiceGia'),
            'phai co diem tiem rieng cho test thay cho tham so handle()');
    }

    /**
     * @test
     */
    public function container_VAN_tiem_du_tham_so_khai_null()
    {
        // Test CHIM HOANG YEN: no khang dinh cai LY DO cua hai test tren van con dung. Neu
        // mot ban Laravel sau nay doi thu tu getClass()/isDefaultValueAvailable() thi test
        // nay do, va luc do moi biet la co the bo khuon phong thu di.
        foreach (array(Tt12SubmitService::class, CtdtSubmitService::class) as $lop) {
            $dv = app($lop);
            $tt = new ReflectionProperty($dv, 'loginService');
            $tt->setAccessible(true);

            $this->assertNotNull($tt->getValue($dv),
                $lop . ': container khong con tiem nua - doc lai chu thich dau tep nay');
        }
    }

    /** @test */
    public function service_tu_dung_thi_KHONG_co_loginService()
    {
        // Ban `new` truc tiep - ban ma job phai dung - de loginService rong, nen gui() se
        // goi taoLogin($maCskcb) va token lay dung theo ma co so cua ho so.
        $tt = new ReflectionProperty(new Tt12SubmitService(), 'loginService');
        $tt->setAccessible(true);

        $this->assertNull($tt->getValue(new Tt12SubmitService()),
            'new Tt12SubmitService() phai de loginService rong de nhanh taoLogin() chay');
    }
}

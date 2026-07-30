<?php

namespace Tests\Unit;

use App\Services\BHYT\CauHinhCoSo;
use App\Services\BHYTLoginService;
use Tests\TestCase;
use Tests\Support\LocComment;

/**
 * Canh duong gui XML len cong BHXH khong quay lai ba loi cu:
 *  - ma tinh chot cung '01' cho moi co so, trong khi 37470 o Ninh Binh phai la '37';
 *  - nhan BHYTXmlSubmitService qua container, khien no dung BHYTLoginService KHONG ma co so
 *    va lan gui dau tien nem ngoai le;
 *  - tai khoan trong body lay tu khoi BHYT cu chu khong phai cua co so gui.
 */
class GuiXmlTheoCoSoTest extends TestCase
{
    use LocComment;

    protected function maJob($ten)
    {
        // Bo comment truoc khi quet: mot chuoi nam trong comment se lam test xanh gia.
        return $this->maKhongComment(base_path('app/Jobs/' . $ten . '.php'));
    }

    public function cacJob()
    {
        return ['SubmitXml3176Job', 'SubmitQd130XmlJob'];
    }

    /** @test */
    public function hai_job_khong_con_lay_ma_tinh_tu_config()
    {
        foreach ($this->cacJob() as $job) {
            $this->assertNotContains('organization.BHYT.ma_tinh', $this->maJob($job),
                $job . ' con lay ma tinh chot cung tu config');
        }
    }

    /** @test */
    public function hai_job_suy_ma_tinh_tu_ma_co_so()
    {
        foreach ($this->cacJob() as $job) {
            $this->assertContains('CauHinhCoSo::maTinh($this->macskcb)', $this->maJob($job),
                $job . ' phai suy ma tinh tu ma co so cua chinh ho so');
        }
    }

    /** @test */
    public function hai_job_dung_login_service_bang_ma_co_so()
    {
        foreach ($this->cacJob() as $job) {
            $this->assertContains('new BHYTLoginService($this->macskcb)', $this->maJob($job),
                $job . ' phai dung BHYTLoginService bang ma co so cua ho so');
        }
    }

    /**
     * Container khong biet ho so thuoc co so nao, nen de no dung ho la sai ve nguyen tac chu
     * khong chi sai ve ket qua.
     */
    /** @test */
    public function hai_job_khong_nhan_submit_service_qua_container()
    {
        foreach ($this->cacJob() as $job) {
            $ma = $this->maJob($job);
            $viTri = strpos($ma, 'function handle');
            $this->assertNotFalse($viTri, $job . ' khong tim thay handle()');

            $chuKy = substr($ma, $viTri, strpos($ma, ')', $viTri) - $viTri);

            $this->assertNotContains('BHYTXmlSubmitService', $chuKy,
                $job . ' con nhan BHYTXmlSubmitService qua container');
        }
    }

    /** @test */
    public function submit_service_lay_tai_khoan_tu_login_service()
    {
        $ma = $this->maKhongComment(base_path('app/Services/BHYTXmlSubmitService.php'));

        $this->assertContains('$this->loginService->username()', $ma);
        $this->assertContains('$this->loginService->password()', $ma);
        $this->assertNotContains("\$this->config['username']", $ma,
            'Tai khoan trong body phai cung nguon voi token, khong lay tu khoi BHYT cu');
    }

    /** @test */
    public function hai_service_khong_con_ham_gui_da_chet()
    {
        foreach (['Xml3176Service', 'Qd130XmlService'] as $s) {
            $ma = $this->maKhongComment(base_path('app/Services/' . $s . '.php'));

            $this->assertNotContains('submitXmlToBHYT', $ma, $s . ' con ham gui da chet');
            $this->assertNotContains('new BHYTXmlSubmitService', $ma,
                $s . ' con dung service gui trong constructor - day la nut that cu');
        }
    }

    /**
     * Nghiem thu bang so: co so Ninh Binh khong con bi gan ma tinh '01'.
     */
    /** @test */
    public function ma_tinh_dung_cho_tung_co_so()
    {
        $this->assertSame('37', CauHinhCoSo::maTinh('37470'));
        $this->assertSame('01', CauHinhCoSo::maTinh('01929'));
        $this->assertNotSame(
            CauHinhCoSo::maTinh('37470'),
            CauHinhCoSo::maTinh('01929'),
            'Hai co so khac tinh phai ra hai ma tinh khac nhau'
        );
    }

    /**
     * Tai khoan gui phai la cua co so, khong phai tai khoan chot cung trong khoi BHYT cu.
     * Khong cham mang: chi doc cau hinh.
     */
    /** @test */
    public function tai_khoan_gui_la_cua_co_so_khong_phai_khoi_cu()
    {
        config([
            'organization.BHYT.username' => 'cu_01013_BV',
            'organization.BHYT_CO_SO' => [
                '37470' => ['username' => 'rieng_37470', 'password' => 'p'],
            ],
        ]);

        $this->assertSame('rieng_37470', (new BHYTLoginService('37470'))->username());
    }
}

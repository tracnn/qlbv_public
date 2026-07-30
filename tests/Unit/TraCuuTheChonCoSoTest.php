<?php

namespace Tests\Unit;

use App\Http\Requests\InsuranceRequest;
use Tests\TestCase;
use Tests\Support\LocComment;

/**
 * Canh man tra cuu the thu cong khong quay lai hai loi cu:
 *  - muon correct_facility_code (von mang nghia "noi DKBD dung tuyen") lam ma co so cua
 *    chinh benh vien;
 *  - goi checkInsuranceCard() ma khong truyen tai khoan cua co so, khien viec chon co so
 *    tro thanh vo nghia trong khi man hinh trong nhu van chay.
 */
class TraCuuTheChonCoSoTest extends TestCase
{
    use LocComment;

    protected function maController()
    {
        // Bo comment truoc khi quet: mot chuoi nam trong comment se lam test xanh gia.
        return $this->maKhongComment(
            base_path('app/Http/Controllers/Insurance/Manager/InsuranceController.php')
        );
    }

    /** @test */
    public function controller_khong_con_muon_correct_facility_code()
    {
        $this->assertNotContains('correct_facility_code', $this->maController(),
            'correct_facility_code mang nghia noi DKBD dung tuyen, khong phai ma co so cua benh vien');
    }

    /** @test */
    public function controller_khong_dung_login_service_trong_constructor()
    {
        $ma = $this->maController();
        $viTriConstructor = strpos($ma, '__construct');
        $viTriSearch = strpos($ma, 'function search');

        $this->assertNotFalse($viTriConstructor);
        $this->assertNotFalse($viTriSearch);

        $thanConstructor = substr($ma, $viTriConstructor, $viTriSearch - $viTriConstructor);

        $this->assertNotContains('new BHYTLoginService', $thanConstructor,
            'Ma co so den tu request nen phai dung service trong search(), khong phai constructor');
    }

    /** @test */
    public function controller_truyen_login_service_xuong_checkInsuranceCard()
    {
        $ma = $this->maController();

        $this->assertContains('new BHYTLoginService($params[\'ma_cskcb\'])', $ma,
            'Phai dung service bang ma co so nguoi dung chon');
        $this->assertContains('$accessToken, $idToken, $loginService', $ma,
            'Phai truyen $loginService xuong checkInsuranceCard, neu khong viec chon co so vo nghia');
    }

    /** @test */
    public function checkInsuranceCard_nhan_tham_so_login_service()
    {
        $ma = $this->maKhongComment(base_path('app/BHYT.php'));

        $this->assertContains('$loginService = null', $ma,
            'Tham so phai TUY CHON de 5 noi goi con lai khong phai doi');
        $this->assertContains('$loginService->username()', $ma);
        $this->assertContains('$loginService->password()', $ma);
    }

    /**
     * Da xay ra that: check_by_user tat, nhanh else doc khoi BHYT cu (dang de rong) chu khong
     * doc theo co so, nen cong tra ve "Null hoTenCb" du nguoi dung da chon dung co so.
     */
    /** @test */
    public function nhanh_khong_theo_nguoi_dung_phai_doc_theo_co_so()
    {
        $ma = $this->maKhongComment(base_path('app/BHYT.php'));

        $this->assertContains('$loginService->hoTenCb()', $ma,
            'Nhanh else phai lay ho ten can bo cua CO SO duoc chon');
        $this->assertContains('$loginService->cccdCb()', $ma);
    }

    /** @test */
    public function controller_chan_som_khi_thieu_can_bo_tra_cuu()
    {
        $ma = $this->maController();

        $this->assertContains('check_by_user', $ma,
            'Phai chan som khi thieu ho ten/CCCD can bo, thay vi de cong bao "Null hoTenCb"');
        $this->assertContains('hoTenCb()', $ma);
    }

    /**
     * ma_cskcb KHONG duoc la 'required'. Da xay ra that: dat 'required' lam vo ca 8 duong
     * tro sau vao man nay (bao cao trai tuyen, danh sach XML3176, XML QD130, kiem tra du
     * lieu gui, tra cuu he thong...) - chung chi truyen so the / ho ten / ngay sinh.
     * Nguoi dung khong lam gi sai ma nhan thong bao do.
     */
    /** @test */
    public function request_khong_bat_buoc_ma_co_so()
    {
        config(['organization.BHYT_CO_SO' => [
            '01929' => ['username' => 'u', 'password' => 'p'],
            '37470' => ['username' => 'u', 'password' => 'p'],
        ]]);

        $luat = (new InsuranceRequest())->rules();
        $phan = explode('|', $luat['ma_cskcb']);

        $this->assertArrayHasKey('ma_cskcb', $luat);
        $this->assertNotContains('required', $phan,
            'De required se lam vo cac duong tro sau vao man tra cuu');
        $this->assertContains('nullable', $phan);
    }

    /**
     * Thieu ma co so thi dung lai o man da dien san, KHONG goi len cong - chua biet dung tai
     * khoan cua co so nao.
     */
    /** @test */
    public function controller_dung_lai_khi_thieu_ma_co_so()
    {
        $ma = $this->maController();

        $this->assertContains("\$params['ma_cskcb'] === ''", $ma,
            'Phai co nhanh xu ly khi thieu ma co so');
    }

    /**
     * Hai man co san ma_cskcb tren ban ghi thi phai truyen di, de bam mot phat ra ket qua
     * thay vi bat nguoi dung chon lai co so da biet.
     */
    /** @test */
    public function hai_man_xml_truyen_ma_co_so_vao_lien_ket()
    {
        foreach ([
            'app/Http/Controllers/BHYT/BHYTXml3176Controller.php',
            'app/Http/Controllers/BHYT/BHYTQd130Controller.php',
        ] as $tep) {
            $ma = $this->maKhongComment(base_path($tep));

            $this->assertContains("'ma_cskcb' => \$result->ma_cskcb", $ma,
                $tep . ': lien ket tra cuu the phai truyen ma co so cua ho so');
        }
    }

    /**
     * Khong tin trinh duyet: o chon va localStorage deu sua duoc tu phia nguoi dung.
     */
    /** @test */
    public function request_chan_ma_ngoai_danh_sach()
    {
        config(['organization.BHYT_CO_SO' => [
            '01929' => ['username' => 'u', 'password' => 'p'],
        ]]);

        $luat = (new InsuranceRequest())->rules();
        $luatIn = '';

        foreach (explode('|', $luat['ma_cskcb']) as $phan) {
            if (strpos($phan, 'in:') === 0) {
                $luatIn = substr($phan, 3);
            }
        }

        $maHopLe = explode(',', $luatIn);

        $this->assertContains('01929', $maHopLe);
        $this->assertNotContains('01013', $maHopLe,
            'Co so chua khai tai khoan khong duoc coi la hop le');
    }

    /** @test */
    public function view_co_khoa_localstorage()
    {
        $ma = file_get_contents(
            base_path('resources/views/insurance/manager/check-card/search.blade.php')
        );

        $this->assertContains('bhyt_tra_cuu_ma_cskcb', $ma);
        $this->assertContains('name="ma_cskcb"', $ma);
    }
}

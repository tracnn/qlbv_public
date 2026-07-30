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

    /** @test */
    public function request_bat_buoc_chon_co_so()
    {
        config(['organization.BHYT_CO_SO' => [
            '01929' => ['username' => 'u', 'password' => 'p'],
            '37470' => ['username' => 'u', 'password' => 'p'],
        ]]);

        $luat = (new InsuranceRequest())->rules();

        $this->assertArrayHasKey('ma_cskcb', $luat);
        $this->assertContains('required', explode('|', $luat['ma_cskcb']));
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

<?php

namespace Tests\Unit;

use App\Services\BHYTLoginService;
use Tests\TestCase;

/**
 * Khoa cache PHAI kem ma co so.
 *
 * Thiet ke cu dung mot khoa duy nhat 'bhyt_tokens' cho moi co so: token cua co so nay ghi
 * de co so kia, va moi loi goi sau do sai danh nghia ma KHONG co dau hieu gi. Day la kieu
 * hong im lang nguy hiem nhat cua ban cu.
 *
 * Phan giai tai khoan/khoa cache la LUOI (chi luc dung, khong phai luc dung doi tuong):
 * BHYTXmlSubmitService dung service nay ngay trong constructor cua no, va Xml3176Service
 * lai dung BHYTXmlSubmitService vo dieu kien. Neu phan giai som trong constructor, chi
 * VIEC DUNG doi tuong (ke ca o duong doc XML von khong bao gio dang nhap cong) da nem
 * ngoai le - day chinh la hoi quy da xay ra mot lan va bi bat lai bang hai test cuoi tep
 * nay.
 */
class BHYTLoginServiceCacheTest extends TestCase
{
    protected function setUp()
    {
        parent::setUp();

        config(['organization.BHYT_CO_SO' => [
            '01929' => ['username' => 'u1929', 'password' => 'p1929'],
            '37470' => ['username' => 'u37470', 'password' => 'p37470'],
        ]]);
    }

    /** Goi ham rieng tu (private) khoaCache() qua Reflection - khong cham mang */
    protected function khoa(BHYTLoginService $s)
    {
        $r = new \ReflectionMethod($s, 'khoaCache');
        $r->setAccessible(true);

        return $r->invoke($s);
    }

    /** @test */
    public function hai_co_so_co_khoa_cache_khac_nhau()
    {
        $a = $this->khoa(new BHYTLoginService('01929'));
        $b = $this->khoa(new BHYTLoginService('37470'));

        $this->assertNotSame($a, $b, 'Hai co so dung chung khoa cache - token se ghi de nhau');
    }

    /** @test */
    public function khoa_cache_co_chua_ma_co_so()
    {
        $this->assertContains('01929', $this->khoa(new BHYTLoginService('01929')));
    }

    /**
     * @test
     *
     * Hoi quy da xay ra: phan giai tai khoan trong constructor lam BHYTXmlSubmitService
     * (dung service nay ngay trong constructor cua no) nem ngoai le chi vi DUNG doi
     * tuong, du chua he goi login(). Phan giai LUOI nen dung khong tham so phai dung
     * duoc binh thuong.
     */
    public function khong_tham_so_thi_dung_duoc_binh_thuong()
    {
        $s = new BHYTLoginService();

        $this->assertInstanceOf(BHYTLoginService::class, $s);
    }

    /**
     * @test
     *
     * Dung khong nem, nhung goi toi mot duong can tai khoan (hoTenCb() - khong cham
     * mang) thi PHAI nem, vi ma co so rong khong duoc coi la mot co so hop le. Nem
     * chu khong doan: doan nghia la quay lai dung loi roi ve tai khoan mac dinh.
     */
    public function khong_truyen_ma_co_so_ma_dung_tai_khoan_thi_nem_ngoai_le()
    {
        $s = new BHYTLoginService();

        $this->expectException(\InvalidArgumentException::class);

        $s->hoTenCb();
    }

    /**
     * @test
     *
     * Dung voi ma co so chua khai cung khong nem (phan giai luoi), nhung goi toi mot
     * duong can tai khoan (hoTenCb() - khong cham mang) thi PHAI nem.
     */
    public function co_so_chua_khai_ma_dung_tai_khoan_thi_nem_ngoai_le()
    {
        $s = new BHYTLoginService('99999');

        $this->expectException(\InvalidArgumentException::class);

        $s->hoTenCb();
    }

    /** @test */
    public function co_so_chua_khai_thi_khong_nem_ngay_luc_dung()
    {
        $s = new BHYTLoginService('99999');

        $this->assertInstanceOf(BHYTLoginService::class, $s);
    }
}

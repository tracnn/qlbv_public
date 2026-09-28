<?php

namespace Tests\Unit\Xml3176;

use App\Services\Xml3176\Xml3176Importer;
use Tests\TestCase;

/**
 * MOI ho so deu phai duoc ra loi truoc khi len cong - khong con duong re nao bo qua.
 *
 * Tu 10/09/2026 den 28/09/2026 co mot cong chan o diem phat job: ho so co
 * MA_DOITUONG_KCB thuoc danh sach xml3176.ma_doituong_kcb_khong_kiem (ho so dich vu,
 * ma 9) khong duoc day job ra loi. Nguoi dung da yeu cau go: ho so dich vu cung phai
 * duoc kiem truoc khi gui cong BHXH.
 *
 * Test nay chot CHIEU NGUOC LAI cua cong do. Dung ban chat: mot cong bo qua ra loi
 * hong theo kieu IM LANG - ho so van nhap, van xuat, van gui cong, chi la khong ai
 * kiem - nen phai co canh gac chan viec lang le dung lai no.
 */
class Xml3176KhongConCongBoQuaTest extends TestCase
{
    /** @test */
    public function importer_khong_con_ham_quyet_dinh_co_ra_loi_hay_khong()
    {
        $this->assertFalse(method_exists(Xml3176Importer::class, 'canKiemLoi'),
            'Da go cong bo qua ra loi: khong duoc dung lai canKiemLoi()');
    }

    /** @test */
    public function khong_con_khoa_cau_hinh_loai_tru_ma_doi_tuong()
    {
        $this->assertNull(config('xml3176.ma_doituong_kcb_khong_kiem'),
            'Khoa cau hinh loai tru ma doi tuong KCB da bi go, khong duoc khai lai');
    }

    /** @test */
    public function khong_con_lop_so_ma_doi_tuong_voi_danh_sach_loai_tru()
    {
        // Kiem TEP chu khong class_exists(): classmap cua composer con giu ten lop da xoa
        // cho toi khi ai do chay dump-autoload, va class_exists() se di include tep khong
        // con ton tai roi nem ErrorException - do la loi cua moi truong, khong phai cua ma.
        $this->assertFileNotExists(app_path('Services/Xml3176/Support/DoiTuongKcbMatcher.php'),
            'DoiTuongKcbMatcher chi phuc vu cong bo qua, da xoa cung cong');
    }

    /** @test */
    public function ma_nguon_importer_khong_con_dieu_kien_bo_qua_ra_loi()
    {
        $src = file_get_contents(app_path('Services/Xml3176/Xml3176Importer.php'));

        $this->assertNotContains('canKiemLoi', $src);
        $this->assertNotContains('DoiTuongKcbMatcher', $src);
        $this->assertNotContains('ma_doituong_kcb_khong_kiem', $src);

        // Phat job va kiem tong the van con - go cong khong duoc lam mat luon viec ra loi.
        $this->assertContains('CheckXml3176TypeJob::dispatch', $src);
        $this->assertContains('checkXml3176Complete', $src);
    }
}

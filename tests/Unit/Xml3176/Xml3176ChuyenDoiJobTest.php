<?php

namespace Tests\Unit\Xml3176;

use Tests\TestCase;
use Tests\Support\LocComment;

class Xml3176ChuyenDoiJobTest extends TestCase
{
    use LocComment;

    /** @test */
    public function khong_con_dispatch_theo_tung_dong()
    {
        $ma = $this->maKhongComment(app_path('Services/Xml3176Service.php'));

        $this->assertNotContains('CheckXml3176ErrorsJob', $ma,
            'Van con dispatch mot job moi dong');
    }

    /** @test */
    public function khong_con_cho_nao_dispatch_job_cu()
    {
        foreach ([
            app_path('Services/Xml3176Service.php'),
            app_path('Services/Xml3176/Xml3176Importer.php'),
        ] as $file) {
            $this->assertNotContains('CheckXml3176ErrorsJob', $this->maKhongComment($file),
                basename($file) . ' con dispatch job cu');
        }
    }

    /** @test */
    public function lop_job_cu_van_con_de_hang_doi_rut_can()
    {
        // Lop nay tung bi xoa han va viec do gay loi that tren san xuat: hang doi con job
        // cu dang cho, mat lop thi chung khong unserialize duoc, roi vao failed_jobs, keo
        // theo mat ket qua kiem loi cua nhung ho so vua nhap.
        //
        // Khong con ai dispatch no (test tren da khoa dieu do), nhung PHAI con file de
        // nhung job da nam trong hang doi chay not.
        $this->assertFileExists(app_path('Jobs/CheckXml3176ErrorsJob.php'),
            'Xoa lop nay khi hang doi chua rut can se lam chet cac job dang cho');
    }

    /** @test */
    public function xoa_du_lieu_ho_so_thi_xoa_ca_loi()
    {
        // deleteExistingXml3176 truoc day KHONG xoa loi, trong khi deleteXml3176XmlAndError
        // thi co - mot diem bat nhat co san. Nay dong lai de nhap lai khong con sot loi
        // cua loai XML khong con xuat hien.
        $ma = $this->maKhongComment(app_path('Services/Xml3176Service.php'));

        $dau = strpos($ma, 'function deleteExistingXml3176');
        $this->assertNotFalse($dau);

        $than = substr($ma, $dau, 1200);
        $this->assertContains('Xml3176ErrorResult', $than);
    }

    /** @test */
    public function checker_xml1_khong_con_tu_xoa_loi()
    {
        // deleteErrors() xoa TOAN BO loi cua ho so. Nam trong job XML1 thi mot lan retry
        // se xoa sach ket qua ma 11 job kia vua tim ra.
        $ma = $this->maKhongComment(app_path('Services/Xml3176Xml1Checker.php'));

        $this->assertNotContains('deleteErrors', $ma);
    }

    /** @test */
    public function importer_dispatch_job_theo_loai_sau_commit()
    {
        $ma = $this->maKhongComment(app_path('Services/Xml3176/Xml3176Importer.php'));

        $viTriTransaction = strpos($ma, 'DB::transaction');
        $viTriDispatch    = strpos($ma, 'CheckXml3176TypeJob::dispatch');

        $this->assertNotFalse($viTriDispatch, 'Importer chua dispatch job theo loai');
        $this->assertGreaterThan($viTriTransaction, $viTriDispatch,
            'Phai dispatch SAU khoi transaction');

        // Kiem loai truoc, kiem tong the sau - giu dung thu tu FIFO hien nay.
        $this->assertLessThan(strpos($ma, 'checkXml3176Complete'), $viTriDispatch);
    }
}

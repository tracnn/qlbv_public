<?php

namespace Tests\Unit;

use Tests\TestCase;
use Tests\Support\LocComment;

/**
 * Canh nguyen tac: co duoc hoi TRUOC khi sinh viec.
 *
 * Truoc day noi dispatch khong kiem co - moi ho so export deu day mot job vao hang doi roi
 * job tu thoat. Tat ma van ton viec. Test nay chot lai thu tu do bang vi tri chuoi trong ma
 * nguon (da bo comment, vi mot chuoi nam trong comment se lam test xanh gia).
 */
class CoLapCongTacLuongTest extends TestCase
{
    use LocComment;

    /**
     * Khang dinh $truoc xuat hien truoc $sau trong ma nguon.
     */
    protected function xuatHienTruoc($tep, $truoc, $sau, $thongDiep)
    {
        $ma = $this->maKhongComment(base_path($tep));

        $viTriTruoc = strpos($ma, $truoc);
        $viTriSau = strpos($ma, $sau);

        $this->assertNotFalse($viTriTruoc, $tep . ': khong tim thay "' . $truoc . '"');
        $this->assertNotFalse($viTriSau, $tep . ': khong tim thay "' . $sau . '"');
        $this->assertLessThan($viTriSau, $viTriTruoc, $thongDiep);
    }

    /** @test */
    public function xml3176_hoi_co_truoc_khi_dispatch_job_gui()
    {
        $this->xuatHienTruoc(
            'app/Services/Xml3176Service.php',
            'submit_xml_3176_enabled',
            'SubmitXml3176Job::dispatch',
            'Phai hoi co truoc khi dispatch, neu khong tat van sinh job vao hang doi'
        );
    }

    /** @test */
    public function qd130_hoi_co_truoc_khi_dispatch_job_gui()
    {
        $this->xuatHienTruoc(
            'app/Services/Qd130XmlService.php',
            'submit_xml_enabled',
            'SubmitQd130XmlJob::dispatch',
            'Phai hoi co truoc khi dispatch, neu khong tat van sinh job vao hang doi'
        );
    }

    /**
     * Job van phai kiem lai co: no co the nam cho trong hang doi rat lau, giua luc do cau
     * hinh co the da bi tat. Va viec kiem phai dung TRUOC moi viec khac.
     */
    /** @test */
    public function hai_job_kiem_co_truoc_khi_dung_service()
    {
        $this->xuatHienTruoc(
            'app/Jobs/SubmitXml3176Job.php',
            'submit_xml_3176_enabled',
            'new BHYTXmlSubmitService',
            'Job phai kiem co truoc khi dung service - lam viec truoc khi hoi la sai thu tu'
        );

        $this->xuatHienTruoc(
            'app/Jobs/SubmitQd130XmlJob.php',
            'submit_xml_enabled',
            'new BHYTXmlSubmitService',
            'Job phai kiem co truoc khi dung service - lam viec truoc khi hoi la sai thu tu'
        );
    }

    /** @test */
    public function file_copy_service_co_du_hai_duong_copy()
    {
        $ma = $this->maKhongComment(base_path('app/Services/FileCopyService.php'));

        $this->assertContains('copyExportXml3176ToTrucDuLieuYTe', $ma);
        $this->assertContains('copyExportXml3176ToCongDuLieuYTeDienBien', $ma,
            'Thieu duong copy sang Dien Bien thi lenh quet canh mot thu muc khong ai ghi vao');
        $this->assertContains('organization.truc_du_lieu_y_te', $ma);
        $this->assertContains('organization.cong_du_lieu_y_te_dien_bien', $ma);
    }

    /** @test */
    public function luong_export_goi_ca_hai_duong_copy()
    {
        $ma = $this->maKhongComment(base_path('app/Services/Xml3176Service.php'));

        $this->assertContains('copyExportXml3176ToTrucDuLieuYTe', $ma);
        $this->assertContains('copyExportXml3176ToCongDuLieuYTeDienBien', $ma);
    }
}

<?php

namespace Tests\Unit;

use Tests\TestCase;
use Tests\Support\LocComment;

/**
 * Canh quy tac: ho so chua ky so thi khong gui len cong BHXH, va phai GHI NHAN trang thai do
 * chu khong bo qua im lang.
 */
class ChuaKyKhongGuiTest extends TestCase
{
    use LocComment;

    /**
     * Luong XML3176 KHONG con o day: tu 29/09/2026 no la mot chuoi withChain, SignXml3176Job
     * quyet dinh chuoi co di toi buoc gui qua QuyetDinhGui, khong con dispatch job gui. Hanh
     * vi do duoc kiem bang test chay that o Tests\Unit\Xml3176\Chuoi\SignXml3176JobTest.
     */
    public function cacLuong()
    {
        return [
            ['app/Services/Qd130XmlService.php', 'SubmitQd130XmlJob::dispatch'],
        ];
    }

    /** @test */
    public function luong_qd130_hoi_quyet_dinh_gui()
    {
        foreach ($this->cacLuong() as list($tep, $_)) {
            $ma = $this->maKhongComment(base_path($tep));
            $this->assertContains('QuyetDinhGui::nen', $ma, $tep . ': phai hoi QuyetDinhGui truoc khi gui');
        }
    }

    /** @test */
    public function luong_qd130_hoi_quyet_dinh_truoc_khi_dispatch()
    {
        foreach ($this->cacLuong() as list($tep, $dispatch)) {
            $ma = $this->maKhongComment(base_path($tep));
            $this->assertLessThan(strpos($ma, $dispatch), strpos($ma, 'QuyetDinhGui::nen'),
                $tep . ': phai quyet dinh truoc khi dispatch, khong phai sau');
        }
    }

    /** @test */
    public function luong_qd130_co_nhanh_ghi_nhan_chua_ky()
    {
        foreach ($this->cacLuong() as list($tep, $_)) {
            $ma = $this->maKhongComment(base_path($tep));
            $this->assertContains('QuyetDinhGui::CHUA_KY', $ma, $tep . ': thieu nhanh xu ly ho so chua ky');
            $this->assertContains('chưa ký số', $ma, $tep . ': phai ghi thong diep cho nguoi dung');
        }
    }

    /** @test */
    public function job_ky_xml3176_hoi_quyet_dinh_gui_va_ghi_nhan_chua_ky()
    {
        $ma = $this->maKhongComment(base_path('app/Jobs/SignXml3176Job.php'));

        $this->assertContains('QuyetDinhGui::nen', $ma);
        $this->assertContains('QuyetDinhGui::CHUA_KY', $ma);
        $this->assertContains('chưa ký số', $ma);
    }

    /**
     * Trang thai ky la thuoc tinh cua tep da ghi ra dia, khong tu doi trong luc job nam cho.
     * Kiem lai trong job la mot truy van CSDL thua cho moi job.
     */
    /** @test */
    public function job_khong_kiem_lai_trang_thai_ky()
    {
        foreach (['app/Jobs/SubmitXml3176Job.php', 'app/Jobs/SubmitQd130XmlJob.php'] as $tep) {
            $ma = $this->maKhongComment(base_path($tep));

            $this->assertNotContains('is_signed', $ma,
                $tep . ': khong can kiem lai trang thai ky trong job');
        }
    }
}

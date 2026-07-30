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

    public function cacLuong()
    {
        return [
            ['app/Services/Xml3176Service.php', 'SubmitXml3176Job::dispatch'],
            ['app/Services/Qd130XmlService.php', 'SubmitQd130XmlJob::dispatch'],
        ];
    }

    /** @test */
    public function hai_luong_export_deu_hoi_quyet_dinh_gui()
    {
        foreach ($this->cacLuong() as list($tep, $_)) {
            // Bo comment truoc khi quet: mot chuoi nam trong comment se lam test xanh gia.
            $ma = $this->maKhongComment(base_path($tep));

            $this->assertContains('QuyetDinhGui::nen', $ma,
                $tep . ': phai hoi QuyetDinhGui truoc khi gui');
        }
    }

    /** @test */
    public function quyet_dinh_duoc_hoi_truoc_khi_dispatch()
    {
        foreach ($this->cacLuong() as list($tep, $dispatch)) {
            $ma = $this->maKhongComment(base_path($tep));

            $viTriHoi = strpos($ma, 'QuyetDinhGui::nen');
            $viTriDispatch = strpos($ma, $dispatch);

            $this->assertNotFalse($viTriHoi, $tep . ': khong tim thay QuyetDinhGui::nen');
            $this->assertNotFalse($viTriDispatch, $tep . ': khong tim thay ' . $dispatch);
            $this->assertLessThan($viTriDispatch, $viTriHoi,
                $tep . ': phai quyet dinh truoc khi dispatch, khong phai sau');
        }
    }

    /**
     * Chua ky ma bo qua im lang thi nguoi dung khong biet vi sao ho so khong di. Phai ghi
     * submit_error - dung hinh dang cua mot ho so bi cong tu choi.
     */
    /** @test */
    public function hai_luong_deu_co_nhanh_ghi_nhan_chua_ky()
    {
        foreach ($this->cacLuong() as list($tep, $_)) {
            $ma = $this->maKhongComment(base_path($tep));

            $this->assertContains('QuyetDinhGui::CHUA_KY', $ma,
                $tep . ': thieu nhanh xu ly ho so chua ky');
            $this->assertContains('chưa ký số', $ma,
                $tep . ': phai ghi thong diep cho nguoi dung, khong bo qua im lang');
        }
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

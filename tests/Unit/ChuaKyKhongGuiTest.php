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
        foreach (['app/Jobs/SubmitXml3176Job.php'] as $tep) {
            $ma = $this->maKhongComment(base_path($tep));

            $this->assertNotContains('is_signed', $ma,
                $tep . ': khong can kiem lai trang thai ky trong job');
        }
    }
}

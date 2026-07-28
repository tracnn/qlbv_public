<?php

namespace Tests\Unit\Xml3176;

use Tests\TestCase;
use App\Jobs\CheckXml3176TypeJob;

class CheckXml3176TypeJobTest extends TestCase
{
    /** @test */
    public function job_ton_tai_va_nhan_hai_tham_so()
    {
        $job = new CheckXml3176TypeJob('MALK1', 'XML2');

        $this->assertInstanceOf(CheckXml3176TypeJob::class, $job);
    }

    /** @test */
    public function job_tu_xoa_loi_cua_rieng_loai_minh_truoc_khi_ghi()
    {
        // Day la thu lam moi job TU IDEMPOTENT: chay lai bao nhieu lan cung ra mot ket
        // qua, khong phu thuoc thu tu hang doi hay retry.
        $src = file_get_contents(app_path('Jobs/CheckXml3176TypeJob.php'));

        $this->assertContains("where('xml'", $src);
        $this->assertContains('delete()', $src);
    }

    /** @test */
    public function job_dung_che_do_gom_va_dong_lai_trong_finally()
    {
        // Hong giua chung thi phan da tim duoc van ghi, va khong ro bo dem sang job sau.
        $src = file_get_contents(app_path('Jobs/CheckXml3176TypeJob.php'));

        $this->assertContains('batDauGom', $src);
        $this->assertContains('ketThucGom', $src);
        $this->assertContains('finally', $src);
    }

    /** @test */
    public function error_service_la_singleton_de_job_va_checker_dung_chung_bo_dem()
    {
        // Bo dem la TRANG THAI CUA DOI TUONG. Neu job va checker nhan hai thuc the khac
        // nhau thi che do gom im lang khong co tac dung, va so truy van van nhu cu.
        $a = app(\App\Services\Xml3176ErrorService::class);
        $b = app(\App\Services\Xml3176ErrorService::class);

        $this->assertSame($a, $b);
    }

    /** @test */
    public function checker_nhan_dung_thuc_the_ma_job_bat_gom()
    {
        // Kiem THAT: giai lop checker qua container roi hoi chinh no xem service no giu
        // co phai cai vua bat gom khong.
        $loi = app(\App\Services\Xml3176ErrorService::class);
        $loi->batDauGom();

        $checker = app(\App\Services\Xml3176Xml2Checker::class);

        $thuocTinh = new \ReflectionProperty(\App\Services\Xml3176Xml2Checker::class, 'xmlErrorService');
        $thuocTinh->setAccessible(true);

        $this->assertSame($loi, $thuocTinh->getValue($checker),
            'Checker giu mot thuc the khac -> che do gom vo tac dung');

        $loi->ketThucGom();
    }
}

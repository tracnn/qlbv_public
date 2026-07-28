<?php

namespace Tests\Unit\Xml3176;

use Tests\TestCase;
use App\Services\Xml3176\Xml3176CheckTypes;

class Xml3176CheckTypesTest extends TestCase
{
    /** @test */
    public function bang_dang_ky_phu_dung_12_loai_ma_job_cu_xu_ly()
    {
        // Dung 12 loai co checker. XML6, XML12, XML15 KHONG co checker - dieu do la co
        // san, khong phai thieu sot cua dot nay.
        $mongDoi = ['XML1', 'XML2', 'XML3', 'XML4', 'XML5', 'XML7',
                    'XML8', 'XML9', 'XML10', 'XML11', 'XML13', 'XML14'];

        $this->assertEquals($mongDoi, array_keys(Xml3176CheckTypes::LOAI));
    }

    /** @test */
    public function moi_lop_model_va_checker_deu_ton_tai()
    {
        foreach (Xml3176CheckTypes::LOAI as $loai => $ch) {
            $this->assertTrue(class_exists($ch['model']), "Thieu model cho $loai: {$ch['model']}");
            $this->assertTrue(class_exists($ch['checker']), "Thieu checker cho $loai: {$ch['checker']}");
            $this->assertTrue(
                method_exists($ch['checker'], 'checkErrors'),
                "{$ch['checker']} khong co checkErrors"
            );
        }
    }

    /** @test */
    public function co_checker_tu_choi_loai_ngoai_danh_sach()
    {
        $this->assertTrue(Xml3176CheckTypes::coChecker('XML2'));
        $this->assertFalse(Xml3176CheckTypes::coChecker('XML6'));
        $this->assertFalse(Xml3176CheckTypes::coChecker('XMLComplete'));
        $this->assertFalse(Xml3176CheckTypes::coChecker(''));
    }

    /** @test */
    public function cau_hinh_nem_loi_khi_loai_ngoai_danh_sach()
    {
        $this->expectException(\InvalidArgumentException::class);

        Xml3176CheckTypes::cauHinh('XML99');
    }
}

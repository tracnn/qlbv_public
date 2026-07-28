<?php

namespace Tests\Unit\Xml3176;

use Tests\TestCase;
use Tests\Support\LocComment;

class Xml3176ImporterTransactionTest extends TestCase
{
    use LocComment;

    /** @test */
    public function nhap_mot_ho_so_phai_nam_trong_transaction()
    {
        // deleteExistingXml3176() xoa 13 bang roi moi ghi lai tung phan. Khong co
        // transaction thi dut giua chung la mat ca du lieu cu lan moi.
        // Bo comment truoc khi quet: test nay kiem SU TON TAI, nen chuoi nam trong mot
        // dong comment se lam no XANH NHAM trong khi ma that su khong co.
        $src = $this->maKhongComment(app_path('Services/Xml3176/Xml3176Importer.php'));

        $this->assertContains('DB::transaction', $src,
            'nhapTuChuoi khong con boc transaction - ho so co the mat du lieu cu lan moi');
    }

    /** @test */
    public function day_job_kiem_tra_va_xuat_nam_ngoai_transaction()
    {
        // checkXml3176Complete/exportXml3176 chi day job. Dat SAU commit de rollback
        // khong de lai job mo coi tro toi du lieu khong ton tai.
        // Bo comment truoc khi quet: test nay kiem SU TON TAI, nen chuoi nam trong mot
        // dong comment se lam no XANH NHAM trong khi ma that su khong co.
        $src = $this->maKhongComment(app_path('Services/Xml3176/Xml3176Importer.php'));

        $viTriTransaction = strpos($src, 'DB::transaction');
        $viTriCheck       = strpos($src, 'checkXml3176Complete');
        $viTriExport      = strpos($src, 'exportXml3176');

        $this->assertNotFalse($viTriTransaction);
        $this->assertNotFalse($viTriCheck);
        $this->assertNotFalse($viTriExport);

        $this->assertGreaterThan($viTriTransaction, $viTriCheck,
            'checkXml3176Complete phai nam sau khoi transaction');
        $this->assertGreaterThan($viTriTransaction, $viTriExport,
            'exportXml3176 phai nam sau khoi transaction');
    }
}

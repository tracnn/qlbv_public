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
    public function ma_phien_ghi_trong_transaction_chuoi_day_sau_commit()
    {
        // Ma phien PHAI ghi trong transaction nap: ghi sau commit thi co mot khoanh khac du
        // lieu moi da hien ra (loi cu da xoa) ma ma cu van con hieu luc - job xuat cua chuoi
        // cu chay dung luc do se xuat du lieu chua kiem.
        //
        // Chuoi PHAI day sau commit: rollback khong de lai job mo coi tro toi du lieu khong ton tai.
        $src = $this->maKhongComment(app_path('Services/Xml3176/Xml3176Importer.php'));

        $viTriTransaction = strpos($src, 'DB::transaction');
        $viTriGhiMa = strpos($src, "'chain_token' => \$maPhien");
        $viTriCatch = strpos($src, 'catch (\\Exception');
        $viTriXep = strpos($src, 'Xml3176ChuoiXuLy::xepSauNap');

        $this->assertNotFalse($viTriGhiMa, 'Bo nap khong ghi ma phien');
        $this->assertGreaterThan($viTriTransaction, $viTriGhiMa);
        $this->assertLessThan($viTriCatch, $viTriGhiMa, 'Ma phien phai ghi BEN TRONG transaction');
        $this->assertGreaterThan($viTriCatch, $viTriXep, 'Chuoi phai day SAU transaction');
    }
}

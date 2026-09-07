<?php

namespace Tests\Unit\Mcct;

use App\Services\Mcct\QuyetDinhGoiCong;
use Tests\TestCase;

class QuyetDinhGoiCongTest extends TestCase
{
    const BAY_GIO = '2026-09-07 12:00:00';

    /** @test */
    public function khong_yeu_cau_lam_moi_thi_khong_goi_cong()
    {
        $ra = QuyetDinhGoiCong::nen(false, null, self::BAY_GIO, 900);

        $this->assertFalse($ra['goi']);
        $this->assertSame(0, $ra['con_lai']);
    }

    /** Chua tung tra the nay: phai goi, du dang bat lam_moi hay khong co ban ghi cu */
    /** @test */
    public function lam_moi_va_chua_tung_tra_thi_goi_cong()
    {
        $ra = QuyetDinhGoiCong::nen(true, null, self::BAY_GIO, 900);

        $this->assertTrue($ra['goi']);
        $this->assertSame(0, $ra['con_lai']);
    }

    /**
     * Vua tra 5 phut truoc, khau do 15 phut: KHONG goi lai. Con lai 600 giay.
     *
     * Day la nhanh quan trong nhat - no la thu chan mot vong lap hong o he thong goi lam
     * chay het han muc cua cong.
     */
    /** @test */
    public function vua_tra_trong_khau_do_thi_khong_goi_lai()
    {
        $ra = QuyetDinhGoiCong::nen(true, '2026-09-07 11:55:00', self::BAY_GIO, 900);

        $this->assertFalse($ra['goi']);
        $this->assertSame(600, $ra['con_lai']);
    }

    /** @test */
    public function tra_qua_khau_do_thi_goi_lai()
    {
        $ra = QuyetDinhGoiCong::nen(true, '2026-09-07 11:44:00', self::BAY_GIO, 900);

        $this->assertTrue($ra['goi']);
        $this->assertSame(0, $ra['con_lai']);
    }

    /** Dung BIEN khau do: 900 giay truoc thi da het khau do, duoc goi lai */
    /** @test */
    public function dung_bien_khau_do_thi_duoc_goi_lai()
    {
        $ra = QuyetDinhGoiCong::nen(true, '2026-09-07 11:45:00', self::BAY_GIO, 900);

        $this->assertTrue($ra['goi']);
    }

    /** Truoc bien mot giay thi van bi chan, con lai dung 1 giay */
    /** @test */
    public function truoc_bien_mot_giay_thi_van_bi_chan()
    {
        $ra = QuyetDinhGoiCong::nen(true, '2026-09-07 11:45:01', self::BAY_GIO, 900);

        $this->assertFalse($ra['goi']);
        $this->assertSame(1, $ra['con_lai']);
    }

    /** Khau do 0 = TAT chan: luon goi cong */
    /** @test */
    public function khau_do_bang_khong_thi_luon_goi()
    {
        $ra = QuyetDinhGoiCong::nen(true, self::BAY_GIO, self::BAY_GIO, 0);

        $this->assertTrue($ra['goi']);
    }

    /**
     * Moc tra cuu nam o TUONG LAI (dong ho lech, hoac du lieu hong): coi nhu vua tra xong -
     * chan lai. Cho goi la mo duong cho mot dong ho lech lam thung ca co che chan.
     */
    /** @test */
    public function moc_tra_o_tuong_lai_thi_van_chan()
    {
        $ra = QuyetDinhGoiCong::nen(true, '2026-09-07 13:00:00', self::BAY_GIO, 900);

        $this->assertFalse($ra['goi']);
        $this->assertSame(900, $ra['con_lai'], 'Chan tron khau do, khong tra so am');
    }

    /** Chuoi thoi gian rac thi coi nhu chua tra bao gio - khong duoc lam vo phep tinh */
    /** @test */
    public function moc_tra_khong_doc_duoc_thi_coi_nhu_chua_tra()
    {
        $ra = QuyetDinhGoiCong::nen(true, 'khong-phai-ngay-thang', self::BAY_GIO, 900);

        $this->assertTrue($ra['goi']);
    }
}

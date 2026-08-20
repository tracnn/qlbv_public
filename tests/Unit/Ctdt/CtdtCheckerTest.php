<?php

namespace Tests\Unit\Ctdt;

use Tests\TestCase;
use App\Services\Ctdt\Kiem\CtdtChecker;

/**
 * Bo kiem la HAM THUAN: nhan mang du lieu, tra mang loi. Khong doc CSDL, khong biet
 * Eloquent - nen kiem duoc het cac nhanh ma khong can dung mot bang nao.
 */
class CtdtCheckerTest extends TestCase
{
    /** Bo du lieu CT03 hop le, de tung test chi thay doi dung thu no dang kiem. */
    private function ct03HopLe(array $ghiDe = [])
    {
        return array_merge([
            'MA_YTE'    => 'YT001',
            'HO_TEN'    => 'Nguyen Van Test',
            'NGAY_SINH' => '19950914',
            'NGAY_VAO'  => '201912121200',
            'NGAY_RA'   => '201912180001',
            'MA_THE'    => 'DN1234567890',
            'GIOI_TINH' => '1',
        ], $ghiDe);
    }

    private function maLoi(array $loi)
    {
        return array_column($loi, 'ma_loi');
    }

    /** @test */
    public function ho_so_hop_le_khong_sinh_loi_nao()
    {
        $this->assertSame([], CtdtChecker::kiem('CT03', $this->ct03HopLe(), '01929'));
    }

    /** @test */
    public function thieu_truong_bat_buoc_sinh_CTDT001_muc_chan()
    {
        $loi = CtdtChecker::kiem('CT03', $this->ct03HopLe(['HO_TEN' => '']), '01929');

        $this->assertContains('CTDT001', $this->maLoi($loi));
        $this->assertSame('HO_TEN', $loi[0]['ten_truong']);
        $this->assertSame('chan', $loi[0]['muc_do']);
        $this->assertContains('HO_TEN', $loi[0]['mo_ta'], 'Mo ta phai neu ten truong');
    }

    /** @test */
    public function truong_bat_buoc_vang_han_cung_sinh_CTDT001()
    {
        // Dung HO_TEN chu khong phai MA_YTE: MA_YTE khong con la truong bat buoc (cong van
        // 2076 cho phep de trong de BHXH tu sinh). Bat bien can canh o day la "vang HAN cung
        // bi bat", khong phai "MA_YTE bi bat" - array_key_exists() va trim('') la hai duong
        // khac nhau, va chi mot trong hai duoc test o cho khac.
        $duLieu = $this->ct03HopLe();
        unset($duLieu['HO_TEN']);

        $this->assertContains('CTDT001', $this->maLoi(CtdtChecker::kiem('CT03', $duLieu, '01929')));
    }

    /** @test */
    public function truong_bat_buoc_chi_co_khoang_trang_van_la_thieu()
    {
        $loi = CtdtChecker::kiem('CT03', $this->ct03HopLe(['HO_TEN' => '   ']), '01929');

        $this->assertContains('CTDT001', $this->maLoi($loi));
    }

    /** @test */
    public function thieu_ma_the_chi_la_canh_bao()
    {
        // Tre em khong the (TEKT = 1) la hop le. Chan o day se chan nham ho so tre so sinh.
        $loi = CtdtChecker::kiem('CT03', $this->ct03HopLe(['MA_THE' => '']), '01929');

        $ma = $this->maLoi($loi);
        $this->assertContains('CTDT008', $ma);
        $this->assertNotContains('CTDT001', $ma, 'Ma the KHONG duoc la loi muc chan');
        $this->assertSame('canh_bao', $loi[0]['muc_do']);
    }

    /** @test */
    public function ngay_chap_nhan_ca_ba_do_dai()
    {
        foreach (['20251003', '202510031530', '20251003153045'] as $ngay) {
            $loi = CtdtChecker::kiem('CT03', $this->ct03HopLe(['NGAY_VAO' => $ngay, 'NGAY_RA' => $ngay]), '01929');

            $this->assertSame([], $loi, 'Ngay ' . $ngay . ' phai hop le');
        }
    }

    /** @test */
    public function ngay_co_chu_sinh_CTDT002()
    {
        $loi = CtdtChecker::kiem('CT03', $this->ct03HopLe(['NGAY_SINH' => '1995091X']), '01929');

        $this->assertContains('CTDT002', $this->maLoi($loi));
    }

    /** @test */
    public function ngay_do_dai_le_sinh_CTDT002()
    {
        $loi = CtdtChecker::kiem('CT03', $this->ct03HopLe(['NGAY_SINH' => '1995091']), '01929');

        $this->assertContains('CTDT002', $this->maLoi($loi));
    }

    /** @test */
    public function ngay_khong_co_that_sinh_CTDT002()
    {
        // Thang 13 va ngay 32: dung do dai, dung chu so, nhung khong ton tai tren lich.
        foreach (['19951301', '19950932'] as $ngay) {
            $loi = CtdtChecker::kiem('CT03', $this->ct03HopLe(['NGAY_SINH' => $ngay]), '01929');

            $this->assertContains('CTDT002', $this->maLoi($loi), $ngay . ' phai bi bat');
        }
    }

    /** @test */
    public function ngay_29_thang_2_nam_khong_nhuan_bi_bat()
    {
        $loi = CtdtChecker::kiem('CT03', $this->ct03HopLe(['NGAY_SINH' => '19950229']), '01929');

        $this->assertContains('CTDT002', $this->maLoi($loi));
    }

    /** @test */
    public function ngay_29_thang_2_nam_nhuan_hop_le()
    {
        $loi = CtdtChecker::kiem('CT03', $this->ct03HopLe(['NGAY_SINH' => '19960229']), '01929');

        $this->assertSame([], $loi);
    }

    /** @test */
    public function truong_ngay_rong_khong_bi_kiem_dinh_dang()
    {
        // Truong ngay KHONG bat buoc va de trong la hop le. Bat dinh dang o o trong se
        // sinh mot bien loi gia cho moi ho so.
        $loi = CtdtChecker::kiem('CT03', $this->ct03HopLe(['NGOAITRU_TUNGAY' => '']), '01929');

        $this->assertSame([], $loi);
    }

    /** @test */
    public function gioi_tinh_ngoai_mien_sinh_CTDT003()
    {
        $loi = CtdtChecker::kiem('CT03', $this->ct03HopLe(['GIOI_TINH' => '4']), '01929');

        $this->assertContains('CTDT003', $this->maLoi($loi));
    }

    /** @test */
    public function gioi_tinh_1_2_3_deu_hop_le()
    {
        foreach (['1', '2', '3'] as $g) {
            $this->assertSame([], CtdtChecker::kiem('CT03', $this->ct03HopLe(['GIOI_TINH' => $g]), '01929'));
        }
    }

    /** @test */
    public function loai_giay_to_ngoai_mien_sinh_CTDT004()
    {
        $loi = CtdtChecker::kiem('CT03', $this->ct03HopLe(['LOAI_GIAYTO' => '9']), '01929');

        $this->assertContains('CTDT004', $this->maLoi($loi));
    }

    /** @test */
    public function loai_giay_to_0_den_4_deu_hop_le()
    {
        foreach (['0', '1', '2', '3', '4'] as $l) {
            $this->assertSame([], CtdtChecker::kiem('CT03', $this->ct03HopLe(['LOAI_GIAYTO' => $l]), '01929'));
        }
    }

    /** @test */
    public function truong_co_ngoai_0_1_chi_la_canh_bao()
    {
        $loi = CtdtChecker::kiem('CT03', $this->ct03HopLe(['TEKT' => '2']), '01929');

        $this->assertContains('CTDT005', $this->maLoi($loi));
        $this->assertSame('canh_bao', $loi[0]['muc_do']);
    }

    /** @test */
    public function ngay_ra_som_hon_ngay_vao_sinh_CTDT006()
    {
        $loi = CtdtChecker::kiem('CT03', $this->ct03HopLe([
            'NGAY_VAO' => '201912181200',
            'NGAY_RA'  => '201912120001',
        ]), '01929');

        $this->assertContains('CTDT006', $this->maLoi($loi));
    }

    /** @test */
    public function ngay_ra_cung_ngay_voi_ngay_vao_la_hop_le()
    {
        // Kham roi ra trong ngay la chuyen thuong. So sanh phai la "som hon", khong phai
        // "khong lon hon".
        $loi = CtdtChecker::kiem('CT03', $this->ct03HopLe([
            'NGAY_VAO' => '201912120800',
            'NGAY_RA'  => '201912121600',
        ]), '01929');

        $this->assertSame([], $loi);
    }

    /** @test */
    public function cap_ngay_lech_do_dai_van_so_sanh_dung()
    {
        // CT06 dung NGAY_VAO/NGAY_RA dang 8 ky tu, CT03 dang 12. So sanh ca chuoi se cho
        // ket qua sai khi hai the trong cung mot cap khac do dai.
        $loi = CtdtChecker::kiem('CT06', [
            'HO_TEN' => 'Tran Thi Test', 'NGAY_SINH' => '19480826',
            'NGAY_VAO' => '20251030', 'NGAY_RA' => '202510031530',
        ], '01929');

        $this->assertContains('CTDT006', $this->maLoi($loi), 'NGAY_RA 03/10 som hon NGAY_VAO 30/10');
    }

    /** @test */
    public function chi_mot_ve_cua_cap_ngay_co_gia_tri_thi_khong_so_sanh()
    {
        $loi = CtdtChecker::kiem('CT03', $this->ct03HopLe(['NGAY_RA' => '']), '01929');

        // Van co CTDT001 vi NGAY_RA bat buoc, nhung KHONG duoc co CTDT006.
        $this->assertNotContains('CTDT006', $this->maLoi($loi));
    }

    /** @test */
    public function macskcb_trong_chung_tu_lech_voi_ho_so_sinh_CTDT007()
    {
        $loi = CtdtChecker::kiem('GIAYBAOTU', [
            'MA_GBT' => 'GBT-1', 'HO_TEN' => 'Nguyen Van Test',
            'NGAY_SINH' => '20220101', 'NGAY_TV' => '202510070200',
            'MACSKCB' => '37470',
        ], '01929');

        $this->assertContains('CTDT007', $this->maLoi($loi));
    }

    /** @test */
    public function macskcb_khop_thi_khong_sinh_loi()
    {
        $loi = CtdtChecker::kiem('GIAYBAOTU', [
            'MA_GBT' => 'GBT-1', 'HO_TEN' => 'Nguyen Van Test',
            'NGAY_SINH' => '20220101', 'NGAY_TV' => '202510070200',
            'MACSKCB' => '01929', 'MA_THE' => 'DN1',
        ], '01929');

        $this->assertSame([], $loi);
    }

    /** @test */
    public function chung_tu_khong_khai_macskcb_thi_khong_kiem()
    {
        // Chi CT2025 va giay bao tu mang MACSKCB. Cac loai khac khong co the do.
        $loi = CtdtChecker::kiem('CT03', $this->ct03HopLe(), '01929');

        $this->assertNotContains('CTDT007', $this->maLoi($loi));
    }

    /** @test */
    public function loai_la_tra_mang_rong_khong_nem()
    {
        // Loai chua co trong bang bat buoc: bo kiem khong biet doi gi, nen khong doi gi.
        // Nem o day se lam ca job kiem do vi mot loai moi cua BHXH.
        $this->assertSame([], CtdtChecker::kiem('CT99', ['GI_DO' => 'x'], '01929'));
    }

    /** @test */
    public function moi_loi_deu_co_du_bon_khoa()
    {
        $loi = CtdtChecker::kiem('CT03', $this->ct03HopLe([
            'HO_TEN' => '', 'NGAY_SINH' => 'xxxx', 'GIOI_TINH' => '9', 'TEKT' => '5',
        ]), '01929');

        $this->assertNotEmpty($loi);

        foreach ($loi as $mot) {
            $this->assertSame(['ma_loi', 'ten_truong', 'mo_ta', 'muc_do'], array_keys($mot));
            $this->assertNotEmpty($mot['mo_ta']);
        }
    }

    /** @test */
    public function moi_ma_loi_sinh_ra_deu_co_trong_danh_muc_config()
    {
        // Sinh mot ma khong co trong danh muc thi man hinh se hien mot dong loi khong ai
        // tra cuu duoc.
        $loi = CtdtChecker::kiem('CT03', $this->ct03HopLe([
            'HO_TEN' => '', 'NGAY_SINH' => 'xxxx', 'GIOI_TINH' => '9',
            'LOAI_GIAYTO' => '9', 'TEKT' => '5', 'MA_THE' => '',
        ]), '01929');

        $danhMuc = config('ctdt.ma_loi');

        foreach ($loi as $mot) {
            $this->assertArrayHasKey($mot['ma_loi'], $danhMuc, 'Ma la: ' . $mot['ma_loi']);
            $this->assertSame($danhMuc[$mot['ma_loi']]['muc_do'], $mot['muc_do'],
                $mot['ma_loi'] . ': muc do phai lay tu danh muc, khong go tay');
        }
    }

    /** @test */
    public function macskcb_ho_so_rong_thi_khong_sinh_CTDT007()
    {
        // Ho so chua phan giai duoc ma co so (macskcbHoSo = '') thi khong co gi de doi
        // chieu; bao lech o day la bao bua.
        $loi = CtdtChecker::kiem('GIAYBAOTU', [
            'MA_GBT' => 'GBT-1', 'HO_TEN' => 'Nguyen Van Test',
            'NGAY_SINH' => '20220101', 'NGAY_TV' => '202510070200',
            'MACSKCB' => '37470',
        ], '');

        $this->assertNotContains('CTDT007', $this->maLoi($loi));
    }

    /** @test */
    public function mot_ve_cap_ngay_sai_dinh_dang_thi_khong_them_CTDT006()
    {
        // Mot ve da sai dinh dang (da co CTDT002) thi so sanh tiep chi sinh them mot loi
        // thu hai cho cung mot nguyen nhan, lam nguoi doc tuong co hai van de.
        $loi = CtdtChecker::kiem('CT03', $this->ct03HopLe([
            'NGAY_VAO' => 'xxxx',
            'NGAY_RA'  => '201912180001',
        ]), '01929');

        $ma = $this->maLoi($loi);
        $this->assertContains('CTDT002', $ma);
        $this->assertNotContains('CTDT006', $ma);
    }
}

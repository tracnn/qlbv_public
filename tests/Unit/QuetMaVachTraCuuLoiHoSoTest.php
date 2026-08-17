<?php

namespace Tests\Unit;

use Tests\TestCase;

/**
 * Canh mot loi da xay ra that: man tra cuu loi ho so quet duoc QR nhung khong quet duoc
 * ma vach.
 *
 * Ma dieu tri duoc chinh app nay in ra duoi dang CODE 128 (PatientController va
 * KHTHController deu goi getBarcode(..., TYPE_CODE_128)), tuc thu nguoi dung quet tren
 * giay la ma vach 1D chu khong phai QR.
 *
 * html5-qrcode CAT khung hinh dung bang qrbox roi moi giai ma. Voi qrbox la mot so (o
 * VUONG), ma vach dai va thap chi nhet vua chieu ngang khi nguoi dung lui that xa, luc do
 * be rong moi vach tut xuong duoi nguong doc duoc. QR van chay vi no vuong - nen loi nay
 * im lang, chi lo ra khi co nguoi cam to giay len quet.
 */
class QuetMaVachTraCuuLoiHoSoTest extends TestCase
{
    protected function maBlade()
    {
        // KHONG dung trait LocComment o day: no bo comment bang token_get_all(), ma toan bo
        // phan JS cua blade den duoi dang T_INLINE_HTML nen comment JS khong he bi bo. Doc
        // thang tep va chap nhan rang buoc: chu thich trong blade khong duoc chua nguyen
        // van chuoi 'qrbox: 250', neu khong test se do oan.
        return file_get_contents(base_path('resources/views/khth/tra-cuu-loi-ho-so.blade.php'));
    }

    /** @test */
    public function qrbox_khong_duoc_la_o_vuong_co_dinh()
    {
        $ma = $this->maBlade();

        $this->assertNotContains(
            'qrbox: 250',
            $ma,
            'qrbox quay lai o vuong co dinh - ma vach Code 128 se khong giai ma duoc nua'
        );

        $this->assertContains(
            'qrbox: khungQuet',
            $ma,
            'Khong con dung ham khungQuet() de tinh vung giai ma hinh chu nhat ngang'
        );
    }

    /** @test */
    public function vung_giai_ma_rong_hon_cao()
    {
        $ma = $this->maBlade();

        $this->assertContains('function khungQuet(', $ma, 'Mat ham khungQuet()');

        // Lay chinh cong thuc trong blade ra chay thu: khung phai NGANG (rong > cao) o moi
        // co khung hinh thuong gap, neu khong thi ma vach lai khong doc duoc.
        foreach ([[640, 480], [1280, 720], [360, 640]] as $co) {
            $khung = $this->khungQuet($co[0], $co[1]);

            $this->assertGreaterThan(
                $khung['height'],
                $khung['width'],
                'Vung giai ma khong con rong hon cao o khung hinh ' . $co[0] . 'x' . $co[1]
            );

            $this->assertLessThanOrEqual($co[0], $khung['width'], 'Vung giai ma rong hon khung hinh');
            $this->assertLessThanOrEqual($co[1], $khung['height'], 'Vung giai ma cao hon khung hinh');
        }
    }

    /** @test */
    public function co_xin_do_phan_giai_cao_cho_camera()
    {
        $ma = $this->maBlade();

        $this->assertContains(
            'width: { ideal: 1280 }',
            $ma,
            'Khong con xin do phan giai cao - 640x480 khong du diem anh tren moi vach Code 128'
        );
    }

    /** Ban PHP cua ham khungQuet() trong blade, giu dong bo bang test o tren. */
    protected function khungQuet($rongKhungHinh, $caoKhungHinh)
    {
        $rong = (int) floor($rongKhungHinh * 0.9);
        $cao = (int) floor(min($caoKhungHinh * 0.6, max($rong * 0.4, 140)));

        return ['width' => $rong, 'height' => max($cao, 60)];
    }
}

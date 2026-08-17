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

    /**
     * Vung quet la mot o CAT ra tu khung hinh roi moi giai ma; de trong thi thu vien giai
     * ma toan khung hinh. Ma vach dai va thap rat de nam ngoai o cat do, nen man nay khong
     * dat vung quet.
     *
     * Luu y cho nguoi sua sau: chu thich trong blade khong duoc viet nguyen van 'qrbox' kem
     * dau hai cham, neu khong test nay do oan (doc thang tep, khong boc tach comment).
     *
     * @test
     */
    public function khong_cat_vung_quet_ma_giai_ma_toan_khung_hinh()
    {
        $this->assertNotContains(
            'qrbox:',
            $this->maBlade(),
            'Da dat lai vung quet - ma vach dai de nam ngoai o cat va khong bao gio giai ma duoc'
        );
    }

    /**
     * Do phan giai la dieu kien de giai ma duoc ma vach: do tren may that, camera mac dinh
     * cho 480x640 va Code 128 cua ma dieu tri chi con ~1,6 diem anh moi vach, duoi nguong
     * ~2 ma ZXing can.
     *
     * Phai xin bang 'advanced' - theo chuan WebRTC no la co gang het suc, khong dat thi bo
     * qua. Xin bang 'ideal'/'min' tung lam may that khong mo duoc camera.
     *
     * @test
     */
    public function xin_do_phan_giai_cao_theo_kieu_khong_lam_hong_viec_mo_camera()
    {
        $ma = $this->maBlade();

        $this->assertContains(
            'advanced: [{ width: 1920',
            $ma,
            'Khong con xin do phan giai cao - ma vach se khong du diem anh moi vach de giai ma'
        );

        foreach (['width: { ideal', 'width: { min'] as $cam) {
            $this->assertNotContains(
                $cam,
                $ma,
                'Rang buoc do phan giai cung da quay lai - tung lam dien thoai khong mo duoc camera'
            );
        }
    }

    /**
     * Dem khung hinh la thu duy nhat phan biet "vong quet khong chay" voi "co doc khung
     * hinh nhung khong ra ma". Hai nguyen nhan nay nhin tu ngoai giong het nhau.
     *
     * @test
     */
    public function co_hien_so_khung_hinh_da_quet()
    {
        $ma = $this->maBlade();

        $this->assertContains('id="trang-thai-quet"', $ma, 'Mat dong trang thai duoi khung camera');
        $this->assertContains('demKhungQuet++', $ma, 'Khong con dem khung hinh da quet');
    }

    /**
     * Thu lai bang mot lenh start() thu hai ngay trong .catch lam thu vien nem
     * "Cannot transition to a new state, already under transition", va loi that cua lan
     * dau bi che mat - da xay ra that.
     *
     * @test
     */
    public function loi_mo_camera_duoc_hien_nguyen_van()
    {
        $ma = $this->maBlade();

        $this->assertContains(
            "alert('Không mở được camera: '",
            $ma,
            'Bao loi mo camera quay lai dang chung chung, khong chan doan duoc tu xa'
        );
    }

}

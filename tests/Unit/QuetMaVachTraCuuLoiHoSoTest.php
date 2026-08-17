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
     * Mac dinh thu vien uu tien BarcodeDetector san co cua trinh duyet. Tren may thu
     * nghiem, camera chay binh thuong nhung khong bao gio bat duoc ma nao - ke ca QR - nen
     * ep dung ZXing di kem thu vien.
     *
     * @test
     */
    public function ep_dung_bo_giai_ma_zxing()
    {
        $this->assertContains(
            'useBarCodeDetectorIfSupported: false',
            $this->maBlade(),
            'Da tro lai dung BarcodeDetector cua trinh duyet - tung khong bat duoc ma nao'
        );
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
     * Da tung them rang buoc { width: { ideal: 1280 } } vao lenh mo camera de Code 128 de
     * giai ma hon; may that bao "Khong mo duoc camera" nen da bo. Neu sau nay co nguoi
     * dinh them lai, phai kiem tren dien thoai truoc chu khong chi tren may ban.
     *
     * @test
     */
    public function khong_kem_rang_buoc_do_phan_giai_khi_mo_camera()
    {
        $this->assertNotContains(
            'ideal:',
            $this->maBlade(),
            'Rang buoc do phan giai da quay lai - tung lam dien thoai khong mo duoc camera'
        );
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

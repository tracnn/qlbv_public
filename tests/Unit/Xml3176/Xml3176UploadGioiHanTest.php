<?php

namespace Tests\Unit\Xml3176;

use Tests\TestCase;
use Tests\Support\LocComment;

class Xml3176UploadGioiHanTest extends TestCase
{
    use LocComment;

    private function thanUploadData()
    {
        $ma = $this->maKhongComment(app_path('Http/Controllers/BHYT/BHYTXml3176Controller.php'));

        $dau = strpos($ma, 'function uploadData');
        $this->assertNotFalse($dau, 'Khong tim thay uploadData');

        // 600 ky tu dau than ham - du xa de phu phan noi gioi han o dau ham.
        return substr($ma, $dau, 600);
    }

    /** @test */
    public function upload_data_noi_ca_hai_gioi_han()
    {
        // Mot file XML3176 duoc phep toi 100 MB. Giai ma base64 roi dung SimpleXML cho
        // tung phan lam bo nho phinh gap nhieu lan kich thuoc file, ma may chu moi gioi
        // han 128 MB / 120 giay - con Dropzone thi cho toi 300 giay.
        $than = $this->thanUploadData();

        $this->assertContains('set_time_limit', $than,
            'uploadData khong noi gioi han thoi gian - file lon se chet o moc 120 giay');
        $this->assertContains("ini_set('memory_limit'", $than,
            'uploadData khong noi gioi han bo nho');
    }

    /** @test */
    public function khong_sao_chep_muc_4096M_cua_cac_lop_export()
    {
        // Cac lop Exports/ dung 4096M vi chung chay khi MOT nguoi bam xuat bao cao.
        // uploadData la endpoint web ma Dropzone ban 2 request song song moi nguoi dung:
        // cho moi request 4 GB tren may cau hinh 128 MB co the lam can RAM that, va
        // tien trinh bi he dieu hanh giet thi te hon han mot loi PHP sach se.
        $this->assertNotContains('4096M', $this->thanUploadData(),
            'Da sao chep muc 4096M cua cac lop Export vao endpoint web');
    }

    /** @test */
    public function hai_khoa_cau_hinh_ton_tai_va_hop_le()
    {
        $thoiGian = config('xml3176.import_time_limit');
        $boNho    = config('xml3176.import_memory_limit');

        $this->assertInternalType('int', $thoiGian, 'import_time_limit phai la so nguyen');
        $this->assertGreaterThan(0, $thoiGian);

        $this->assertRegExp('/^\d+[KMG]$/', (string) $boNho,
            'import_memory_limit phai co dang so kem don vi, vi du 512M');
    }
}

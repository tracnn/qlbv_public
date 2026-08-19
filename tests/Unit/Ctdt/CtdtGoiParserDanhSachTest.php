<?php

namespace Tests\Unit\Ctdt;

use Tests\TestCase;
use Tests\Support\GoiCtdtMau;
use App\Services\Ctdt\CtdtGoiParser;
use App\Services\Ctdt\Loi\GoiKhongDocDuocException;

/**
 * Chuan hoa ba dich vu ve CUNG MOT dang: mang cac HOSO, moi HOSO la mang chung tu.
 */
class CtdtGoiParserDanhSachTest extends TestCase
{
    use GoiCtdtMau;

    /** @test */
    public function ct2025_mot_ho_so_nhieu_chung_tu()
    {
        $xml = $this->goiCt2025([[
            $this->chungTu('CT03', ['MA_YTE' => 'YT001', 'HO_TEN' => 'Nguyen Van Test']),
            $this->chungTu('CT04', ['MA_CT' => 'CT-1']),
        ]]);

        $goi = CtdtGoiParser::doc($xml);
        $ds = CtdtGoiParser::danhSachHoSo($goi, 'CT2025');

        $this->assertCount(1, $ds, 'Mot HOSO');
        $this->assertCount(2, $ds[0], 'Hai chung tu trong ho so do');

        $this->assertSame('CT03', $ds[0][0]['loai_ho_so']);
        // 'noi_dung' la CHUOI XML CHUA PARSE tai day - parse chi xay ra o phanTichChungTu(),
        // ben trong try/transaction rieng cua tung ho so ben phia CtdtImporter.
        $this->assertTrue(is_string($ds[0][0]['noi_dung']));
        $this->assertContains('YT001', $ds[0][0]['noi_dung']);

        $this->assertSame('CT04', $ds[0][1]['loai_ho_so']);

        $daPhanTich = CtdtGoiParser::phanTichChungTu($ds[0], 1);
        $this->assertSame('CT03', $daPhanTich[0]['noi_dung']->getName());
        $this->assertSame('YT001', (string) $daPhanTich[0]['noi_dung']->MA_YTE);
    }

    /** @test */
    public function ct2025_NHIEU_ho_so_khong_bi_bo_sot()
    {
        // Ban cu cua XML3176 duyet ->HOSO->FILEHOSO. Trong SimpleXML, ->HOSO tren mot tap
        // nhieu phan tu TU LAY PHAN TU DAU, nen ho so thu hai tro di bi bo HOAN TOAN -
        // khong loi, khong log, va nguoi dung van nhan "thanh cong".
        $xml = $this->goiCt2025([
            [$this->chungTu('CT03', ['MA_YTE' => 'YT001'])],
            [$this->chungTu('CT03', ['MA_YTE' => 'YT002'])],
            [$this->chungTu('CT03', ['MA_YTE' => 'YT003'])],
        ]);

        $ds = CtdtGoiParser::danhSachHoSo(CtdtGoiParser::doc($xml), 'CT2025');

        $this->assertCount(3, $ds);
        $this->assertContains('YT002', $ds[1][0]['noi_dung']);
        $this->assertContains('YT003', $ds[2][0]['noi_dung']);

        $hoSo2 = CtdtGoiParser::phanTichChungTu($ds[1], 2);
        $hoSo3 = CtdtGoiParser::phanTichChungTu($ds[2], 3);
        $this->assertSame('YT002', (string) $hoSo2[0]['noi_dung']->MA_YTE);
        $this->assertSame('YT003', (string) $hoSo3[0]['noi_dung']->MA_YTE);
    }

    /** @test */
    public function giu_dung_thu_tu_xuat_hien_trong_tep()
    {
        // Thu tu quyet dinh chung tu nao duoc lay lam khoa ho so (chung tu dau tien co
        // MA_YTE thang). Sap xep lai o day se doi khoa cua ho so mot cach im lang.
        $xml = $this->goiCt2025([[
            $this->chungTu('CT04', ['MA_CT' => 'CT-1']),
            $this->chungTu('CT03', ['MA_YTE' => 'YT001']),
            $this->chungTu('CT06', ['MA_BHXH' => 'BH-1']),
        ]]);

        $ds = CtdtGoiParser::danhSachHoSo(CtdtGoiParser::doc($xml), 'CT2025');

        $this->assertSame(['CT04', 'CT03', 'CT06'], array_column($ds[0], 'loai_ho_so'));
    }

    /** @test */
    public function giay_bao_tu_chuan_hoa_thanh_mot_ho_so_mot_chung_tu()
    {
        $xml = $this->goiGbt(['MA_GBT' => '00002.GBT.XXXX.25', 'HO_TEN' => 'Nguyen Van Test']);

        $ds = CtdtGoiParser::danhSachHoSo(CtdtGoiParser::doc($xml), 'GBT');

        $this->assertCount(1, $ds);
        $this->assertCount(1, $ds[0]);
        $this->assertSame('GIAYBAOTU', $ds[0][0]['loai_ho_so']);
        $this->assertTrue(is_string($ds[0][0]['noi_dung']));
        $this->assertContains('00002.GBT.XXXX.25', $ds[0][0]['noi_dung']);

        $daPhanTich = CtdtGoiParser::phanTichChungTu($ds[0], 1);
        $this->assertSame('GIAYBAOTU', $daPhanTich[0]['noi_dung']->getName());
        $this->assertSame('00002.GBT.XXXX.25', (string) $daPhanTich[0]['noi_dung']->MA_GBT);
    }

    /** @test */
    public function giay_chung_sinh_chuan_hoa_thanh_mot_ho_so_mot_chung_tu()
    {
        $xml = $this->goiGcs(['MA_GCS' => '00005.GCS.XXXXX.25', 'HOTEN_NND' => 'Pham Minh Test']);

        $ds = CtdtGoiParser::danhSachHoSo(CtdtGoiParser::doc($xml), 'GCS');

        $this->assertCount(1, $ds);
        $this->assertSame('GIAYCHUNGSINH', $ds[0][0]['loai_ho_so']);

        $daPhanTich = CtdtGoiParser::phanTichChungTu($ds[0], 1);
        $this->assertSame('00005.GCS.XXXXX.25', (string) $daPhanTich[0]['noi_dung']->MA_GCS);
    }

    /** @test */
    public function ct2025_khong_co_hoso_nao_thi_tra_mang_rong_khong_nem()
    {
        // Goi rong la chuyen cua tang tren quyet dinh (CtdtImporter ghi "khong co ho so"),
        // khong phai loi cu phap de parser tu nem.
        $goi = CtdtGoiParser::doc('<HSCHUNGTU><THONGTINHOSO><DANHSACHHOSO/></THONGTINHOSO></HSCHUNGTU>');

        $this->assertSame([], CtdtGoiParser::danhSachHoSo($goi, 'CT2025'));
    }

    /** @test */
    public function ct2025_ho_so_khong_co_filehoso_thi_la_ho_so_rong()
    {
        $goi = CtdtGoiParser::doc(
            '<HSCHUNGTU><THONGTINHOSO><DANHSACHHOSO><HOSO/></DANHSACHHOSO></THONGTINHOSO></HSCHUNGTU>'
        );

        $ds = CtdtGoiParser::danhSachHoSo($goi, 'CT2025');

        $this->assertCount(1, $ds);
        $this->assertSame([], $ds[0]);
    }

    /** @test */
    public function noi_dung_base64_hong_thi_danh_sach_ho_so_khong_nem()
    {
        // danhSachHoSo() chi tach khung, KHONG parse - noi dung base64 hong khong duoc phep
        // lam no nem, neu khong mot NOIDUNGFILE hong o mot ho so se keo ca tep bi tu choi
        // truoc khi vong lap per-ho-so cua importer kip chay.
        $goi = CtdtGoiParser::doc(
            '<HSCHUNGTU><THONGTINHOSO><DANHSACHHOSO><HOSO><FILEHOSO>'
            . '<LOAIHOSO>CT03</LOAIHOSO><NOIDUNGFILE>' . base64_encode('<CT03><chua dong') . '</NOIDUNGFILE>'
            . '</FILEHOSO></HOSO></DANHSACHHOSO></THONGTINHOSO></HSCHUNGTU>'
        );

        $ds = CtdtGoiParser::danhSachHoSo($goi, 'CT2025');

        $this->assertCount(1, $ds);
        $this->assertSame('CT03', $ds[0][0]['loai_ho_so']);
        $this->assertTrue(is_string($ds[0][0]['noi_dung']));
    }

    /** @test */
    public function noi_dung_base64_hong_thi_phan_tich_chung_tu_nem_voi_ngu_canh_ro()
    {
        $goi = CtdtGoiParser::doc(
            '<HSCHUNGTU><THONGTINHOSO><DANHSACHHOSO><HOSO><FILEHOSO>'
            . '<LOAIHOSO>CT03</LOAIHOSO><NOIDUNGFILE>' . base64_encode('<CT03><chua dong') . '</NOIDUNGFILE>'
            . '</FILEHOSO></HOSO></DANHSACHHOSO></THONGTINHOSO></HSCHUNGTU>'
        );
        $ds = CtdtGoiParser::danhSachHoSo($goi, 'CT2025');

        try {
            CtdtGoiParser::phanTichChungTu($ds[0], 2);
            $this->fail('Phai nem GoiKhongDocDuocException');
        } catch (GoiKhongDocDuocException $e) {
            $this->assertContains('Ho so #2', $e->getMessage());
            $this->assertContains('CT03', $e->getMessage());
        }
    }

    /** @test */
    public function bao_tu_thieu_the_noi_dung_thi_tra_ho_so_rong()
    {
        $this->assertSame([[]], CtdtGoiParser::danhSachHoSo(CtdtGoiParser::doc('<HSDLGBT/>'), 'GBT'));
    }
}

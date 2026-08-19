<?php

namespace Tests\Unit\Ctdt;

use Tests\TestCase;
use Tests\Support\GoiCtdtMau;
use App\Services\Ctdt\CtdtGoiParser;
use App\Services\Ctdt\CtdtMaHoSo;
use App\Services\Ctdt\Loi\KhongXacDinhDuocMaHoSoException;

/**
 * Khoa ho so quyet dinh viec GHI DE khi nap lai. Sai khoa co hai kieu, va ca hai deu im
 * lang: khoa qua hep thi nap lai de ra ban ghi thu hai; khoa qua rong thi hai ho so khac
 * nhau bi coi la mot va mat du lieu.
 */
class CtdtMaHoSoTest extends TestCase
{
    use GoiCtdtMau;

    private function hoSoDau($xml, $dichVu)
    {
        $ds = CtdtGoiParser::danhSachHoSo(CtdtGoiParser::doc($xml), $dichVu);

        // danhSachHoSo() tra 'noi_dung' la chuoi XML CHUA PARSE (hop dong Task I-1);
        // CtdtMaHoSo::cua() van can \SimpleXMLElement nen phai phanTichChungTu() truoc.
        return CtdtGoiParser::phanTichChungTu($ds[0], 1);
    }

    /** @test */
    public function lay_ma_yte_cua_chung_tu_dau_tien_co_the_do()
    {
        $xml = $this->goiCt2025([[
            $this->chungTu('CT04', ['MA_CT' => 'CT-1']),
            $this->chungTu('CT03', ['MA_YTE' => 'YT001']),
            $this->chungTu('GIAYDIEUTRINOITRU', ['MA_YTE' => 'YT999']),
        ]]);

        $this->assertSame('YT001', CtdtMaHoSo::cua($this->hoSoDau($xml, 'CT2025'), 'Id-goi-mau', 1));
    }

    /** @test */
    public function giay_bao_tu_lay_ma_gbt()
    {
        $xml = $this->goiGbt(['MA_GBT' => '00002.GBT.XXXX.25']);

        $this->assertSame('00002.GBT.XXXX.25', CtdtMaHoSo::cua($this->hoSoDau($xml, 'GBT'), 'Id-gbt-mau', 1));
    }

    /** @test */
    public function giay_chung_sinh_lay_ma_gcs()
    {
        $xml = $this->goiGcs(['MA_GCS' => '00005.GCS.XXXXX.25']);

        $this->assertSame('00005.GCS.XXXXX.25', CtdtMaHoSo::cua($this->hoSoDau($xml, 'GCS'), 'Id-gcs-mau', 1));
    }

    /** @test */
    public function ho_so_chi_gom_CT04_CT06_CT07_thi_lui_ve_id_goi_kem_chi_so()
    {
        // Ba loai nay KHONG co MA_YTE - han che da biet, ghi trong dac ta muc 4.3.
        $xml = $this->goiCt2025([[
            $this->chungTu('CT04', ['MA_CT' => 'CT-1']),
            $this->chungTu('CT07', ['MA_CT' => 'CT-2']),
        ]], ['id' => 'Id-abc']);

        $this->assertSame('Id-abc#1', CtdtMaHoSo::cua($this->hoSoDau($xml, 'CT2025'), 'Id-abc', 1));
    }

    /** @test */
    public function hai_ho_so_cung_thieu_ma_yte_trong_MOT_tep_khong_duoc_trung_khoa()
    {
        // Ca hai HOSO dung chung mot Id cua THONGTINHOSO. Neu khoa lui chi la Id thi ho so
        // thu hai GHI DE ho so thu nhat ngay trong cung mot lan nap - mat du lieu im lang.
        $xml = $this->goiCt2025([
            [$this->chungTu('CT04', ['MA_CT' => 'CT-1'])],
            [$this->chungTu('CT04', ['MA_CT' => 'CT-2'])],
        ], ['id' => 'Id-abc']);

        $ds = CtdtGoiParser::danhSachHoSo(CtdtGoiParser::doc($xml), 'CT2025');

        $khoa1 = CtdtMaHoSo::cua(CtdtGoiParser::phanTichChungTu($ds[0], 1), 'Id-abc', 1);
        $khoa2 = CtdtMaHoSo::cua(CtdtGoiParser::phanTichChungTu($ds[1], 2), 'Id-abc', 2);

        $this->assertNotSame($khoa1, $khoa2, 'Hai ho so trong cung mot tep phai co khoa khac nhau');
        $this->assertSame('Id-abc#1', $khoa1);
        $this->assertSame('Id-abc#2', $khoa2);
    }

    /** @test */
    public function ma_yte_thang_hon_id_goi()
    {
        $xml = $this->goiCt2025([[$this->chungTu('CT03', ['MA_YTE' => 'YT001'])]], ['id' => 'Id-abc']);

        $this->assertSame('YT001', CtdtMaHoSo::cua($this->hoSoDau($xml, 'CT2025'), 'Id-abc', 1));
    }

    /** @test */
    public function khong_co_ma_yte_lan_id_goi_thi_nem()
    {
        // Bia mot khoa tu MA_THE + NGAY_VAO se lam hai ho so khac nhau bi coi la mot.
        // Nem de nguoi van hanh biet ngay tai buoc nap.
        $xml = $this->goiCt2025([[$this->chungTu('CT04', ['MA_CT' => 'CT-1'])]]);

        $this->expectException(KhongXacDinhDuocMaHoSoException::class);

        CtdtMaHoSo::cua($this->hoSoDau($xml, 'CT2025'), null, 1);
    }

    /** @test */
    public function ho_so_rong_thi_nem()
    {
        $this->expectException(KhongXacDinhDuocMaHoSoException::class);

        CtdtMaHoSo::cua([], null, 1);
    }

    /** @test */
    public function ho_so_rong_nhung_co_id_goi_thi_van_lui_duoc()
    {
        $this->assertSame('Id-abc#3', CtdtMaHoSo::cua([], 'Id-abc', 3));
    }

    /** @test */
    public function ma_yte_rong_khong_duoc_coi_la_co()
    {
        $xml = $this->goiCt2025([[$this->chungTu('CT03', ['MA_YTE' => ''])]], ['id' => 'Id-abc']);

        $this->assertSame('Id-abc#1', CtdtMaHoSo::cua($this->hoSoDau($xml, 'CT2025'), 'Id-abc', 1));
    }

    /** @test */
    public function loai_la_khong_lam_do_ca_ho_so_khi_con_loai_khac_cho_duoc_khoa()
    {
        // Loai la se bi CtdtImporter bat rieng. O buoc suy khoa thi bo qua no va di tiep,
        // vi tu choi ca ho so chi vi mot loai la se lam mat mot ho so hop le.
        $chungTu = [
            ['loai_ho_so' => 'CT99', 'noi_dung' => simplexml_load_string('<CT99/>')],
            ['loai_ho_so' => 'CT03', 'noi_dung' => simplexml_load_string('<CT03><MA_YTE>YT001</MA_YTE></CT03>')],
        ];

        $this->assertSame('YT001', CtdtMaHoSo::cua($chungTu, 'Id-abc', 1));
    }
}

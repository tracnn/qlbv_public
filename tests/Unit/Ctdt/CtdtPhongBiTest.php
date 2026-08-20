<?php

namespace Tests\Unit\Ctdt;

use Tests\TestCase;
use App\Services\Ctdt\CtdtPhongBi;

/**
 * Ham THUAN: mang vao, chuoi XML ra. Khong doc CSDL.
 *
 * Phong bi nay la thu THAT SU duoc gui len cong BHXH, nen moi khang dinh o day deu la mot
 * dieu kien cong dat ra - khong phai so thich cua ta.
 */
class CtdtPhongBiTest extends TestCase
{
    private function hoSoCt2025(array $ghiDe = [])
    {
        return array_merge([
            'dich_vu'    => 'CT2025',
            'macskcb'    => '01929',
            'id_goi_xml' => 'Id-abc-123',
            'ngay_lap'   => '20260820',
        ], $ghiDe);
    }

    private function chungTuCt03()
    {
        return [[
            'loai_ho_so'   => 'CT03',
            'noi_dung_goc' => '<CT03><MA_YTE>YT001</MA_YTE></CT03>',
        ]];
    }

    /** @test */
    public function CT2025_dung_the_goc_HSCHUNGTU()
    {
        $xml = CtdtPhongBi::dung($this->hoSoCt2025(), $this->chungTuCt03());
        $goi = simplexml_load_string($xml);

        $this->assertNotFalse($goi, 'Phong bi phai la XML hop le');
        $this->assertSame('HSCHUNGTU', $goi->getName());
    }

    /** @test */
    public function CT2025_co_du_bon_tang_the()
    {
        $goi = simplexml_load_string(CtdtPhongBi::dung($this->hoSoCt2025(), $this->chungTuCt03()));

        $this->assertSame('01929', (string) $goi->THONGTINDONVI->MACSKCB);
        $this->assertSame('20260820', (string) $goi->THONGTINHOSO->NGAYLAP);
        $this->assertSame('Id-abc-123', (string) $goi->THONGTINHOSO['Id']);
        $this->assertCount(1, $goi->THONGTINHOSO->DANHSACHHOSO->HOSO);
    }

    /** @test */
    public function SOLUONGHOSO_luon_bang_1()
    {
        // Goi goc co the chua nhieu HOSO, nhung ta gui TUNG ho so mot vi trang thai va MaGD
        // deu theo tung ho so. Ghi lai so cua goi goc la khai bao sai voi cong.
        $goi = simplexml_load_string(CtdtPhongBi::dung(
            $this->hoSoCt2025(['so_luong_ho_so' => 7]),
            $this->chungTuCt03()
        ));

        $this->assertSame('1', (string) $goi->THONGTINHOSO->SOLUONGHOSO);
    }

    /** @test */
    public function noi_dung_chung_tu_duoc_ma_hoa_base64_va_giai_ra_dung_nguyen_van()
    {
        $goc = '<CT03><MA_YTE>YT001</MA_YTE><HO_TEN>Nguyen Van Test</HO_TEN></CT03>';

        $goi = simplexml_load_string(CtdtPhongBi::dung($this->hoSoCt2025(), [
            ['loai_ho_so' => 'CT03', 'noi_dung_goc' => $goc],
        ]));

        $file = $goi->THONGTINHOSO->DANHSACHHOSO->HOSO->FILEHOSO;

        $this->assertSame('CT03', (string) $file->LOAIHOSO);
        $this->assertSame($goc, base64_decode((string) $file->NOIDUNGFILE));
    }

    /** @test */
    public function nhieu_chung_tu_thanh_nhieu_FILEHOSO_trong_MOT_HOSO()
    {
        // Mot ho so co the co nhieu chung tu (giay ra vien + tom tat benh an). Tach chung
        // ra thanh nhieu HOSO la bien mot ho so thanh nhieu ho so truoc mat cong.
        $goi = simplexml_load_string(CtdtPhongBi::dung($this->hoSoCt2025(), [
            ['loai_ho_so' => 'CT03', 'noi_dung_goc' => '<CT03/>'],
            ['loai_ho_so' => 'CT04', 'noi_dung_goc' => '<CT04/>'],
        ]));

        $this->assertCount(1, $goi->THONGTINHOSO->DANHSACHHOSO->HOSO);
        $this->assertCount(2, $goi->THONGTINHOSO->DANHSACHHOSO->HOSO->FILEHOSO);
    }

    /** @test */
    public function co_the_rong_CHUKYDONVI_cho_dich_vu_ky_ghi_vao()
    {
        // Dich vu ky GHI chu ky vao mot the DA TON TAI (tag_store_signature_value =
        // 'CHUKYDONVI'), khong tu tao. Xml3176Service.php:1146 va Qd130XmlService.php:1056
        // deu addChild('CHUKYDONVI') truoc khi goi ky - lam khac di la ky xong khong co
        // chu ky nao trong tep.
        $goi = simplexml_load_string(CtdtPhongBi::dung($this->hoSoCt2025(), $this->chungTuCt03()));

        $this->assertTrue(isset($goi->CHUKYDONVI), 'Phai co the CHUKYDONVI rong');
        $this->assertSame('', trim((string) $goi->CHUKYDONVI));
    }

    /** @test */
    public function GBT_dung_the_goc_HSDLGBT_va_phang()
    {
        // Goi GBT/GCS phang hon: the goc chua TRUC TIEP mot GIAYBAOTU, khong co
        // THONGTINDONVI/THONGTINHOSO/DANHSACHHOSO, khong base64.
        $xml = CtdtPhongBi::dung(
            ['dich_vu' => 'GBT', 'macskcb' => '01929', 'id_goi_xml' => 'Id-gbt', 'ngay_lap' => null],
            [['loai_ho_so' => 'GIAYBAOTU', 'noi_dung_goc' => '<GIAYBAOTU Id="Id-gbt"><MA_GBT>G1</MA_GBT></GIAYBAOTU>']]
        );

        $goi = simplexml_load_string($xml);

        $this->assertSame('HSDLGBT', $goi->getName());
        $this->assertSame('G1', (string) $goi->GIAYBAOTU->MA_GBT);
        $this->assertFalse(isset($goi->THONGTINHOSO), 'Goi GBT khong co THONGTINHOSO');
        $this->assertTrue(isset($goi->CHUKYDONVI));
    }

    /** @test */
    public function GBT_giu_nguyen_thuoc_tinh_Id_cua_chung_tu()
    {
        // Chu ky XMLDSig tro toi Id-*; mat thuoc tinh Id la chu ky khong tham chieu duoc
        // vao dau, va cong tra 205.
        $goi = simplexml_load_string(CtdtPhongBi::dung(
            ['dich_vu' => 'GBT', 'macskcb' => '01929', 'id_goi_xml' => 'Id-gbt', 'ngay_lap' => null],
            [['loai_ho_so' => 'GIAYBAOTU', 'noi_dung_goc' => '<GIAYBAOTU Id="Id-gbt-999"><MA_GBT>G1</MA_GBT></GIAYBAOTU>']]
        ));

        $this->assertSame('Id-gbt-999', (string) $goi->GIAYBAOTU['Id']);
    }

    /** @test */
    public function GCS_dung_the_goc_HSDLGCS()
    {
        $goi = simplexml_load_string(CtdtPhongBi::dung(
            ['dich_vu' => 'GCS', 'macskcb' => '01929', 'id_goi_xml' => 'Id-gcs', 'ngay_lap' => null],
            [['loai_ho_so' => 'GIAYCHUNGSINH', 'noi_dung_goc' => '<GIAYCHUNGSINH Id="Id-gcs"><MA_GCS>C1</MA_GCS></GIAYCHUNGSINH>']]
        ));

        $this->assertSame('HSDLGCS', $goi->getName());
        $this->assertSame('C1', (string) $goi->GIAYCHUNGSINH->MA_GCS);
    }

    /** @test */
    public function noi_dung_goc_co_khai_bao_XML_van_ghep_duoc()
    {
        // CtdtLuuHoSo luu noi_dung_goc bang asXML() tren mot tai lieu da parse, nen chuoi
        // co the mang san dong khai bao xml. Ghep thang vao giua mot tai lieu khac
        // la XML hong - phai cat bo truoc.
        $khaiBao = '<' . '?xml version="1.0" encoding="UTF-8"?' . '>';
        $goc = $khaiBao . "\n" . '<GIAYBAOTU Id="Id-1"><MA_GBT>G1</MA_GBT></GIAYBAOTU>';

        $xml = CtdtPhongBi::dung(
            ['dich_vu' => 'GBT', 'macskcb' => '01929', 'id_goi_xml' => 'Id-1', 'ngay_lap' => null],
            [['loai_ho_so' => 'GIAYBAOTU', 'noi_dung_goc' => $goc]]
        );

        $goi = simplexml_load_string($xml);

        $this->assertNotFalse($goi, 'Phong bi phai la XML hop le du noi dung goc co khai bao');
        $this->assertSame('G1', (string) $goi->GIAYBAOTU->MA_GBT);
    }

    /** @test */
    public function CT2025_noi_dung_chung_tu_hong_thi_nem()
    {
        // CT2025 la dich vu chinh - phan lon ho so di duong nay. Truoc day chi nhanh phang
        // GBT/GCS duoc kiem, nen mot chung tu CT03 hong van duoc dong goi, ky va gui.
        $this->expectException(\InvalidArgumentException::class);

        CtdtPhongBi::dung($this->hoSoCt2025(), [
            ['loai_ho_so' => 'CT03', 'noi_dung_goc' => '<CT03>chua dong the'],
        ]);
    }

    /** @test */
    public function CT2025_base64_giu_NGUYEN_VAN_chuoi_goc_khong_serialize_lai()
    {
        // Parse chi de KIEM. Base64 lai ban da serialize se doi khoang trang va khai bao,
        // lam noi dung gui len cong khac voi noi dung da nap - va tab "XML goc" tren man
        // chi tiet se noi mot dang khac voi thu BHXH nhan duoc.
        $goc = '<CT03>  <MA_YTE>YT001</MA_YTE>  </CT03>';

        $goi = simplexml_load_string(CtdtPhongBi::dung($this->hoSoCt2025(), [
            ['loai_ho_so' => 'CT03', 'noi_dung_goc' => $goc],
        ]));

        $this->assertSame($goc, base64_decode(
            (string) $goi->THONGTINHOSO->DANHSACHHOSO->HOSO->FILEHOSO->NOIDUNGFILE
        ));
    }

    /** @test */
    public function noi_dung_goc_co_khoang_trang_truoc_khai_bao_XML_van_ghep_duoc()
    {
        // Day la truong hop DUY NHAT phep cat khai bao that su can: loadXML() loi khi co
        // khoang trang dung truoc khai bao. Khong co test nay thi lan don dep sau se go
        // preg_replace di, bo test van xanh, va loi chi lo ra tren du lieu that.
        $khaiBao = '<' . '?xml version="1.0" encoding="UTF-8"?' . '>';
        $goc = "\n  " . $khaiBao . "\n" . '<GIAYBAOTU Id="Id-1"><MA_GBT>G1</MA_GBT></GIAYBAOTU>';

        $goi = simplexml_load_string(CtdtPhongBi::dung(
            ['dich_vu' => 'GBT', 'macskcb' => '01929', 'id_goi_xml' => 'Id-1', 'ngay_lap' => null],
            [['loai_ho_so' => 'GIAYBAOTU', 'noi_dung_goc' => $goc]]
        ));

        $this->assertNotFalse($goi);
        $this->assertSame('G1', (string) $goi->GIAYBAOTU->MA_GBT);
    }

    /** @test */
    public function dich_vu_la_thi_nem()
    {
        $this->expectException(\InvalidArgumentException::class);

        CtdtPhongBi::dung($this->hoSoCt2025(['dich_vu' => 'KHONG_TON_TAI']), $this->chungTuCt03());
    }

    /** @test */
    public function danh_sach_chung_tu_rong_thi_nem()
    {
        // Mot phong bi khong co chung tu nao la mot goi rong gui len cong: cong nhan, tra
        // MaGD, va ta tuong da gui thanh cong mot ho so von khong co gi ben trong.
        $this->expectException(\InvalidArgumentException::class);

        CtdtPhongBi::dung($this->hoSoCt2025(), []);
    }

    /** @test */
    public function noi_dung_chung_tu_hong_thi_nem_chu_khong_gui_goi_thieu()
    {
        // Neu bo qua mot chung tu hong roi van gui, cong nhan mot ho so THIEU chung tu va
        // tra MaGD - hong im lang, khong lo ra cho toi luc doi soat.
        $this->expectException(\InvalidArgumentException::class);

        CtdtPhongBi::dung(
            ['dich_vu' => 'GBT', 'macskcb' => '01929', 'id_goi_xml' => 'Id-1', 'ngay_lap' => null],
            [['loai_ho_so' => 'GIAYBAOTU', 'noi_dung_goc' => '<GIAYBAOTU>chua dong the']]
        );
    }

    /** @test */
    public function ngay_lap_rong_thi_bo_the_NGAYLAP_chu_khong_ghi_rong()
    {
        $goi = simplexml_load_string(CtdtPhongBi::dung(
            $this->hoSoCt2025(['ngay_lap' => null]),
            $this->chungTuCt03()
        ));

        $this->assertFalse(isset($goi->THONGTINHOSO->NGAYLAP),
            'Thieu NGAYLAP thi bo han the, dung ghi mot the rong');
    }

    /** @test */
    public function ky_tu_dac_biet_trong_ma_co_so_duoc_thoat()
    {
        // macskcb den tu XML ben ngoai. Ghep thang vao chuoi XML la mo duong cho mot gia
        // tri chua '<' pha vo ca tai lieu - hoac te hon, chen them the.
        $xml = CtdtPhongBi::dung($this->hoSoCt2025(['macskcb' => 'A&B']), $this->chungTuCt03());

        $goi = simplexml_load_string($xml);

        $this->assertNotFalse($goi, 'XML phai con hop le');
        $this->assertSame('A&B', (string) $goi->THONGTINDONVI->MACSKCB);
    }
}

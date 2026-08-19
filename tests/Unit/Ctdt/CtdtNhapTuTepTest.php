<?php

namespace Tests\Unit\Ctdt;

use Tests\TestCase;
use Tests\Support\DungBangCtdtSqlite;
use Tests\Support\GoiCtdtMau;
use App\Services\Ctdt\CtdtImporter;
use App\Services\Ctdt\CtdtMacskcb;
use App\Services\Ctdt\Loi\ThieuMacskcbException;
use App\Services\Ctdt\Loi\MacskcbKhongHopLeException;
use App\Models\BHYT\Ctdt\CtdtHoSo;

class CtdtNhapTuTepTest extends TestCase
{
    use DungBangCtdtSqlite;
    use GoiCtdtMau;

    /** @var CtdtImporter */
    private $importer;

    /** @var array duong dan tep tam da tao, de don o tearDown */
    private $tepTam = [];

    protected function setUp()
    {
        parent::setUp();
        $this->chuanBiBangCtdt();
        $this->importer = new CtdtImporter();
        config(['organization.BHYT.ma_cskcb' => '01013']);
    }

    protected function tearDown()
    {
        foreach ($this->tepTam as $duongDan) {
            if (file_exists($duongDan)) {
                unlink($duongDan);
            }
        }

        parent::tearDown();
    }

    private function tepTam($noiDung)
    {
        $duongDan = tempnam(sys_get_temp_dir(), 'ctdt') . '.xml';
        file_put_contents($duongDan, $noiDung);
        $this->tepTam[] = $duongDan;

        return $duongDan;
    }

    /** @test */
    public function nhap_tu_tep_cho_ket_qua_giong_nhap_tu_chuoi()
    {
        $xml = $this->goiCt2025([[$this->chungTu('CT03', ['MA_YTE' => 'YT001'])]]);

        $kq = $this->importer->nhapTuTep($this->tepTam($xml));

        $this->assertTrue($kq->thanhCong, (string) $kq->lyDoThatBai);
        $this->assertSame(['YT001'], $kq->dsMaHoSo);
        $this->assertSame(1, CtdtHoSo::count());
    }

    /** @test */
    public function nhap_tu_tep_ghi_duong_dan_goc_khi_khong_truyen_tuy_chon()
    {
        // Duong dan tep la thu duy nhat noi lai ho so nay den tu dau. Bat nguoi goi tu
        // truyen lai mot lan nua la moi khi mot noi goi quen mat dau vet.
        $xml = $this->goiCt2025([[$this->chungTu('CT03', ['MA_YTE' => 'YT001'])]]);
        $duongDan = $this->tepTam($xml);

        $this->importer->nhapTuTep($duongDan);

        $this->assertSame($duongDan, CtdtHoSo::first()->duong_dan_goc);
    }

    /** @test */
    public function tuy_chon_duong_dan_goc_truyen_vao_thi_thang()
    {
        $xml = $this->goiCt2025([[$this->chungTu('CT03', ['MA_YTE' => 'YT001'])]]);

        $this->importer->nhapTuTep($this->tepTam($xml), ['duong_dan_goc' => 'inbox/goi-1.xml']);

        $this->assertSame('inbox/goi-1.xml', CtdtHoSo::first()->duong_dan_goc);
    }

    /** @test */
    public function tep_khong_doc_duoc_thi_that_bai_som_khong_nem()
    {
        $kq = $this->importer->nhapTuTep('C:\\khong\\ton\\tai\\goi.xml');

        $this->assertFalse($kq->thanhCong);
        $this->assertContains('khong doc duoc', mb_strtolower((string) $kq->lyDoThatBai));
        $this->assertSame(0, CtdtHoSo::count());
    }

    /** @test */
    public function tep_rong_thi_that_bai_som()
    {
        $kq = $this->importer->nhapTuTep($this->tepTam(''));

        $this->assertFalse($kq->thanhCong);
        $this->assertSame(0, CtdtHoSo::count());
    }

    /** @test */
    public function phan_giai_ma_co_so_uu_tien_gia_tri_trong_goi()
    {
        $this->assertSame('01929', CtdtMacskcb::phanGiai('01929', '37470'));
    }

    /** @test */
    public function phan_giai_lui_ve_lua_chon_cua_nguoi_nap()
    {
        $this->assertSame('37470', CtdtMacskcb::phanGiai(null, '37470'));
    }

    /** @test */
    public function phan_giai_lui_ve_cau_hinh_don_vi()
    {
        config(['organization.BHYT.ma_cskcb' => '01013']);

        $this->assertSame('01013', CtdtMacskcb::phanGiai(null, null));
    }

    /** @test */
    public function can_ca_ba_nguon_thi_nem()
    {
        config(['organization.BHYT.ma_cskcb' => '']);

        $this->expectException(ThieuMacskcbException::class);

        CtdtMacskcb::phanGiai(null, null);
    }

    /** @test */
    public function ma_dai_qua_nam_ky_tu_thi_nem()
    {
        $this->expectException(MacskcbKhongHopLeException::class);

        CtdtMacskcb::phanGiai('ABCDEFGHIJ', null);
    }

    /** @test */
    public function nap_de_len_ho_so_da_gui_thi_ket_qua_neu_dich_danh()
    {
        // Nguoi dung duoc phep ghi de, nhung phai BIET minh vua xoa mat trang thai gui cua
        // ho so nao. Im lang o day nghia la mot ho so da doi soat voi BHXH bi mat dau vet
        // ma khong ai hay.
        $xml = $this->goiCt2025([[$this->chungTu('CT03', ['MA_YTE' => 'YT001'])]]);

        $this->importer->nhapTuChuoi($xml);
        CtdtHoSo::where('ma_ho_so', 'YT001')->update([
            'ma_gd'        => 'HS_123456',
            'ma_ket_qua'   => '200',
            'submitted_at' => '2026-08-19 10:00:00',
        ]);

        $kq = $this->importer->nhapTuChuoi($xml);

        $this->assertTrue($kq->thanhCong);
        $this->assertCount(1, $kq->dsGhiDeDaGui);
        $this->assertSame('YT001', $kq->dsGhiDeDaGui[0]['ma_ho_so']);
        $this->assertSame('HS_123456', $kq->dsGhiDeDaGui[0]['ma_gd']);
    }

    /** @test */
    public function ho_so_chua_tung_gui_thi_khong_vao_danh_sach_canh_bao()
    {
        $xml = $this->goiCt2025([[$this->chungTu('CT03', ['MA_YTE' => 'YT001'])]]);

        $this->importer->nhapTuChuoi($xml);
        $kq = $this->importer->nhapTuChuoi($xml);

        $this->assertSame([], $kq->dsGhiDeDaGui);
    }
}

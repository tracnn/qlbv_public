<?php

namespace Tests\Unit\Ctdt;

use Tests\TestCase;
use Tests\Support\DungBangCtdtSqlite;
use Tests\Support\GoiCtdtMau;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use App\Http\Controllers\BHYT\BHYTCtdtController;
use App\Models\BHYT\Ctdt\CtdtHoSo;

class CtdtUploadTest extends TestCase
{
    use DungBangCtdtSqlite;
    use GoiCtdtMau;

    /** @var BHYTCtdtController */
    private $controller;

    private $tepTam = [];

    protected function setUp()
    {
        parent::setUp();
        $this->chuanBiBangCtdt();
        $this->controller = new BHYTCtdtController();
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

    private function tepTaiLen($tenHienThi, $noiDung)
    {
        $duongDan = tempnam(sys_get_temp_dir(), 'up') . '.xml';
        file_put_contents($duongDan, $noiDung);
        $this->tepTam[] = $duongDan;

        // Tham so cuoi = true: bo qua kiem tra "da tai len that qua HTTP chua".
        return new UploadedFile($duongDan, $tenHienThi, 'text/xml', filesize($duongDan), null, true);
    }

    private function yeuCau(array $tep, array $thamSo = [])
    {
        $request = Request::create('/bhyt/ctdt/import/upload', 'POST', $thamSo);
        $request->files->set('xmls', $tep);

        return $request;
    }

    private function layJson($phanHoi)
    {
        return json_decode($phanHoi->getContent(), true);
    }

    /** @test */
    public function tai_len_mot_tep_hop_le()
    {
        $xml = $this->goiCt2025([[$this->chungTu('CT03', ['MA_YTE' => 'YT001'])]]);

        $kq = $this->layJson($this->controller->uploadData(
            $this->yeuCau([$this->tepTaiLen('goi-1.xml', $xml)])
        ));

        $this->assertTrue($kq['thanh_cong']);
        $this->assertCount(1, $kq['chi_tiet']);
        $this->assertSame('goi-1.xml', $kq['chi_tiet'][0]['tep']);
        $this->assertSame(1, $kq['chi_tiet'][0]['so_thanh_cong']);
        $this->assertSame(1, CtdtHoSo::count());
    }

    /** @test */
    public function bao_ket_qua_theo_TUNG_tep_khong_gop_thanh_mot_cau()
    {
        // Tai len 5 tep ma chi bao "co loi" thi nguoi dung phai mo tung tep ra doan.
        $tot = $this->goiCt2025([[$this->chungTu('CT03', ['MA_YTE' => 'YT001'])]]);

        $kq = $this->layJson($this->controller->uploadData($this->yeuCau([
            $this->tepTaiLen('tot.xml', $tot),
            $this->tepTaiLen('hong.xml', '<HSCHUNGTU><chua dong'),
        ])));

        $this->assertFalse($kq['thanh_cong']);
        $this->assertCount(2, $kq['chi_tiet']);

        $theoTen = [];

        foreach ($kq['chi_tiet'] as $ct) {
            $theoTen[$ct['tep']] = $ct;
        }

        $this->assertTrue($theoTen['tot.xml']['thanh_cong']);
        $this->assertFalse($theoTen['hong.xml']['thanh_cong']);
        $this->assertNotEmpty($theoTen['hong.xml']['ly_do']);
        $this->assertSame(1, CtdtHoSo::count(), 'Tep tot van phai vao duoc');
    }

    /** @test */
    public function canh_bao_dich_danh_ho_so_da_gui_bi_ghi_de()
    {
        $xml = $this->goiCt2025([[$this->chungTu('CT03', ['MA_YTE' => 'YT001'])]]);

        $this->controller->uploadData($this->yeuCau([$this->tepTaiLen('lan1.xml', $xml)]));

        CtdtHoSo::where('ma_ho_so', 'YT001')->update([
            'ma_gd' => 'HS_123456', 'ma_ket_qua' => '200',
        ]);

        $kq = $this->layJson($this->controller->uploadData(
            $this->yeuCau([$this->tepTaiLen('lan2.xml', $xml)])
        ));

        $this->assertTrue($kq['thanh_cong']);
        $this->assertCount(1, $kq['chi_tiet'][0]['ghi_de_da_gui']);
        $this->assertSame('YT001', $kq['chi_tiet'][0]['ghi_de_da_gui'][0]['ma_ho_so']);
        $this->assertSame('HS_123456', $kq['chi_tiet'][0]['ghi_de_da_gui'][0]['ma_gd']);
    }

    /** @test */
    public function ma_co_so_nguoi_nap_chon_duoc_truyen_xuong()
    {
        // Goi giay chung sinh khong mang ma co so; o chon tren man nap la duong duy nhat.
        $xml = $this->goiGcs(['MA_GCS' => 'GCS-1']);

        $this->controller->uploadData($this->yeuCau(
            [$this->tepTaiLen('gcs.xml', $xml)],
            ['macskcb' => '01929']
        ));

        $this->assertSame('01929', CtdtHoSo::first()->macskcb);
    }

    /** @test */
    public function khong_co_tep_nao_thi_bao_loi_khong_nem()
    {
        $kq = $this->layJson($this->controller->uploadData($this->yeuCau([])));

        $this->assertFalse($kq['thanh_cong']);
        $this->assertNotEmpty($kq['thong_diep']);
    }

    /** @test */
    public function khong_dang_nhap_van_nap_duoc()
    {
        $xml = $this->goiCt2025([[$this->chungTu('CT03', ['MA_YTE' => 'YT001'])]]);

        $this->controller->uploadData($this->yeuCau([$this->tepTaiLen('goi.xml', $xml)]));

        // Khong dang nhap trong test don vi nen imported_by de trong, nhung cot phai ton tai
        // va khong lam vo luong nap.
        $this->assertSame(1, CtdtHoSo::count());
    }
}

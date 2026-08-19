<?php

namespace Tests\Unit\Ctdt;

use Tests\TestCase;
use Tests\Support\DungBangCtdtSqlite;
use Tests\Support\GoiCtdtMau;
use App\Http\Controllers\BHYT\BHYTCtdtController;
use App\Services\Ctdt\CtdtImporter;
use App\Models\BHYT\Ctdt\CtdtHoSo;
use App\Models\BHYT\Ctdt\CtdtChungTu;
use App\Models\BHYT\Ctdt\CtdtCt03;

class CtdtChiTietTest extends TestCase
{
    use DungBangCtdtSqlite;
    use GoiCtdtMau;

    /** @var BHYTCtdtController */
    private $controller;

    protected function setUp()
    {
        parent::setUp();
        $this->chuanBiBangCtdt();
        $this->controller = new BHYTCtdtController();
        config(['organization.BHYT.ma_cskcb' => '01013']);
    }

    private function napMau()
    {
        $xml = $this->goiCt2025([[
            $this->chungTu('CT03', [
                'MA_YTE' => 'YT001', 'HO_TEN' => 'Nguyen Van Test', 'CHAN_DOAN' => 'Dau bung',
            ]),
            $this->chungTu('CT04', ['MA_CT' => 'CT-1']),
        ]]);

        (new CtdtImporter())->nhapTuChuoi($xml);

        return CtdtHoSo::where('ma_ho_so', 'YT001')->firstOrFail();
    }

    /** @test */
    public function man_chi_tiet_tra_ho_so_va_danh_sach_tab()
    {
        $this->napMau();

        $view = $this->controller->detail('YT001');
        $duLieu = $view->getData();

        $this->assertSame('YT001', $duLieu['hoSo']->ma_ho_so);
        $this->assertSame('bhyt.ctdt.detail', $view->getName());

        $ma = array_column($duLieu['tabs'], 'ma');
        $this->assertContains('CT03', $ma);
        $this->assertContains('CT04', $ma);
        $this->assertContains('__XML__', $ma);
    }

    /** @test */
    public function ma_ho_so_khong_ton_tai_thi_nem()
    {
        // firstOrFail() nem ModelNotFoundException; Laravel chi doi no thanh 404 o tang xu ly
        // ngoai le cua HTTP, ma test nay goi thang controller nen thay ngoai le goc.
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        $this->controller->detail('KHONG_TON_TAI');
    }

    /** @test */
    public function tab_chung_tu_tra_du_nhan_va_gia_tri()
    {
        $this->napMau();

        $duLieu = $this->controller->detailTab('YT001', 'CT03')->getData();

        $this->assertSame('CT03', $duLieu['loai']);
        $this->assertCount(1, $duLieu['banGhi']);

        $dong = $duLieu['banGhi'][0];

        // Moi dong la ['nhan' => nhan tieng Viet, 'gia_tri' => gia tri]
        $theoNhan = [];

        foreach ($dong as $o) {
            $theoNhan[$o['nhan']] = $o['gia_tri'];
        }

        $this->assertSame('Nguyen Van Test', $theoNhan['Họ tên']);
        $this->assertSame('Dau bung', $theoNhan['Chẩn đoán']);
        $this->assertSame('YT001', $theoNhan['Mã y tế']);
    }

    /** @test */
    public function tab_chung_tu_bo_qua_o_trong_de_khoi_lam_nhieu_man_hinh()
    {
        // CT03 co 33 truong; mot ho so that thuong chi dien mot phan. Hien du 33 dong trong
        // do lam nguoi doc phai loc bang mat.
        $this->napMau();

        $duLieu = $this->controller->detailTab('YT001', 'CT03')->getData();

        foreach ($duLieu['banGhi'][0] as $o) {
            $this->assertNotSame('', (string) $o['gia_tri'], 'O trong khong duoc hien');
        }
    }

    /** @test */
    public function tab_xml_goc_tra_noi_dung_nguyen_van()
    {
        $this->napMau();

        $duLieu = $this->controller->detailTab('YT001', '__XML__')->getData();

        $this->assertCount(2, $duLieu['chungTu']);
        $this->assertContains('<MA_YTE>YT001</MA_YTE>', $duLieu['chungTu'][0]->noi_dung_goc);
    }

    /** @test */
    public function tab_ho_so_khong_co_thi_404()
    {
        // Tham so {loai} den tu URL. Khong doi chieu thi ep duoc controller truy van mot
        // bang khong lien quan toi ho so dang xem.
        $this->napMau();

        $this->expectException(\Symfony\Component\HttpKernel\Exception\NotFoundHttpException::class);

        $this->controller->detailTab('YT001', 'GIAYBAOTU');
    }

    /** @test */
    public function xoa_ho_so_don_sach_ca_chung_tu_va_chi_tiet()
    {
        $this->napMau();

        $this->controller->delete('YT001');

        $this->assertSame(0, CtdtHoSo::count());
        $this->assertSame(0, CtdtChungTu::count());
        $this->assertSame(0, CtdtCt03::count());
    }

    /** @test */
    public function xoa_ho_so_khong_ton_tai_thi_nem()
    {
        $this->expectException(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

        $this->controller->delete('KHONG_TON_TAI');
    }

    /** @test */
    public function ma_ho_so_co_dau_thang_van_tra_dung_ho_so()
    {
        // Ho so roi vao nhanh lui GUID co ma dang 'Id-abc#1'. Neu URL khong ma hoa hoac
        // controller cat sai thi man chi tiet tra 404 cho dung nhung ho so kho tim nhat.
        (new CtdtImporter())->nhapTuChuoi(
            $this->goiCt2025([[$this->chungTu('CT04', ['MA_CT' => 'CT-1'])]], ['id' => 'Id-abc'])
        );

        $view = $this->controller->detail('Id-abc#1');

        $this->assertSame('Id-abc#1', $view->getData()['hoSo']->ma_ho_so);
    }
}

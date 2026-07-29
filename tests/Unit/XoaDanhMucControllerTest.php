<?php

namespace Tests\Unit;

use App\Http\Controllers\Category\CategoryBHYTController;
use Illuminate\Http\Request;
use Tests\TestCase;

/**
 * Phu tang controller cua chuc nang xoa danh muc — XoaDanhMucTest chi phu ham thuan
 * dieuKien(), khong cham toi rao chan phia may chu that su.
 *
 * TUYET DOI KHONG duoc de test nao roi toi nhanh xoa/dem chay that tren CSDL: moi ca duoi
 * day deu dung o nhanh 422 (loi dau vao), TRUOC khi truyVanXoa() dung toi DB::table(...).
 */
class XoaDanhMucControllerTest extends TestCase
{
    protected function controller()
    {
        return $this->app->make(CategoryBHYTController::class);
    }

    /** @test */
    public function xoa_danh_muc_tra_422_khi_thieu_xac_nhan()
    {
        $req = Request::create('/category/bhyt/xoa-danh-muc', 'POST', [
            'loai' => 'icd10',
        ]);

        $res = $this->controller()->xoaDanhMuc($req);

        $this->assertSame(422, $res->getStatusCode());
        $this->assertSame('Phải gõ đúng chữ XOA để xác nhận', $res->getData()->message);
    }

    /** @test */
    public function xoa_danh_muc_tra_422_khi_xac_nhan_sai_hoa_thuong()
    {
        // "xoa" chu thuong khong duoc chap nhan: phai go dung "XOA" hoa toan bo, tranh
        // bam nham do go tat.
        $req = Request::create('/category/bhyt/xoa-danh-muc', 'POST', [
            'loai' => 'icd10',
            'xac_nhan' => 'xoa',
        ]);

        $res = $this->controller()->xoaDanhMuc($req);

        $this->assertSame(422, $res->getStatusCode());
    }

    /** @test */
    public function dem_xoa_danh_muc_tra_422_khi_ma_cskcb_khong_hop_le()
    {
        // Ma khong nam trong DanhSachCoSo::danhSach(): controller phai chan truoc khi
        // dung toi truy van xoa.
        $req = Request::create('/category/bhyt/xoa-danh-muc/dem', 'GET', [
            'loai' => 'medicine',
            'ma_cskcb' => 'ma-khong-ton-tai-xyz',
        ]);

        $res = $this->controller()->demXoaDanhMuc($req);

        $this->assertSame(422, $res->getStatusCode());
        $this->assertSame('Cơ sở khám chữa bệnh không hợp lệ', $res->getData()->message);
    }

    /** @test */
    public function dem_xoa_danh_muc_tra_422_khi_loai_khong_ton_tai()
    {
        $req = Request::create('/category/bhyt/xoa-danh-muc/dem', 'GET', [
            'loai' => 'khong_co_loai_nay',
        ]);

        $res = $this->controller()->demXoaDanhMuc($req);

        $this->assertSame(422, $res->getStatusCode());
        $this->assertSame('Loai danh muc khong hop le', $res->getData()->message);
    }

    /** @test */
    public function xoa_danh_muc_tra_422_khi_loai_khong_ton_tai_du_da_xac_nhan_dung()
    {
        // Xac nhan dung "XOA" nhung loai sai: van phai chan o buoc dung dieu kien, khong
        // duoc di toi delete().
        $req = Request::create('/category/bhyt/xoa-danh-muc', 'POST', [
            'loai' => 'khong_co_loai_nay',
            'xac_nhan' => 'XOA',
        ]);

        $res = $this->controller()->xoaDanhMuc($req);

        $this->assertSame(422, $res->getStatusCode());
        $this->assertSame('Loai danh muc khong hop le', $res->getData()->message);
    }
}

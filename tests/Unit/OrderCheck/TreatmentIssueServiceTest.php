<?php

namespace Tests\Unit\OrderCheck;

use App\Services\OrderCheck\TreatmentIssueService;
use Illuminate\Support\Facades\DB;
use Tests\Support\DungBangLoiDotDieuTriSqlite;
use Tests\TestCase;

class TreatmentIssueServiceTest extends TestCase
{
    use DungBangLoiDotDieuTriSqlite;

    protected function setUp()
    {
        parent::setUp();

        $this->chuanBiBangLoi();
    }

    protected function dichVu()
    {
        return new TreatmentIssueService();
    }

    /** @test */
    public function loc_vi_pham_theo_ma_dot_dieu_tri()
    {
        $this->themViPham(['treatment_code' => '01013250800123']);
        $this->themViPham(['treatment_code' => 'DOT-KHAC', 'treatment_id' => 9002]);

        $ketQua = $this->dichVu()->cua('01013250800123');

        $this->assertCount(1, $ketQua['data']['order_check']);
        $this->assertEquals('01013250800123', $ketQua['data']['treatment_code']);
    }

    /** @test */
    public function mac_dinh_bo_dong_false_positive()
    {
        $this->themViPham(['status' => 'new']);
        $this->themViPham(['status' => 'false_positive']);

        $ketQua = $this->dichVu()->cua('01013250800123');

        $this->assertCount(1, $ketQua['data']['order_check']);
        $this->assertEquals('new', $ketQua['data']['order_check'][0]['status']);
    }

    /** @test */
    public function truyen_status_tuong_minh_thi_lay_dung_trang_thai_do()
    {
        $this->themViPham(['status' => 'new']);
        $this->themViPham(['status' => 'false_positive']);

        $ketQua = $this->dichVu()->cua('01013250800123', null, ['status' => 'false_positive']);

        $this->assertCount(1, $ketQua['data']['order_check']);
        $this->assertEquals('false_positive', $ketQua['data']['order_check'][0]['status']);
    }

    /** @test */
    public function detail_json_duoc_giai_ma_thanh_mang()
    {
        $this->themViPham(['detail' => '{"ma_dv":"XN001"}']);

        $ketQua = $this->dichVu()->cua('01013250800123');

        $this->assertEquals(['ma_dv' => 'XN001'], $ketQua['data']['order_check'][0]['detail']);
    }

    /**
     * Mot dong detail hong khong duoc lam chet ca lan goi API.
     *
     * @test
     */
    public function detail_hong_thi_tra_null_chu_khong_nem_loi()
    {
        $this->themViPham(['detail' => '{khong-phai-json']);

        $ketQua = $this->dichVu()->cua('01013250800123');

        $this->assertNull($ketQua['data']['order_check'][0]['detail']);
    }

    /** @test */
    public function dot_sach_tra_ba_mang_rong()
    {
        $ketQua = $this->dichVu()->cua('KHONG-CO-DOT-NAY');

        $this->assertSame([], $ketQua['data']['order_check']);
        $this->assertSame([], $ketQua['data']['hein_card']);
        $this->assertSame([], $ketQua['data']['xml3176']);
    }
}

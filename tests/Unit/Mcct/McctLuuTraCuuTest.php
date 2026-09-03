<?php

namespace Tests\Unit\Mcct;

use App\Models\Mcct\McctChiPhi;
use App\Models\Mcct\McctTraCuu;
use App\Services\Mcct\KetQuaMcct;
use App\Services\Mcct\McctLuuTraCuu;
use Tests\TestCase;

class McctLuuTraCuuTest extends TestCase
{
    /**
     * Don dep bang tay theo dung ban ghi minh tao ra.
     *
     * KHONG dung RefreshDatabase hay DatabaseMigrations: ngay 2026-08-21 mot test dung
     * DatabaseMigrations da DROP sach CSDL phat trien. Xoa theo id la du.
     */
    protected $daTao = [];

    protected function tearDown()
    {
        foreach ($this->daTao as $id) {
            McctChiPhi::where('tra_cuu_id', $id)->delete();
            McctTraCuu::where('id', $id)->delete();
        }

        parent::tearDown();
    }

    protected function thamSo()
    {
        return [
            'ma_cskcb' => '01929',
            'ma_the' => 'DN4010100000001',
            'ho_ten' => 'NGUYEN VAN A',
            'ngay_sinh' => '01/01/1990',
            'nguon' => 'thu_cong',
            'tra_boi' => 'kiemthu',
            'nguong' => 14040000.0,
            'du_dieu_kien' => false,
        ];
    }

    protected function ketQua200()
    {
        return KetQuaMcct::tuMang([
            'MaKetQua' => '200',
            'GhiChu' => 'Nguồn DL ... tính đến: 05/08/2026 17:30',
            'DataCCT' => [
                [
                    'Id' => 123456, 'ngayTraCuu' => '10/08/2026',
                    'maThe' => 'DN4010100000001', 'maCskcb' => '01001',
                    'ngayVao' => '02/04/2026', 'ngayRa' => '05/04/2026',
                    'maDoiTuongKCB' => 'DN',
                    'tBNCCTMCCT' => 250000, 'tBNCCTLuyKe' => 1250000,
                    'ngayNhanCong' => '10/04/2026', 'ngayNhan' => '10/04/2026',
                ],
                [
                    'Id' => 123457, 'ngayTraCuu' => '10/08/2026',
                    'maThe' => 'DN4010100000001', 'maCskcb' => '01001',
                    'ngayVao' => '01/02/2026', 'ngayRa' => '03/02/2026',
                    'maDoiTuongKCB' => 'DN',
                    'tBNCCTMCCT' => 100000, 'tBNCCTLuyKe' => 900000,
                    'ngayNhanCong' => '05/02/2026', 'ngayNhan' => '05/02/2026',
                ],
            ],
            'ThongTinSoThe' => [
                'hoTen' => 'Nguyễn Văn A', 'ngaySinh' => '01/01/1990',
                'ngayKetThuc' => '31/12/2026', 'maBhxh' => '0100000001',
            ],
        ]);
    }

    /** @test */
    public function luu_phien_tra_va_du_cac_dong_chi_phi()
    {
        $ban = McctLuuTraCuu::luu($this->ketQua200(), $this->thamSo());
        $this->daTao[] = $ban->id;

        $this->assertSame('200', $ban->ma_ket_qua);
        $this->assertSame('01929', $ban->ma_cskcb);
        $this->assertSame('0100000001', $ban->the_ma_bhxh);
        $this->assertSame(2, McctChiPhi::where('tra_cuu_id', $ban->id)->count());
    }

    /** GhiChu phai luu NGUYEN VAN - no chua moc thoi gian cua du lieu */
    /** @test */
    public function luu_ghi_chu_nguyen_van()
    {
        $ban = McctLuuTraCuu::luu($this->ketQua200(), $this->thamSo());
        $this->daTao[] = $ban->id;

        $this->assertContains('tính đến: 05/08/2026 17:30', $ban->ghi_chu);
    }

    /** @test */
    public function luu_luy_ke_lon_nhat_chu_khong_lay_dong_dau()
    {
        $ban = McctLuuTraCuu::luu($this->ketQua200(), $this->thamSo());
        $this->daTao[] = $ban->id;

        $this->assertEquals(1250000, $ban->luy_ke_lon_nhat);
    }

    /**
     * Nguong duoc GHI VAO BANG, khong tinh lai luc doc. Doi bang luong trong config sau do
     * doc lai ban ghi cu phai ra dung con so cu.
     */
    /** @test */
    public function ghi_nguong_vao_bang_chu_khong_tinh_lai()
    {
        $ban = McctLuuTraCuu::luu($this->ketQua200(), $this->thamSo());
        $this->daTao[] = $ban->id;

        config(['mcct.luong_co_so' => ['2023-07-01' => 99999999]]);

        $docLai = McctTraCuu::find($ban->id);

        $this->assertEquals(14040000, $docLai->nguong_ap_dung);
        $this->assertSame(0, (int) $docLai->du_dieu_kien_mien);
    }

    /**
     * Ma 204 van phai co mot dong phien tra - khong co dong chi phi nao. Bo qua lan hong la
     * mat dung thu can den khi di hoi cong.
     */
    /** @test */
    public function ma_204_van_luu_phien_tra_khong_co_dong_nao()
    {
        $kq = KetQuaMcct::tuMang([
            'MaKetQua' => '204',
            'GhiChu' => 'Không tìm thấy dữ liệu!',
            'DataCCT' => null,
            'ThongTinSoThe' => null,
        ]);

        $thamSo = $this->thamSo();
        $thamSo['du_dieu_kien'] = null;

        $ban = McctLuuTraCuu::luu($kq, $thamSo);
        $this->daTao[] = $ban->id;

        $this->assertSame('204', $ban->ma_ket_qua);
        $this->assertNull($ban->the_ma_bhxh);
        $this->assertSame(0, McctChiPhi::where('tra_cuu_id', $ban->id)->count());
    }
}

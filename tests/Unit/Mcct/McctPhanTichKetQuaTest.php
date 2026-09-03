<?php

namespace Tests\Unit\Mcct;

use App\Services\Mcct\KetQuaMcct;
use Tests\TestCase;

class McctPhanTichKetQuaTest extends TestCase
{
    /** Nguyen van vi du trong phu luc mo ta API, khong rut gon */
    protected function jsonThanhCong()
    {
        return [
            'MaKetQua' => '200',
            'GhiChu' => 'Nguồn DL lấy từ các CSKCB đề nghị thanh toán KCB BHYT trên HTTTGĐ BHYT tính đến: 05/08/2026 17:30',
            'DataCCT' => [
                [
                    'Id' => 123456,
                    'ngayTraCuu' => '10/08/2026',
                    'maThe' => 'DN4010100000001',
                    'maCskcb' => '01001',
                    'ngayVao' => '02/04/2026',
                    'ngayRa' => '05/04/2026',
                    'maDoiTuongKCB' => 'DN',
                    'tBNCCTMCCT' => 250000,
                    'tBNCCTLuyKe' => 1250000,
                    'ngayNhanCong' => '10/04/2026',
                    'ngayNhan' => '10/04/2026',
                    'duPhong1' => '', 'duPhong2' => '', 'duPhong3' => '',
                    'duPhong4' => '', 'duPhong5' => '',
                ],
            ],
            'ThongTinSoThe' => [
                'hoTen' => 'Nguyễn Văn A',
                'ngaySinh' => '01/01/1990',
                'ngayKetThuc' => '31/12/2026',
                'maBhxh' => '0100000001',
                'duPhong1' => '', 'duPhong2' => '', 'duPhong3' => '',
                'duPhong4' => '', 'duPhong5' => '',
            ],
        ];
    }

    /** @test */
    public function doc_dung_ma_ket_qua_va_ghi_chu()
    {
        $kq = KetQuaMcct::tuMang($this->jsonThanhCong());

        $this->assertSame('200', $kq->maKetQua);
        $this->assertTrue($kq->thanhCong());
        $this->assertContains('tính đến: 05/08/2026 17:30', $kq->ghiChu);
    }

    /** @test */
    public function doc_dung_thong_tin_the()
    {
        $kq = KetQuaMcct::tuMang($this->jsonThanhCong());

        $this->assertSame('Nguyễn Văn A', $kq->thongTinThe['ho_ten']);
        $this->assertSame('01/01/1990', $kq->thongTinThe['ngay_sinh']);
        $this->assertSame('2026-12-31', $kq->thongTinThe['ngay_ket_thuc']);
        $this->assertSame('0100000001', $kq->thongTinThe['ma_bhxh']);
    }

    /** @test */
    public function doi_ngay_sang_dang_csdl_va_tien_sang_so()
    {
        $kq = KetQuaMcct::tuMang($this->jsonThanhCong());
        $d = $kq->dong[0];

        $this->assertSame(123456, $d['id_cong']);
        $this->assertSame('2026-04-02', $d['ngay_vao']);
        $this->assertSame('2026-04-05', $d['ngay_ra']);
        $this->assertSame(250000.0, $d['t_bn_cct_mcct']);
        $this->assertSame(1250000.0, $d['t_bn_cct_luy_ke']);
    }

    /** Nam truong duPhong luon rong theo phu luc - khong duoc mang vao DTO */
    /** @test */
    public function bo_qua_cac_truong_du_phong()
    {
        $kq = KetQuaMcct::tuMang($this->jsonThanhCong());

        $this->assertArrayNotHasKey('duPhong1', $kq->dong[0]);
        $this->assertArrayNotHasKey('duPhong1', $kq->thongTinThe);
    }

    /** Phu luc ghi ro: chuoi rong khi khong co gia tri. Phai thanh null, khong phai '0000-00-00' */
    /** @test */
    public function chuoi_ngay_rong_thanh_null()
    {
        $json = $this->jsonThanhCong();
        $json['DataCCT'][0]['ngayRa'] = '';

        $kq = KetQuaMcct::tuMang($json);

        $this->assertNull($kq->dong[0]['ngay_ra']);
    }

    /** Cong co the tra so duoi dang chuoi - khong duoc de lot xuong CSDL thanh chuoi */
    /** @test */
    public function tien_dang_chuoi_van_thanh_so()
    {
        $json = $this->jsonThanhCong();
        $json['DataCCT'][0]['tBNCCTLuyKe'] = '1250000.50';

        $kq = KetQuaMcct::tuMang($json);

        $this->assertSame(1250000.5, $kq->dong[0]['t_bn_cct_luy_ke']);
    }

    /**
     * Ma 204: phu luc ghi ro DataCCT va ThongTinSoThe deu tra ve NULL. Truoc day mot mang
     * null vao foreach la loi chet nguoi - phai chiu duoc.
     */
    /** @test */
    public function ma_204_voi_du_lieu_null_khong_no()
    {
        $kq = KetQuaMcct::tuMang([
            'MaKetQua' => '204',
            'GhiChu' => 'Không tìm thấy dữ liệu!',
            'DataCCT' => null,
            'ThongTinSoThe' => null,
        ]);

        $this->assertSame('204', $kq->maKetQua);
        $this->assertFalse($kq->thanhCong());
        $this->assertSame([], $kq->dong);
        $this->assertSame([], $kq->thongTinThe);
        $this->assertSame(0.0, $kq->luyKeLonNhat());
    }

    /** Phan hoi thieu han khoa (vd 401 than rong da duoc dung thanh mang) cung khong duoc no */
    /** @test */
    public function mang_rong_khong_no()
    {
        $kq = KetQuaMcct::tuMang([]);

        $this->assertSame('', $kq->maKetQua);
        $this->assertSame([], $kq->dong);
        $this->assertSame(0.0, $kq->luyKeLonNhat());
    }

    /** @test */
    public function luy_ke_lon_nhat_lay_gia_tri_max_chu_khong_lay_dong_dau()
    {
        $json = $this->jsonThanhCong();
        $json['DataCCT'][] = $json['DataCCT'][0];
        $json['DataCCT'][1]['tBNCCTLuyKe'] = 3000000;

        $kq = KetQuaMcct::tuMang($json);

        $this->assertSame(3000000.0, $kq->luyKeLonNhat());
    }
}

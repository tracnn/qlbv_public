<?php

namespace Tests\Unit\Mcct;

use App\Services\Mcct\KetQuaMcct;
use App\Services\Mcct\McctPhanHoiJson;
use Tests\TestCase;

class McctPhanHoiJsonTest extends TestCase
{
    protected function ketQua($maKetQua, $ghiChu, array $dong = [], $the = null)
    {
        return KetQuaMcct::tuMang([
            'MaKetQua' => $maKetQua,
            'GhiChu' => $ghiChu,
            'DataCCT' => $dong,
            'ThongTinSoThe' => $the,
        ]);
    }

    protected function motDong($luyKe)
    {
        return [
            'Id' => 123456, 'ngayTraCuu' => '10/08/2026',
            'maThe' => 'DN4010100000001', 'maCskcb' => '01001',
            'ngayVao' => '02/04/2026', 'ngayRa' => '05/04/2026',
            'maDoiTuongKCB' => 'DN',
            'tBNCCTMCCT' => 250000, 'tBNCCTLuyKe' => $luyKe,
            'ngayNhanCong' => '10/04/2026', 'ngayNhan' => '10/04/2026',
        ];
    }

    /** @test */
    public function ma_200_thi_ok_va_khong_co_thong_bao()
    {
        $kq = $this->ketQua('200', 'tính đến: 05/08/2026 17:30', [$this->motDong(1250000)], [
            'hoTen' => 'Nguyễn Văn A', 'ngaySinh' => '01/01/1990',
            'ngayKetThuc' => '31/12/2026', 'maBhxh' => '0100000001',
        ]);

        $ra = McctPhanHoiJson::tuKetQua($kq, 14040000.0, false, '01929');

        $this->assertTrue($ra['ok']);
        $this->assertSame('200', $ra['ma_ket_qua']);
        $this->assertNull($ra['thong_bao'], 'Tra cuu thanh cong thi khong co thong bao loi');
        $this->assertSame(1250000.0, $ra['luy_ke']);
        $this->assertSame(14040000.0, $ra['nguong']);
        $this->assertFalse($ra['du_dieu_kien']);
        $this->assertCount(1, $ra['dong']);
        $this->assertSame('0100000001', $ra['thong_tin_the']['ma_bhxh']);
    }

    /**
     * GhiChu phai di NGUYEN VAN sang JSON: no chua moc "du lieu tinh den ...", va man hinh
     * bat buoc hien moc do truoc khi nguoi dung ket luan voi nguoi benh.
     */
    /** @test */
    public function ghi_chu_di_nguyen_van_sang_json()
    {
        $kq = $this->ketQua('200', 'Nguồn DL ... tính đến: 05/08/2026 17:30');

        $ra = McctPhanHoiJson::tuKetQua($kq, 14040000.0, false, '01929');

        $this->assertSame('Nguồn DL ... tính đến: 05/08/2026 17:30', $ra['ghi_chu']);
    }

    /**
     * Ma 204 noi duoc HAI kha nang. Cong dung chung mot ma cho hai chuyen khac nhau; noi gop
     * thanh "the sai" la day nguoi dung di sua cai khong sai.
     */
    /** @test */
    public function ma_204_noi_ro_hai_kha_nang()
    {
        $ra = McctPhanHoiJson::tuKetQua($this->ketQua('204', 'Không tìm thấy dữ liệu!'), 0.0, null, '01929');

        $this->assertFalse($ra['ok']);
        $this->assertSame('warning', $ra['muc_do']);
        $this->assertContains('sai thông tin thẻ', $ra['thong_bao']);
        $this->assertContains('chưa phát sinh', $ra['thong_bao']);
    }

    /**
     * Ma 500 tach lam hai theo GhiChu: tai khoan bi han che tra cuu la van de TAI KHOAN,
     * khong phai loi he thong. Gop chung se day nguoi doc di do nham huong hang gio.
     */
    /** @test */
    public function ma_500_han_che_tra_cuu_neu_ro_co_so()
    {
        $kq = $this->ketQua('500', 'Có lỗi xảy ra trong quá trình tra cứu!');

        $ra = McctPhanHoiJson::tuKetQua($kq, 0.0, null, '01929');

        $this->assertSame('danger', $ra['muc_do']);
        $this->assertContains('hạn chế tra cứu', $ra['thong_bao']);
        $this->assertContains('01929', $ra['thong_bao'], 'Phai neu ro co so nao bi han che');
    }

    /** @test */
    public function ma_500_loi_he_thong_khong_bi_nham_thanh_han_che_tra_cuu()
    {
        $kq = $this->ketQua('500', 'Có lỗi hệ thống xảy ra trong quá trình xử lý!');

        $ra = McctPhanHoiJson::tuKetQua($kq, 0.0, null, '01929');

        $this->assertSame('danger', $ra['muc_do']);
        $this->assertNotContains('hạn chế tra cứu', $ra['thong_bao']);
        $this->assertContains('500', $ra['thong_bao']);
    }

    /** @test */
    public function loi_tra_ve_khung_du_khoa_de_javascript_khong_vo()
    {
        $ra = McctPhanHoiJson::loi('Không kết nối được cổng BHXH');

        foreach (['ok', 'ma_ket_qua', 'ghi_chu', 'thong_tin_the', 'dong', 'luy_ke', 'nguong',
            'du_dieu_kien', 'thong_bao', 'muc_do'] as $khoa) {
            $this->assertArrayHasKey($khoa, $ra, "Thieu khoa $khoa - javascript se doc undefined");
        }

        $this->assertFalse($ra['ok']);
        $this->assertSame([], $ra['dong']);
        $this->assertSame('danger', $ra['muc_do']);
        $this->assertSame('Không kết nối được cổng BHXH', $ra['thong_bao']);
    }

    /**
     * Khung khoa cua nhanh loi phai TRUNG khung cua nhanh thanh cong - javascript doc mot
     * kieu du lieu duy nhat, khong phai doan xem lan nay tra ve hinh dang nao.
     */
    /** @test */
    public function khung_khoa_cua_loi_va_ket_qua_giong_nhau()
    {
        $co = McctPhanHoiJson::tuKetQua($this->ketQua('200', 'x'), 1.0, true, '01929');
        $khong = McctPhanHoiJson::loi('x');

        $this->assertSame(array_keys($co), array_keys($khong));
    }
}

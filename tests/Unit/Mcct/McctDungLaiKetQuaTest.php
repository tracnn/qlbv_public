<?php

namespace Tests\Unit\Mcct;

use App\Services\Mcct\McctDungLaiKetQua;
use App\Services\Mcct\McctPhanHoiJson;
use Tests\TestCase;

class McctDungLaiKetQuaTest extends TestCase
{
    protected function bangLuong()
    {
        return [
            '2023-07-01' => 1800000,
            '2024-07-01' => 2340000,
            '2026-07-01' => 2530000,
        ];
    }

    protected function phien($ghiDe = [])
    {
        return array_merge([
            'id' => 10,
            'ma_cskcb' => '01929',
            'ma_the' => 'HT3382797052765',
            'ma_ket_qua' => '200',
            'ghi_chu' => 'Nguồn DL ... tính đến: 14/08/2026 14:41',
            'the_ho_ten' => 'Trần Thị Vân',
            'the_ngay_sinh' => '20/10/1964',
            'the_ngay_ket_thuc' => '2027-06-30',
            'the_ma_bhxh' => '03800',
            'luy_ke_lon_nhat' => '3862166.00',
            'nguong_ap_dung' => '15180000.00',
            'du_dieu_kien_mien' => 0,
            'tra_luc' => '2026-09-03 16:23:35',
        ], $ghiDe);
    }

    protected function dong()
    {
        return [
            ['id_cong' => 3131600192, 'ma_the' => 'HT3382797052765', 'ma_cskcb' => '01929',
                'ngay_vao' => '2026-08-13', 'ngay_ra' => '2026-08-14', 'ma_doi_tuong_kcb' => '1.5',
                't_bn_cct_mcct' => '893973.00', 't_bn_cct_luy_ke' => '3862166.00',
                'ngay_nhan_cong' => '2026-08-14', 'ngay_nhan' => '2026-08-14',
                'ngay_tra_cuu' => '2026-09-03'],
            ['id_cong' => 3090063339, 'ma_the' => 'HT3382797052765', 'ma_cskcb' => '01929',
                'ngay_vao' => '2026-06-12', 'ngay_ra' => '2026-06-23', 'ma_doi_tuong_kcb' => '1.5',
                't_bn_cct_mcct' => '1120157.00', 't_bn_cct_luy_ke' => '1396758.00',
                'ngay_nhan_cong' => '2026-06-23', 'ngay_nhan' => '2026-06-23',
                'ngay_tra_cuu' => '2026-09-03'],
        ];
    }

    /**
     * Khung khoa phai TRUNG khung cua duong goi cong that (cong them hai khoa cua cache).
     * Javascript dung CHUNG mot bo dung ket qua cho ca hai duong; lech mot khoa la mot cho
     * hien 'undefined' tren man hinh.
     */
    /** @test */
    public function khung_khoa_trung_voi_duong_goi_cong_that()
    {
        $cache = McctDungLaiKetQua::tuBanGhi($this->phien(), $this->dong(), $this->bangLuong(), 6);
        $that = McctPhanHoiJson::loi('x');

        foreach (array_keys($that) as $khoa) {
            $this->assertArrayHasKey($khoa, $cache, "Thieu khoa $khoa so voi duong goi that");
        }

        $this->assertArrayHasKey('tu_cache', $cache);
        $this->assertArrayHasKey('tra_luc', $cache);
    }

    /** @test */
    public function dung_lai_du_thong_tin_the_va_cac_dot()
    {
        $ra = McctDungLaiKetQua::tuBanGhi($this->phien(), $this->dong(), $this->bangLuong(), 6);

        $this->assertTrue($ra['ok']);
        $this->assertTrue($ra['tu_cache']);
        $this->assertSame('2026-09-03 16:23:35', $ra['tra_luc']);
        $this->assertSame('200', $ra['ma_ket_qua']);
        $this->assertContains('tính đến: 14/08/2026 14:41', $ra['ghi_chu']);

        $this->assertSame('Trần Thị Vân', $ra['thong_tin_the']['ho_ten']);
        $this->assertSame('03800', $ra['thong_tin_the']['ma_bhxh']);

        $this->assertCount(2, $ra['dong']);
        $this->assertSame(893973.0, $ra['dong'][0]['t_bn_cct_mcct']);
        $this->assertSame(3862166.0, $ra['dong'][0]['t_bn_cct_luy_ke']);
        $this->assertSame('2026-08-14', $ra['dong'][0]['ngay_ra']);
    }

    /** Tien trong CSDL la chuoi decimal - khong duoc de lot xuong javascript thanh chuoi */
    /** @test */
    public function tien_doc_tu_csdl_thanh_so()
    {
        $ra = McctDungLaiKetQua::tuBanGhi($this->phien(), $this->dong(), $this->bangLuong(), 6);

        $this->assertInternalType('float', $ra['dong'][0]['t_bn_cct_luy_ke']);
        $this->assertInternalType('float', $ra['luy_ke']);
        $this->assertSame(3862166.0, $ra['luy_ke']);
    }

    /**
     * DIEM COT LOI: nguong tinh lai theo NGAY TRA CUA LAN DO, khong phai hom nay.
     *
     * Tinh theo hom nay thi mot ban ghi cu se doi ket luan moi khi luong co so thay doi -
     * dung cai ma nguyen tac "ghi nguong vao bang chu khong tinh lai" da chan tu dau.
     */
    /** @test */
    public function nguong_tinh_theo_ngay_tra_cua_lan_do()
    {
        $ra = McctDungLaiKetQua::tuBanGhi($this->phien(), $this->dong(), $this->bangLuong(), 6);

        // Ngay tra 03/9/2026 -> luong hien hanh 2.530.000, co moc doi luong 01/7/2026.
        $this->assertSame(2530000, $ra['muc']['luong_hien_tai']);
        $this->assertTrue($ra['muc']['co_doi_luong']);
        $this->assertSame('2026-07-01', $ra['muc']['moc_doi_luong']);
        $this->assertEquals(1396758.0, $ra['muc']['da_dong_truoc_moc'], '', 0.01);
        $this->assertEquals(15066588.03, $ra['muc']['tong_nguong_ca_nam'], '', 0.05);
        $this->assertEquals(11204422.03, $ra['muc']['con_thieu'], '', 0.05);
    }

    /** @test */
    public function ban_ghi_tra_nam_truoc_dung_luong_co_so_nam_do()
    {
        $ra = McctDungLaiKetQua::tuBanGhi(
            $this->phien(['tra_luc' => '2025-05-20 09:00:00']), $this->dong(), $this->bangLuong(), 6);

        $this->assertSame(2340000, $ra['muc']['luong_hien_tai'], 'Nam 2025 chua co moc 01/7/2026');
        $this->assertFalse($ra['muc']['co_doi_luong']);
    }

    /** @test */
    public function khong_co_ban_ghi_thi_bao_ro_de_javascript_tu_goi_cong()
    {
        $ra = McctDungLaiKetQua::khongCo();

        $this->assertFalse($ra['ok']);
        $this->assertTrue($ra['tu_cache']);
        $this->assertFalse($ra['co_du_lieu']);
        $this->assertNull($ra['tra_luc']);
        $this->assertSame([], $ra['dong']);
    }

    /** Ban ghi co that thi co_du_lieu phai la true - javascript phan biet bang dung khoa nay */
    /** @test */
    public function co_ban_ghi_thi_co_du_lieu_la_true()
    {
        $ra = McctDungLaiKetQua::tuBanGhi($this->phien(), $this->dong(), $this->bangLuong(), 6);

        $this->assertTrue($ra['co_du_lieu']);
    }

    /** Phien khong co dot nao (ma 200 nhung bang chi phi rong) khong duoc lam vo phep tinh */
    /** @test */
    public function phien_khong_co_dot_nao_khong_no()
    {
        $ra = McctDungLaiKetQua::tuBanGhi($this->phien(), [], $this->bangLuong(), 6);

        $this->assertTrue($ra['ok']);
        $this->assertSame([], $ra['dong']);
        $this->assertSame(0.0, $ra['luy_ke']);
        $this->assertFalse($ra['muc']['du_dieu_kien']);
    }
}

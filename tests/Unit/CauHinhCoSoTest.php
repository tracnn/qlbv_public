<?php

namespace Tests\Unit;

use App\Services\BHYT\CauHinhCoSo;
use Tests\TestCase;

class CauHinhCoSoTest extends TestCase
{
    protected function ds()
    {
        return [
            '01929' => [
                'username' => 'u1929', 'password' => 'p1929',
                'ho_ten_cb' => 'Nguyen Van A', 'cccd_cb' => '001',
            ],
            '37470' => [
                'username' => 'u37470', 'password' => 'p37470',
                'ho_ten_cb' => 'Tran Thi B', 'cccd_cb' => '002',
            ],
            '01283' => ['username' => 'u01283'],   // thieu password
        ];
    }

    /** @test */
    public function co_so_khai_du_thi_tra_dung_bo()
    {
        $c = CauHinhCoSo::cua('01929', $this->ds());

        $this->assertSame('u1929', $c['username']);
        $this->assertSame('p1929', $c['password']);
        $this->assertSame('Nguyen Van A', $c['ho_ten_cb']);
        $this->assertSame('001', $c['cccd_cb']);
    }

    /**
     * KHONG duoc roi ve tai khoan mac dinh: tra bang tai khoan cua co so khac chinh la
     * thu lam ket qua khong hop le.
     */
    /** @test */
    public function co_so_chua_khai_thi_nem_ngoai_le()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageRegExp('/99999/');

        CauHinhCoSo::cua('99999', $this->ds());
    }

    /** @test */
    public function thieu_mat_khau_thi_nem_ngoai_le()
    {
        $this->expectException(\InvalidArgumentException::class);

        CauHinhCoSo::cua('01283', $this->ds());
    }

    /**
     * Khoi BHYT_CO_SO xuat xuong voi cac o de RONG (nguoi van hanh dien sau). Chuoi rong phai
     * bi chan y het khoa thieu, neu khong he thong lang le goi cong bang tai khoan trong.
     */
    /** @test */
    public function mat_khau_rong_thi_nem_ngoai_le()
    {
        $this->expectException(\InvalidArgumentException::class);

        CauHinhCoSo::cua('01929', [
            '01929' => ['username' => 'u', 'password' => '', 'ho_ten_cb' => '', 'cccd_cb' => ''],
        ]);
    }

    /** @test */
    public function username_rong_thi_nem_ngoai_le()
    {
        $this->expectException(\InvalidArgumentException::class);

        CauHinhCoSo::cua('01929', [
            '01929' => ['username' => '', 'password' => 'p', 'ho_ten_cb' => '', 'cccd_cb' => ''],
        ]);
    }

    /** @test */
    public function ma_co_so_rong_thi_nem_ngoai_le()
    {
        $this->expectException(\InvalidArgumentException::class);

        CauHinhCoSo::cua('', $this->ds());
    }

    /** @test */
    public function ma_co_so_null_thi_nem_ngoai_le()
    {
        $this->expectException(\InvalidArgumentException::class);

        CauHinhCoSo::cua(null, $this->ds());
    }

    /**
     * Chap nhan CA HAI cach dat ten. Khoi BHYT cu trong cung tep config dung hoTenCb/cccdCb
     * nen nguoi khai rat de viet theo kieu do; chi nhan ho_ten_cb thi khoa sai se tra chuoi
     * rong TRONG IM LANG va loi chi lo ra o thong bao "Null hoTenCb" cua cong BHXH.
     */
    /** @test */
    public function nhan_ca_hai_cach_dat_ten_ho_ten_cb()
    {
        $ds = ['01929' => [
            'username' => 'u', 'password' => 'p',
            'hoTenCb' => 'Nguyen Van A', 'cccdCb' => '001',
        ]];

        $c = CauHinhCoSo::cua('01929', $ds);

        $this->assertSame('Nguyen Van A', $c['ho_ten_cb']);
        $this->assertSame('001', $c['cccd_cb']);
    }

    /** @test */
    public function ten_khoa_gach_duoi_duoc_uu_tien()
    {
        $ds = ['01929' => [
            'username' => 'u', 'password' => 'p',
            'ho_ten_cb' => 'Dung', 'hoTenCb' => 'Cu',
        ]];

        $this->assertSame('Dung', CauHinhCoSo::cua('01929', $ds)['ho_ten_cb']);
    }

    /** @test */
    public function khoa_co_nhung_de_rong_thi_lay_cach_dat_ten_kia()
    {
        $ds = ['01929' => [
            'username' => 'u', 'password' => 'p',
            'ho_ten_cb' => '', 'hoTenCb' => 'Nguyen Van A',
        ]];

        $this->assertSame('Nguyen Van A', CauHinhCoSo::cua('01929', $ds)['ho_ten_cb']);
    }

    /** @test */
    public function ma_tinh_la_hai_ky_tu_dau()
    {
        // 37470 o Ninh Binh -> 37, khong phai 01 nhu cau hinh cu chot cung.
        $this->assertSame('37', CauHinhCoSo::maTinh('37470'));
        $this->assertSame('01', CauHinhCoSo::maTinh('01929'));
        $this->assertSame('01', CauHinhCoSo::maTinh('01283'));
    }

    /** @test */
    public function ma_qua_ngan_thi_nem_ngoai_le()
    {
        $this->expectException(\InvalidArgumentException::class);

        CauHinhCoSo::maTinh('1');
    }

    /** @test */
    public function thieu_ho_ten_cb_van_tra_ve_chuoi_rong_khong_nem()
    {
        // ho_ten_cb / cccd_cb thieu thi KHONG chan tra cuu - chung chi la thong tin can bo,
        // khong phai dieu kien dang nhap. Tra chuoi rong de goi vao cong khong bi null.
        $ds = ['01929' => ['username' => 'u', 'password' => 'p']];
        $c = CauHinhCoSo::cua('01929', $ds);

        $this->assertSame('', $c['ho_ten_cb']);
        $this->assertSame('', $c['cccd_cb']);
    }
}

<?php

namespace Tests\Unit;

use App\Services\BHYT\CoSoTraCuu;
use Tests\TestCase;

class CoSoTraCuuTest extends TestCase
{
    protected function dsCoSo()
    {
        return [
            '01929' => ['username' => 'u1', 'password' => 'p1'],
            '37470' => ['username' => 'u2', 'password' => 'p2'],
        ];
    }

    /** @test */
    public function gan_nhan_tu_his_khi_co()
    {
        $ra = CoSoTraCuu::danhSach($this->dsCoSo(), [
            '01929' => '01929 — Benh vien A',
            '37470' => '37470 — Benh vien B',
        ]);

        $this->assertSame(['01929', '37470'], CoSoTraCuu::maDangChuoi($ra));
        $this->assertSame('01929 — Benh vien A', $ra['01929']);
        $this->assertSame('37470 — Benh vien B', $ra['37470']);
    }

    /**
     * Co so khai trong config ma HIS khong co van phai chon duoc: no tra cuu duoc that,
     * chi la khong lay duoc ten dep.
     */
    /** @test */
    public function co_so_khai_ma_his_khong_co_thi_hien_ma_tran()
    {
        $ra = CoSoTraCuu::danhSach($this->dsCoSo(), ['01929' => '01929 — Benh vien A']);

        $this->assertSame('01929 — Benh vien A', $ra['01929']);
        $this->assertSame('37470', $ra['37470'], 'Thieu nhan HIS thi phai hien ma tran');
    }

    /**
     * Day la diem cot loi: co so HIS co ma CHUA KHAI tai khoan thi KHONG duoc xuat hien.
     * Chon vao no chac chan loi - mot lua chon khong bao gio dung duoc la bay nguoi dung.
     */
    /** @test */
    public function co_so_his_chua_khai_tai_khoan_thi_khong_xuat_hien()
    {
        $ra = CoSoTraCuu::danhSach($this->dsCoSo(), [
            '01929' => '01929 — Benh vien A',
            '37470' => '37470 — Benh vien B',
            '01013' => '01013 — Co so chua khai',
        ]);

        $this->assertArrayNotHasKey('01013', $ra);
        $this->assertCount(2, $ra);
    }

    /** @test */
    public function cau_hinh_rong_thi_tra_mang_rong()
    {
        $this->assertSame([], CoSoTraCuu::danhSach([], ['01929' => '01929 — Benh vien A']));
    }

    /**
     * HIS hong thi DanhSachCoSo::doc() tra mang rong. Man tra cuu van phai dung duoc.
     */
    /** @test */
    public function his_hong_thi_van_chon_duoc_bang_ma_tran()
    {
        $ra = CoSoTraCuu::danhSach($this->dsCoSo(), []);

        $this->assertSame(['01929' => '01929', '37470' => '37470'], $ra);
    }

    /** @test */
    public function sap_theo_ma()
    {
        $ra = CoSoTraCuu::danhSach([
            '37470' => ['username' => 'u'],
            '01283' => ['username' => 'u'],
            '01929' => ['username' => 'u'],
        ], []);

        $this->assertSame(['01283', '01929', '37470'], CoSoTraCuu::maDangChuoi($ra));
    }

    /**
     * PHP ep khoa mang dang so ve int: '37470' thanh int 37470, con '01929' giu chuoi vi co
     * so 0 dau. maDangChuoi() la cach an toan duy nhat de lay danh sach ma dem so sanh hoac
     * dem lam luat validation `in:`.
     */
    /** @test */
    public function ma_dang_chuoi_ep_moi_khoa_ve_chuoi()
    {
        $ra = CoSoTraCuu::danhSach($this->dsCoSo(), []);
        $ma = CoSoTraCuu::maDangChuoi($ra);

        foreach ($ma as $m) {
            $this->assertInternalType('string', $m);
        }

        $this->assertTrue(in_array('37470', $ma, true), 'Phai so khop duoc kieu chuoi nghiem ngat');
        $this->assertTrue(in_array('01929', $ma, true));
    }

    /** @test */
    public function bo_qua_khoa_rong()
    {
        $ra = CoSoTraCuu::danhSach(['' => ['username' => 'u'], '01929' => ['username' => 'u']], []);

        $this->assertSame(['01929'], CoSoTraCuu::maDangChuoi($ra));
    }
}

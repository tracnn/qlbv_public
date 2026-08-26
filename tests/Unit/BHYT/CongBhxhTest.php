<?php

namespace Tests\Unit\BHYT;

use Tests\TestCase;
use App\Services\BHYT\CongBhxh;

/**
 * Canh diem ghep URL cong BHXH.
 *
 * VI SAO NEM CHU KHONG ROI VE MAC DINH: config/organization.php nam trong .gitignore nen
 * mot ban sao moi KHONG co khoa base_url. Neu thieu ma roi ve host that, thi mot may test
 * quen khai se GUI HO SO THAT LEN CONG THAT - hong theo huong te nhat co the. Nem thi hong
 * ngay, on ao, dung cho.
 */
class CongBhxhTest extends TestCase
{
    /** @test */
    public function ghep_base_url_voi_duong_dan()
    {
        config(['organization.BHYT.base_url' => 'https://egw.baohiemxahoi.gov.vn']);

        $this->assertSame(
            'https://egw.baohiemxahoi.gov.vn/api/DanhMucGW/GuiDanhMuc03_DMTHUOC',
            CongBhxh::url('/api/DanhMucGW/GuiDanhMuc03_DMTHUOC')
        );
    }

    /** @test */
    public function dau_gach_thua_o_hai_dau_khong_sinh_gach_doi()
    {
        // Nguoi khai cau hinh rat de go them dau '/' cuoi host. Sinh ra '//api/...' thi
        // cong tra 404 va thong bao khong noi gi ve nguyen nhan.
        config(['organization.BHYT.base_url' => 'https://egw.baohiemxahoi.gov.vn/']);

        $this->assertSame(
            'https://egw.baohiemxahoi.gov.vn/api/token/take',
            CongBhxh::url('/api/token/take')
        );
    }

    /** @test */
    public function duong_dan_thieu_gach_dau_van_ghep_dung()
    {
        config(['organization.BHYT.base_url' => 'https://egw.baohiemxahoi.gov.vn']);

        $this->assertSame(
            'https://egw.baohiemxahoi.gov.vn/api/token/take',
            CongBhxh::url('api/token/take')
        );
    }

    /** @test */
    public function base_url_rong_thi_NEM_chu_khong_roi_ve_host_that()
    {
        config(['organization.BHYT.base_url' => '']);

        $this->expectException(\RuntimeException::class);

        CongBhxh::url('/api/token/take');
    }

    /** @test */
    public function base_url_khong_khai_thi_NEM()
    {
        config(['organization.BHYT' => []]);

        $this->expectException(\RuntimeException::class);

        CongBhxh::url('/api/token/take');
    }

    /** @test */
    public function thong_bao_khi_nem_chi_ro_khoa_phai_khai()
    {
        // Nguoi van hanh gap loi nay tren mot may vua dung xong, khong co ngu canh gi.
        // Thong bao phai noi thang phai khai gi, o dau.
        config(['organization.BHYT.base_url' => '']);

        try {
            CongBhxh::url('/api/token/take');
            $this->fail('Phai nem khi thieu base_url');
        } catch (\RuntimeException $e) {
            $this->assertContains('organization.BHYT.base_url', $e->getMessage());
        }
    }
}

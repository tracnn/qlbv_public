<?php

namespace Tests\Unit\Xml3176\Support;

use App\Services\Xml3176\Support\TextNormalizer;
use Tests\TestCase;

class TextNormalizerTest extends TestCase
{
    /** @test */
    public function chuan_trim_gop_khoang_trang_ha_chu_thuong()
    {
        $this->assertEquals('a b c', TextNormalizer::chuan("  A   B\tC "));
    }

    /** @test */
    public function chuan_null_tra_chuoi_rong()
    {
        $this->assertEquals('', TextNormalizer::chuan(null));
    }

    /** @test */
    public function chuan_hai_chuoi_khac_khoang_trang_hoa_thuong_thi_bang_nhau()
    {
        $this->assertEquals(
            TextNormalizer::chuan('Diễn Biến  ổn định'),
            TextNormalizer::chuan('diễn biến ổn định')
        );
    }

    /** @test */
    public function bang_khong_phan_biet_hoa_thuong()
    {
        $this->assertTrue(TextNormalizer::bang('Paracetamol 500MG', 'paracetamol 500mg'));
    }

    /**
     * mb_strtolower chu khong phai strtolower: strtolower lam hong chu Viet co dau.
     *
     * @test
     */
    public function bang_dung_voi_chu_viet_co_dau_viet_hoa()
    {
        $this->assertTrue(TextNormalizer::bang('ĐƯỜNG HUYẾT MAO MẠCH', 'Đường huyết mao mạch'));
    }

    /** @test */
    public function bang_bo_qua_khoang_trang_thua()
    {
        $this->assertTrue(TextNormalizer::bang('  Thuoc   A ', 'Thuoc A'));
    }

    /** @test */
    public function bang_null_bang_chuoi_rong()
    {
        $this->assertTrue(TextNormalizer::bang(null, ''));
    }

    /** @test */
    public function khac_noi_dung_thi_khong_bang()
    {
        $this->assertFalse(TextNormalizer::bang('Thuoc A', 'Thuoc B'));
    }

    /**
     * So NGHIEM NGAT sau chuan hoa: '1e3' == '1000' la true trong PHP 7 neu so long.
     *
     * @test
     */
    public function khong_so_long_kieu_so_hoc()
    {
        $this->assertFalse(TextNormalizer::bang('1e3', '1000'));
    }

    /**
     * Chu khac dau KHONG duoc coi la bang - chi bo phan biet hoa thuong, khong bo dau.
     *
     * @test
     */
    public function khac_dau_thi_khong_bang()
    {
        $this->assertFalse(TextNormalizer::bang('Duong huyet', 'Đường huyết'));
    }
}

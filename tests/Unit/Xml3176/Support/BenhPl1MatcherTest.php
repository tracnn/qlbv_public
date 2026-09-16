<?php

namespace Tests\Unit\Xml3176\Support;

use App\Services\Xml3176\Support\BenhPl1Matcher;
use Tests\TestCase;

/**
 * Spec muc 6. Du lieu la mot phan that cua Phu luc I Thong tu 01/2025/TT-BYT.
 */
class BenhPl1MatcherTest extends TestCase
{
    private function dong($stt, $ma, $loai = 'bao_gom', $tuoiDuoi = null)
    {
        return ['stt' => $stt, 'ma_icd' => $ma, 'loai' => $loai, 'tuoi_duoi' => $tuoiDuoi];
    }

    /** Dong 14, 16, 21, 22 (rut gon C00-C97 con C38/C50/C83), 23, 44, 62. */
    private function danhMuc()
    {
        return [
            $this->dong(14, 'C25'),
            $this->dong(16, 'C38'),
            $this->dong(16, 'C38.4', 'tru'),
            $this->dong(21, 'C79.3'),
            $this->dong(22, 'C38', 'bao_gom', 18),
            $this->dong(22, 'C50', 'bao_gom', 18),
            $this->dong(22, 'C83', 'bao_gom', 18),
            $this->dong(23, 'C83'),
            $this->dong(23, 'C83.5', 'tru'),
            $this->dong(44, 'I51.2'),
            $this->dong(44, 'L51.2'),
            $this->dong(62, 'Z94'),
            $this->dong(1, 'A17.0'),
        ];
    }

    private function khop($ma, $tuoi = 40)
    {
        return BenhPl1Matcher::kiemTra($ma, $this->danhMuc(), $tuoi)['khop'];
    }

    /** @test */
    public function ma_3_ky_tu_bao_gom_moi_ma_chi_tiet()
    {
        // Ghi chu 1 cua Phu luc I.
        $this->assertTrue($this->khop('C25.3'));
        $this->assertTrue($this->khop('C25'));
        $this->assertTrue($this->khop('Z94.0'));
    }

    /** @test */
    public function ma_4_ky_tu_phai_khop_dung()
    {
        // Ghi chu 2: co ma chi tiet 4 ky tu thi phai ghi ro.
        $this->assertTrue($this->khop('C79.3'));
        $this->assertFalse($this->khop('C79'));
        $this->assertFalse($this->khop('C79.1'));
    }

    /** @test */
    public function ma_tru_chi_loai_trong_chinh_dong_cua_no()
    {
        $this->assertFalse($this->khop('C38.4', 40), 'C38.4 bi tru o dong 16, dong 22 can duoi 18 tuoi');
        $this->assertTrue($this->khop('C38.1', 40));
        $this->assertTrue($this->khop('C38.4', 10), 'Dong 22 van bao gom C38.4 cho nguoi duoi 18 tuoi');
    }

    /** @test */
    public function c83_5_theo_tuoi()
    {
        $this->assertTrue($this->khop('C83.5', 10));

        $kq = BenhPl1Matcher::kiemTra('C83.5', $this->danhMuc(), 40);
        $this->assertFalse($kq['khop']);
        $this->assertSame([22], $kq['stt_sai_tuoi']);

        // Khong xac dinh duoc tuoi thi coi nhu khop - thieu can cu thi khong bao.
        $this->assertTrue($this->khop('C83.5', null));
    }

    /** @test */
    public function dung_18_tuoi_la_khong_con_duoi_18()
    {
        $this->assertTrue($this->khop('C50.9', 17));
        $this->assertFalse($this->khop('C50.9', 18));
        $this->assertTrue($this->khop('C50.9', 10));
    }

    /** @test */
    public function dong_44_nhan_ca_i51_2_lan_l51_2()
    {
        $this->assertTrue($this->khop('I51.2'));
        $this->assertTrue($this->khop('L51.2'));
    }

    /** @test */
    public function chuan_hoa_ma_truoc_khi_so()
    {
        $this->assertTrue($this->khop('a17.0†'));
        $this->assertTrue($this->khop(' Z94.0 '));
        $this->assertSame('A17.0', BenhPl1Matcher::chuanHoaMa(' a17.0† '));
        $this->assertSame('G01', BenhPl1Matcher::chuanHoaMa('G01*'));
    }

    /** @test */
    public function ma_ngoai_danh_muc_hoac_rong()
    {
        $kq = BenhPl1Matcher::kiemTra('G44.0', $this->danhMuc(), 40);
        $this->assertFalse($kq['khop']);
        $this->assertSame([], $kq['stt_sai_tuoi']);

        $this->assertFalse($this->khop(''));
    }

    /** @test */
    public function danh_muc_rong_thi_khong_khop()
    {
        $this->assertFalse(BenhPl1Matcher::kiemTra('C25', [], 40)['khop']);
    }
}

<?php

namespace Tests\Unit\Xml3176\Nvyt;

use App\Services\Xml3176\NvytLookup;
use App\Services\Xml3176\NvytRule;
use PHPUnit\Framework\TestCase;

class NvytPhanLoaiTest extends TestCase
{
    private function dong(array $them = []): array
    {
        return array_merge([
            ['macchn' => '027588/HNO-CCHN', 'ma_bhxh' => '0101234567', 'so_dinh_danh' => '042068003011', 'ho_ten' => 'NGUYỄN VĂN A'],
            ['macchn' => '0010344/BYT-CCHN', 'ma_bhxh' => '', 'so_dinh_danh' => '', 'ho_ten' => 'TRẦN B'],
            ['macchn' => '', 'ma_bhxh' => '0109999999', 'so_dinh_danh' => '001188031198', 'ho_ten' => 'LÊ C'],
            ['macchn' => '008650/NĐ-CCHN', 'ma_bhxh' => '', 'so_dinh_danh' => '', 'ho_ten' => 'PHẠM D'],
        ], $them);
    }

    private function rule(array $them = []): NvytRule
    {
        return new NvytRule(NvytLookup::tuDong($this->dong($them)));
    }

    /** @test */
    public function hop_le_khop_cchn_khong_phan_biet_hoa_thuong_va_trim()
    {
        $this->assertNull($this->rule()->phanLoai(NvytRule::BS_CHI_DINH, ' 027588/hno-cchn '));
    }

    /** @test */
    public function ttdv_hop_le_theo_cchn_dinh_danh_bhxh()
    {
        $r = $this->rule();
        foreach (['027588/HNO-CCHN', '042068003011', '0101234567'] as $v) {
            $this->assertNull($r->phanLoai(NvytRule::TTDV, $v), $v);
        }
    }

    /** @test */
    public function chuyen_mon_gui_dinh_danh_bao_sai_va_goi_y_cchn()
    {
        $kq = $this->rule()->phanLoai(NvytRule::NGUOI_THUC_HIEN, '042068003011');
        $this->assertSame('NVYT_SAI_DINH_DANG', $kq['key']);
        $this->assertContains('số định danh của NGUYỄN VĂN A', $kq['ly_do']);
        $this->assertContains('027588/HNO-CCHN', $kq['ly_do']);
    }

    /** @test */
    public function chuyen_mon_gui_bhxh_nguoi_khong_co_cchn()
    {
        $kq = $this->rule()->phanLoai(NvytRule::BS_DOC_KQ, '0109999999');
        $this->assertSame('NVYT_SAI_DINH_DANG', $kq['key']);
        $this->assertContains('chưa có CCHN trong danh mục NVYT', $kq['ly_do']);
    }

    /** @test */
    public function cchn_dung_dang_khong_co()
    {
        $r = $this->rule();
        $kq = $r->phanLoai(NvytRule::BS_CHI_DINH, '040535/BYT-CCHN');
        $this->assertSame('NVYT_KHONG_CO_TRONG_DM', $kq['key']);
        $kq = $r->phanLoai(NvytRule::TTDV, '040535/BYT-CCHN');
        $this->assertSame('NVYT_KHONG_CO_TRONG_DM', $kq['key']);
        $this->assertContains('đã tra theo CCHN, số định danh, mã BHXH', $kq['ly_do']);
    }

    /** @test */
    public function ttdv_12_so_khong_khop_la_khong_co_trong_dm()
    {
        $kq = $this->rule()->phanLoai(NvytRule::TTDV, '001188031199');
        $this->assertSame('NVYT_KHONG_CO_TRONG_DM', $kq['key']);
    }

    /** @test */
    public function chuyen_mon_12_so_khong_khop_la_sai_dinh_danh()
    {
        $kq = $this->rule()->phanLoai(NvytRule::NGUOI_THUC_HIEN, '001188031199');
        $this->assertSame('NVYT_SAI_DINH_DANG', $kq['key']);
        $this->assertContains('không phải mã CCHN', $kq['ly_do']);
    }

    /** @test */
    public function tai_khoan_may()
    {
        $kq = $this->rule()->phanLoai(NvytRule::BS_DOC_KQ, 'vietrad');
        $this->assertSame('NVYT_SAI_DINH_DANG', $kq['key']);
        $this->assertContains('tài khoản/tên đăng nhập', $kq['ly_do']);
    }

    /** @test */
    public function so_thieu_duoi_goi_y_duy_nhat()
    {
        $kq = $this->rule()->phanLoai(NvytRule::BS_CHI_DINH, '10344');
        $this->assertSame('NVYT_SAI_DINH_DANG', $kq['key']);
        $this->assertContains('số CCHN thiếu phần đuôi', $kq['ly_do']);
        $this->assertContains('có thể là 0010344/BYT-CCHN', $kq['ly_do']);

        $r2 = $this->rule([['macchn' => '010344/HNO-CCHN', 'ma_bhxh' => '', 'so_dinh_danh' => '', 'ho_ten' => 'X']]);
        $kq = $r2->phanLoai(NvytRule::BS_CHI_DINH, '10344');
        $this->assertNotContains('có thể là', $kq['ly_do']);
    }

    /** @test */
    public function chu_d_hai_kieu_dung_dang()
    {
        $r = $this->rule();
        $this->assertNull($r->phanLoai(NvytRule::BS_CHI_DINH, '008650/NĐ-CCHN'));
        $kq = $r->phanLoai(NvytRule::BS_CHI_DINH, "008651/N\u{00D0}-CCHN");
        $this->assertSame('NVYT_KHONG_CO_TRONG_DM', $kq['key']);
    }

    /** @test */
    public function gia_tri_rong_trong_danh_muc_khong_tao_khoa()
    {
        $bang = NvytLookup::tuDong($this->dong());
        $this->assertTrue($bang['co_du_lieu']);
        $this->assertFalse(array_key_exists('', $bang['ma_bhxh']));
        $this->assertFalse(array_key_exists('', $bang['so_dinh_danh']));
        $this->assertFalse(array_key_exists('', $bang['macchn']));
    }

    /** @test */
    public function danh_muc_trong_khong_bao()
    {
        $r = new NvytRule(NvytLookup::tuDong([]));
        $this->assertNull($r->phanLoai(NvytRule::BS_DOC_KQ, 'vietrad'));
    }
}

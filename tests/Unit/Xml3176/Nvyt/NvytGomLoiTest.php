<?php

namespace Tests\Unit\Xml3176\Nvyt;

use App\Services\Xml3176\NvytLookup;
use App\Services\Xml3176\NvytRule;
use PHPUnit\Framework\TestCase;

class NvytGomLoiTest extends TestCase
{
    private function rule(bool $trong = false): NvytRule
    {
        $dong = $trong ? [] : [
            ['macchn' => '027588/HNO-CCHN', 'ma_bhxh' => '0101234567', 'so_dinh_danh' => '042068003011', 'ho_ten' => 'NGUYỄN VĂN A'],
            ['macchn' => '0010344/BYT-CCHN', 'ma_bhxh' => '', 'so_dinh_danh' => '', 'ho_ten' => 'TRẦN B'],
        ];

        return new NvytRule(NvytLookup::tuDong($dong));
    }

    private function o(string $vaiTro, int $xml, $giaTri, bool $batBuoc = true, string $nhan = ''): array
    {
        return ['vai_tro' => $vaiTro, 'xml' => $xml, 'gia_tri' => $giaTri, 'bat_buoc' => $batBuoc, 'nhan' => $nhan];
    }

    /** @test */
    public function mot_gia_tri_nhieu_vai_tro_mot_loi()
    {
        $v = '040535/BYT-CCHN';
        $o = [];
        foreach (['a', 'b', 'c', 'd', 'e'] as $n) {
            $o[] = $this->o(NvytRule::NGUOI_THUC_HIEN, 3, $v, true, "DV $n");
        }
        $o[] = $this->o(NvytRule::BS_CHI_DINH, 2, $v);
        $o[] = $this->o(NvytRule::BS_CHI_DINH, 2, $v);
        $o[] = $this->o(NvytRule::NGUOI_THUC_HIEN, 5, $v, true, 'DV z');

        $kq = $this->rule()->kiem($o);
        $this->assertCount(1, $kq);
        $this->assertSame('NVYT_KHONG_CO_TRONG_DM', $kq[0]['key']);
        $m = $kq[0]['mo_ta'];
        $this->assertContains('BS chỉ định XML2 (2 dòng)', $m);
        $this->assertContains('người thực hiện XML3 (5 dòng', $m);
        $this->assertContains('người thực hiện XML5 (1 dòng', $m);
        $this->assertLessThan(mb_strpos($m, 'người thực hiện'), mb_strpos($m, 'BS chỉ định'));
        $this->assertContains('và 2 mục khác', $m);
    }

    /** @test */
    public function hoa_thuong_khac_nhau_van_mot_loi()
    {
        $kq = $this->rule()->kiem([
            $this->o(NvytRule::BS_DOC_KQ, 4, 'vietrad'),
            $this->o(NvytRule::BS_DOC_KQ, 4, 'VIETRAD '),
        ]);
        $this->assertCount(1, $kq);
        $this->assertSame('NVYT_SAI_DINH_DANG', $kq[0]['key']);
        $this->assertContains('(2 dòng', $kq[0]['mo_ta']);
        $this->assertStringStartsWith('vietrad ', $kq[0]['mo_ta']);
    }

    /** @test */
    public function thieu_gom_theo_vai_tro()
    {
        $kq = $this->rule()->kiem([
            $this->o(NvytRule::TTDV, 14, ' '),
            $this->o(NvytRule::TTDV, 1, null),
            $this->o(NvytRule::BS_CHI_DINH, 2, '', false),
        ]);
        $this->assertCount(1, $kq);
        $this->assertSame('NVYT_THIEU', $kq[0]['key']);
        $this->assertContains('thủ trưởng đơn vị', $kq[0]['mo_ta']);
        $this->assertLessThan(mb_strpos($kq[0]['mo_ta'], 'XML14'), mb_strpos($kq[0]['mo_ta'], 'XML1'));
        $this->assertSame('Thiếu mã thủ trưởng đơn vị ở XML1, XML14.', $kq[0]['mo_ta']);
    }

    /** @test */
    public function thieu_chuyen_mon_co_so_dong_va_nhan()
    {
        $kq = $this->rule()->kiem([
            $this->o(NvytRule::NGUOI_THUC_HIEN, 3, '', true, 'DV a'),
            $this->o(NvytRule::NGUOI_THUC_HIEN, 3, '', true, 'DV b'),
            $this->o(NvytRule::NGUOI_THUC_HIEN, 5, '', true, ''),
        ]);
        $this->assertSame(
            'Thiếu mã người thực hiện ở 3 dòng: XML3 (2 dòng: DV a, DV b), XML5 (1 dòng).',
            $kq[0]['mo_ta']
        );
    }

    /** @test */
    public function hop_le_khong_loi()
    {
        $o = [];
        foreach ([NvytRule::TTDV, NvytRule::BS_CHI_DINH, NvytRule::NGUOI_THUC_HIEN, NvytRule::BS_DOC_KQ] as $vt) {
            $o[] = $this->o($vt, 3, '027588/HNO-CCHN');
        }
        $this->assertSame([], $this->rule()->kiem($o));
    }

    /** @test */
    public function ttdv_hop_le_chuyen_mon_sai_cung_gia_tri()
    {
        $kq = $this->rule()->kiem([
            $this->o(NvytRule::TTDV, 1, '042068003011'),
            $this->o(NvytRule::NGUOI_THUC_HIEN, 3, '042068003011'),
        ]);
        $this->assertCount(1, $kq);
        $this->assertSame('NVYT_SAI_DINH_DANG', $kq[0]['key']);
        $this->assertContains('người thực hiện XML3', $kq[0]['mo_ta']);
        $this->assertNotContains('thủ trưởng', $kq[0]['mo_ta']);
    }

    /** @test */
    public function danh_muc_trong_chi_con_thieu()
    {
        $kq = $this->rule(true)->kiem([
            $this->o(NvytRule::BS_DOC_KQ, 4, 'vietrad'),
            $this->o(NvytRule::TTDV, 1, ''),
        ]);
        $this->assertCount(1, $kq);
        $this->assertSame('NVYT_THIEU', $kq[0]['key']);
    }

    /** @test */
    public function ten_loi_theo_hang_TEN()
    {
        $kq = $this->rule()->kiem([
            $this->o(NvytRule::TTDV, 1, ''),
            $this->o(NvytRule::BS_CHI_DINH, 2, '040535/BYT-CCHN'),
            $this->o(NvytRule::BS_DOC_KQ, 4, 'vietrad'),
        ]);
        $this->assertCount(3, $kq);
        foreach ($kq as $l) {
            $this->assertSame(NvytRule::TEN[$l['key']], $l['ten']);
        }
        // THIEU truoc, sau do loi gia tri theo thu tu xuat hien
        $this->assertSame(['NVYT_THIEU', 'NVYT_KHONG_CO_TRONG_DM', 'NVYT_SAI_DINH_DANG'], array_column($kq, 'key'));
    }
}

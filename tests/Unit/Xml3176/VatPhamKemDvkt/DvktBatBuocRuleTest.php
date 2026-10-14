<?php

namespace Tests\Unit\Xml3176\VatPhamKemDvkt;

use App\Services\Xml3176\VatPhamKemDvktLookup;
use App\Services\Xml3176\VatPhamKemDvktRule;
use Tests\TestCase;

class DvktBatBuocRuleTest extends TestCase
{
    private function rule(array $batBuoc = ['18.0219.0041' => 'CLVT tầng trên ổ bụng có cản quang']): VatPhamKemDvktRule
    {
        return new VatPhamKemDvktRule(VatPhamKemDvktLookup::tuDong([
            ['loai' => 'THUOC', 'ma_vat_pham' => '40.644', 'ten_vat_pham' => 'Omnipaque', 'ten_khop' => '', 'ma_dvkt' => '18.0219.0041', 'ten_dvkt' => 'CLVT'],
            ['loai' => 'THUOC', 'ma_vat_pham' => '40.645', 'ten_vat_pham' => 'Iopamiro', 'ten_khop' => '', 'ma_dvkt' => '18.0219.0041', 'ten_dvkt' => 'CLVT'],
        ], array_map(function ($ma, $ten) { return ['ma_dvkt' => $ma, 'ten_dvkt' => $ten]; }, array_keys($batBuoc), $batBuoc)));
    }

    private function dv($ma, $bhtt, $ten = 'CLVT tầng trên ổ bụng')
    {
        return (object) ['ma_dich_vu' => $ma, 'ma_vat_tu' => '', 'ten_dich_vu' => $ten, 't_bhtt' => $bhtt, 'so_luong' => 1, 'ngay_yl' => '202610070900'];
    }

    private function th($ma, $ten, $bhtt)
    {
        return (object) ['ma_thuoc' => $ma, 'ten_thuoc' => $ten, 'ma_dich_vu' => null, 't_bhtt' => $bhtt, 'ngay_yl' => '202610070900'];
    }

    private function keys(array $loi): array
    {
        return array_column($loi, 'key');
    }

    /** @test */
    public function co_dvkt_va_thuoc_bhyt_thi_khong_loi()
    {
        $this->assertSame([], $this->rule()->kiem([$this->dv('18.0219.0041', 522960)], [$this->th('40.645', 'Iopamiro', 295680)]));
    }

    /** @test */
    public function khong_co_vat_pham_thi_bao()
    {
        $loi = $this->rule()->kiem([$this->dv('18.0219.0041', 522960)], []);

        $this->assertSame(['DVKT_THIEU_VAT_PHAM_KEM'], $this->keys($loi));
        $this->assertContains('hồ sơ không có vật phẩm đi kèm', $loi[0]['mo_ta']);
        $this->assertContains('Omnipaque', $loi[0]['mo_ta']);
        $this->assertContains('522.960đ', $loi[0]['mo_ta']);
        $this->assertContains('07/10/2026', $loi[0]['mo_ta']);
    }

    /** @test */
    public function goi_y_theo_loai_dvkt()
    {
        $chung = $this->rule()->kiem([$this->dv('18.0219.0041', 522960)], []);
        $this->assertContains('hoặc bổ sung vật phẩm đi kèm.', $chung[0]['mo_ta']);
        $this->assertNotContains('cản quang', $chung[0]['mo_ta']);

        $cq = $this->rule()->kiem([$this->dv('18.0219.0041', 522960, 'CLVT tầng trên ổ bụng có cản quang')], []);
        $this->assertContains('(vd có/không cản quang)', $cq[0]['mo_ta']);
        $this->assertNotContains('hoặc bổ sung vật phẩm', $cq[0]['mo_ta']);
    }

    /** @test */
    public function co_vat_pham_quy_khong_tra_thi_bao_rieng()
    {
        $loi = $this->rule()->kiem([$this->dv('18.0219.0041', 522960)], [$this->th('40.644', 'Omnipaque', 0)]);

        $this->assertSame(['DVKT_THIEU_VAT_PHAM_KEM'], $this->keys($loi));
        $this->assertContains('có Omnipaque nhưng quỹ BHYT không thanh toán vật phẩm', $loi[0]['mo_ta']);
    }

    /** @test */
    public function dvkt_quy_khong_tra_hoac_khong_trong_danh_sach_thi_im_lang()
    {
        $this->assertSame([], $this->rule()->kiem([$this->dv('18.0219.0041', 0)], []));
        $this->assertSame([], $this->rule([])->kiem([$this->dv('18.0219.0041', 522960)], []));
    }

    /** @test */
    public function dvkt_gt_chuan_hoa()
    {
        $this->assertSame(['DVKT_THIEU_VAT_PHAM_KEM'], $this->keys($this->rule()->kiem([$this->dv('18.0219.0041_GT', 1000)], [])));
    }

    /** @test */
    public function tu_dong_bo_dong_bat_buoc_bi_tat()
    {
        $b = VatPhamKemDvktLookup::tuDong([], [['ma_dvkt' => '18.0219.0041', 'ten_dvkt' => 'X', 'hieu_luc' => false]]);

        $this->assertSame([], $b['DVKT_BAT_BUOC']);
    }
}

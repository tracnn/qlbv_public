<?php

namespace Tests\Unit\Xml3176\VatPhamKemDvkt;

use App\Services\Xml3176\VatPhamKemDvktRule;
use Tests\TestCase;

class VatPhamKemDvktRuleTest extends TestCase
{
    private function bang(): array
    {
        return [
            'VTYT' => [
                'N08.00.150|buồng tiêm truyền cấy dưới da' => ['12.0448.1187' => 'Đặt buồng tiêm truyền dưới da'],
                'N04.04.010|ống dẫn lưu đuôi lợn' => ['02.0086.0106' => 'Dẫn lưu'],
            ],
            'THUOC' => [
                '40.644|' => ['18.0150.0041' => 'CLVT có cản quang', '18.0156.0041' => 'CLVT bụng có cản quang'],
            ],
        ];
    }

    private function dv($ma, $bhtt, $sl = 1, $ten = 'DVKT')
    {
        return (object) ['ma_dich_vu' => $ma, 'ma_vat_tu' => '', 'ten_dich_vu' => $ten, 't_bhtt' => $bhtt, 'so_luong' => $sl, 'ngay_yl' => '202610050800'];
    }

    private function vt($ma, $ten, $bhtt, $maDv = null, $goi = null)
    {
        return (object) ['ma_dich_vu' => $maDv, 'ma_vat_tu' => $ma, 'ten_vat_tu' => $ten, 'goi_vtyt' => $goi, 't_bhtt' => $bhtt, 'so_luong' => 1, 'ngay_yl' => '202610050900'];
    }

    private function th($ma, $ten, $bhtt, $maDv = null)
    {
        return (object) ['ma_thuoc' => $ma, 'ten_thuoc' => $ten, 'ma_dich_vu' => $maDv, 't_bhtt' => $bhtt, 'ngay_yl' => '202610071000'];
    }

    private function kiem(array $xml3, array $xml2 = []): array
    {
        return (new VatPhamKemDvktRule($this->bang()))->kiem($xml3, $xml2);
    }

    private function keys(array $loi): array
    {
        return array_column($loi, 'key');
    }

    /** @test */
    public function r1_vtyt_khong_co_dvkt_me()
    {
        $loi = $this->kiem([$this->vt('N08.00.150', 'Buồng tiêm truyền cấy dưới da', 4188000)]);

        $this->assertSame(['VTYT_KHONG_CO_DVKT_ME'], $this->keys($loi));
        $this->assertContains('không có DVKT mẹ trong hồ sơ', $loi[0]['mo_ta']);
        $this->assertContains('4.188.000đ', $loi[0]['mo_ta']);
        $this->assertContains('05/10/2026', $loi[0]['mo_ta']);
        $this->assertContains('12.0448.1187 Đặt buồng tiêm truyền dưới da', $loi[0]['mo_ta']);
    }

    /** @test */
    public function r1_dvkt_me_co_nhung_quy_khong_tra()
    {
        $loi = $this->kiem([
            $this->dv('12.0448.1187', 0, 1, 'Đặt buồng tiêm truyền dưới da'),
            $this->vt('N08.00.150', 'Buồng tiêm truyền cấy dưới da', 4188000),
        ]);

        $this->assertSame(['VTYT_KHONG_CO_DVKT_ME'], $this->keys($loi));
        $this->assertContains('có trong hồ sơ nhưng quỹ BHYT không thanh toán', $loi[0]['mo_ta']);
    }

    /** @test */
    public function r1_dvkt_me_duoc_bhyt_tra_thi_khong_loi()
    {
        $this->assertSame([], $this->kiem([
            $this->dv('12.0448.1187', 1070800),
            $this->vt('N08.00.150', 'Buồng tiêm truyền cấy dưới da', 4188000),
        ]));
    }

    /** @test */
    public function r1_dvkt_me_hau_to_gt_van_khop()
    {
        $this->assertSame([], $this->kiem([
            $this->dv('12.0448.1187_GT', 1070800),
            $this->vt('N08.00.150', 'Buồng tiêm truyền cấy dưới da', 4188000),
        ]));
    }

    /** @test */
    public function r1_vat_pham_khong_bhyt_tra_hoac_khong_trong_bang_thi_im_lang()
    {
        $this->assertSame([], $this->kiem([$this->vt('N08.00.150', 'Buồng tiêm truyền cấy dưới da', 0)]));
        $this->assertSame([], $this->kiem([$this->vt('N03.01.070', 'Bơm tiêm 10ml', 5000)]));
    }

    /** @test */
    public function r1_ma_nhom_chung_khac_ten_khong_khop()
    {
        $this->assertSame([], $this->kiem([$this->vt('N04.04.010', 'Catheter chẩn đoán 4 cực', 692790)]));
        $this->assertSame(['VTYT_KHONG_CO_DVKT_ME'], $this->keys($this->kiem([$this->vt('N04.04.010', 'Ống dẫn lưu đuôi lợn', 692790)])));
    }

    /** @test */
    public function r1_gop_mot_loi_moi_vat_pham()
    {
        $loi = $this->kiem([
            $this->vt('N08.00.150', 'Buồng tiêm truyền cấy dưới da', 1000000),
            $this->vt('N08.00.150', 'Buồng tiêm truyền cấy dưới da', 2000000),
        ]);

        $this->assertCount(1, $loi);
        $this->assertContains('3.000.000đ', $loi[0]['mo_ta']);
    }

    /** @test */
    public function r1_bo_qua_dong_da_co_lien_ket()
    {
        // Dong co ma_dich_vu thuoc R2; R1 khong bao trung.
        $loi = $this->kiem([$this->vt('N08.00.150', 'Buồng tiêm truyền cấy dưới da', 4188000, '12.0448.1187', 'G1')]);

        $this->assertSame(['GOI_VTYT_KHONG_KHOP'], $this->keys($loi));
    }

    /** @test */
    public function r1_thuoc_can_quang_khong_co_clvt_co_can_quang()
    {
        $loi = $this->kiem([$this->dv('18.0149.0040', 502400, 1, 'CLVT sọ não không tiêm')], [$this->th('40.644', 'Omnipaque', 363632)]);

        $this->assertSame(['THUOC_KHONG_CO_DVKT_ME'], $this->keys($loi));
        $this->assertContains('Omnipaque (40.644)', $loi[0]['mo_ta']);
    }

    /** @test */
    public function r1_thuoc_co_dvkt_me_bhyt_thi_khong_loi()
    {
        $this->assertSame([], $this->kiem([$this->dv('18.0156.0041', 653700)], [$this->th('40.644', 'Omnipaque', 363632)]));
    }

    /** @test */
    public function r1_danh_sach_can_mot_trong_cat_5_ma()
    {
        $me = [];
        for ($i = 1; $i <= 7; $i++) {
            $me['01.000' . $i . '.0001'] = 'DV ' . $i;
        }
        $rule = new VatPhamKemDvktRule(['VTYT' => ['N08.00.150|buồng' => $me], 'THUOC' => []]);

        $loi = $rule->kiem([$this->vt('N08.00.150', 'Buồng', 100)], []);

        $this->assertContains('và 2 mã khác', $loi[0]['mo_ta']);
        $this->assertNotContains('01.0006.0001', $loi[0]['mo_ta']);
    }

    /** @test */
    public function r2_goi_vtyt_dvkt_me_thieu()
    {
        $loi = $this->kiem([$this->vt('N06.02.020.0549.274.0004', 'Giá đỡ', 29000000, '18.0659.0054', 'G1')]);

        $this->assertSame(['GOI_VTYT_KHONG_KHOP'], $this->keys($loi));
        $this->assertContains('DVKT mẹ 18.0659.0054 không có trong hồ sơ', $loi[0]['mo_ta']);
    }

    /** @test */
    public function r2_goi_vtyt_dvkt_me_quy_khong_tra()
    {
        $loi = $this->kiem([
            $this->dv('18.0659.0054', 0),
            $this->vt('N06.02.020.0549.274.0004', 'Giá đỡ', 29000000, '18.0659.0054', 'G1'),
        ]);

        $this->assertSame(['GOI_VTYT_KHONG_KHOP'], $this->keys($loi));
        $this->assertContains('quỹ BHYT không thanh toán', $loi[0]['mo_ta']);
    }

    /** @test */
    public function r2_so_goi_vuot_so_lan_dvkt()
    {
        $xml3 = [
            $this->dv('18.0659.0054', 6000000, 1),
            $this->vt('N06.02.020.0549.274.0004', 'Giá đỡ', 29000000, '18.0659.0054', 'G1'),
            $this->vt('N07.01.240', 'Bóng nong', 3000000, '18.0659.0054', 'G2'),
        ];
        $loi = $this->kiem($xml3);

        $this->assertSame(['GOI_VTYT_KHONG_KHOP'], $this->keys($loi));
        $this->assertContains('2 gói VTYT', $loi[0]['mo_ta']);

        $xml3[0] = $this->dv('18.0659.0054', 12000000, 2);
        $this->assertSame([], $this->kiem($xml3));
    }

    /** @test */
    public function r2_thuoc_gan_dvkt_me_khong_tra()
    {
        $loi = $this->kiem([$this->dv('18.0156.0041', 0)], [$this->th('40.644', 'Omnipaque', 363632, '18.0156.0041')]);

        $this->assertSame(['THUOC_DVKT_ME_KHONG_KHOP'], $this->keys($loi));
    }

    /** @test */
    public function r2_thuoc_gan_dvkt_me_bhyt_tra_thi_khong_loi()
    {
        $this->assertSame([], $this->kiem([$this->dv('18.0156.0041', 653700)], [$this->th('40.644', 'Omnipaque', 363632, '18.0156.0041')]));
    }

    /** @test */
    public function bang_rong_thi_r1_im_lang_r2_van_chay()
    {
        $rule = new VatPhamKemDvktRule([]);

        $this->assertSame([], $rule->kiem([$this->vt('N08.00.150', 'Buồng tiêm truyền cấy dưới da', 4188000)], []));
        $this->assertSame(['GOI_VTYT_KHONG_KHOP'], $this->keys($rule->kiem([$this->vt('N08.00.150', 'Buồng', 1, '12.0448.1187', 'G1')], [])));
    }
}

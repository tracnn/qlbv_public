<?php

namespace Tests\Unit\GiaoBan;

use Tests\TestCase;
use App\Services\GiaoBan\BangDieuTri;

class BangDieuTriTest extends TestCase
{
    private function cfg($id, $ten, $sort, array $metrics, $block = 'dieu_tri', $active = 1)
    {
        return [
            'id' => $id, 'block_type' => $block, 'display_name' => $ten,
            'sort_order' => $sort, 'is_active' => $active, 'metrics' => $metrics,
        ];
    }

    private function m($code, $name, $type = 'census_from', $valueType = null)
    {
        $m = ['code' => $code, 'name' => $name, 'type' => $type];

        if ($valueType !== null) {
            $m['input'] = ['value_type' => $valueType];
        }

        return $m;
    }

    private function o($deptId, $code, $auto, $manual = null)
    {
        return ['dept_config_id' => $deptId, 'metric_code' => $code,
                'auto_value' => $auto, 'manual_value' => $manual];
    }

    /** Chi tieu co bat co hien tren slide Hoat dong dieu tri. */
    private function mCo($code, $name, $type = 'census_from', $valueType = null)
    {
        $m = $this->m($code, $name, $type, $valueType);
        $m['dieu_tri_slide'] = true;

        return $m;
    }

    /** @test */
    public function khong_khoa_dieu_tri_nao_thi_bang_rong()
    {
        $b = BangDieuTri::dung([
            $this->cfg(1, 'KKB', 1, [$this->m('a', 'A')], 'kham'),
            $this->cfg(2, 'CDHA', 2, [$this->m('b', 'B')], 'can_lam_sang'),
        ], []);

        $this->assertSame([], $b['cot']);
        $this->assertSame([], $b['dong']);
        $this->assertSame([], $b['tong']);
    }

    /** @test */
    public function chi_lay_khoi_dieu_tri()
    {
        $b = BangDieuTri::dung([
            $this->cfg(1, 'KKB', 1, [$this->m('a', 'A')], 'kham'),
            $this->cfg(2, 'Nội TH', 2, [$this->m('bn_cu', 'BN cũ')]),
        ], []);

        $this->assertCount(1, $b['dong']);
        $this->assertSame('Nội TH', $b['dong'][0]['ten']);
    }

    /** @test */
    public function bo_qua_khoa_da_tat()
    {
        $b = BangDieuTri::dung([
            $this->cfg(1, 'Cũ', 1, [$this->m('bn_cu', 'BN cũ')], 'dieu_tri', 0),
            $this->cfg(2, 'Nội TH', 2, [$this->m('bn_cu', 'BN cũ')]),
        ], []);

        $this->assertCount(1, $b['dong']);
    }

    /** @test */
    public function hai_khoa_cung_nhan_gop_mot_cot()
    {
        $b = BangDieuTri::dung([
            $this->cfg(1, 'Ngoại', 1, [$this->m('de_mo', 'Đẻ mổ')]),
            $this->cfg(2, 'Phụ sản', 2, [$this->m('de_mo', 'Đẻ mổ')]),
        ], [$this->o(1, 'de_mo', 3), $this->o(2, 'de_mo', 5)]);

        $this->assertCount(1, $b['cot']);
        $this->assertSame('Đẻ mổ', $b['cot'][0]['nhan']);
        $this->assertSame([3.0], $b['dong'][0]['o']);
        $this->assertSame([5.0], $b['dong'][1]['o']);
        $this->assertSame([8.0], $b['tong']);
    }

    /** @test */
    public function cung_ma_khac_nhan_thi_tach_hai_cot()
    {
        // He qua co chu dich cua viec khoa cot theo NHAN, xem spec muc 4.3.
        $b = BangDieuTri::dung([
            $this->cfg(1, 'A', 1, [$this->m('bn_cu', 'BN cũ')]),
            $this->cfg(2, 'B', 2, [$this->m('bn_cu', 'Bệnh nhân cũ')]),
        ], []);

        $this->assertCount(2, $b['cot']);
    }

    /** @test */
    public function khong_co_nhan_thi_lay_ma_lam_khoa()
    {
        $b = BangDieuTri::dung([
            $this->cfg(1, 'A', 1, [['code' => 'bn_cu', 'type' => 'census_from']]),
        ], [$this->o(1, 'bn_cu', 7)]);

        $this->assertCount(1, $b['cot']);
        $this->assertSame('bn_cu', $b['cot'][0]['nhan']);
        $this->assertSame([7.0], $b['dong'][0]['o']);
    }

    /** @test */
    public function chi_tieu_chuoi_khong_thanh_cot()
    {
        $b = BangDieuTri::dung([
            $this->cfg(1, 'Ngoại', 1, [
                $this->m('bn_cu', 'BN cũ'),
                $this->m('ds_mo', 'Danh sách mổ', 'manual', 'text'),
            ]),
        ], []);

        $this->assertCount(1, $b['cot']);
        $this->assertSame('BN cũ', $b['cot'][0]['nhan']);
    }

    /** @test */
    public function chi_tieu_nhap_tay_kieu_so_van_thanh_cot()
    {
        $b = BangDieuTri::dung([
            $this->cfg(1, 'A', 1, [$this->m('de_mo', 'Đẻ mổ', 'manual', 'int')]),
        ], [$this->o(1, 'de_mo', null, 4)]);

        $this->assertCount(1, $b['cot']);
        $this->assertSame([4.0], $b['dong'][0]['o']);
    }

    /** @test */
    public function khoa_khong_khai_chi_tieu_thi_o_bang_khong()
    {
        $b = BangDieuTri::dung([
            $this->cfg(1, 'A', 1, [$this->m('bn_cu', 'BN cũ')]),
            $this->cfg(2, 'B', 2, [$this->m('de_mo', 'Đẻ mổ')]),
        ], [$this->o(1, 'bn_cu', 9), $this->o(2, 'de_mo', 2)]);

        $this->assertSame([9.0, 0.0], $b['dong'][0]['o']);
        $this->assertSame([0.0, 2.0], $b['dong'][1]['o']);
        $this->assertSame([9.0, 2.0], $b['tong']);
    }

    /** @test */
    public function manual_value_de_len_auto_value()
    {
        $b = BangDieuTri::dung([
            $this->cfg(1, 'A', 1, [$this->m('bn_cu', 'BN cũ')]),
        ], [$this->o(1, 'bn_cu', 10, 12)]);

        $this->assertSame([12.0], $b['dong'][0]['o']);
    }

    /** @test */
    public function ca_hai_gia_tri_rong_thi_bang_khong()
    {
        $b = BangDieuTri::dung([
            $this->cfg(1, 'A', 1, [$this->m('bn_cu', 'BN cũ')]),
        ], [$this->o(1, 'bn_cu', null, null)]);

        $this->assertSame([0.0], $b['dong'][0]['o']);
    }

    /** @test */
    public function cot_toan_percent_thi_khong_cong_tong()
    {
        $b = BangDieuTri::dung([
            $this->cfg(1, 'A', 1, [$this->m('ty_le', 'Tỷ lệ', 'manual', 'percent')]),
            $this->cfg(2, 'B', 2, [$this->m('ty_le', 'Tỷ lệ', 'manual', 'percent')]),
        ], [$this->o(1, 'ty_le', 40), $this->o(2, 'ty_le', 60)]);

        $this->assertTrue($b['cot'][0]['percent']);
        $this->assertSame([null], $b['tong']);
    }

    /** @test */
    public function cot_tron_percent_va_so_dem_thi_van_cong()
    {
        $b = BangDieuTri::dung([
            $this->cfg(1, 'A', 1, [$this->m('x', 'X', 'manual', 'percent')]),
            $this->cfg(2, 'B', 2, [$this->m('x', 'X', 'manual', 'int')]),
        ], [$this->o(1, 'x', 40), $this->o(2, 'x', 2)]);

        $this->assertFalse($b['cot'][0]['percent']);
        $this->assertSame([42.0], $b['tong']);
    }

    /** @test */
    public function thu_tu_cot_theo_sort_order_roi_theo_thu_tu_khai()
    {
        $b = BangDieuTri::dung([
            $this->cfg(2, 'Sau', 2, [$this->m('c', 'C'), $this->m('a', 'A')]),
            $this->cfg(1, 'Truoc', 1, [$this->m('a', 'A'), $this->m('b', 'B')]),
        ], []);

        $nhan = array_map(function ($c) { return $c['nhan']; }, $b['cot']);

        $this->assertSame(['A', 'B', 'C'], $nhan);
    }

    /** @test */
    public function dong_sap_theo_sort_order()
    {
        $b = BangDieuTri::dung([
            $this->cfg(2, 'Sau', 2, [$this->m('a', 'A')]),
            $this->cfg(1, 'Truoc', 1, [$this->m('a', 'A')]),
        ], []);

        $this->assertSame('Truoc', $b['dong'][0]['ten']);
        $this->assertSame('Sau', $b['dong'][1]['ten']);
    }

    /** @test */
    public function o_cua_khoa_khac_khong_bi_lay_nham()
    {
        $b = BangDieuTri::dung([
            $this->cfg(1, 'A', 1, [$this->m('bn_cu', 'BN cũ')]),
            $this->cfg(2, 'B', 2, [$this->m('bn_cu', 'BN cũ')]),
        ], [$this->o(1, 'bn_cu', 5)]);

        $this->assertSame([5.0], $b['dong'][0]['o']);
        $this->assertSame([0.0], $b['dong'][1]['o']);
    }

    /** @test */
    public function khoa_khai_hai_ma_khac_nhau_cung_mot_nhan_thi_cong_don()
    {
        // Cung mot khoa, hai chi tieu khac ma nhung trung nhan -> mot cot, cong lai.
        $b = BangDieuTri::dung([
            $this->cfg(1, 'A', 1, [$this->m('x1', 'Đẻ mổ'), $this->m('x2', 'Đẻ mổ')]),
        ], [$this->o(1, 'x1', 3), $this->o(1, 'x2', 4)]);

        $this->assertCount(1, $b['cot']);
        $this->assertSame([7.0], $b['dong'][0]['o']);
    }

    // ===== Co chon cot cho slide =====

    /** @test */
    public function khong_co_nao_bat_thi_hien_toan_bo_cot_nhu_truoc()
    {
        // Tuong thich nguoc: trien khai xong ma chua ai cau hinh thi slide khong duoc trang.
        $b = BangDieuTri::dung([
            $this->cfg(1, 'A', 1, [$this->m('a', 'A'), $this->m('b', 'B')]),
        ], []);

        $nhan = array_map(function ($c) { return $c['nhan']; }, $b['cot']);

        $this->assertSame(['A', 'B'], $nhan);
    }

    /** @test */
    public function co_it_nhat_mot_co_thi_chi_cot_bat_co_len_slide()
    {
        $b = BangDieuTri::dung([
            $this->cfg(1, 'A', 1, [$this->mCo('bn_cu', 'BN cũ'), $this->m('bn_vao', 'BN vào')]),
        ], []);

        $this->assertCount(1, $b['cot']);
        $this->assertSame('BN cũ', $b['cot'][0]['nhan']);
    }

    /** @test */
    public function khoa_khong_bat_co_van_do_so_vao_cot_da_ton_tai()
    {
        // Co chon COT chu khong chon o: KHTH bat mot noi, moi khoa cung nhan deu duoc cong.
        $b = BangDieuTri::dung([
            $this->cfg(1, 'A', 1, [$this->mCo('de_mo', 'Đẻ mổ')]),
            $this->cfg(2, 'B', 2, [$this->m('de_mo', 'Đẻ mổ')]),
        ], [$this->o(1, 'de_mo', 3), $this->o(2, 'de_mo', 5)]);

        $this->assertCount(1, $b['cot']);
        $this->assertSame([3.0], $b['dong'][0]['o']);
        $this->assertSame([5.0], $b['dong'][1]['o']);
        $this->assertSame([8.0], $b['tong']);
    }

    /** @test */
    public function khai_bao_khong_bat_co_van_lam_cot_mat_tinh_percent()
    {
        // Khoa A (sort 1, khong bat co, so tuyet doi) duyet TRUOC khoa B (sort 2, bat co,
        // percent). So cua A van duoc cong vao cot -> cot khong con la percent, phai co tong.
        $b = BangDieuTri::dung([
            $this->cfg(1, 'A', 1, [$this->m('x', 'X', 'manual', 'int')]),
            $this->cfg(2, 'B', 2, [$this->mCo('x', 'X', 'manual', 'percent')]),
        ], [$this->o(1, 'x', 2), $this->o(2, 'x', 40)]);

        $this->assertCount(1, $b['cot']);
        $this->assertFalse($b['cot'][0]['percent']);
        $this->assertSame([42.0], $b['tong']);
    }

    /** @test */
    public function cot_toan_percent_va_deu_bat_co_thi_van_khong_cong_tong()
    {
        $b = BangDieuTri::dung([
            $this->cfg(1, 'A', 1, [$this->mCo('ty_le', 'Tỷ lệ', 'manual', 'percent')]),
            $this->cfg(2, 'B', 2, [$this->mCo('ty_le', 'Tỷ lệ', 'manual', 'percent')]),
        ], [$this->o(1, 'ty_le', 40), $this->o(2, 'ty_le', 60)]);

        $this->assertTrue($b['cot'][0]['percent']);
        $this->assertSame([null], $b['tong']);
    }

    /** @test */
    public function loc_theo_co_van_giu_thu_tu_sort_order_roi_thu_tu_khai()
    {
        $b = BangDieuTri::dung([
            $this->cfg(2, 'Sau', 2, [$this->m('c', 'C'), $this->mCo('a', 'A')]),
            $this->cfg(1, 'Truoc', 1, [$this->mCo('a', 'A'), $this->mCo('b', 'B')]),
        ], []);

        $nhan = array_map(function ($c) { return $c['nhan']; }, $b['cot']);

        $this->assertSame(['A', 'B'], $nhan);
    }

    /**
     * batCo() dung !empty co y: du lieu cu tu ban ghi khac co the mang 1 hoac '1' thay vi bool.
     * Neu ai do "don dep" thanh === true, ca test nay se do va giu lai ly do ton tai cua dong do.
     *
     * @test
     */
    public function co_dieu_tri_slide_dang_so_nguyen_hoac_chuoi_van_duoc_nhan_la_bat()
    {
        $b1 = BangDieuTri::dung([
            $this->cfg(1, 'A', 1, [array_merge($this->m('bn_cu', 'BN cũ'), ['dieu_tri_slide' => 1])]),
        ], []);
        $b2 = BangDieuTri::dung([
            $this->cfg(1, 'A', 1, [array_merge($this->m('bn_cu', 'BN cũ'), ['dieu_tri_slide' => '1'])]),
        ], []);

        $this->assertCount(1, $b1['cot']);
        $this->assertSame('BN cũ', $b1['cot'][0]['nhan']);
        $this->assertCount(1, $b2['cot']);
        $this->assertSame('BN cũ', $b2['cot'][0]['nhan']);
    }

    /** @test */
    public function chi_tieu_chuoi_bat_co_van_khong_thanh_cot()
    {
        // MetricValidator da chan tu cau hinh, nhung du lieu cu co the con — khong duoc vo.
        $b = BangDieuTri::dung([
            $this->cfg(1, 'A', 1, [
                $this->mCo('bn_cu', 'BN cũ'),
                $this->mCo('ds_mo', 'Danh sách mổ', 'manual', 'text'),
            ]),
        ], []);

        $this->assertCount(1, $b['cot']);
        $this->assertSame('BN cũ', $b['cot'][0]['nhan']);
    }
}

<?php

namespace Tests\Unit\GiaoBan;

use Tests\TestCase;
use App\Services\GiaoBan\MetricValidator;

class MetricValidatorTest extends TestCase
{
    protected function hopLe()
    {
        return [
            ['code' => 'bn_cu', 'name' => 'BN cũ', 'type' => 'census_from'],
            ['code' => 'bn_ra_vien', 'name' => 'BN ra viện', 'type' => 'end_type', 'end_codes' => ['RV', 'CV']],
        ];
    }

    /** @test */
    public function bo_chi_tieu_hop_le_khong_co_loi()
    {
        $this->assertSame([], MetricValidator::validate($this->hopLe(), 'dieu_tri'));
    }

    /** @test */
    public function danh_sach_rong_bi_chan()
    {
        $loi = MetricValidator::validate([], 'dieu_tri');
        $this->assertCount(1, $loi);
        $this->assertEquals(-1, $loi[0]['index']);
    }

    /** @test */
    public function code_trung_bi_chan_va_bao_dung_vi_tri()
    {
        $m = $this->hopLe();
        $m[1]['code'] = 'bn_cu';
        $loi = MetricValidator::validate($m, 'dieu_tri');

        $this->assertCount(1, $loi);
        $this->assertEquals(1, $loi[0]['index']);
        $this->assertEquals('code', $loi[0]['field']);
    }

    /** @test */
    public function code_sai_dinh_dang_bi_chan()
    {
        foreach (['BN_Cu', '1bn', 'bn cu', 'bn-cu', ''] as $xau) {
            $m = $this->hopLe();
            $m[0]['code'] = $xau;
            $loi = MetricValidator::validate($m, 'dieu_tri');
            $this->assertNotEmpty($loi, "Code '$xau' le ra phai bi chan");
            $this->assertEquals('code', $loi[0]['field']);
        }
    }

    /** @test */
    public function name_rong_bi_chan()
    {
        $m = $this->hopLe();
        $m[0]['name'] = '   ';
        $loi = MetricValidator::validate($m, 'dieu_tri');
        $this->assertEquals('name', $loi[0]['field']);
    }

    /** @test */
    public function type_la_bi_chan()
    {
        $m = $this->hopLe();
        $m[0]['type'] = 'khong_ton_tai';
        $loi = MetricValidator::validate($m, 'dieu_tri');
        $this->assertEquals('type', $loi[0]['field']);
    }

    /** @test */
    public function type_khong_hop_voi_khoi_bi_chan()
    {
        // census_from chi dung cho khoi dieu_tri
        $loi = MetricValidator::validate($this->hopLe(), 'can_lam_sang');
        $this->assertNotEmpty($loi);
        $this->assertEquals('type', $loi[0]['field']);
    }

    /** @test */
    public function field_bat_buoc_thieu_hoac_rong_bi_chan()
    {
        $m = [['code' => 'x', 'name' => 'X', 'type' => 'end_type']];
        $loi = MetricValidator::validate($m, 'dieu_tri');
        $this->assertEquals('end_codes', $loi[0]['field']);

        $m[0]['end_codes'] = [];
        $loi = MetricValidator::validate($m, 'dieu_tri');
        $this->assertEquals('end_codes', $loi[0]['field']);
    }

    /** @test */
    public function bed_ids_phai_la_mang_so_nguyen()
    {
        $m = [['code' => 'gyc', 'name' => 'Giường YC', 'type' => 'bed_count', 'bed_ids' => ['abc']]];
        $loi = MetricValidator::validate($m, 'dieu_tri');
        $this->assertEquals('bed_ids', $loi[0]['field']);
    }

    /** @test */
    public function khoa_filter_ngoai_whitelist_bi_chan()
    {
        $m = [['code' => 'dv', 'name' => 'DV', 'type' => 'service_count',
               'filter' => ['execute_department_id_self' => true, 'khoa_bia_dat' => [1]]]];
        $loi = MetricValidator::validate($m, 'can_lam_sang');
        $this->assertEquals('filter.khoa_bia_dat', $loi[0]['field']);
    }

    /** @test */
    public function khong_duoc_vua_khai_ids_vua_khai_nhom_khac()
    {
        $m = [['code' => 'dv', 'name' => 'DV', 'type' => 'service_count',
               'filter' => ['execute_department_id_self' => true,
                            'diim_type_ids' => [1], 'diim_type_other_of' => [1, 2]]]];
        $loi = MetricValidator::validate($m, 'can_lam_sang');
        $this->assertEquals('filter.diim_type_ids', $loi[0]['field']);
    }

    /** @test */
    public function khong_duoc_vua_tu_thuc_hien_vua_chi_dinh_khoa_cu_the()
    {
        $m = [['code' => 'dv', 'name' => 'DV', 'type' => 'service_count',
               'filter' => ['execute_department_id_self' => true, 'execute_room_ids' => [9]]]];
        $loi = MetricValidator::validate($m, 'can_lam_sang');
        $this->assertEquals('filter.execute_department_id_self', $loi[0]['field']);
    }

    /** @test */
    public function filter_cua_exam_visit_khong_nhan_khoa_cua_service_count()
    {
        $m = [['code' => 'lk', 'name' => 'Lượt khám', 'type' => 'exam_visit',
               'filter' => ['service_type_ids' => [2]]]];
        $loi = MetricValidator::validate($m, 'kham');
        $this->assertEquals('filter.service_type_ids', $loi[0]['field']);
    }

    /** @test */
    public function nhap_tay_min_lon_hon_max_bi_chan()
    {
        $m = [['code' => 'cg', 'name' => 'Chuyên gia', 'type' => 'manual',
               'input' => ['value_type' => 'int', 'min' => 10, 'max' => 5]]];
        $loi = MetricValidator::validate($m, 'kham');
        $this->assertEquals('input.min', $loi[0]['field']);
    }

    /** @test */
    public function nhap_tay_default_ngoai_khoang_bi_chan()
    {
        $m = [['code' => 'cg', 'name' => 'Chuyên gia', 'type' => 'manual',
               'input' => ['value_type' => 'int', 'min' => 0, 'max' => 10, 'default' => 99]]];
        $loi = MetricValidator::validate($m, 'kham');
        $this->assertEquals('input.default', $loi[0]['field']);
    }

    /** @test */
    public function nhap_tay_value_type_la_bi_chan()
    {
        $m = [['code' => 'cg', 'name' => 'Chuyên gia', 'type' => 'manual',
               'input' => ['value_type' => 'chuoi']]];
        $loi = MetricValidator::validate($m, 'kham');
        $this->assertEquals('input.value_type', $loi[0]['field']);
    }

    /** @test */
    public function nhap_tay_percent_gioi_han_0_100()
    {
        $m = [['code' => 'cs', 'name' => 'Công suất', 'type' => 'manual',
               'input' => ['value_type' => 'percent', 'max' => 150]]];
        $loi = MetricValidator::validate($m, 'kham');
        $this->assertEquals('input.max', $loi[0]['field']);
    }

    /** @test */
    public function nhap_tay_khoa_la_trong_input_bi_chan()
    {
        $m = [['code' => 'cg', 'name' => 'Chuyên gia', 'type' => 'manual',
               'input' => ['decimals' => 3]]];
        $loi = MetricValidator::validate($m, 'kham');
        $this->assertEquals('input.decimals', $loi[0]['field']);
    }

    /** @test */
    public function json_hong_tra_loi_cap_danh_sach()
    {
        $loi = MetricValidator::validateJson('{khong phai json', 'dieu_tri');
        $this->assertEquals(-1, $loi[0]['index']);
        $this->assertEquals('metrics', $loi[0]['field']);
    }

    /** @test */
    public function json_hop_le_di_qua_duoc()
    {
        $this->assertSame([], MetricValidator::validateJson(json_encode($this->hopLe()), 'dieu_tri'));
    }

    /** @test */
    public function goi_loi_thanh_body_422_kem_vi_tri_chi_tieu()
    {
        $loi = [
            ['index' => 2, 'field' => 'code', 'message' => 'Mã sai.'],
            ['index' => 3, 'field' => 'name', 'message' => 'Tên trống.'],
        ];
        $body = MetricValidator::toResponsePayload($loi);

        $this->assertEquals('Mã sai. (chỉ tiêu thứ 3)', $body['message']);
        $this->assertSame($loi, $body['errors']);
    }

    /** @test */
    public function loi_cap_danh_sach_khong_kem_vi_tri()
    {
        $body = MetricValidator::toResponsePayload(
            [['index' => -1, 'field' => 'metrics', 'message' => 'Phải có ít nhất một chỉ tiêu.']]
        );

        $this->assertEquals('Phải có ít nhất một chỉ tiêu.', $body['message']);
    }
}

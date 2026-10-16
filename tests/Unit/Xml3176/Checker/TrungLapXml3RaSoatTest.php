<?php

namespace Tests\Unit\Xml3176\Checker;

use App\Models\BHYT\Xml3176Xml1;
use App\Models\BHYT\Xml3176Xml3;
use App\Services\Xml3176Xml3Checker;
use Illuminate\Support\Facades\DB;
use Tests\Support\Xml3176RuleTestSupport;
use Tests\TestCase;

/**
 * Ra soat 15/10/2026 tren prod: mot sai sot XML3 bi bao hai lan (cung ma, hoac hai ma
 * chong nhau). Moi test dung mot sai sot va doi dung MOT dong loi.
 *   A1  PP_VO_CAM_EMPTY/_INVALID: khoi kiem bi chep hai lan (306 dong trung)
 *   A4  OVERLAPPING_BED_USAGE: nhieu dong doi tac cung mo ta (208 dong thua)
 *   B11 CHUA_CO_GIA vs TB_CO_DON_GIA, CHUA_CO_GIA vs INVALID_APPROVED_DON_GIA_BH
 *   B12 INVALID_APPROVED_TYLE_TT_BH vs INFO_ERROR_TYLE_TT_BH (tyle ngoai 0..100)
 */
class TrungLapXml3RaSoatTest extends TestCase
{
    use Xml3176RuleTestSupport;

    protected function setUp()
    {
        parent::setUp();
        $this->bootXml3176Sqlite([
            '2026_01_09_152832_create_xml3176_xml3s_table.php',
            '2024_06_12_145200_create_danh_muc_dich_vu_table.php',
            '2026_07_28_130000_add_ma_cskcb_to_service_catalogs.php',
            '2026_09_10_100000_create_dvkt_can_ma_may_table.php',
            '2024_06_12_143949_create_danh_muc_vat_tu_table.php',
        ]);
    }

    private function dong(array $attrs): Xml3176Xml3
    {
        $d = new Xml3176Xml3(array_merge(['ma_lk' => 'L1', 'stt' => 1], $attrs));
        $d->setRelation('Xml3176Xml1', new Xml3176Xml1(['ma_lk' => 'L1', 'ma_cskcb' => null,
            'ngay_vao' => '202610050800', 'ngay_ra' => '202610080800']));

        return $d;
    }

    private function dem($ds, string $ma): int
    {
        return count(array_keys($this->errorCodes($ds), $ma));
    }

    private function goi(string $method, Xml3176Xml3 $d)
    {
        return $this->invokePrivate($this->makeChecker(Xml3176Xml3Checker::class), $method, $d);
    }

    // A1 ---------------------------------------------------------------------

    /** @test */
    public function pp_vo_cam_rong_chi_bao_mot_lan()
    {
        $nhom = config('xml3176.service_groups_requiring_anesthesia')[0];
        $ds = $this->goi('infoChecker', $this->dong(['ma_nhom' => $nhom, 'so_luong' => 1, 'pp_vo_cam' => '']));

        $this->assertSame(1, $this->dem($ds, 'XML3_INFO_ERROR_PP_VO_CAM_EMPTY'));
    }

    /** @test */
    public function pp_vo_cam_sai_chi_bao_mot_lan()
    {
        $nhom = config('xml3176.service_groups_requiring_anesthesia')[0];
        $ds = $this->goi('infoChecker', $this->dong(['ma_nhom' => $nhom, 'so_luong' => 1, 'pp_vo_cam' => '9']));

        $this->assertSame(1, $this->dem($ds, 'XML3_INFO_ERROR_PP_VO_CAM_INVALID'));
    }

    // A4 ---------------------------------------------------------------------

    /** @test */
    public function trung_giuong_voi_nhieu_dong_cung_mo_ta_chi_bao_mot_lan()
    {
        $giuong = ['ma_nhom' => 15, 'ma_khoa' => 'K30', 'ma_giuong' => 'K001', 'ma_dich_vu' => 'K30.NO1'];
        // Ho so L2 co hai dong giuong cung khung gio (vd tach dong theo gia) => cung mo ta.
        Xml3176Xml3::create($giuong + ['ma_lk' => 'L2', 'stt' => 1, 'ngay_th_yl' => '202610050900', 'ngay_kq' => '202610060900']);
        Xml3176Xml3::create($giuong + ['ma_lk' => 'L2', 'stt' => 2, 'ngay_th_yl' => '202610050900', 'ngay_kq' => '202610060900']);

        $ds = $this->goi('checkBedGroupCodeConditions', $this->dong($giuong + [
            'id' => 999, 'ngay_th_yl' => '202610051000', 'ngay_kq' => '202610061000',
        ]));

        $this->assertSame(1, $this->dem($ds, 'XML3_OVERLAPPING_BED_USAGE'));
    }

    /** @test */
    public function trung_giuong_voi_hai_ho_so_khac_nhau_van_mot_dong_neu_du_hai_ho_so()
    {
        $giuong = ['ma_nhom' => 15, 'ma_khoa' => 'K30', 'ma_giuong' => 'K001', 'ma_dich_vu' => 'K30.NO1'];
        Xml3176Xml3::create($giuong + ['ma_lk' => 'L2', 'stt' => 1, 'ngay_th_yl' => '202610050900', 'ngay_kq' => '202610060900']);
        Xml3176Xml3::create($giuong + ['ma_lk' => 'L3', 'stt' => 1, 'ngay_th_yl' => '202610050930', 'ngay_kq' => '202610060930']);

        $loi = collect($this->goi('checkBedGroupCodeConditions', $this->dong($giuong + [
            'id' => 999, 'ngay_th_yl' => '202610051000', 'ngay_kq' => '202610061000',
        ])))->where('error_code', 'XML3_OVERLAPPING_BED_USAGE')->values();

        $this->assertCount(1, $loi);
        $this->assertContains('L2', $loi[0]->description);
        $this->assertContains('L3', $loi[0]->description);
    }

    // B11 --------------------------------------------------------------------

    /** @test */
    public function ma_tb_duoi_0000_co_don_gia_chi_bao_tb()
    {
        $ds = $this->goi('checkCauTrucMaDichVu', $this->dong([
            'ma_dich_vu' => '02.0261.0000_TB', 'don_gia_bh' => 500000, 'don_gia_bv' => 500000,
        ]));

        $this->assertSame(1, $this->dem($ds, 'XML3_MA_DICH_VU_TB_CO_DON_GIA'));
        $this->assertSame(0, $this->dem($ds, 'XML3_MA_DICH_VU_CHUA_CO_GIA_CO_DON_GIA_BH'));
    }

    /** @test */
    public function ma_chua_co_gia_co_don_gia_khong_bao_them_vuot_gia_phe_duyet()
    {
        DB::table('service_catalogs')->insert([
            'ma_dich_vu' => '02.0261.0000', 'ten_dich_vu' => 'DV chưa có giá',
            'don_gia' => 0, 'quy_trinh' => '', 'tu_ngay' => '20240101', 'den_ngay' => null,
        ]);
        $d = $this->dong(['ma_nhom' => 8, 'ma_dich_vu' => '02.0261.0000', 'ten_dich_vu' => 'DV chưa có giá',
            'don_gia_bh' => 500000]);

        $this->assertSame(1, $this->dem($this->goi('checkCauTrucMaDichVu', $d), 'XML3_MA_DICH_VU_CHUA_CO_GIA_CO_DON_GIA_BH'));
        $this->assertSame(0, $this->dem($this->goi('checkMedicalService', $d), 'XML3_INVALID_APPROVED_DON_GIA_BH'));
    }

    // B12 --------------------------------------------------------------------

    private function vatTu($tyle): Xml3176Xml3
    {
        DB::table('medical_supply_catalogs')->insert([
            'ma_vat_tu' => 'VT1', 'nhom_vat_tu' => 'N', 'ten_vat_tu' => 'Kim', 'don_gia_bh' => 1000,
            'tyle_tt_bh' => 100, 'tt_thau' => 'QD1;G1;N1;2026',
        ]);

        return $this->dong(['ma_nhom' => 10, 'ma_vat_tu' => 'VT1', 'ten_vat_tu' => 'Kim', 'don_gia_bh' => 1000,
            'tyle_tt_bh' => $tyle, 'tt_thau' => 'QD1;G1;N1;2026', 'ma_dich_vu' => '']);
    }

    /** @test */
    public function tyle_ngoai_0_100_chi_bao_info_error()
    {
        $d = $this->vatTu(150);

        $this->assertSame(1, $this->dem($this->goi('infoChecker', $d), 'XML3_INFO_ERROR_TYLE_TT_BH'));
        $this->assertSame(0, $this->dem($this->goi('checkMedicalSupplyCatalog', $d), 'XML3_INVALID_APPROVED_TYLE_TT_BH'));
    }

    /** @test */
    public function tyle_hop_le_lech_danh_muc_van_bao_approved()
    {
        $d = $this->vatTu(50);

        $this->assertSame(0, $this->dem($this->goi('infoChecker', $d), 'XML3_INFO_ERROR_TYLE_TT_BH'));
        $this->assertSame(1, $this->dem($this->goi('checkMedicalSupplyCatalog', $d), 'XML3_INVALID_APPROVED_TYLE_TT_BH'));
    }
}

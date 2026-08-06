<?php

namespace Tests\Unit\OrderCheck;

use App\Services\OrderCheck\TreatmentIssueService;
use Illuminate\Support\Facades\DB;
use Tests\Support\DungBangLoiDotDieuTriSqlite;
use Tests\TestCase;

class TreatmentIssueServiceTest extends TestCase
{
    use DungBangLoiDotDieuTriSqlite;

    protected function setUp()
    {
        parent::setUp();

        $this->chuanBiBangLoi();
    }

    protected function dichVu()
    {
        return new TreatmentIssueService();
    }

    /** @test */
    public function loc_vi_pham_theo_ma_dot_dieu_tri()
    {
        $this->themViPham(['treatment_code' => '01013250800123']);
        $this->themViPham(['treatment_code' => 'DOT-KHAC', 'treatment_id' => 9002]);

        $ketQua = $this->dichVu()->cua('01013250800123');

        $this->assertCount(1, $ketQua['data']['order_check']);
        $this->assertEquals('01013250800123', $ketQua['data']['treatment_code']);
    }

    /** @test */
    public function mac_dinh_bo_dong_false_positive()
    {
        $this->themViPham(['status' => 'new']);
        $this->themViPham(['status' => 'false_positive']);

        $ketQua = $this->dichVu()->cua('01013250800123');

        $this->assertCount(1, $ketQua['data']['order_check']);
        $this->assertEquals('new', $ketQua['data']['order_check'][0]['status']);
    }

    /** @test */
    public function truyen_status_tuong_minh_thi_lay_dung_trang_thai_do()
    {
        $this->themViPham(['status' => 'new']);
        $this->themViPham(['status' => 'false_positive']);

        $ketQua = $this->dichVu()->cua('01013250800123', null, ['status' => 'false_positive']);

        $this->assertCount(1, $ketQua['data']['order_check']);
        $this->assertEquals('false_positive', $ketQua['data']['order_check'][0]['status']);
    }

    /** @test */
    public function detail_json_duoc_giai_ma_thanh_mang()
    {
        $this->themViPham(['detail' => '{"ma_dv":"XN001"}']);

        $ketQua = $this->dichVu()->cua('01013250800123');

        $this->assertEquals(['ma_dv' => 'XN001'], $ketQua['data']['order_check'][0]['detail']);
    }

    /**
     * Mot dong detail hong khong duoc lam chet ca lan goi API.
     *
     * @test
     */
    public function detail_hong_thi_tra_null_chu_khong_nem_loi()
    {
        $this->themViPham(['detail' => '{khong-phai-json']);

        $ketQua = $this->dichVu()->cua('01013250800123');

        $this->assertNull($ketQua['data']['order_check'][0]['detail']);
    }

    /** @test */
    public function dot_sach_tra_ba_mang_rong()
    {
        $ketQua = $this->dichVu()->cua('KHONG-CO-DOT-NAY');

        $this->assertSame([], $ketQua['data']['order_check']);
        $this->assertSame([], $ketQua['data']['hein_card']);
        $this->assertSame([], $ketQua['data']['xml3176']);
    }

    protected function themTraThe(array $ghiDe = [])
    {
        DB::table('check_hein_cards')->insert(array_merge([
            'ma_lk'      => '01013250800123',
            'ma_tracuu'  => '000',
            'ma_kiemtra' => '00',
            'ma_ketqua'  => 'Hop le',
            'ghi_chu'    => null,
            'ma_the'     => 'DN4010112345678',
            'created_at' => '2026-08-05 14:00:00',
            'updated_at' => '2026-08-05 14:03:00',
        ], $ghiDe));
    }

    /** @test */
    public function the_hop_le_thi_nhom_tra_the_rong()
    {
        $this->themTraThe(['ma_tracuu' => '000', 'ma_kiemtra' => '00']);

        $ketQua = $this->dichVu()->cua('01013250800123');

        $this->assertSame([], $ketQua['data']['hein_card']);
    }

    /** @test */
    public function the_bat_thuong_thi_tra_ve_mot_dong()
    {
        $this->themTraThe(['ma_tracuu' => '005', 'ma_ketqua' => 'The het han']);

        $ketQua = $this->dichVu()->cua('01013250800123');

        $this->assertCount(1, $ketQua['data']['hein_card']);
        $this->assertEquals('005', $ketQua['data']['hein_card'][0]['ma_tracuu']);
        $this->assertEquals('The het han', $ketQua['data']['hein_card'][0]['ma_ketqua']);
        $this->assertEquals('2026-08-05 14:03:00', $ketQua['data']['hein_card'][0]['checked_at']);
    }

    /**
     * HIS da co san thong tin benh nhan; day them PII sang chi lam tang be mat lo lot.
     *
     * @test
     */
    public function khong_tra_ve_thong_tin_dinh_danh_benh_nhan()
    {
        $this->themTraThe(['ma_kiemtra' => '01']);

        $dong = $this->dichVu()->cua('01013250800123')['data']['hein_card'][0];

        foreach (['ho_ten', 'ngay_sinh', 'dia_chi', 'maso_bhxh', 'ma_the'] as $cot) {
            $this->assertArrayNotHasKey($cot, $dong);
        }

        $this->assertEquals('****5678', $dong['ma_the_masked']);
    }

    /** @test */
    public function che_ma_the_xu_ly_the_rong_va_the_ngan()
    {
        $this->assertNull(TreatmentIssueService::cheMaThe(null));
        $this->assertNull(TreatmentIssueService::cheMaThe('   '));
        $this->assertEquals('****AB', TreatmentIssueService::cheMaThe('AB'));
    }

    protected function themLoiXml(array $ghiDe = [])
    {
        DB::table('xml3176_error_results')->insert(array_merge([
            'xml'            => 'XML1',
            'ma_lk'          => '01013250800123',
            'stt'            => 1,
            'ngay_yl'        => '20260805',
            'ngay_kq'        => '20260805',
            'error_code'     => 'L001',
            'description'    => 'Chi tiet loi',
            'critical_error' => 1,
            'created_at'     => '2026-08-05 15:00:00',
            'updated_at'     => '2026-08-05 15:00:00',
        ], $ghiDe));
    }

    protected function themDanhMucLoi(array $ghiDe = [])
    {
        DB::table('xml3176_error_catalogs')->insert(array_merge([
            'xml'            => 'XML1',
            'error_code'     => 'L001',
            'error_name'     => 'Sai ma the BHYT',
            'description'    => null,
            'critical_error' => 1,
            'is_check'       => 1,
            'created_at'     => '2026-01-09 00:00:00',
            'updated_at'     => '2026-01-09 00:00:00',
        ], $ghiDe));
    }

    /** @test */
    public function lay_loi_xml3176_kem_ten_loi_tu_danh_muc()
    {
        $this->themDanhMucLoi();
        $this->themLoiXml();

        $dong = $this->dichVu()->cua('01013250800123')['data']['xml3176'];

        $this->assertCount(1, $dong);
        $this->assertEquals('Sai ma the BHYT', $dong[0]['error_name']);
        $this->assertTrue($dong[0]['critical_error']);
        $this->assertEquals('20260805', $dong[0]['ngay_yl']);
    }

    /**
     * xml3176_error_catalogs unique theo CAP (xml, error_code). Join thieu cot xml se
     * nhan mot dong loi thanh nhieu dong khi ma loi do ton tai o nhieu loai XML.
     *
     * @test
     */
    public function cung_ma_loi_o_hai_loai_xml_thi_khong_nhan_dong()
    {
        $this->themDanhMucLoi(['xml' => 'XML1', 'error_name' => 'Ten cua XML1']);
        $this->themDanhMucLoi(['xml' => 'XML2', 'error_name' => 'Ten cua XML2']);
        $this->themLoiXml(['xml' => 'XML1']);

        $dong = $this->dichVu()->cua('01013250800123')['data']['xml3176'];

        $this->assertCount(1, $dong);
        $this->assertEquals('Ten cua XML1', $dong[0]['error_name']);
    }

    /** @test */
    public function loi_khong_co_trong_danh_muc_van_duoc_tra_ve()
    {
        $this->themLoiXml(['error_code' => 'L999']);

        $dong = $this->dichVu()->cua('01013250800123')['data']['xml3176'];

        $this->assertCount(1, $dong);
        $this->assertNull($dong[0]['error_name']);
    }

    /** @test */
    public function loi_xml_sap_xep_theo_xml_roi_toi_stt()
    {
        $this->themLoiXml(['xml' => 'XML2', 'stt' => 1]);
        $this->themLoiXml(['xml' => 'XML1', 'stt' => 2]);
        $this->themLoiXml(['xml' => 'XML1', 'stt' => 1]);

        $dong = $this->dichVu()->cua('01013250800123')['data']['xml3176'];

        $this->assertEquals(['XML1', 1], [$dong[0]['xml'], $dong[0]['stt']]);
        $this->assertEquals(['XML1', 2], [$dong[1]['xml'], $dong[1]['stt']]);
        $this->assertEquals(['XML2', 1], [$dong[2]['xml'], $dong[2]['stt']]);
    }

    /** @test */
    public function tom_tat_dem_du_ba_nhom()
    {
        $this->themViPham(['severity' => 'critical']);
        $this->themViPham(['severity' => 'warning']);
        $this->themTraThe(['ma_tracuu' => '005']);
        $this->themLoiXml(['critical_error' => 1]);
        $this->themLoiXml(['stt' => 2, 'critical_error' => 0]);

        $tomTat = $this->dichVu()->cua('01013250800123')['summary'];

        $this->assertEquals(5, $tomTat['total']);
        $this->assertEquals(2, $tomTat['order_check']);
        $this->assertEquals(1, $tomTat['hein_card']);
        $this->assertEquals(2, $tomTat['xml3176']);
        $this->assertTrue($tomTat['has_error']);
        $this->assertFalse($tomTat['truncated']);
    }

    /**
     * critical gop hai nguon: severity=critical cua y lenh va critical_error cua XML3176.
     * Nhom tra the khong co khai niem muc do nen khong tinh vao critical, nhung van tinh
     * vao total.
     *
     * @test
     */
    public function critical_gop_y_lenh_va_xml3176()
    {
        $this->themViPham(['severity' => 'critical']);
        $this->themViPham(['severity' => 'warning']);
        $this->themLoiXml(['critical_error' => 1]);
        $this->themTraThe(['ma_tracuu' => '005']);

        $tomTat = $this->dichVu()->cua('01013250800123')['summary'];

        $this->assertEquals(2, $tomTat['critical']);
    }

    /** @test */
    public function dot_sach_thi_has_error_bang_false()
    {
        $tomTat = $this->dichVu()->cua('KHONG-CO-DOT-NAY')['summary'];

        $this->assertEquals(0, $tomTat['total']);
        $this->assertFalse($tomTat['has_error']);
    }

    /**
     * Tran cung de mot dot dieu tri dai khong lam vo gioi han 128MB cua may chu.
     *
     * @test
     */
    public function cham_tran_thi_cat_bot_va_bat_co_truncated()
    {
        for ($i = 0; $i < TreatmentIssueService::TRAN_MOI_NHOM + 5; $i++) {
            $this->themLoiXml(['stt' => $i + 1]);
        }

        $ketQua = $this->dichVu()->cua('01013250800123');

        $this->assertCount(TreatmentIssueService::TRAN_MOI_NHOM, $ketQua['data']['xml3176']);
        $this->assertTrue($ketQua['summary']['truncated']);
    }

    /**
     * Chi truyen treatment_id thi van phai ra duoc hai nhom kia - chung khoa theo ma_lk.
     *
     * @test
     */
    public function chi_truyen_treatment_id_van_suy_ra_duoc_ma_lk()
    {
        $this->themViPham(['treatment_id' => 9001, 'treatment_code' => '01013250800123']);
        $this->themLoiXml();

        $ketQua = $this->dichVu()->cua(null, 9001);

        $this->assertEquals('01013250800123', $ketQua['data']['treatment_code']);
        $this->assertCount(1, $ketQua['data']['xml3176']);
    }

    /** @test */
    public function treatment_id_khong_co_vi_pham_thi_hai_nhom_kia_rong()
    {
        $this->themLoiXml();

        $ketQua = $this->dichVu()->cua(null, 7777);

        $this->assertNull($ketQua['data']['treatment_code']);
        $this->assertSame([], $ketQua['data']['xml3176']);
    }
}

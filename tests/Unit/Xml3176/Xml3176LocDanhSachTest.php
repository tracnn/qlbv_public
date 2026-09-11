<?php

namespace Tests\Unit\Xml3176;

use App\Services\BHYT\Xml3176LocDanhSach;
use Illuminate\Http\Request;
use Tests\TestCase;

/**
 * Man danh sach XML3176 ap 15 bo loc, nhung hai nut xuat chi chuyen duoc mot phan:
 * nut "danh sach ho so" thuc te chi 6/15 co tac dung (5 bo loc JS khong gui, va
 * xml_filter_status + xml3176_error_catalog duoc gui nhung lop Export doc vao bien
 * cuc bo roi KHONG dung), nut "danh sach loi" thieu 6/15.
 *
 * Sai lam nay da duoc va ba lan theo kieu them tung tham so mot (xem chu thich trong
 * index.blade.php) va lan nao cung sot tiep. Lop nay la MOT nguon duy nhat cho ca man
 * danh sach lan ca ba lop Export.
 */
class Xml3176LocDanhSachTest extends TestCase
{
    private function loc(array $ghiDe = []): array
    {
        return array_merge([
            'date_from' => '2026-09-01 00:00:00',
            'date_to'   => '2026-09-30 23:59:59',
            'date_type' => 'date_in',
        ], $ghiDe);
    }

    private function sql(array $ghiDe = []): string
    {
        return Xml3176LocDanhSach::truyVanHoSo($this->loc($ghiDe), [])->toSql();
    }

    /** @test */
    public function tu_request_doc_du_moi_khoa()
    {
        $r = Request::create('/x', 'GET', [
            'date_from' => 'a', 'date_to' => 'b', 'date_type' => 'date_in',
            'treatment_code' => 'TC', 'patient_code' => 'PC',
            'xml_filter_status' => 'has_error', 'xml3176_error_catalog' => '7',
            'hein_card_filter' => 'has_hein_card', 'payment_date_filter' => 'has_payment_date',
            'treatment_type_fillter' => '01', 'ma_khoa' => 'K01',
            'xml_export_status' => 'has_export', 'xml_submit_status' => 'has_submit',
            'xml_sign_status' => 'has_sign', 'imported_by' => 'nguoi', 'ma_cskcb' => '01929',
        ]);

        $loc = Xml3176LocDanhSach::tuRequest($r);

        foreach (Xml3176LocDanhSach::KHOA as $k) {
            $this->assertArrayHasKey($k, $loc, "tuRequest() bo sot khoa $k");
        }

        // Khoa tren man hinh la 'xml3176_error_catalog', khong phai '..._id'.
        $this->assertSame('7', $loc['xml3176_error_catalog']);
        $this->assertSame('TC', $loc['treatment_code']);
        $this->assertSame('K01', $loc['ma_khoa']);
    }

    /** @test */
    public function danh_sach_khoa_du_16_va_khong_trung()
    {
        $k = Xml3176LocDanhSach::KHOA;
        $this->assertCount(16, $k);
        $this->assertSame($k, array_values(array_unique($k)));
    }

    // ─── Hai bo loc truoc day BI BO QUA IM LANG trong Xml3176XmlExport ───────

    /** @test */
    public function xml_filter_status_duoc_ap_that_su()
    {
        // Xml3176XmlExport doc bien nay roi khong dung -> nguoi dung loc "co loi
        // nghiem trong" van nhan ve toan bo ho so trong khoang ngay.
        $this->assertContains('exists', strtolower($this->sql(['xml_filter_status' => 'has_error_critical'])));
        $this->assertContains('not exists', strtolower($this->sql(['xml_filter_status' => 'no_error'])));
    }

    /** @test */
    public function loc_theo_ma_loi_khong_co_trong_danh_muc_thi_khong_doi_truy_van()
    {
        $this->assertSame($this->sql(), $this->sql(['xml3176_error_catalog' => null]));
    }

    // ─── Nam bo loc JS khong gui ────────────────────────────────────────────

    /** @test */
    public function ma_khoa_va_loai_kcb_va_the_bhyt_deu_sinh_dieu_kien()
    {
        $this->assertContains('ma_khoa', $this->sql(['ma_khoa' => 'K01']));
        $this->assertContains('ma_loai_kcb', $this->sql(['treatment_type_fillter' => '01']));
        $this->assertContains('ma_the_bhyt', $this->sql(['hein_card_filter' => 'has_hein_card']));
    }

    /** @test */
    public function ma_ho_so_va_ma_benh_nhan_ngan_mach_dung_nhu_man_danh_sach()
    {
        // Man danh sach: co ma ho so thi BO khoang ngay va moi bo loc khac.
        $sql = $this->sql(['treatment_code' => 'TC', 'ma_khoa' => 'K01']);
        $this->assertContains('ma_lk', $sql);
        $this->assertNotContains('ngay_vao', $sql);
        $this->assertNotContains('ma_khoa', $sql);

        $sql = $this->sql(['patient_code' => 'PC', 'ma_khoa' => 'K01']);
        $this->assertContains('ma_bn', $sql);
        $this->assertNotContains('ngay_vao', $sql);
    }

    /** @test */
    public function ma_ho_so_uu_tien_hon_ma_benh_nhan()
    {
        $sql = $this->sql(['treatment_code' => 'TC', 'patient_code' => 'PC']);
        $this->assertContains('ma_lk', $sql);
        $this->assertNotContains('ma_bn', $sql);
    }

    // ─── Khoang ngay ────────────────────────────────────────────────────────

    /** @test */
    public function date_type_chon_dung_cot_ngay()
    {
        $this->assertContains('ngay_vao', $this->sql(['date_type' => 'date_in']));
        $this->assertContains('ngay_ra', $this->sql(['date_type' => 'date_out']));
        $this->assertContains('created_at', $this->sql(['date_type' => 'date_create']));
        $this->assertContains('updated_at', $this->sql(['date_type' => 'date_update']));
        // Mac dinh la ngay thanh toan, giong man danh sach.
        $this->assertContains('ngay_ttoan', $this->sql(['date_type' => null]));
    }

    /** @test */
    public function ngay_dang_chuoi_10_ky_tu_duoc_no_ra_dau_va_cuoi_ngay()
    {
        $q = Xml3176LocDanhSach::truyVanHoSo(
            ['date_from' => '2026-09-01', 'date_to' => '2026-09-30', 'date_type' => 'date_in'], []);
        $b = $q->getBindings();
        $this->assertContains('202609010000', $b);
        $this->assertContains('202609302359', $b);
    }

    // ─── Loc co so ──────────────────────────────────────────────────────────

    /** @test */
    public function ma_co_so_chi_ap_khi_nam_trong_danh_sach()
    {
        $q = Xml3176LocDanhSach::truyVanHoSo($this->loc(['ma_cskcb' => '01929']), ['01929' => 'BV A']);
        $this->assertContains('01929', $q->getBindings());

        // Ma la thi KHONG loc (man danh sach, khong phai thao tac ghi).
        $q = Xml3176LocDanhSach::truyVanHoSo($this->loc(['ma_cskcb' => 'XXX']), ['01929' => 'BV A']);
        $this->assertNotContains('XXX', $q->getBindings());
    }

    // ─── Truy van ma_lk dung cho danh sach loi ──────────────────────────────

    /** @test */
    public function truy_van_ma_lk_chi_lay_mot_cot_nhung_giu_du_bo_loc()
    {
        $sql = Xml3176LocDanhSach::truyVanMaLk($this->loc(['ma_khoa' => 'K01']), [])->toSql();
        $this->assertContains('ma_lk', $sql);
        $this->assertContains('ma_khoa', $sql, 'Truy van ma_lk phai mang theo DU bo loc cua man danh sach');
        $this->assertNotContains('ho_ten', $sql);
    }
}

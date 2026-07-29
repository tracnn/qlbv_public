<?php

namespace Tests\Unit\Import;

use DB;
use Tests\TestCase;
use App\Services\Import\GhiTheoLo;
use App\Services\Import\KetQuaNhapDanhMuc;

class GhiTheoLoTest extends TestCase
{
    /** @test */
    public function khoa_dong_gom_dung_cac_cot_khoa()
    {
        $a = GhiTheoLo::khoaDong(['ma' => 'A', 'ten' => 'X', 'gia' => 10], ['ma', 'ten']);
        $b = GhiTheoLo::khoaDong(['ma' => 'A', 'ten' => 'X', 'gia' => 99], ['ma', 'ten']);

        $this->assertSame($a, $b, 'Cot ngoai khoa khong duoc anh huong');
    }

    /** @test */
    public function khoa_dong_phan_biet_khi_mot_cot_khoa_khac()
    {
        $a = GhiTheoLo::khoaDong(['ma' => 'A', 'cs' => '01929'], ['ma', 'cs']);
        $b = GhiTheoLo::khoaDong(['ma' => 'A', 'cs' => '37470'], ['ma', 'cs']);

        $this->assertNotSame($a, $b);
    }

    /** @test */
    public function khoa_dong_bo_khoang_trang_thua()
    {
        $this->assertSame(
            GhiTheoLo::khoaDong(['ma' => '  A  '], ['ma']),
            GhiTheoLo::khoaDong(['ma' => 'A'], ['ma'])
        );
    }

    /** @test */
    public function khoa_dong_coi_null_va_chuoi_rong_la_mot()
    {
        $this->assertSame(
            GhiTheoLo::khoaDong(['ma' => 'A', 'cs' => null], ['ma', 'cs']),
            GhiTheoLo::khoaDong(['ma' => 'A', 'cs' => ''], ['ma', 'cs'])
        );
    }

    /** @test */
    public function khoa_dong_coi_10_va_10_chan_la_mot()
    {
        // Cot decimal(18,2) tra ve '10.00'; khong chuan hoa thi dong da co bi coi la moi.
        $this->assertSame(
            GhiTheoLo::khoaDong(['gia' => 10], ['gia']),
            GhiTheoLo::khoaDong(['gia' => '10.00'], ['gia'])
        );

        $this->assertSame(
            GhiTheoLo::khoaDong(['gia' => '10.5'], ['gia']),
            GhiTheoLo::khoaDong(['gia' => '10.50'], ['gia'])
        );
    }

    /** @test */
    public function khoa_dong_giu_nguyen_so_0_dan_dau_cua_ma()
    {
        // Ma co so '01929' ma ep sang so se mat so 0 dan dau va dung voi '1929'.
        $this->assertNotSame(
            GhiTheoLo::khoaDong(['cs' => '01929'], ['cs']),
            GhiTheoLo::khoaDong(['cs' => '1929'], ['cs'])
        );
    }

    /** @test */
    public function khoa_dong_thieu_cot_thi_khong_no()
    {
        $this->assertInternalType('string', GhiTheoLo::khoaDong(['ma' => 'A'], ['ma', 'khong_co']));
    }

    /** @test */
    public function khong_thay_doi_thi_khong_can_cap_nhat()
    {
        $cu = (object) ['ten' => 'X', 'gia' => '10'];

        $this->assertFalse(GhiTheoLo::coThayDoi(['ten' => 'X', 'gia' => '10'], $cu));
    }

    /** @test */
    public function so_sanh_khong_phan_biet_kieu_so_va_chuoi()
    {
        // Gia tri tu Excel la chuoi, tu CSDL co the la so.
        $this->assertFalse(GhiTheoLo::coThayDoi(['gia' => '10'], (object) ['gia' => 10]));
    }

    /** @test */
    public function so_sanh_khong_phan_biet_dinh_dang_thap_phan()
    {
        // Cot decimal(18,2) tra ve '10.00'; so bang chuoi se cap nhat oan MOI dong moi lan
        // nhap lai.
        $this->assertFalse(GhiTheoLo::coThayDoi(['gia' => 10], (object) ['gia' => '10.00']));
        $this->assertFalse(GhiTheoLo::coThayDoi(['gia' => '10'], (object) ['gia' => '10.000']));
        $this->assertTrue(GhiTheoLo::coThayDoi(['gia' => 10], (object) ['gia' => '10.01']));
    }

    /** @test */
    public function chuoi_khong_phai_so_van_so_nguyen_van()
    {
        $this->assertTrue(GhiTheoLo::coThayDoi(['ten' => 'A'], (object) ['ten' => 'B']));
        $this->assertFalse(GhiTheoLo::coThayDoi(['ten' => '  A '], (object) ['ten' => 'A']));
    }

    /** @test */
    public function so_sanh_coi_null_va_chuoi_rong_la_mot()
    {
        $this->assertFalse(GhiTheoLo::coThayDoi(['ghi_chu' => ''], (object) ['ghi_chu' => null]));
    }

    /** @test */
    public function mot_truong_doi_thi_can_cap_nhat()
    {
        $cu = (object) ['ten' => 'X', 'gia' => '10'];

        $this->assertTrue(GhiTheoLo::coThayDoi(['ten' => 'Y', 'gia' => '10'], $cu));
    }

    /** @test */
    public function truong_moi_chua_co_ben_cu_thi_can_cap_nhat()
    {
        $this->assertTrue(GhiTheoLo::coThayDoi(['ten' => 'X', 'gia' => '10'], (object) ['ten' => 'X']));
    }

    /** @test */
    public function chi_so_cac_truong_duoc_nhap_khong_so_cot_khac_cua_ban_ghi()
    {
        // Ban ghi cu co id, created_at... khong duoc coi la "thay doi".
        $cu = (object) ['id' => 7, 'ten' => 'X', 'created_at' => '2024-01-01'];

        $this->assertFalse(GhiTheoLo::coThayDoi(['ten' => 'X'], $cu));
    }

    // ===== cham co so du lieu =====

    private function cotKhoa()
    {
        return ['ma_thuoc', 'ten_thuoc', 'ham_luong', 'so_dang_ky', 'don_gia_bh',
                'tt_thau', 'tu_ngay', 'ma_cskcb'];
    }

    private function dong($ma, $maCskcb = '01929', $ten = 'X')
    {
        return [
            'ma_thuoc' => $ma, 'ten_hoat_chat' => 'X', 'ten_thuoc' => $ten,
            'don_vi_tinh' => 'Vien', 'ham_luong' => '1', 'duong_dung' => 'Uong',
            'ma_duong_dung' => '1', 'dang_bao_che' => 'Vien', 'so_dang_ky' => 'SDK',
            'don_gia_bh' => 10, 'tt_thau' => 'T', 'tu_ngay' => '20240101',
            'ma_cskcb' => $maCskcb,
        ];
    }

    private function don()
    {
        DB::table('medicine_catalogs')->where('ma_thuoc', 'like', 'ZZLO%')->delete();
    }

    /** @test */
    public function chen_moi_roi_nhap_lai_thi_khong_ghi_them()
    {
        $this->don();

        try {
            $kq = new KetQuaNhapDanhMuc();
            (new GhiTheoLo('medicine_catalogs', $this->cotKhoa(), $kq))
                ->ghi([['dong_excel' => 2, 'du_lieu' => $this->dong('ZZLO1')]]);

            $this->assertSame(1, $kq->toArray()['so_da_nhap']);

            $kq2 = new KetQuaNhapDanhMuc();
            (new GhiTheoLo('medicine_catalogs', $this->cotKhoa(), $kq2))
                ->ghi([['dong_excel' => 2, 'du_lieu' => $this->dong('ZZLO1')]]);

            $a = $kq2->toArray();
            $this->assertSame(0, $a['so_da_nhap'], 'Nhap lai ma van chen them');
            $this->assertSame(1, $a['so_khong_doi']);
        } finally {
            $this->don();
        }
    }

    /** @test */
    public function chi_cap_nhat_dong_thuc_su_doi()
    {
        $this->don();

        try {
            $kq = new KetQuaNhapDanhMuc();
            (new GhiTheoLo('medicine_catalogs', $this->cotKhoa(), $kq))
                ->ghi([['dong_excel' => 2, 'du_lieu' => $this->dong('ZZLO2', '01929', 'Ten cu')]]);

            $kq2 = new KetQuaNhapDanhMuc();
            (new GhiTheoLo('medicine_catalogs', $this->cotKhoa(), $kq2))
                ->ghi([['dong_excel' => 2, 'du_lieu' => $this->dong('ZZLO2', '01929', 'Ten cu') + ['nuoc_sx' => 'VN']]]);

            $this->assertSame(1, $kq2->toArray()['so_da_cap_nhat']);
            $this->assertSame('VN', DB::table('medicine_catalogs')->where('ma_thuoc', 'ZZLO2')->value('nuoc_sx'));
        } finally {
            $this->don();
        }
    }

    /** @test */
    public function hai_co_so_cung_ma_thuoc_thanh_hai_dong()
    {
        $this->don();

        try {
            $kq = new KetQuaNhapDanhMuc();
            (new GhiTheoLo('medicine_catalogs', $this->cotKhoa(), $kq))->ghi([
                ['dong_excel' => 2, 'du_lieu' => $this->dong('ZZLO3', '01929')],
                ['dong_excel' => 3, 'du_lieu' => $this->dong('ZZLO3', '37470')],
            ]);

            $this->assertSame(2, $kq->toArray()['so_da_nhap']);
            $this->assertSame(2, DB::table('medicine_catalogs')->where('ma_thuoc', 'ZZLO3')->count());
        } finally {
            $this->don();
        }
    }

    /** @test */
    public function hai_dong_trung_khoa_trong_cung_tep_chi_thanh_mot()
    {
        $this->don();

        try {
            $kq = new KetQuaNhapDanhMuc();
            (new GhiTheoLo('medicine_catalogs', $this->cotKhoa(), $kq))->ghi([
                ['dong_excel' => 2, 'du_lieu' => $this->dong('ZZLO4', '01929', 'Dong truoc')],
                ['dong_excel' => 3, 'du_lieu' => $this->dong('ZZLO4', '01929', 'Dong truoc')],
            ]);

            $this->assertSame(1, DB::table('medicine_catalogs')->where('ma_thuoc', 'ZZLO4')->count());
        } finally {
            $this->don();
        }
    }

    /** @test */
    public function o_dinh_tab_khong_lam_chen_lai_dong_da_co()
    {
        // Bat bien: gia tri khoa da luu khong con khoang trang thua, va gia tri vao cung
        // da qua CatalogImportService::catKhoangTrang(). Khi do o Excel dinh TAB van phai
        // khop dong da co.
        //
        // Neu bat bien bi pha, truy van tra se truot va dong bi chen them MOI LAN NHAP -
        // da gap that voi ma dich vu '24.0019.1685.K.01910' (sinh 5 ban).
        $this->don();

        try {
            $kq = new KetQuaNhapDanhMuc();
            (new GhiTheoLo('medicine_catalogs', $this->cotKhoa(), $kq))
                ->ghi([['dong_excel' => 2, 'du_lieu' => $this->dong('ZZLO9')]]);

            $kq2 = new KetQuaNhapDanhMuc();
            (new GhiTheoLo('medicine_catalogs', $this->cotKhoa(), $kq2))->ghi([[
                'dong_excel' => 2,
                'du_lieu' => \App\Services\CatalogImportService::catKhoangTrang(
                    $this->dong("ZZLO9\t")
                ),
            ]]);

            $a = $kq2->toArray();

            $this->assertSame(0, $a['so_da_nhap'], 'Dong da co van bi chen lai');
            $this->assertSame(1, $a['so_khong_doi']);
        } finally {
            $this->don();
        }
    }
}

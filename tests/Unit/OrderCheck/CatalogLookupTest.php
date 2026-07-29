<?php

namespace Tests\Unit\OrderCheck;

use DB;
use Tests\TestCase;
use App\Services\OrderCheck\Support\CatalogLookup;

class CatalogLookupTest extends TestCase
{
    /** @test */
    public function bang_rong_thi_khong_san_sang()
    {
        // Day la phep kiem QUAN TRONG NHAT cua ca dot: don vi chua nhap danh muc ma quy
        // tac van chay thi MOI dich vu thanh vi pham - sai ma trong nhu dung.
        $lk = new CatalogLookup('service_catalogs', 'ma_dich_vu');

        $this->assertFalse($lk->sanSang(),
            'Bang danh muc dang rong ma van bao san sang - quy tac se bat loi oan toan bo');
    }

    /** @test */
    public function chua_nap_thi_khong_ma_nao_duoc_coi_la_co()
    {
        $lk = new CatalogLookup('service_catalogs', 'ma_dich_vu');

        $this->assertFalse($lk->coTrongDanhMuc('XYZ'));
    }

    /** @test */
    public function nap_lo_rong_khong_no()
    {
        $lk = new CatalogLookup('service_catalogs', 'ma_dich_vu');
        $lk->nap([]);

        $this->assertFalse($lk->coTrongDanhMuc('XYZ'));
    }

    /** @test */
    public function ma_rong_hoac_null_khong_bao_gio_duoc_coi_la_co()
    {
        $lk = new CatalogLookup('service_catalogs', 'ma_dich_vu');
        $lk->nap(['', null, '  ']);

        $this->assertFalse($lk->coTrongDanhMuc(''));
        $this->assertFalse($lk->coTrongDanhMuc(null));
        $this->assertFalse($lk->coTrongDanhMuc('  '));
    }

    /** @test */
    public function nap_hai_lan_thi_cong_don_chu_khong_xoa_lan_truoc()
    {
        // Moi phieu nap mot lo; lo sau khong duoc xoa ket qua lo truoc trong cung
        // vong doi doi tuong.
        $lk = new CatalogLookup('service_catalogs', 'ma_dich_vu');

        $lk->datSanChoTest(['A1']);
        $lk->datSanChoTest(['B2']);

        $this->assertTrue($lk->coTrongDanhMuc('A1'));
        $this->assertTrue($lk->coTrongDanhMuc('B2'));
    }

    /** @test */
    public function so_sanh_ma_khong_phan_biet_khoang_trang_thua()
    {
        $lk = new CatalogLookup('service_catalogs', 'ma_dich_vu');
        $lk->datSanChoTest(['A1']);

        $this->assertTrue($lk->coTrongDanhMuc(' A1 '));
    }

    private function traThuoc(array $dong)
    {
        $lk = new CatalogLookup('medicine_catalogs', 'ma_thuoc', 'ten_thuoc');
        $lk->datSanChoTest([], $dong);

        return $lk;
    }

    /** @test */
    public function tra_duoc_ten_theo_ma()
    {
        // Mot ma BHXH co the mang nhieu ten: do tren HIS that co ma 226 ten.
        $lk = $this->traThuoc([
            '40.805' => [
                ['ten' => 'Wosulin 30/70', 'tu' => '20240101', 'den' => '20241231'],
                ['ten' => 'INSUNOVA - 30/70', 'tu' => '20240101', 'den' => '20241231'],
            ],
        ]);

        $this->assertSame(['Wosulin 30/70', 'INSUNOVA - 30/70'], $lk->tenTheoMa('40.805', 20240601));
        $this->assertSame([], $lk->tenTheoMa('99.999', 20240601));
    }

    /** @test */
    public function ten_cua_dong_het_hieu_luc_bi_loai()
    {
        $lk = $this->traThuoc([
            'A1' => [
                ['ten' => 'Ten cu', 'tu' => '20230101', 'den' => '20231231'],
                ['ten' => 'Ten moi', 'tu' => '20240101', 'den' => ''],
            ],
        ]);

        $this->assertSame(['Ten cu'], $lk->tenTheoMa('A1', 20230601));
        $this->assertSame(['Ten moi'], $lk->tenTheoMa('A1', 20240601));
    }

    /** @test */
    public function ma_het_hieu_luc_coi_nhu_khong_co_trong_danh_muc()
    {
        $lk = $this->traThuoc([
            'A1' => [['ten' => 'X', 'tu' => '20230101', 'den' => '20231231']],
        ]);

        $this->assertTrue($lk->coTrongDanhMuc('A1', 20230601));
        $this->assertFalse($lk->coTrongDanhMuc('A1', 20240601));
    }

    /** @test */
    public function khong_truyen_ngay_thi_khong_loc_hieu_luc()
    {
        // Duong lui cho loi goi cu va cho test khong quan tam ngay.
        $lk = $this->traThuoc([
            'A1' => [['ten' => 'X', 'tu' => '20230101', 'den' => '20231231']],
        ]);

        $this->assertTrue($lk->coTrongDanhMuc('A1'));
    }

    /** @test */
    public function ten_trung_nhau_chi_tra_mot_lan()
    {
        $lk = $this->traThuoc([
            'A1' => [
                ['ten' => 'X', 'tu' => '', 'den' => ''],
                ['ten' => ' X ', 'tu' => '', 'den' => ''],
            ],
        ]);

        $this->assertSame(['X'], $lk->tenTheoMa('A1', 20240601));
    }

    /** @test */
    public function bang_khong_co_cot_ngay_thi_khong_loc_hieu_luc()
    {
        // icd10_categories khong co tu_ngay/den_ngay.
        $lk = new CatalogLookup('icd10_categories', 'icd_code', null, null, null);
        $lk->datSanChoTest(['A00']);

        $this->assertTrue($lk->coTrongDanhMuc('A00', 20240601));
        $this->assertTrue($lk->coTrongDanhMuc('A00', 19990101));
    }

    /** @test */
    public function dat_rong_cho_test_lam_san_sang_tra_false()
    {
        // Khong duoc dua vao noi dung bang that: icd10_categories dang co 12.229 dong.
        $lk = new CatalogLookup('icd10_categories', 'icd_code', null, null, null, ['is_active' => 1]);
        $lk->datRongChoTest();

        $this->assertFalse($lk->sanSang());
        $this->assertFalse($lk->coTrongDanhMuc('A00'));
    }

    /** @test */
    public function dieu_kien_loc_duoc_ap_trong_san_sang()
    {
        // Bang co dong nhung KHONG dong nao thoa dieu kien -> PHAI tra false. Neu khong,
        // moi ma se thanh vi pham.
        $lk = new CatalogLookup('icd10_categories', 'icd_code', null, null, null, ['is_active' => 9]);

        $this->assertFalse($lk->sanSang(), 'Dieu kien khong duoc ap trong sanSang');
    }

    /** @test */
    public function dieu_kien_loc_duoc_ap_khi_nap()
    {
        DB::table('icd10_categories')->insert([
            ['icd_code' => 'ZZ1', 'icd_name' => 'Tat', 'is_active' => 0],
            ['icd_code' => 'ZZ2', 'icd_name' => 'Bat', 'is_active' => 1],
        ]);

        try {
            $lk = new CatalogLookup('icd10_categories', 'icd_code', null, null, null, ['is_active' => 1]);
            $lk->nap(['ZZ1', 'ZZ2']);

            $this->assertFalse($lk->coTrongDanhMuc('ZZ1'), 'Dong is_active=0 van duoc coi la co');
            $this->assertTrue($lk->coTrongDanhMuc('ZZ2'));
        } finally {
            DB::table('icd10_categories')->whereIn('icd_code', ['ZZ1', 'ZZ2'])->delete();
        }
    }

    /** medicine_catalogs co nhieu cot NOT NULL khong mac dinh; dien du de chen duoc */
    private function dongThuoc($ma, $maCskcb)
    {
        return [
            'ma_thuoc' => $ma, 'ten_hoat_chat' => 'X', 'ten_thuoc' => 'X',
            'don_vi_tinh' => 'Vien', 'ham_luong' => '1', 'duong_dung' => 'Uong',
            'ma_duong_dung' => '1', 'dang_bao_che' => 'Vien', 'so_dang_ky' => 'SDK',
            'ma_cskcb' => $maCskcb,
        ];
    }

    private function traCoSo(array $dong)
    {
        $lk = new CatalogLookup('medicine_catalogs', 'ma_thuoc', 'ten_thuoc',
            'tu_ngay', 'den_ngay', [], 'ma_cskcb');
        $lk->datSanChoTest([], $dong);

        return $lk;
    }

    /** @test */
    public function dong_rong_ma_co_so_dung_chung_moi_co_so()
    {
        // Dieu kien de trien khai khong lam tat cac kiem tra danh muc dang chay: du lieu
        // danh muc cu chua gan ma co so.
        $lk = $this->traCoSo(['A1' => [['ten' => 'X', 'tu' => '', 'den' => '', 'cs' => '']]]);

        $this->assertTrue($lk->coTrongDanhMuc('A1', null, '01929'));
        $this->assertTrue($lk->coTrongDanhMuc('A1', null, '37470'));
    }

    /** @test */
    public function dong_co_ma_co_so_chi_khop_dung_co_so_do()
    {
        $lk = $this->traCoSo(['A1' => [['ten' => 'X', 'tu' => '', 'den' => '', 'cs' => '01929']]]);

        $this->assertTrue($lk->coTrongDanhMuc('A1', null, '01929'));
        $this->assertFalse($lk->coTrongDanhMuc('A1', null, '37470'));
    }

    /** @test */
    public function khong_truyen_co_so_thi_khong_loc()
    {
        $lk = $this->traCoSo(['A1' => [['ten' => 'X', 'tu' => '', 'den' => '', 'cs' => '01929']]]);

        $this->assertTrue($lk->coTrongDanhMuc('A1'));
    }

    /** @test */
    public function ten_theo_ma_cung_loc_theo_co_so()
    {
        $lk = $this->traCoSo(['A1' => [
            ['ten' => 'Ten BM', 'tu' => '', 'den' => '', 'cs' => '01929'],
            ['ten' => 'Ten NB', 'tu' => '', 'den' => '', 'cs' => '37470'],
        ]]);

        $this->assertSame(['Ten BM'], $lk->tenTheoMa('A1', null, '01929'));
        $this->assertSame(['Ten NB'], $lk->tenTheoMa('A1', null, '37470'));
    }

    /** @test */
    public function bang_khong_co_khai_niem_co_so_thi_khong_loc()
    {
        // icd10_categories, medical_staffs khong co cot ma_cskcb.
        $lk = new CatalogLookup('icd10_categories', 'icd_code', null, null, null, [], null);
        $lk->datSanChoTest(['A00']);

        $this->assertTrue($lk->coTrongDanhMuc('A00', null, '01929'));
    }

    /** @test */
    public function san_sang_tinh_rieng_cho_tung_co_so()
    {
        DB::table('medicine_catalogs')->insert([$this->dongThuoc('ZZTH1', '01929')]);

        try {
            $lk = new CatalogLookup('medicine_catalogs', 'ma_thuoc', 'ten_thuoc',
                'tu_ngay', 'den_ngay', [], 'ma_cskcb');

            $this->assertTrue($lk->sanSang('01929'));
            $this->assertFalse($lk->sanSang('37470'),
                'Co so chua nhap danh muc ma van bao san sang');
        } finally {
            DB::table('medicine_catalogs')->where('ma_thuoc', 'ZZTH1')->delete();
        }
    }

    /** @test */
    public function dong_dung_chung_lam_moi_co_so_san_sang()
    {
        DB::table('medicine_catalogs')->insert([$this->dongThuoc('ZZTH2', null)]);

        try {
            $lk = new CatalogLookup('medicine_catalogs', 'ma_thuoc', 'ten_thuoc',
                'tu_ngay', 'den_ngay', [], 'ma_cskcb');

            $this->assertTrue($lk->sanSang('37470'));
        } finally {
            DB::table('medicine_catalogs')->where('ma_thuoc', 'ZZTH2')->delete();
        }
    }

    /** @test */
    public function nap_kieu_cu_va_kieu_moi_cong_don_duoc_voi_nhau()
    {
        $lk = new CatalogLookup('medicine_catalogs', 'ma_thuoc', 'ten_thuoc');
        $lk->datSanChoTest(['A1']);
        $lk->datSanChoTest([], ['B2' => [['ten' => 'Ten B', 'tu' => '', 'den' => '']]]);

        $this->assertTrue($lk->coTrongDanhMuc('A1', 20240601));
        $this->assertTrue($lk->coTrongDanhMuc('B2', 20240601));
        $this->assertSame(['Ten B'], $lk->tenTheoMa('B2', 20240601));
    }
}

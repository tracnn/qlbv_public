<?php

namespace Tests\Unit\Mcct;

use App\Services\Mcct\NguongMienCungChiTra;
use Tests\TestCase;

/**
 * Diem c khoan 2 Dieu 18 ND 188/2025/ND-CP: khi luong co so DOI GIUA NAM, so tien con lai
 * phai cung chi tra tu thoi diem doi luong den het 31/12 la
 *
 *     R = ( 6 - A / L_cu ) x L_moi
 *
 * voi A la tong so tien da cung chi tra tu 01/01 den TRUOC ngay doi luong.
 *
 * Ngoai le: A da du hoac vuot qua 6 thang luong co so cu thi duoc huong quyen loi ngay,
 * KHONG ap dung cong thuc.
 *
 * Lay 6 thang luong co so HIEN HANH lam nguong la SAI quy dinh: no bo qua phan da dong o
 * giai doan luong cu.
 */
class MienCungChiTraTheoQuyDinhTest extends TestCase
{
    protected function bangLuong()
    {
        return [
            '2023-07-01' => 1800000,
            '2024-07-01' => 2340000,
            '2026-07-01' => 2530000,
        ];
    }

    /** @param array $dong cap [ngay_ra, luy_ke] */
    protected function dong(array $cap)
    {
        $ra = [];

        foreach ($cap as $c) {
            $ra[] = ['ngay_ra' => $c[0], 'ngay_nhan' => $c[0], 't_bn_cct_luy_ke' => (float) $c[1]];
        }

        return $ra;
    }

    /** Du lieu THAT cong tra ve ngay 2026-09-03 cho the HT3382797052765 */
    protected function dongThat()
    {
        return $this->dong([
            ['2026-08-14', 3862166], ['2026-07-24', 2968192], ['2026-07-04', 2057558],
            ['2026-06-23', 1396758], ['2026-05-21', 276601],  ['2026-05-11', 207479],
            ['2026-04-23', 183549],  ['2026-04-22', 183549],  ['2026-04-14', 183549],
            ['2026-03-27', 162514],  ['2026-03-14', 140104],  ['2026-02-25', 30835],
            ['2026-01-19', 0],       ['2026-01-13', 0],
        ]);
    }

    /**
     * VI DU CO LOI GIAI trong Thong bao so /BM-TCKT ngay 02/7/2026 cua Benh vien Bach Mai:
     *
     *   "Ong Nguyen Van A ... den ngay 30/6/2026 co so tien cung chi tra la 13.000.000 dong.
     *    Den ngay 01/7/2026 ong A vao vien dieu tri tiep, nhan vien xac dinh muc cung chi tra
     *    nhu sau: = (6 - 13.000.000/2.340.000) * 2.530.000 = 1.124.444 dong, sau do cong them
     *    phan da co so tien cung chi tra: 13.000.000 + 1.124.444 = 14.124.444 dong."
     *
     * Day la vi du CO THAM QUYEN nen no phai nam trong bo test: no chot ca cong thuc lan buoc
     * "cong them phan da dong" ma man hinh phai hien.
     */
    /** @test */
    public function vi_du_ong_nguyen_van_a_trong_thong_bao_benh_vien()
    {
        $dong = $this->dong([['2026-06-30', 13000000]]);

        $ra = NguongMienCungChiTra::tinhTheoQuyDinh($dong, '2026-07-01', $this->bangLuong(), 6);

        $this->assertEquals(13000000.0, $ra['da_dong_truoc_moc'], '', 0.01);
        $this->assertEquals(1124444.44, $ra['so_tien_con_phai_dong'], '', 0.5);
        $this->assertEquals(14124444.44, $ra['tong_nguong_ca_nam'], '', 0.5);
        $this->assertFalse($ra['du_dieu_kien'], 'Thong bao ghi ro: chua du mien cung chi tra');
    }

    /**
     * Tong nguong ca nam = A + R. Day moi la con so SO SANH DUOC voi luy ke - ca hai cung
     * tinh tu 01/01. Rieng R thi tinh tu moc doi luong, dat canh luy ke se khien nguoi dung
     * tru nham va ra so sai.
     */
    /** @test */
    public function tong_nguong_ca_nam_so_sanh_duoc_truc_tiep_voi_luy_ke()
    {
        $ra = NguongMienCungChiTra::tinhTheoQuyDinh(
            $this->dongThat(), '2026-09-03', $this->bangLuong(), 6);

        $this->assertEquals(
            $ra['da_dong_truoc_moc'] + $ra['so_tien_con_phai_dong'],
            $ra['tong_nguong_ca_nam'], '', 0.01);

        $this->assertEquals(15066588.03, $ra['tong_nguong_ca_nam'], '', 0.05);

        // Con thieu phai bang tong nguong tru luy ke - hai cach tinh phai gap nhau o day.
        $this->assertEquals($ra['tong_nguong_ca_nam'] - $ra['luy_ke_tong'], $ra['con_thieu'], '', 0.01);
    }

    /** Nam khong doi luong: tong nguong chinh la 6 x luong hien hanh, nhu cach hieu cu */
    /** @test */
    public function nam_khong_doi_luong_thi_tong_nguong_la_sau_thang_luong()
    {
        $dong = $this->dong([['2025-08-14', 5000000]]);

        $ra = NguongMienCungChiTra::tinhTheoQuyDinh($dong, '2025-09-03', $this->bangLuong(), 6);

        $this->assertEquals(6 * 2340000, $ra['tong_nguong_ca_nam'], '', 0.01);
    }

    /**
     * Da du truoc moc: nguong da vuot qua la 6 x luong CU. Khong duoc tra A + R o day - R am
     * nen tong se be hon chinh so da dong, mot con so vo nghia tren man hinh.
     */
    /** @test */
    public function da_du_truoc_moc_thi_tong_nguong_la_sau_thang_luong_cu()
    {
        $dong = $this->dong([['2026-06-30', 20000000]]);

        $ra = NguongMienCungChiTra::tinhTheoQuyDinh($dong, '2026-09-03', $this->bangLuong(), 6);

        $this->assertTrue($ra['du_dieu_kien']);
        $this->assertEquals(6 * 2340000, $ra['tong_nguong_ca_nam'], '', 0.01);
    }

    /** @test */
    public function du_lieu_that_tinh_dung_so_tien_con_phai_dong()
    {
        $ra = NguongMienCungChiTra::tinhTheoQuyDinh(
            $this->dongThat(), '2026-09-03', $this->bangLuong(), 6);

        // A = luy ke cua dot ra vien 23/06/2026, dot cuoi cung TRUOC moc 01/07/2026
        $this->assertEquals(1396758.0, $ra['da_dong_truoc_moc'], '', 0.01);

        // 6 - 1396758/2340000 = 5,403094871...
        $this->assertEquals(5.403094871794872, $ra['so_thang_con_lai'], '', 0.000001);

        // R = 5,403094871... x 2.530.000
        $this->assertEquals(13669830.03, $ra['so_tien_con_phai_dong'], '', 0.05);

        // B = 3.862.166 - 1.396.758
        $this->assertEquals(2465408.0, $ra['da_dong_doan_hien_tai'], '', 0.01);

        $this->assertEquals(11204422.03, $ra['con_thieu'], '', 0.05);
        $this->assertFalse($ra['du_dieu_kien']);
        $this->assertTrue($ra['co_doi_luong']);
        $this->assertSame('2026-07-01', $ra['moc_doi_luong']);
        $this->assertSame(2340000, $ra['luong_truoc_moc']);
        $this->assertSame(2530000, $ra['luong_hien_tai']);
    }

    /**
     * Cach cu (6 x luong hien hanh) cho ra con thieu 11.317.834 d. Con so do KHAC voi ket
     * qua dung 11.204.422 d - test nay chot lai su khac biet de khong ai vo tinh quay ve
     * cach tinh cu.
     */
    /** @test */
    public function khac_voi_cach_tinh_cung_nhac_sau_thang_luong_hien_hanh()
    {
        $ra = NguongMienCungChiTra::tinhTheoQuyDinh(
            $this->dongThat(), '2026-09-03', $this->bangLuong(), 6);

        $cachCu = 6 * 2530000 - 3862166;

        $this->assertEquals(11317834, $cachCu, '', 0.01);
        $this->assertNotEquals($cachCu, round($ra['con_thieu'], 2));
    }

    /**
     * Nam KHONG co moc doi luong: phai ra dung ket qua nhu cach cu. Day la chot tuong thich
     * nguoc - phan lon cac nam se di qua nhanh nay.
     */
    /** @test */
    public function nam_khong_doi_luong_thi_giu_nguyen_hanh_vi_cu()
    {
        $dong = $this->dong([['2025-08-14', 5000000], ['2025-03-02', 1000000]]);

        $ra = NguongMienCungChiTra::tinhTheoQuyDinh($dong, '2025-09-03', $this->bangLuong(), 6);

        $this->assertFalse($ra['co_doi_luong']);
        $this->assertEquals(0.0, $ra['da_dong_truoc_moc'], '', 0.01);
        $this->assertEquals(6.0, $ra['so_thang_con_lai'], '', 0.000001);
        $this->assertEquals(6 * 2340000, $ra['so_tien_con_phai_dong'], '', 0.01);
        $this->assertEquals(5000000.0, $ra['da_dong_doan_hien_tai'], '', 0.01);
        $this->assertEquals(6 * 2340000 - 5000000, $ra['con_thieu'], '', 0.01);
        $this->assertFalse($ra['du_dieu_kien']);
    }

    /** @test */
    public function nam_khong_doi_luong_va_vuot_nguong_thi_du_dieu_kien()
    {
        $dong = $this->dong([['2025-08-14', 14040001]]);

        $ra = NguongMienCungChiTra::tinhTheoQuyDinh($dong, '2025-09-03', $this->bangLuong(), 6);

        $this->assertTrue($ra['du_dieu_kien']);
        $this->assertEquals(0.0, $ra['con_thieu'], '', 0.01);
    }

    /** Bang dung nguong, nam khong doi luong: cau chu la "lon hon" nen CHUA du */
    /** @test */
    public function bang_dung_nguong_thi_chua_du()
    {
        $dong = $this->dong([['2025-08-14', 14040000]]);

        $ra = NguongMienCungChiTra::tinhTheoQuyDinh($dong, '2025-09-03', $this->bangLuong(), 6);

        $this->assertFalse($ra['du_dieu_kien']);
    }

    /**
     * NGOAI LE cua quy dinh: da dong DU 6 thang luong CU truoc ngay doi luong thi duoc huong
     * quyen loi ngay, khong ap dung cong thuc. Cau chu la "da du HOAC vuot qua" nen dung dau
     * >= - khac voi dau > cua truong hop thuong.
     */
    /** @test */
    public function da_du_sau_thang_luong_cu_truoc_moc_thi_du_dieu_kien_ngay()
    {
        $dong = $this->dong([['2026-08-14', 14100000], ['2026-06-30', 14040000]]);

        $ra = NguongMienCungChiTra::tinhTheoQuyDinh($dong, '2026-09-03', $this->bangLuong(), 6);

        $this->assertTrue($ra['du_dieu_kien'], 'A = 6 x luong cu thi da du, theo dau >=');
        $this->assertEquals(0.0, $ra['so_thang_con_lai'], '', 0.000001);
        $this->assertEquals(0.0, $ra['con_thieu'], '', 0.01);
    }

    /** @test */
    public function vuot_sau_thang_luong_cu_truoc_moc_thi_du_dieu_kien_ngay()
    {
        $dong = $this->dong([['2026-06-30', 20000000]]);

        $ra = NguongMienCungChiTra::tinhTheoQuyDinh($dong, '2026-09-03', $this->bangLuong(), 6);

        $this->assertTrue($ra['du_dieu_kien']);
        $this->assertLessThan(0.0, $ra['so_thang_con_lai']);
        $this->assertEquals(0.0, $ra['con_thieu'], '', 0.01);
    }

    /**
     * Dot VAT QUA moc doi luong (vao 30/06, ra 04/07): tinh theo NGAY RA VIEN nen thuoc giai
     * doan luong MOI, khong duoc cong vao A.
     */
    /** @test */
    public function dot_vat_qua_moc_tinh_theo_ngay_ra_vien()
    {
        $dong = $this->dong([['2026-07-04', 2057558], ['2026-06-23', 1396758]]);

        $ra = NguongMienCungChiTra::tinhTheoQuyDinh($dong, '2026-09-03', $this->bangLuong(), 6);

        $this->assertEquals(1396758.0, $ra['da_dong_truoc_moc'],
            'Dot ra vien 04/07 phai thuoc giai doan luong moi', 0.01);
        $this->assertEquals(660800.0, $ra['da_dong_doan_hien_tai'], '', 0.01);
    }

    /** Dung NGAY moc doi luong: 01/07 da la luong MOI nen khong tinh vao A */
    /** @test */
    public function dot_ra_vien_dung_ngay_doi_luong_thuoc_giai_doan_moi()
    {
        $dong = $this->dong([['2026-07-01', 900000], ['2026-06-30', 500000]]);

        $ra = NguongMienCungChiTra::tinhTheoQuyDinh($dong, '2026-09-03', $this->bangLuong(), 6);

        $this->assertEquals(500000.0, $ra['da_dong_truoc_moc'], '', 0.01);
        $this->assertEquals(400000.0, $ra['da_dong_doan_hien_tai'], '', 0.01);
    }

    /** Ma 204: khong co dot nao. Khong duoc no, va khong duoc ket luan du dieu kien. */
    /** @test */
    public function khong_co_dot_nao_thi_khong_no()
    {
        $ra = NguongMienCungChiTra::tinhTheoQuyDinh([], '2026-09-03', $this->bangLuong(), 6);

        $this->assertEquals(0.0, $ra['da_dong_truoc_moc'], '', 0.01);
        $this->assertEquals(0.0, $ra['da_dong_doan_hien_tai'], '', 0.01);
        $this->assertEquals(6 * 2530000, $ra['so_tien_con_phai_dong'], '', 0.01);
        $this->assertFalse($ra['du_dieu_kien']);
    }

    /**
     * Bang luong chua khai (hoac ngay tra truoc moi moc): luong bang 0 thi KHONG ket luan
     * duoc. Ket luan "du dieu kien" khi khong biet nguong la sai theo huong te nhat.
     */
    /** @test */
    public function chua_khai_luong_co_so_thi_khong_ket_luan_du()
    {
        $dong = $this->dong([['2026-08-14', 99999999]]);

        $ra = NguongMienCungChiTra::tinhTheoQuyDinh($dong, '2026-09-03', [], 6);

        $this->assertSame(0, $ra['luong_hien_tai']);
        $this->assertFalse($ra['du_dieu_kien']);
        $this->assertEquals(0.0, $ra['con_thieu'], '', 0.01);
    }

    /** Dong thieu ngay ra vien thi lui ve ngay nhan ho so, khong duoc lam vo phep tinh */
    /** @test */
    public function dong_thieu_ngay_ra_thi_dung_ngay_nhan()
    {
        $dong = [
            ['ngay_ra' => null, 'ngay_nhan' => '2026-06-20', 't_bn_cct_luy_ke' => 800000.0],
            ['ngay_ra' => '2026-08-14', 'ngay_nhan' => '2026-08-14', 't_bn_cct_luy_ke' => 2000000.0],
        ];

        $ra = NguongMienCungChiTra::tinhTheoQuyDinh($dong, '2026-09-03', $this->bangLuong(), 6);

        $this->assertEquals(800000.0, $ra['da_dong_truoc_moc'], '', 0.01);
    }
}

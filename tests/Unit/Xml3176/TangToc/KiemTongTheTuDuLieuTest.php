<?php

namespace Tests\Unit\Xml3176\TangToc;

use App\Models\BHYT\Xml3176VatPhamKemDvkt;
use App\Services\Xml3176\DuLieuHoSo;
use App\Services\Xml3176\NvytLookup;
use App\Services\Xml3176\NvytThuThap;
use App\Services\Xml3176\VatPhamKemDvktLookup;
use App\Services\Xml3176CompleteChecker;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Tests\Support\FakeXml3176ErrorService;
use Tests\Support\Xml3176RuleTestSupport;
use Tests\TestCase;

/**
 * Ghim hanh vi kiem tong the (Xml3176CompleteChecker::checkErrors) truoc/sau khi chuyen sang doc
 * DuLieuHoSo nap san: cung du lieu -> cung (ma loi, mo ta). Moi ca chay HAI duong (tu nap va
 * truyen DuLieuHoSo) va doi chieu ca hai voi ky vong.
 *
 * Luu y SQLite: chi dung gia tri ma SQLite va MySQL cho cung ket qua (ma_nhom so tron, khong hoa
 * thuong/khoang trang cuoi, tong khong nguyen khi in tho) - ngu nghia rieng MySQL ghim o
 * ngu_nghia_mysql_* (chi chay duoc tren ma moi).
 */
class KiemTongTheTuDuLieuTest extends TestCase
{
    use Xml3176RuleTestSupport;

    protected function setUp()
    {
        parent::setUp();
        $files = ['2024_06_13_094552_create_nhan_vien_y_te_table.php', '2026_04_09_100000_add_so_dinh_danh_to_medical_staffs_table.php'];
        foreach ([1, 2, 3, 4, 5, 7, 8, 9, 10, 11, 13, 14] as $n) {
            $files[] = basename(glob(database_path("migrations/2026_01_09_*_create_xml3176_xml{$n}s_table.php"))[0]);
        }
        $files[] = '2024_07_05_113054_create_medical_organizations_table.php';
        $files[] = '2026_10_13_090000_create_xml3176_vat_pham_kem_dvkt_table.php';
        $this->bootXml3176Sqlite($files);
        NvytLookup::xoaBoNho();
        VatPhamKemDvktLookup::xoaBoNho();
        config(['xml3176.group_code_with_executor' => [1, 2, 3, 8, 18]]);
        config(['xml3176.xml1.ma_doituong_kcb_trai_tuyen' => ['3.1']]);
    }

    protected function tearDown()
    {
        NvytLookup::xoaBoNho();
        VatPhamKemDvktLookup::xoaBoNho();
        parent::tearDown();
    }

    // ------------------------------------------------------------------ du lieu

    private function x1(string $maLk, array $f = []): void
    {
        DB::table('xml3176_xml1s')->insert(array_merge([
            'ma_lk' => $maLk, 'stt' => 1, 'ma_loai_kcb' => '01', 'ma_doituong_kcb' => '1.1',
            'ma_the_bhyt' => 'CA4010112345678', 'ma_cskcb' => '01929', 'ma_ttdv' => 'TTDV1',
            'ngay_vao' => '202610010800', 'ngay_ra' => '202610011000', 'so_ngay_dtri' => 1,
            't_thuoc' => 0, 't_vtyt' => 0, 't_tongchi_bv' => 0, 't_tongchi_bh' => 0, 't_bntt' => 0,
            't_bncct' => 0, 't_bhtt' => 0, 't_nguonkhac' => 0, 't_bhtt_gdv' => 0,
        ], $f));
    }

    private function x2(string $maLk, int $stt, array $f = []): void
    {
        DB::table('xml3176_xml2s')->insert(array_merge(['ma_lk' => $maLk, 'stt' => $stt, 'ma_bac_si' => 'BS1'], $f));
    }

    private function x3(string $maLk, int $stt, array $f = []): void
    {
        DB::table('xml3176_xml3s')->insert(array_merge(
            ['ma_lk' => $maLk, 'stt' => $stt, 'ma_bac_si' => 'BS1', 'nguoi_thuc_hien' => 'NTH1'],
            $f
        ));
    }

    private function x4(string $maLk, int $stt, array $f = []): void
    {
        DB::table('xml3176_xml4s')->insert(array_merge(['ma_lk' => $maLk, 'stt' => $stt, 'ma_bs_doc_kq' => 'BS1'], $f));
    }

    private function x7(string $maLk): void
    {
        DB::table('xml3176_xml7s')->insert(['ma_lk' => $maLk, 'ma_ttdv' => 'TTDV1', 'ma_bs' => 'BS1']);
    }

    // ------------------------------------------------------------------ chay kiem

    /** Error service bat loi checkErrors luu (khong cham CSDL). */
    private function boLuu()
    {
        return new class extends FakeXml3176ErrorService {
            public $loi = [];

            public function saveErrors(string $xmlType, string $ma_lk, int $stt, Collection $errors, array $additionalData = []): void
            {
                foreach ($errors as $e) {
                    $this->loi[] = $xmlType . '|' . $ma_lk . '|' . $stt . '|' . $e->error_code . '|' . $e->description;
                }
            }
        };
    }

    /**
     * Chay checkErrors hai duong (tu nap / DuLieuHoSo nap san), doi chieu nhau, tra list
     * "MA_LOI|mo ta" (bo tien to XMLComplete_, giu thu tu phat sinh). $boNvyt: bo loi NVYT_*.
     */
    private function loi(string $maLk, bool $boNvyt = true): array
    {
        $a = $this->boLuu();
        (new Xml3176CompleteChecker($a))->checkErrors($maLk);

        $b = $this->boLuu();
        (new Xml3176CompleteChecker($b))->checkErrors($maLk, DuLieuHoSo::nap($maLk));

        $this->assertSame($a->loi, $b->loi, 'hai duong kiem phai cho cung ket qua');

        $ra = [];
        foreach ($a->loi as $dong) {
            $p = explode('|', $dong, 5);
            $this->assertSame(['XMLComplete', $maLk, '1'], array_slice($p, 0, 3));
            $ma = substr($p[3], strlen('XMLComplete_'));
            if ($boNvyt && strpos($ma, 'NVYT_') === 0) {
                continue;
            }
            $ra[] = $ma . '|' . $p[4];
        }

        return $ra;
    }

    // ------------------------------------------------------------------ tien

    /** @test */
    public function tien_khop_ke_ca_null_va_thap_phan_le_thi_khong_loi()
    {
        $this->x1('T_OK', [
            'ma_the_bhyt' => 'DN4010112345678',
            't_thuoc' => 0.3, 't_vtyt' => 1.5, 't_tongchi_bv' => 1.8, 't_tongchi_bh' => 2.5,
            't_bntt' => 0.75, 't_bncct' => 0.5, 't_bhtt' => 2, 't_nguonkhac' => 0.25, 't_bhtt_gdv' => 0.4,
        ]);
        $this->x2('T_OK', 1, ['thanh_tien_bv' => 0.1, 'thanh_tien_bh' => 0.1, 't_bntt' => 0.25, 't_bncct' => null, 't_nguonkhac' => 0.25, 't_bhtt' => 0.1, 'ma_pttt' => '1']);
        $this->x2('T_OK', 2, ['thanh_tien_bv' => 0.2, 'thanh_tien_bh' => null, 't_bntt' => null, 't_bncct' => 0.5, 't_bhtt' => 0.3, 'ma_pttt' => '1']);
        $this->x2('T_OK', 3, ['thanh_tien_bv' => null, 'thanh_tien_bh' => 0.2, 't_bhtt' => 5, 'ma_pttt' => '2']);
        $this->x3('T_OK', 1, ['ma_dich_vu' => 'VT1', 'ma_nhom' => '10', 'thanh_tien_bv' => 1, 'thanh_tien_bh' => 1.2, 't_bntt' => 0.5]);
        $this->x3('T_OK', 2, ['ma_dich_vu' => 'VT2', 'ma_nhom' => '11', 'thanh_tien_bv' => 0.5, 'thanh_tien_bh' => 1, 't_bhtt' => null, 'ma_pttt' => '1']);
        $this->x3('T_OK', 3, ['ma_dich_vu' => 'XN1', 'ma_nhom' => '1', 'thanh_tien_bv' => null, 't_bhtt' => 7]);

        $this->assertSame([], $this->loi('T_OK'));
    }

    /** @test */
    public function tien_lech_moi_loai_bao_dung_ma_va_mo_ta()
    {
        $this->x1('T_LECH', [
            'ma_the_bhyt' => 'DN4010112345678',
            't_thuoc' => 1000, 't_vtyt' => 2000, 't_tongchi_bv' => 3000, 't_tongchi_bh' => 4000,
            't_bntt' => 5000, 't_bncct' => 6000, 't_bhtt' => 7000, 't_nguonkhac' => 8000, 't_bhtt_gdv' => 9000,
        ]);
        $this->x2('T_LECH', 1, ['thanh_tien_bv' => 1234.5, 'thanh_tien_bh' => 100.25, 't_bntt' => null, 't_bncct' => 10, 't_nguonkhac' => 1.5, 't_bhtt' => 0.1, 'ma_pttt' => '1']);
        $this->x2('T_LECH', 2, ['thanh_tien_bv' => null, 'thanh_tien_bh' => 0.5, 't_bntt' => 3, 't_bhtt' => 0.2, 'ma_pttt' => '1']);
        $this->x3('T_LECH', 1, ['ma_dich_vu' => 'VT1', 'ma_nhom' => '10', 'thanh_tien_bv' => 50.75, 't_bhtt' => 1000, 'ma_pttt' => '1']);
        $this->x3('T_LECH', 2, ['ma_dich_vu' => 'XN1', 'ma_nhom' => '1', 'thanh_tien_bv' => 20, 'thanh_tien_bh' => 7, 't_nguonkhac' => null, 'ma_pttt' => '2', 't_bhtt' => 50]);

        $this->assertSame([
            'INVALID_EXPENSE_DRUG|Tiền thuốc trong XML1: 1,000 <> tổng tiền trong XML2: 1,235',
            'INVALID_EXPENSE_T_VTYT|Tiền VTYT trong XML1: 2,000 <> tổng tiền VTYT trong XML3: 51',
            'INVALID_EXPENSE_T_TONGCHI_BV|Tiền tổng chi phí trong XML1: 3,000 <> chi phí trong XML2 và XML3: 1,305',
            'INVALID_EXPENSE_T_TONGCHI_BH|Tiền tổng chi phí BH trong XML1: 4,000 <> chi phí BH trong XML2 và XML3: 108',
            'INVALID_EXPENSE_T_BNTT|Tiền tổng chi phí BN trong XML1: 5,000 <> chi phí BN trong XML2 và XML3: 3',
            'INVALID_EXPENSE_T_BNCCT|Tiền tổng chi phí BN CCT trong XML1: 6,000 <> chi phí BN CCT trong XML2 và XML3: 10',
            'INVALID_EXPENSE_T_BHTT|Tiền BHTT trong XML1: 7,000 <> chi phí T_TONGCHI_BH trong XML1 - T_BNCCT trong XML1: -2,000',
            'INVALID_EXPENSE_T_NGUONKHAC|Tiền nguồn khác trong XML1: 8,000 <> chi phí tổng tiền nguồn khác trong XML2 và XML3: 2',
            'INVALID_EXPENSE_T_BHTT_GDV|Tiền bảo hiểm thanh toán GDV trong XML1: 9000 <> tổng tiền trong XML2 và XML3: 1000.3',
        ], $this->loi('T_LECH'));
    }

    /** @test */
    public function tien_ho_so_rong_xml2_xml3_va_the_mien_gdv()
    {
        // Khong dong XML2/XML3: SUM = NULL -> sum() tra 0. The CA bo kiem GDV.
        $this->x1('T_RONG', ['t_thuoc' => 0.004, 't_tongchi_bv' => 5, 't_bhtt_gdv' => 77]);

        $this->assertSame([
            'INVALID_EXPENSE_DRUG|Tiền thuốc trong XML1: 0 <> tổng tiền trong XML2: 0',
            'INVALID_EXPENSE_T_TONGCHI_BV|Tiền tổng chi phí trong XML1: 5 <> chi phí trong XML2 và XML3: 0',
        ], $this->loi('T_RONG'));
    }

    // ------------------------------------------------------------------ ngay giuong

    /** @test */
    public function ngay_giuong_luu_tru_ngan_duoi_4_gio_co_giuong()
    {
        $this->x1('G_NGAN', ['ma_loai_kcb' => '03', 'ngay_vao' => '202610010800', 'ngay_ra' => '202610011000', 'so_ngay_dtri' => 1]);
        $this->x7('G_NGAN');
        $this->x3('G_NGAN', 1, ['ma_dich_vu' => 'G1', 'ma_nhom' => '15', 'so_luong' => 0.5]);

        $this->assertSame([
            'SHORT_INPATIENT_STAY|Thời gian điều trị nội trú nhỏ hơn 4 giờ, không được tính tiền giường.',
        ], $this->loi('G_NGAN'));
    }

    /** @test */
    public function ngay_giuong_4_den_24_gio_thua_va_thua_theo_tt39()
    {
        $this->x1('G_THUA', ['ma_loai_kcb' => '03', 'ngay_vao' => '202610010800', 'ngay_ra' => '202610011800', 'so_ngay_dtri' => 1]);
        $this->x7('G_THUA');
        $this->x3('G_THUA', 1, ['ma_dich_vu' => 'G1', 'ma_nhom' => '14', 'so_luong' => 1.5]);
        $this->x3('G_THUA', 2, ['ma_dich_vu' => 'G2', 'ma_nhom' => '16', 'so_luong' => 1]);
        $this->x3('G_THUA', 3, ['ma_dich_vu' => 'XN', 'ma_nhom' => '1', 'so_luong' => 9]);
        $this->x3('G_THUA', 4, ['ma_dich_vu' => 'G3', 'ma_nhom' => '15', 'so_luong' => null]);

        $this->assertSame([
            'EXCESS_BED_DAYS|Thời gian điều trị nội trú từ 4 đến 24 giờ, tính thừa ngày giường: 2.5',
        ], $this->loi('G_THUA'));
    }

    /** @test */
    public function ngay_giuong_nhieu_ngay_khai_thieu()
    {
        $this->x1('G_THIEU', ['ma_loai_kcb' => '03', 'ngay_vao' => '202610010800', 'ngay_ra' => '202610051000', 'so_ngay_dtri' => 5]);
        $this->x7('G_THIEU');
        $this->x3('G_THIEU', 1, ['ma_dich_vu' => 'G1', 'ma_nhom' => '15', 'so_luong' => 1.25]);
        $this->x3('G_THIEU', 2, ['ma_dich_vu' => 'G2', 'ma_nhom' => '15', 'so_luong' => 1]);

        $this->assertSame([
            'BED_DAYS_BELOW_TT39|Tổng ngày giường khai 2.25 nhỏ hơn số ngày điều trị theo TT39 4 (chênh 1.75).',
        ], $this->loi('G_THIEU'));
    }

    /** @test */
    public function ngay_giuong_khai_du_thi_khong_loi()
    {
        $this->x1('G_DU', ['ma_loai_kcb' => '03', 'ngay_vao' => '202610010800', 'ngay_ra' => '202610051000', 'so_ngay_dtri' => 5]);
        $this->x7('G_DU');
        $this->x3('G_DU', 1, ['ma_dich_vu' => 'G1', 'ma_nhom' => '15', 'so_luong' => 2.5]);
        $this->x3('G_DU', 2, ['ma_dich_vu' => 'G2', 'ma_nhom' => '14', 'so_luong' => 1.5]);

        $this->assertSame([], $this->loi('G_DU'));
    }

    // ------------------------------------------------------------------ thong tin, kham

    /** @test */
    public function noi_tru_thieu_xml7_va_thua_cong_kham()
    {
        $this->x1('K_NT', ['t_tongchi_bh' => 130, 't_bhtt' => 130, 'ma_loai_kcb' => '03', 'ngay_vao' => '202610010800', 'ngay_ra' => '202610011000']);
        $this->x3('K_NT', 1, ['ma_dich_vu' => 'K1', 'ma_nhom' => '13', 'thanh_tien_bh' => 100, 'don_gia_bh' => 100]);
        $this->x3('K_NT', 2, ['ma_dich_vu' => 'K2', 'ma_nhom' => '13', 'thanh_tien_bh' => 30, 'don_gia_bh' => 100]);

        $this->assertSame([
            'INFO_ERROR_XML_COMPLETE_MISSING_XML7|Không tồn tại hồ sơ XML7 (Giấy ra viện) với loại KCB thuộc: 03, 04, 09',
            'ERROR_MULTIPLE_EXAMINATION_FEES_INPATIENT|Chỉ cho phép có một lần công khám, số lượng hiện tại: 2',
        ], $this->loi('K_NT'));
    }

    /** @test */
    public function noi_tru_tron_vien_khong_doi_xml7_mot_cong_kham_khong_loi()
    {
        $this->x1('K_NT1', ['t_tongchi_bh' => 100, 't_bhtt' => 100, 'ma_loai_kcb' => '03', 'ma_loai_rv' => '3', 'ngay_vao' => '202610010800', 'ngay_ra' => '202610011000']);
        $this->x3('K_NT1', 1, ['ma_dich_vu' => 'K1', 'ma_nhom' => '13', 'thanh_tien_bh' => 100, 'don_gia_bh' => 100]);

        $this->assertSame([], $this->loi('K_NT1'));
    }

    /** @test */
    public function ngoai_tru_kham_trung_vuot_tran_va_chua_giam_30()
    {
        $this->x1('K_NGT', ['t_tongchi_bh' => 1250.25, 't_bhtt' => 1250.25]);
        $this->x3('K_NGT', 1, ['ma_dich_vu' => 'KB2', 'ma_nhom' => '13', 'thanh_tien_bh' => 100.5, 'don_gia_bh' => 100.5]);
        $this->x3('K_NGT', 2, ['ma_dich_vu' => 'KB1', 'ma_nhom' => '13', 'thanh_tien_bh' => 100.5, 'don_gia_bh' => 100.5]);
        $this->x3('K_NGT', 3, ['ma_dich_vu' => 'KB2', 'ma_nhom' => '13', 'thanh_tien_bh' => 50.25, 'don_gia_bh' => 80]);
        $this->x3('K_NGT', 4, ['ma_dich_vu' => 'KB1', 'ma_nhom' => '13', 'thanh_tien_bh' => null, 'don_gia_bh' => null]);
        $this->x3('K_NGT', 5, ['ma_dich_vu' => 'XN', 'ma_nhom' => '1', 'thanh_tien_bh' => 999, 'don_gia_bh' => 999]);

        $this->assertSame([
            'DUPLICATE_EXAMINATION_SERVICE|Mỗi dịch vụ khám chỉ được tính 1 lần. Dịch vụ khám bị lặp: kb2, kb1',
            'EXAMINATION_FEE_EXCEEDS_CAP|Tổng tiền khám 251.25 vượt trần 201 (= 2 x mức giá một lần khám 100.5).',
            'EXAMINATION_SECOND_VISIT_RATE|Từ lần khám thứ 2 chỉ được tính 30% mức giá một lần khám; có 3 dòng khám ở mức giá đầy đủ.',
        ], $this->loi('K_NGT'));
    }

    /** @test */
    public function ngoai_tru_kham_hai_chuyen_khoa_dung_30_khong_loi()
    {
        $this->x1('K_NGT_OK', ['t_tongchi_bh' => 130, 't_bhtt' => 130]);
        $this->x3('K_NGT_OK', 1, ['ma_dich_vu' => 'KB1', 'ma_nhom' => '13', 'thanh_tien_bh' => 100, 'don_gia_bh' => 100]);
        $this->x3('K_NGT_OK', 2, ['ma_dich_vu' => 'KB2', 'ma_nhom' => '13', 'thanh_tien_bh' => 30, 'don_gia_bh' => 100]);

        $this->assertSame([], $this->loi('K_NGT_OK'));
    }

    // ------------------------------------------------------------------ tai kham, can nang con

    /** @test */
    public function tai_kham_thieu_xml14_va_can_nang_con_thieu_xml9()
    {
        $this->x1('LB', ['ngay_tai_kham' => '20261010;2026101', 'can_nang_con' => '3200']);

        $this->assertSame([
            'NGAY_TAI_KHAM_SAI_DINH_DANG|Ngày tái khám phải gồm 8 ký tự yyyymmdd và là ngày có thật. Giá trị sai: 2026101',
            'NGAY_TAI_KHAM_KHONG_KHOP_XML14|Hồ sơ hẹn tái khám ngày 20261010 nhưng không có giấy hẹn khám lại (XML14)',
            'CAN_NANG_CON_THIEU_XML9|CAN_NANG_CON = 3200 (chỉ ghi khi sinh con) nhưng hồ sơ không có dòng XML9 nào',
        ], $this->loi('LB'));
    }

    /** @test */
    public function tai_kham_lech_ngay_va_rong_ngay()
    {
        $this->x1('LB2', ['ngay_tai_kham' => '20261010']);
        DB::table('xml3176_xml14s')->insert(['ma_lk' => 'LB2', 'ngay_hen_kl' => '20261012', 'ma_ttdv' => 'T', 'ma_bac_si' => 'B']);
        $this->x1('LB3', ['ngay_tai_kham' => '20261010']);
        DB::table('xml3176_xml14s')->insert(['ma_lk' => 'LB3', 'ngay_hen_kl' => ' ', 'ma_ttdv' => 'T', 'ma_bac_si' => 'B']);

        $this->assertSame([
            'NGAY_TAI_KHAM_KHONG_KHOP_XML14|Giấy hẹn khám lại ghi ngày 20261012 nhưng NGAY_TAI_KHAM của hồ sơ là 20261010',
        ], $this->loi('LB2'));
        $this->assertSame([
            'NGAY_TAI_KHAM_KHONG_KHOP_XML14|Hồ sơ hẹn tái khám ngày 20261010 nhưng giấy hẹn khám lại (XML14) không ghi ngày hẹn (NGAY_HEN_KL rỗng)',
        ], $this->loi('LB3'));
    }

    /** @test */
    public function tai_kham_khop_va_co_xml9_khong_loi()
    {
        $this->x1('LB4', ['ngay_tai_kham' => '20261010;20261012', 'can_nang_con' => '3200;2800']);
        DB::table('xml3176_xml14s')->insert(['ma_lk' => 'LB4', 'ngay_hen_kl' => '20261012', 'ma_ttdv' => 'T', 'ma_bac_si' => 'B']);
        DB::table('xml3176_xml9s')->insert(['ma_lk' => 'LB4', 'ma_ttdv' => 'T']);

        $this->assertSame([], $this->loi('LB4'));
    }

    // ------------------------------------------------------------------ muc huong

    /** @test */
    public function muc_huong_co_dinh_lay_dong_lech_dau_tien_xml2_truoc_xml3()
    {
        $this->x1('MH12', ['ma_doituong_kcb' => '1.2', 'ngay_vao' => '202609010800', 'ngay_ra' => '202609011000']);
        $this->x2('MH12', 1, ['muc_huong' => 100]);
        $this->x2('MH12', 2, ['muc_huong' => null]);
        $this->x2('MH12', 3, ['muc_huong' => 95.5]);
        $this->x2('MH12', 4, ['muc_huong' => 80]);
        $this->x3('MH12', 1, ['ma_dich_vu' => 'DV', 'muc_huong' => 70]);

        $this->assertSame([
            'DOI_TUONG_KCB_MUC_HUONG_CO_DINH|Mã đối tượng 1.2 (Đi KCB tại cơ sở KCB cấp ban đầu) phải có mức hưởng 100% không phụ thuộc thẻ BHYT, nhưng có dòng khai 95.5%',
        ], $this->loi('MH12'));
    }

    /** @test */
    public function muc_huong_theo_moc_xml3_va_linh_thuoc_co_kham()
    {
        $this->x1('MH113', ['ma_doituong_kcb' => '1.13', 'ngay_vao' => '202609010800', 'ngay_ra' => '202609011000']);
        $this->x2('MH113', 1, ['muc_huong' => 50]);
        $this->x3('MH113', 1, ['ma_dich_vu' => 'DV', 'muc_huong' => 50]);
        $this->x3('MH113', 2, ['ma_dich_vu' => 'DV2', 'muc_huong' => 80.25]);

        $this->x1('MH7', ['ma_doituong_kcb' => '7', 't_tongchi_bh' => 100, 't_bhtt' => 100]);
        $this->x3('MH7', 1, ['ma_dich_vu' => 'KB1', 'ma_nhom' => '13', 'thanh_tien_bh' => 100, 'don_gia_bh' => 100]);
        $this->x3('MH7', 2, ['ma_dich_vu' => 'XN', 'ma_nhom' => '1']);

        $this->assertSame([
            'DOI_TUONG_KCB_MUC_HUONG_THEO_MOC|Mã đối tượng 1.13 (Tự đến KCB ngoại trú tại cơ sở cấp cơ bản 50-70 điểm) từ ngày 2026-07-01 có mức hưởng 50%, nhưng có dòng khai 80.25%',
        ], $this->loi('MH113'));
        $this->assertSame([
            'DOI_TUONG_KCB_LINH_THUOC_CO_TIEN_KHAM|Mã đối tượng 7 (Lĩnh thuốc theo giấy hẹn trong dịch bệnh nhóm A hoặc bất khả kháng) là trường hợp chỉ lĩnh thuốc, không khám bệnh, nhưng XML3 có 1 dòng thuộc nhóm dịch vụ khám',
        ], $this->loi('MH7'));
    }

    /** @test */
    public function muc_huong_vuot_tran_lay_max_hai_bang_bo_null()
    {
        // DN4 = quyen loi 80%; t_tongchi_bh lon hon 15% luong co so -> tran 80.
        $this->x1('MHMAX', [
            'ma_the_bhyt' => 'DN4010112345678', 'ngay_vao' => '202609010800', 'ngay_ra' => '202609011000',
            't_tongchi_bh' => 5000000, 't_bhtt' => 5000000,
        ]);
        $this->x2('MHMAX', 1, ['muc_huong' => 80, 'thanh_tien_bh' => 5000000]);
        $this->x2('MHMAX', 2, ['muc_huong' => null]);
        $this->x3('MHMAX', 1, ['ma_dich_vu' => 'DV', 'muc_huong' => 100]);
        $this->x3('MHMAX', 2, ['ma_dich_vu' => 'DV2', 'muc_huong' => 95]);

        $this->assertSame([
            'MUC_HUONG_EXCEEDS_ENTITLEMENT|Hồ sơ đúng tuyến: mức hưởng khai tối đa 100%, trần cho phép 80% (quyền lợi thẻ 80%).',
        ], $this->loi('MHMAX'));
    }

    /** @test */
    public function muc_huong_khong_vuot_tran_hoac_toan_null_thi_khong_loi()
    {
        $this->x1('MHOK', [
            'ma_the_bhyt' => 'DN4010112345678', 'ngay_vao' => '202609010800', 'ngay_ra' => '202609011000',
            't_tongchi_bh' => 5000000, 't_bhtt' => 5000000,
        ]);
        $this->x2('MHOK', 1, ['muc_huong' => 80, 'thanh_tien_bh' => 5000000]);
        $this->x1('MHNULL', ['ma_the_bhyt' => 'DN4010112345678', 'ngay_vao' => '202609010800', 'ngay_ra' => '202609011000']);
        $this->x3('MHNULL', 1, ['ma_dich_vu' => 'DV', 'muc_huong' => null]);

        $this->assertSame([], $this->loi('MHOK'));
        $this->assertSame([], $this->loi('MHNULL'));
    }

    // ------------------------------------------------------------------ XML4 ngay KQ, phau thuat

    /** @test */
    public function xml4_ngay_kq_lech_xml3()
    {
        $this->x1('KQ');
        $this->x3('KQ', 1, ['ma_dich_vu' => 'DV1', 'ma_nhom' => '1', 'ngay_kq' => '202610010900']);
        $this->x3('KQ', 2, ['ma_dich_vu' => 'DV2', 'ma_nhom' => '1', 'ngay_kq' => '']);
        $this->x3('KQ', 3, ['ma_dich_vu' => 'DV3', 'ma_nhom' => '1', 'ngay_kq' => '202610010900']);
        $this->x4('KQ', 1, ['ma_dich_vu' => 'DV1', 'ngay_kq' => '202610020900']);
        $this->x4('KQ', 2, ['ma_dich_vu' => 'DV2', 'ngay_kq' => '202610030900']);
        $this->x4('KQ', 3, ['ma_dich_vu' => 'DV3', 'ngay_kq' => '202610011500']);
        $this->x4('KQ', 4, ['ma_dich_vu' => 'DV1', 'ngay_kq' => '202610040800']);
        $this->x4('KQ', 5, ['ma_dich_vu' => 'DV1', 'ngay_kq' => null]);

        $this->assertSame([
            'XML4_NGAY_KQ_MISMATCH_XML3|Dịch vụ DV1: ngày KQ XML4 (' . strtodatetime('202610020900') . ') khác ngày KQ XML3',
            'XML4_NGAY_KQ_MISMATCH_XML3|Dịch vụ DV1: ngày KQ XML4 (' . strtodatetime('202610040800') . ') khác ngày KQ XML3',
        ], $this->loi('KQ'));
    }

    /** @test */
    public function phau_thuat_lan_2_trong_ngay_thanh_toan_100_theo_thu_tu_ngay_yl()
    {
        $this->x1('PT');
        // stt khong theo ngay_yl: thu tu "lan" phai theo ngay_yl
        $this->x3('PT', 1, ['ma_dich_vu' => 'PT_C', 'ma_nhom' => '8', 'ngay_yl' => '202610011400', 'tyle_tt_dv' => 100]);
        $this->x3('PT', 2, ['ma_dich_vu' => 'PT_A', 'ma_nhom' => '8', 'ngay_yl' => '202610010800', 'tyle_tt_dv' => 100]);
        $this->x3('PT', 3, ['ma_dich_vu' => 'PT_B', 'ma_nhom' => '8', 'ngay_yl' => '202610011000', 'tyle_tt_dv' => 50]);
        $this->x3('PT', 4, ['ma_dich_vu' => 'TT', 'ma_nhom' => '18', 'ngay_yl' => '202610010900', 'tyle_tt_dv' => 100]);
        $this->x3('PT', 5, ['ma_dich_vu' => 'PT_D', 'ma_nhom' => '8', 'ngay_yl' => '202610020800', 'tyle_tt_dv' => 100]);

        $this->assertSame([
            'SECOND_SURGERY_FULL_PAYMENT|Phẫu thuật lần 3 ngày 20261001 (dịch vụ PT_C) thanh toán 100%',
        ], $this->loi('PT'));
    }

    // ------------------------------------------------------------------ vat pham kem DVKT

    /** @test */
    public function vat_pham_kem_dvkt_khong_co_dvkt_me()
    {
        Xml3176VatPhamKemDvkt::create([
            'loai' => 'VTYT', 'ma_vat_pham' => 'N08.00.150', 'ten_khop' => 'buồng tiêm truyền cấy dưới da',
            'ma_dvkt' => '12.0448.1187', 'ten_dvkt' => 'Đặt buồng tiêm truyền dưới da', 'nguon' => 'HOC',
        ]);
        $this->x1('VP');
        $this->x3('VP', 1, ['ma_dich_vu' => 'K33.NO1', 'ma_vat_tu' => '', 'ten_dich_vu' => 'Giường', 't_bhtt' => 340200, 'so_luong' => 1, 'ngay_yl' => '202610050000']);
        $this->x3('VP', 2, ['ma_dich_vu' => '', 'ma_vat_tu' => 'N08.00.150', 'ten_vat_tu' => 'Buồng tiêm truyền cấy dưới da', 't_bhtt' => 4188000, 'so_luong' => 1, 'ngay_yl' => '202610050900']);

        $loi = $this->loi('VP');
        $this->assertCount(1, $loi);
        $this->assertStringStartsWith('VTYT_KHONG_CO_DVKT_ME|', $loi[0]);
        $this->assertContains('N08.00.150', $loi[0]);

        // Co DVKT me BHYT tra -> im lang
        $this->x1('VP_OK');
        $this->x3('VP_OK', 1, ['ma_dich_vu' => '12.0448.1187_GT', 'ma_vat_tu' => '', 't_bhtt' => 1070800, 'so_luong' => 1, 'ngay_yl' => '202610020800']);
        $this->x3('VP_OK', 2, ['ma_dich_vu' => null, 'ma_vat_tu' => 'N08.00.150', 'ten_vat_tu' => 'Buồng tiêm truyền cấy dưới da', 't_bhtt' => 4188000, 'so_luong' => 1, 'ngay_yl' => '202610020900']);
        $this->assertSame([], $this->loi('VP_OK'));
    }

    // ------------------------------------------------------------------ NVYT

    /** @test */
    public function nvyt_thieu_va_sai_dinh_danh_theo_moi_bang()
    {
        $this->x1('NV', ['ma_ttdv' => '']);
        $this->x2('NV', 1, ['ma_bac_si' => 'khongco', 'ten_thuoc' => 'Thuoc A']);
        $this->x3('NV', 1, ['ma_dich_vu' => 'D1', 'ma_nhom' => '3', 'ma_bac_si' => '', 'nguoi_thuc_hien' => '', 'ten_dich_vu' => 'Dich vu 1']);
        $this->x4('NV', 1, ['ma_dich_vu' => 'DV4', 'ma_bs_doc_kq' => '0', 'ten_chi_so' => 'Glucose']);
        DB::table('xml3176_xml5s')->insert(['ma_lk' => 'NV', 'stt' => 1, 'nguoi_thuc_hien' => '']);

        $nvyt = array_values(array_filter($this->loi('NV', false), function ($l) {
            return strpos($l, 'NVYT_') === 0;
        }));
        $this->assertNotEmpty($nvyt);

        // tuHoSo nap san cho cung ket qua nhu tu nap
        $this->assertSame(NvytThuThap::tuHoSo('NV'), NvytThuThap::tuHoSo('NV', DuLieuHoSo::nap('NV')));
        $this->assertEquals(
            (new Xml3176CompleteChecker(new FakeXml3176ErrorService()))->kiemNvyt('NV')->all(),
            (new Xml3176CompleteChecker(new FakeXml3176ErrorService()))->kiemNvyt('NV', DuLieuHoSo::nap('NV'))->all()
        );
    }

    // ------------------------------------------------------------------ ngu nghia MySQL

    /** @test */
    public function ngu_nghia_mysql_ma_nhom_varchar_so_voi_so_nguyen()
    {
        // MySQL: whereIn('ma_nhom', [10, 11]) bind so nguyen -> so sanh dang so: '010', ' 11', '10.0'
        // deu khop; where('ma_pttt', 1) cung vay ('01'). SQLite so chuoi nen ca nay chi dung voi ma moi.
        $this->x1('MY', [
            'ma_the_bhyt' => 'DN4010112345678', 't_vtyt' => 7, 't_tongchi_bv' => 7, 't_bhtt_gdv' => 5,
        ]);
        $this->x3('MY', 1, ['ma_dich_vu' => 'VT1', 'ma_nhom' => '010', 'thanh_tien_bv' => 1, 't_bhtt' => 5, 'ma_pttt' => '01']);
        $this->x3('MY', 2, ['ma_dich_vu' => 'VT2', 'ma_nhom' => ' 11', 'thanh_tien_bv' => 2]);
        $this->x3('MY', 3, ['ma_dich_vu' => 'VT3', 'ma_nhom' => '10.0', 'thanh_tien_bv' => 4]);

        $loi = $this->boLuu();
        (new Xml3176CompleteChecker($loi))->checkErrors('MY', DuLieuHoSo::nap('MY'));
        $ma = array_map(function ($l) {
            return explode('|', $l)[3];
        }, $loi->loi);

        $this->assertNotContains('XMLComplete_INVALID_EXPENSE_T_VTYT', $ma);
        $this->assertNotContains('XMLComplete_INVALID_EXPENSE_T_BHTT_GDV', $ma);
    }

    /** @test */
    public function tong_giuong_in_tho_theo_dang_chuoi_mysql()
    {
        // Builder::sum() cu tra chuoi MySQL ('2', '0.30000000000000004') hoac 0; mo ta EXCESS_BED_DAYS in thang
        $c = new Xml3176CompleteChecker(new FakeXml3176ErrorService());
        $tho = function (float $v) use ($c) {
            return $this->invokePrivate($c, 'tongTho', $v);
        };
        $this->assertSame(0, $tho(0.0));
        $this->assertSame('2', $tho(2.0));
        $this->assertSame('2.5', $tho(2.5));
        $this->assertSame('0.30000000000000004', $tho(0.1 + 0.2));
        $this->assertSame('-3', $tho(-3.0));
    }

    // ------------------------------------------------------------------ dem truy van

    /** @test */
    public function so_truy_van_ho_so()
    {
        $this->x1('DEM', ['ma_loai_kcb' => '03', 'ngay_tai_kham' => '20261010', 'can_nang_con' => '3000', 'ma_doituong_kcb' => '1.2']);
        $this->x2('DEM', 1, ['thanh_tien_bv' => 1, 'muc_huong' => 80]);
        $this->x3('DEM', 1, ['ma_dich_vu' => 'K', 'ma_nhom' => '13', 'so_luong' => 1, 'ngay_kq' => '202610010900']);
        $this->x4('DEM', 1, ['ma_dich_vu' => 'K', 'ngay_kq' => '202610020900']);

        $du = DuLieuHoSo::nap('DEM');
        $dem = 0;
        DB::listen(function ($q) use (&$dem) {
            if (preg_match('/xml3176_xml\d+s/', $q->sql)) {
                $dem++;
            }
        });

        (new Xml3176CompleteChecker($this->boLuu()))->checkErrors('DEM', $du);
        $this->assertSame(0, $dem, 'DuLieuHoSo nap san: khong truy van bang XML nao nua');

        $dem = 0;
        (new Xml3176CompleteChecker($this->boLuu()))->checkErrors('DEM');
        $this->assertSame(count(DuLieuHoSo::LOAI_MAC_DINH), $dem, 'tu nap: moi bang mot truy van');
    }
}

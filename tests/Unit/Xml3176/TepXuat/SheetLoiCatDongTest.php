<?php

namespace Tests\Unit\Xml3176\TepXuat;

use App\Exports\Xml3176ErrorSheetExport;
use Illuminate\Support\Facades\DB;
use Tests\Support\Xml3176RuleTestSupport;
use Tests\TestCase;

/**
 * Hai bo loc ve LOI phai cat ca o muc DONG, khong chi chon ho so.
 *
 * Do 01/10/2026 tren ngay 29/09 (1.682 ho so): loc ma loi XML3_OVERLAPPING_SERVICE_EXECUTION
 * thi man hien 115 ho so nhung ban xuat lay 115.250 dong, chi 51.708 dong dung ma do; loc
 * "co loi nghiem trong" thi 197.665 dong, chi 61.502 dong nghiem trong. Nguoi dung chot:
 * ma loi -> chi dong ma do; nghiem trong/canh bao -> chi dong muc do; loi the -> khong lay
 * dong loi XML. Cac bo loc khac giu nguyen (chi chon ho so).
 */
class SheetLoiCatDongTest extends TestCase
{
    use Xml3176RuleTestSupport;

    /** @var int id danh muc cua ma E_NT */
    private $idMaNghiemTrong;

    protected function setUp()
    {
        parent::setUp();
        $this->bootXml3176Sqlite([
            '2026_01_09_152817_create_xml3176_xml1s_table.php',
            '2026_01_09_161708_create_xml3176_error_catalogs_table.php',
            '2026_01_09_161902_create_xml3176_error_results_table.php',
            '2026_01_09_162044_create_xml3176_informations_table.php',
            '2024_06_11_142642_create_check_hein_cards_table.php',
        ]);

        DB::table('xml3176_xml1s')->insert([
            'ma_lk' => 'LK1', 'stt' => 1, 'ma_khoa' => 'K01',
            'ngay_ttoan' => '202609291000', 'ma_the_bhyt' => 'DN4010112345678',
        ]);

        $this->idMaNghiemTrong = DB::table('xml3176_error_catalogs')->insertGetId(
            ['xml' => 'XML1', 'error_code' => 'E_NT', 'error_name' => 'Nghiem trong', 'critical_error' => 1]);
        DB::table('xml3176_error_catalogs')->insert(
            ['xml' => 'XML1', 'error_code' => 'E_CB', 'error_name' => 'Canh bao', 'critical_error' => 0]);

        // Mot ho so co CA dong nghiem trong lan dong canh bao.
        foreach ([['E_NT', 1], ['E_NT', 1], ['E_CB', 0], ['E_CB', 0], ['E_CB', 0]] as $i => list($ma, $nt)) {
            DB::table('xml3176_error_results')->insert([
                'xml' => 'XML1', 'ma_lk' => 'LK1', 'stt' => $i + 1, 'error_code' => $ma,
                'description' => $ma, 'critical_error' => $nt,
            ]);
        }
    }

    private function maLoiXuatRa(array $ghiDe)
    {
        $loc = array_merge([
            'date_from' => '2026-09-29 00:00:00', 'date_to' => '2026-09-29 23:59:59',
            'date_type' => 'date_payment',
        ], $ghiDe);

        $ma = [];
        foreach ((new Xml3176ErrorSheetExport('XML1', $loc, []))->generator() as $dong) {
            $ma[] = $dong->error_code;
        }
        sort($ma);

        return $ma;
    }

    /** @test */
    public function khong_loc_loi_thi_lay_het_dong_nhu_truoc()
    {
        $this->assertSame(['E_CB', 'E_CB', 'E_CB', 'E_NT', 'E_NT'], $this->maLoiXuatRa([]));
        $this->assertSame(['E_CB', 'E_CB', 'E_CB', 'E_NT', 'E_NT'], $this->maLoiXuatRa(['xml_filter_status' => 'has_error']));
    }

    /** @test */
    public function loc_ma_loi_chi_lay_dong_dung_ma_do()
    {
        $this->assertSame(['E_NT', 'E_NT'], $this->maLoiXuatRa(['xml3176_error_catalog' => $this->idMaNghiemTrong]));
    }

    /** @test */
    public function ma_loi_khong_co_trong_danh_muc_thi_khong_cat()
    {
        // Giong man danh sach: id la thi bo qua bo loc.
        $this->assertCount(5, $this->maLoiXuatRa(['xml3176_error_catalog' => 99999]));
    }

    /** @test */
    public function loc_nghiem_trong_chi_lay_dong_nghiem_trong()
    {
        $this->assertSame(['E_NT', 'E_NT'], $this->maLoiXuatRa(['xml_filter_status' => 'has_error_critical']));
    }

    /** @test */
    public function loc_canh_bao_chi_lay_dong_canh_bao()
    {
        // Ho so LK1 co loi nghiem trong nen man danh sach khong chon no khi loc "chi canh bao"
        // - kiem phan cat dong truc tiep qua Xml3176LocDanhSach::apDongLoi.
        $q = DB::table('xml3176_error_results');
        \App\Services\BHYT\Xml3176LocDanhSach::apDongLoi($q, ['xml_filter_status' => 'has_error_warning']);

        $this->assertSame(['E_CB', 'E_CB', 'E_CB'], $q->orderBy('error_code')->pluck('error_code')->all());
    }

    /** @test */
    public function loc_loi_the_khong_lay_dong_loi_xml()
    {
        foreach (['has_error_hein_card', 'has_error_hein_card_without_xml'] as $trangThai) {
            $q = DB::table('xml3176_error_results');
            \App\Services\BHYT\Xml3176LocDanhSach::apDongLoi($q, ['xml_filter_status' => $trangThai]);

            $this->assertSame(0, $q->count(), $trangThai);
        }
    }

    /** @test */
    public function ket_hop_ma_loi_va_nghiem_trong()
    {
        $this->assertSame([], $this->maLoiXuatRa([
            'xml3176_error_catalog' => $this->idMaNghiemTrong + 1, // E_CB
            'xml_filter_status' => 'has_error_critical',
        ]), 'Ho so co loi nghiem trong va co ma E_CB, nhung khong dong nao vua E_CB vua nghiem trong');
    }

    /** @test */
    public function tra_cuu_dich_danh_thi_khong_cat_dong()
    {
        // Man danh sach: go ma dieu tri / ma BN thi BO moi bo loc khac, ke ca bo loc loi.
        $this->assertCount(5, $this->maLoiXuatRa([
            'treatment_code' => 'LK1',
            'xml3176_error_catalog' => $this->idMaNghiemTrong,
            'xml_filter_status' => 'has_error_critical',
        ]));
    }
}

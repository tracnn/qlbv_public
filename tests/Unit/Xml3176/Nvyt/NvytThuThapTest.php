<?php

namespace Tests\Unit\Xml3176\Nvyt;

use App\Services\Xml3176\NvytLookup;
use App\Services\Xml3176\NvytRule;
use App\Services\Xml3176\NvytThuThap;
use App\Services\Xml3176CompleteChecker;
use App\Services\Xml3176ErrorService;
use Illuminate\Support\Facades\DB;
use Tests\Support\Xml3176RuleTestSupport;
use Tests\TestCase;

class NvytThuThapTest extends TestCase
{
    use Xml3176RuleTestSupport;

    const MA_LK = 'LK001';

    protected function setUp()
    {
        parent::setUp();
        $files = ['2024_06_13_094552_create_nhan_vien_y_te_table.php', '2026_04_09_100000_add_so_dinh_danh_to_medical_staffs_table.php'];
        foreach ([1, 2, 3, 4, 5, 7, 8, 9, 10, 11, 13, 14] as $n) {
            $files[] = basename(glob(database_path("migrations/2026_01_09_*_create_xml3176_xml{$n}s_table.php"))[0]);
        }
        $files[] = '2026_01_09_161708_create_xml3176_error_catalogs_table.php';
        $files[] = '2026_10_15_100000_gop_ma_loi_nvyt_xml3176.php';
        // 2026_04_09_100001 (MODIFY ... NULL) la cu phap MySQL, khong chay duoc tren SQLite: bo qua
        $this->bootXml3176Sqlite($files);
        NvytLookup::xoaBoNho();
        config(['xml3176.group_code_with_executor' => ['3', '8']]);
    }

    protected function tearDown()
    {
        NvytLookup::xoaBoNho();
        parent::tearDown();
    }

    private function them(int $xml, array $f): void
    {
        $goc = ['ma_lk' => self::MA_LK];
        if (\Illuminate\Support\Facades\Schema::hasColumn("xml3176_xml{$xml}s", 'stt')) {
            $goc['stt'] = 1;
        }
        DB::table("xml3176_xml{$xml}s")->insert(array_merge($goc, $f));
    }

    private function nv(string $macchn, string $bhxh = null, string $ten = 'NGUOI A'): void
    {
        static $i = 0;
        $i++;
        DB::table('medical_staffs')->insert([
            'ma_khoa' => 'K', 'ten_khoa' => 'K', 'ma_bhxh' => $bhxh ?? ('B' . $i), 'ho_ten' => $ten, 'gioi_tinh' => 1,
            'chucdanh_nn' => 1, 'macchn' => $macchn, 'ngaycap_cchn' => '', 'noicap_cchn' => '',
            'thoigian_dk' => 1, 'thoigian_ngay' => '', 'thoigian_tuan' => '', 'tu_ngay' => '20200101',
        ]);
    }

    private function loc(array $o, string $vaiTro, int $xml): array
    {
        return array_values(array_filter($o, function ($c) use ($vaiTro, $xml) {
            return $c['vai_tro'] === $vaiTro && $c['xml'] === $xml;
        }));
    }

    /** @test */
    public function xml3_tach_cham_phay_trim_bo_manh_rong()
    {
        $this->them(3, ['stt' => 1, 'ma_dich_vu' => 'D1', 'ma_nhom' => '1', 'ma_bac_si' => 'X1', 'nguoi_thuc_hien' => 'A; B;']);
        $o = $this->loc(NvytThuThap::tuHoSo(self::MA_LK), NvytRule::NGUOI_THUC_HIEN, 3);
        $this->assertSame(['A', 'B'], array_column($o, 'gia_tri'));
    }

    /** @test */
    public function xml3_nguoi_thuc_hien_bat_buoc_theo_nhom()
    {
        $this->them(3, ['stt' => 1, 'ma_dich_vu' => 'D1', 'ma_nhom' => '3', 'ma_bac_si' => 'X', 'nguoi_thuc_hien' => '']);
        $this->them(3, ['stt' => 2, 'ma_dich_vu' => 'D2', 'ma_nhom' => '1', 'ma_bac_si' => 'X', 'nguoi_thuc_hien' => '']);
        $o = $this->loc(NvytThuThap::tuHoSo(self::MA_LK), NvytRule::NGUOI_THUC_HIEN, 3);
        $this->assertCount(2, $o);
        $this->assertTrue($o[0]['bat_buoc']);
        $this->assertFalse($o[1]['bat_buoc']);
        // ma_bac_si rong luon bat buoc
        $bs = $this->loc(NvytThuThap::tuHoSo(self::MA_LK), NvytRule::BS_CHI_DINH, 3);
        $this->assertTrue($bs[0]['bat_buoc']);
    }

    /** @test */
    public function moi_xml_mot_o_dung_vai_tro()
    {
        $this->them(1, ['ma_ttdv' => 'T1']);
        $this->them(2, ['stt' => 1, 'ma_bac_si' => 'B2', 'ten_thuoc' => 'Thuoc']);
        $this->them(3, ['stt' => 1, 'ma_dich_vu' => 'D', 'ma_bac_si' => 'B3', 'nguoi_thuc_hien' => 'N3', 'ma_nhom' => '1']);
        $this->them(4, ['stt' => 1, 'ma_dich_vu' => 'DV4', 'ma_bs_doc_kq' => 'R4']);
        $this->them(5, ['stt' => 1, 'nguoi_thuc_hien' => 'N5']);
        $this->them(7, ['ma_ttdv' => 'T7', 'ma_bs' => 'B7']);
        $this->them(8, ['ma_ttdv' => 'T8']);
        $this->them(9, ['ma_ttdv' => 'T9']);
        $this->them(10, ['ma_ttdv' => 'T10']);
        $this->them(11, ['ma_ttdv' => 'T11', 'ma_bs' => 'B11']);
        $this->them(13, ['ma_ttdv' => 'T13', 'ma_bac_si' => 'B13']);
        $this->them(14, ['ma_ttdv' => 'T14', 'ma_bac_si' => 'B14']);

        $o = NvytThuThap::tuHoSo(self::MA_LK);
        $got = [];
        foreach ($o as $c) {
            $got[] = $c['vai_tro'] . '|' . $c['xml'] . '|' . $c['gia_tri'];
        }
        sort($got);
        $mong = [
            'TTDV|1|T1', 'BS_CHI_DINH|2|B2', 'BS_CHI_DINH|3|B3', 'NGUOI_THUC_HIEN|3|N3', 'BS_DOC_KQ|4|R4',
            'NGUOI_THUC_HIEN|5|N5', 'TTDV|7|T7', 'BS_CHI_DINH|7|B7', 'TTDV|8|T8', 'TTDV|9|T9', 'TTDV|10|T10',
            'TTDV|11|T11', 'BS_CHI_DINH|11|B11', 'TTDV|13|T13', 'BS_CHI_DINH|13|B13', 'TTDV|14|T14', 'BS_CHI_DINH|14|B14',
        ];
        sort($mong);
        $this->assertSame($mong, $got);
        $this->assertSame('Thuoc', $this->loc($o, NvytRule::BS_CHI_DINH, 2)[0]['nhan']);
        $this->assertSame('DV4', $this->loc($o, NvytRule::BS_DOC_KQ, 4)[0]['nhan']);
        $xmls = array_column($o, 'xml');
        $sorted = $xmls;
        sort($sorted);
        $this->assertSame($sorted, $xmls, 'o phai sap theo XML tang dan');
    }

    /** @test */
    public function ho_so_khac_khong_lan_vao()
    {
        DB::table('xml3176_xml1s')->insert(['ma_lk' => 'KHAC', 'stt' => 1, 'ma_ttdv' => 'Z']);
        $this->assertSame([], NvytThuThap::tuHoSo(self::MA_LK));
    }

    /** @test */
    public function kiem_nvyt_ghi_ma_xmlcomplete_va_muc_chan()
    {
        $this->nv('027588/HNO-CCHN');
        $this->them(1, ['ma_ttdv' => '']);
        $this->them(4, ['stt' => 1, 'ma_dich_vu' => 'DV4', 'ma_bs_doc_kq' => 'vietrad']);

        $checker = new Xml3176CompleteChecker(new Xml3176ErrorService());
        $loi = $checker->kiemNvyt(self::MA_LK);

        $this->assertSame(['XMLComplete_NVYT_THIEU', 'XMLComplete_NVYT_SAI_DINH_DANG'], $loi->pluck('error_code')->all());
        $this->assertTrue((bool) $loi[0]->critical_error);
        $this->assertFalse((bool) $loi[1]->critical_error);
        $this->assertSame('Thiếu mã nhân viên y tế', $loi[0]->error_name);
        $this->assertContains('vietrad', $loi[1]->description);
    }
}

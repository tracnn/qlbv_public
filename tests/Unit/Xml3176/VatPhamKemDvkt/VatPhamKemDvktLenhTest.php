<?php

namespace Tests\Unit\Xml3176\VatPhamKemDvkt;

use App\Exports\VatPhamKemDvktExport;
use App\Imports\VatPhamKemDvktImport;
use App\Models\BHYT\Xml3176VatPhamKemDvkt;
use App\Services\Xml3176\VatPhamKemDvktLearner;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Tests\Support\Xml3176RuleTestSupport;
use Tests\TestCase;

class VatPhamKemDvktLenhTest extends TestCase
{
    use Xml3176RuleTestSupport;

    private $thuMuc;

    protected function setUp()
    {
        parent::setUp();
        $this->bootXml3176Sqlite([
            '2026_01_09_152826_create_xml3176_xml2s_table.php',
            '2026_01_09_152832_create_xml3176_xml3s_table.php',
            '2026_10_13_090000_create_xml3176_vat_pham_kem_dvkt_table.php',
        ]);
        for ($i = 0; $i < 10; $i++) {
            DB::table('xml3176_xml3s')->insert([
                'ma_lk' => 'HS' . $i, 'stt' => $i + 1, 'ma_vat_tu' => 'N08.00.150', 'ten_vat_tu' => 'Buồng tiêm truyền',
                'ma_dich_vu' => $i < 9 ? '12.0448.1187' : null, 't_bhtt' => 1000, 'so_luong' => 1, 'ngay_yl' => '202609010800',
            ]);
        }
        $this->thuMuc = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'vpkd_' . uniqid();
        mkdir($this->thuMuc);
    }

    protected function tearDown()
    {
        foreach (glob($this->thuMuc . DIRECTORY_SEPARATOR . '*') as $tep) {
            unlink($tep);
        }
        rmdir($this->thuMuc);
        parent::tearDown();
    }

    private function ghiXlsx(array $hang): string
    {
        $duongDan = $this->thuMuc . DIRECTORY_SEPARATOR . 'nap.xlsx';
        $sheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $ws = $sheet->getActiveSheet();
        foreach ($hang as $i => $dong) {
            foreach (array_values($dong) as $j => $v) {
                $ws->setCellValueExplicitByColumnAndRow($j + 1, $i + 1, (string) $v, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            }
        }
        (new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($sheet))->save($duongDan);
        $sheet->disconnectWorksheets();

        return $duongDan;
    }

    /** @test */
    public function lenh_hoc_che_do_thu_khong_ghi()
    {
        $ma = Artisan::call('xml3176:hoc-vat-pham-kem-dvkt', ['--thu' => true]);

        $this->assertSame(0, $ma);
        $this->assertContains('VTYT: 1 vật phẩm, 1 cặp', Artisan::output());
        $this->assertSame(0, Xml3176VatPhamKemDvkt::count());
    }

    /** @test */
    public function lenh_hoc_ghi_va_xuat()
    {
        Excel::fake();

        Artisan::call('xml3176:hoc-vat-pham-kem-dvkt', ['--xuat' => 'vpkd.xlsx']);

        $this->assertSame(1, Xml3176VatPhamKemDvkt::count());
        Excel::assertStored('vpkd.xlsx', 'local');
    }

    /** @test */
    public function lenh_hoc_tu_choi_nguong_ngoai_khoang()
    {
        $this->assertSame(1, Artisan::call('xml3176:hoc-vat-pham-kem-dvkt', ['--nguong' => '1.5']));
    }

    /** @test */
    public function xuat_giu_ma_dang_chuoi_va_hieu_luc_1_0()
    {
        $ex = new VatPhamKemDvktExport([
            ['loai' => 'THUOC', 'ma_vat_pham' => '40.540', 'ma_dvkt' => '18.0659.0054', 'hieu_luc' => false],
            ['loai' => 'VTYT', 'ma_vat_pham' => 'N08.00.150', 'ma_dvkt' => '12.0448.1187'],
        ]);

        $this->assertSame(VatPhamKemDvktExport::COT, $ex->headings());
        $this->assertSame('40.540', $ex->array()[0][1]);
        $this->assertSame('0', $ex->array()[0][6]);
        $this->assertSame('1', $ex->array()[1][6]);
    }

    /** @test */
    public function xuat_ra_tep_that_roi_doc_lai_giu_ma_dang_chuoi()
    {
        $noiDung = Excel::raw(new VatPhamKemDvktExport([
            ['loai' => 'THUOC', 'ma_vat_pham' => '40.540', 'ma_dvkt' => '18.0659.0054', 'hieu_luc' => false],
        ]), \Maatwebsite\Excel\Excel::XLSX);
        $duongDan = $this->thuMuc . DIRECTORY_SEPARATOR . 'xuat.xlsx';
        file_put_contents($duongDan, $noiDung);

        $imp = new VatPhamKemDvktImport();
        Excel::import($imp, $duongDan);

        $this->assertSame('40.540', $imp->hang[0]['ma_vat_pham']);
        $this->assertSame('18.0659.0054', $imp->hang[0]['ma_dvkt']);
        $this->assertSame('0', $imp->hang[0]['hieu_luc']);
    }

    /** @test */
    public function nap_them_dong_tay_cap_nhat_dong_co_san_va_bao_loi()
    {
        Artisan::call('xml3176:hoc-vat-pham-kem-dvkt');
        $tep = $this->ghiXlsx([
            VatPhamKemDvktExport::COT,
            ['VTYT', 'N08.00.150', 'Buồng tiêm truyền', '12.0448.1187', 'Đặt buồng', 'HOC', '0', '9', '0.9', 'Đã rà soát'],
            ['VTYT', 'N06.02.100', 'Stent đại tràng', '20.0053.0105', 'Nội soi đặt stent', '', '1', '', '', ''],
            ['XYZ', 'N06.02.100', '', '20.0053.0105', '', '', '1', '', '', ''],
            ['THUOC', '', '', '18.0150.0041', '', '', '1', '', '', ''],
        ]);

        $ma = Artisan::call('xml3176:nap-vat-pham-kem-dvkt', ['tep' => $tep]);
        $ra = Artisan::output();

        $this->assertSame(1, $ma);
        $this->assertContains('Thêm 1, cập nhật 1, lỗi 2', $ra);
        $this->assertContains('Dòng 4', $ra);
        $this->assertContains('Dòng 5', $ra);

        $hoc = Xml3176VatPhamKemDvkt::where('ma_vat_pham', 'N08.00.150')->first();
        $this->assertFalse($hoc->hieu_luc);
        $this->assertSame('Đã rà soát', $hoc->ghi_chu);

        $tay = Xml3176VatPhamKemDvkt::where('ma_vat_pham', 'N06.02.100')->first();
        $this->assertSame('TAY', $tay->nguon);
        $this->assertSame('stent đại tràng', $tay->ten_khop);
        $this->assertTrue($tay->hieu_luc);
    }

    /** @test */
    public function nap_tep_khong_ton_tai_thi_bao_loi()
    {
        $this->assertSame(1, Artisan::call('xml3176:nap-vat-pham-kem-dvkt', ['tep' => $this->thuMuc . '/khong-co.xlsx']));
    }

    /** @test */
    public function doc_hieu_luc()
    {
        foreach ([null, '', '1', 'x', 'có', 1, true] as $v) {
            $this->assertTrue(VatPhamKemDvktLearner::docHieuLuc($v), var_export($v, true));
        }
        foreach (['0', 'false', 'không', 'khong', 'N', 0, false] as $v) {
            $this->assertFalse(VatPhamKemDvktLearner::docHieuLuc($v), var_export($v, true));
        }
    }

    /** @test */
    public function nap_bao_loi_ma_dang_so_va_vtyt_nhom_chung_thieu_ten()
    {
        $kq = (new VatPhamKemDvktLearner())->nap([
            ['loai' => 'THUOC', 'ma_vat_pham' => 40.54, 'ma_dvkt' => '18.0150.0041'],
            ['loai' => 'THUOC', 'ma_vat_pham' => '40.540', 'ma_dvkt' => 18.015],
            ['loai' => 'VTYT', 'ma_vat_pham' => 'N06.02.100', 'ten_vat_pham' => '', 'ma_dvkt' => '20.0053.0105'],
            ['loai' => 'VTYT', 'ma_vat_pham' => 'N06.02.100.01', 'ten_vat_pham' => '', 'ma_dvkt' => '20.0053.0105'],
        ]);

        $this->assertSame(1, $kq['them']);
        $this->assertCount(3, $kq['loi']);
        $this->assertContains('định dạng Text', $kq['loi'][0]['ly_do']);
        $this->assertContains('định dạng Text', $kq['loi'][1]['ly_do']);
        $this->assertContains('cần tên vật phẩm', $kq['loi'][2]['ly_do']);
        $this->assertSame(4, $kq['loi'][2]['dong']);
    }

    /** @test */
    public function lenh_hoc_thu_kem_xuat_canh_bao_nguon_rong()
    {
        Excel::fake();

        Artisan::call('xml3176:hoc-vat-pham-kem-dvkt', ['--thu' => true, '--xuat' => 'vpkd_thu.xlsx']);

        $this->assertContains('nguon rỗng', Artisan::output());
        Excel::assertStored('vpkd_thu.xlsx', 'local');
    }
}

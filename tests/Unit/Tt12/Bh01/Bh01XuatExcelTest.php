<?php

namespace Tests\Unit\Tt12\Bh01;

use App\Services\Tt12\Bh01\Bh01DacTa;
use App\Services\Tt12\Bh01\Bh01GhiBan;
use App\Services\Tt12\Bh01\Bh01Ky;
use App\Services\Tt12\Bh01\Bh01NhapExcel;
use App\Services\Tt12\Bh01\Bh01XuatExcel;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\Support\DungBangBh01Sqlite;
use Tests\TestCase;

class Bh01XuatExcelTest extends TestCase
{
    use DungBangBh01Sqlite;

    private $tepTao = array();

    public function setUp()
    {
        parent::setUp();
        $this->dungBangBh01();
        Storage::fake('exportTt12');
    }

    protected function tearDown()
    {
        foreach ($this->tepTao as $tep) {
            @unlink($tep);
        }
        parent::tearDown();
    }

    private function xuat()
    {
        return new Bh01XuatExcel(sys_get_temp_dir());
    }

    private function dongNguon(array $ghiDe = array())
    {
        $mau = array(
            'ho_ten' => 'NGUYEN VAN A', 'ngay_sinh' => '198001010000', 'gioi_tinh' => '1',
            'ma_the_bhyt' => 'GD4010000000001', 'ma_benh_chinh' => 'E11',
            'ngay_vao' => '202610010800', 'ngay_vao_noi_tru' => '', 'ngay_ra' => '202610051000',
            'so_ngay_dtri' => '4', 'ma_loai_kcb' => '01',
            't_tongchi_bv' => '1234.5', 't_tongchi_bh' => '1000', 't_bhtt' => '800',
            't_bncct' => '100', 't_bntt' => '200', 't_nguonkhac' => '0',
            'ma_cskcb' => '01929', 'nam_qt' => '2026', 'thang_qt' => '09',
        );
        return array('du_lieu' => array_merge($mau, $ghiDe), 'ma_lk' => null, 'vi_tri' => 'x');
    }

    private function banMau()
    {
        $cacDong = array(
            $this->dongNguon(array('ho_ten' => 'AAA')),
            $this->dongNguon(array(
                'ho_ten' => 'BBB', 'ma_the_bhyt' => 'GD401000000000123',
                'ma_benh_chinh' => "I10\u{2020}", 'ma_loai_kcb' => '09',
            )),
        );

        return (new Bh01GhiBan())->ghi('01929', Bh01Ky::tu(2026, 9), 'tester', function ($nhanLo) use ($cacDong) {
            $nhanLo($cacDong);
        });
    }

    private function doc($duongDan)
    {
        $this->tepTao[] = $duongDan;
        return IOFactory::load($duongDan)->getActiveSheet();
    }

    /** @test */
    public function xuat_ban_dung_tieu_de_va_dong()
    {
        $sheet = $this->doc($this->xuat()->xuatBan($this->banMau()));

        $this->assertSame('C79', $sheet->getTitle());
        $tieuDe = array();
        foreach (Bh01DacTa::the() as $dt) {
            $tieuDe[] = $dt['the'];
        }
        $hang1 = $sheet->rangeToArray('A1:T1', null, false, false, false)[0];
        $this->assertSame($tieuDe, $hang1);
        $this->assertSame(3, $sheet->getHighestRow());
        $this->assertSame('1', (string) $sheet->getCell('A2')->getValue());
        $this->assertSame('AAA', $sheet->getCell('B2')->getValue());
        $this->assertSame('2', (string) $sheet->getCell('A3')->getValue());
        $this->assertSame('BBB', $sheet->getCell('B3')->getValue());
    }

    /** @test */
    public function moi_o_la_chu_giu_so_0_dau()
    {
        $sheet = $this->doc($this->xuat()->xuatBan($this->banMau()));

        // R = MA_CSKCB, T = THANG_QT, K = MA_LOAI_KCB
        foreach (array('R2' => '01929', 'T2' => '09', 'K2' => '01') as $o => $kyVong) {
            $this->assertSame($kyVong, $sheet->getCell($o)->getValue(), $o);
            $this->assertSame(DataType::TYPE_STRING, $sheet->getCell($o)->getDataType(), $o);
        }
    }

    /** @test */
    public function tep_mau_chi_co_tieu_de()
    {
        $sheet = $this->doc($this->xuat()->tepMau());

        $this->assertSame('C79', $sheet->getTitle());
        $this->assertSame(1, $sheet->getHighestRow());
        $this->assertSame('STT', $sheet->getCell('A1')->getValue());
        $this->assertSame('THANG_QT', $sheet->getCell('T1')->getValue());
    }

    /** @test */
    public function vong_xuat_nhap_khong_mat_du_lieu()
    {
        $cu = $this->banMau();
        $duongDan = $this->xuat()->xuatBan($cu);
        $this->tepTao[] = $duongDan;

        $moi = (new Bh01NhapExcel())->nhap($duongDan, 'xuat.xlsx', '01929', Bh01Ky::tu(2026, 9), 'tester')['ban'];

        $this->assertNotSame($cu->id, $moi->id);
        $a = DB::table('bh01_dong')->where('ban_id', $cu->id)->orderBy('stt')->get();
        $b = DB::table('bh01_dong')->where('ban_id', $moi->id)->orderBy('stt')->get();
        $this->assertCount(2, $b);

        foreach ($a as $i => $dongCu) {
            foreach (Bh01DacTa::the() as $dt) {
                $k = strtolower($dt['the']);
                $this->assertEquals((string) $dongCu->$k, (string) $b[$i]->$k, $k . ' dong ' . $i);
            }
        }
        $this->assertSame("I10\u{2020}", $b[1]->ma_benh_chinh);
        $this->assertSame('', (string) $b[0]->ngay_vao_noi_tru);
    }
}

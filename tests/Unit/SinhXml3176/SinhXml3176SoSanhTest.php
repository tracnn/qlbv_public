<?php

namespace Tests\Unit\SinhXml3176;

use App\Services\SinhXml3176\PhongBi;
use App\Services\SinhXml3176\XmlCon;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class SinhXml3176SoSanhTest extends TestCase
{
    private $goc;
    private $his;
    private $qlbv;
    private $csv;

    protected function setUp()
    {
        parent::setUp();
        $this->goc = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'sinhxml3176_sosanh_' . uniqid();
        $this->his = $this->goc . DIRECTORY_SEPARATOR . 'his';
        $this->qlbv = $this->goc . DIRECTORY_SEPARATOR . 'qlbv';
        $this->csv = $this->goc . DIRECTORY_SEPARATOR . 'ra.csv';
        mkdir($this->his, 0777, true);
        mkdir($this->qlbv, 0777, true);
    }

    protected function tearDown()
    {
        foreach ([$this->his, $this->qlbv] as $d) {
            foreach (glob($d . DIRECTORY_SEPARATOR . '*') ?: [] as $f) {
                @unlink($f);
            }
            @rmdir($d);
        }
        @unlink($this->csv);
        @rmdir($this->goc);
        parent::tearDown();
    }

    private function xml1($maLk, $tong = '15000.00')
    {
        return [['MA_LK' => $maLk, 'STT' => '1', 'HO_TEN' => 'Nguyen Van A', 'T_TONGCHI_BV' => $tong, 'GHI_CHU' => '']];
    }

    private function xml2($maLk, array $dong)
    {
        $kq = [];
        foreach ($dong as $d) {
            $kq[] = array_merge([
                'MA_LK' => $maLk, 'STT' => '1', 'MA_THUOC' => 'T01', 'NGAY_YL' => '202610221030',
                'SO_LUONG' => '2', 'THANH_TIEN_BH' => '15000.00',
            ], $d);
        }

        return $kq;
    }

    private function ghi($thuMuc, $ten, array $xmlCon, $bom = false, $mtime = null)
    {
        $f = $thuMuc . DIRECTORY_SEPARATOR . $ten;
        if ($bom) {
            foreach ($xmlCon as $k => $v) {
                $xmlCon[$k] = "\xEF\xBB\xBF" . $v;
            }
        }
        file_put_contents($f, PhongBi::chuoi('01929', $xmlCon, new \DateTimeImmutable('2026-10-22')));
        if ($mtime !== null) {
            touch($f, $mtime);
        }

        return $f;
    }

    private function chay()
    {
        $ma = Artisan::call('sinhxml3176:so-sanh', ['--his' => $this->his, '--qlbv' => $this->qlbv, '--ra' => $this->csv]);
        $this->assertSame(0, $ma, Artisan::output());
        $noiDung = file_get_contents($this->csv);
        $this->assertSame("\xEF\xBB\xBF", substr($noiDung, 0, 3));
        $dong = [];
        $h = fopen('php://memory', 'r+');
        fwrite($h, substr($noiDung, 3));
        rewind($h);
        while (($r = fgetcsv($h)) !== false) {
            $dong[] = $r;
        }
        fclose($h);

        return $dong;
    }

    public function test_giong_nhau_thi_csv_rong()
    {
        $xml = function ($giaTri) {
            return [
                'XML1' => XmlCon::chuoi('XML1', $this->xml1('LK1')),
                'XML2' => XmlCon::chuoi('XML2', $this->xml2('LK1', [['THANH_TIEN_BH' => $giaTri]])),
            ];
        };
        // HIS co BOM trong XML con, so "15000" == "15000.00"
        $this->ghi($this->his, 'HIS_1.xml', $xml('15000'), true);
        $this->ghi($this->qlbv, 'QLBV_20261022103000_A.xml', $xml('15000.00'));

        $dong = $this->chay();
        $this->assertSame(['ma_lk', 'bang', 'khoa_dong', 'truong', 'gia_tri_his', 'gia_tri_qlbv'], $dong[0]);
        $this->assertCount(1, $dong);
    }

    public function test_lech_mot_truong_xml2()
    {
        $this->ghi($this->his, 'HIS_1.xml', [
            'XML1' => XmlCon::chuoi('XML1', $this->xml1('LK1')),
            'XML2' => XmlCon::chuoi('XML2', $this->xml2('LK1', [['THANH_TIEN_BH' => '15000.00']])),
        ]);
        $this->ghi($this->qlbv, 'QLBV_1.xml', [
            'XML1' => XmlCon::chuoi('XML1', $this->xml1('LK1')),
            'XML2' => XmlCon::chuoi('XML2', $this->xml2('LK1', [['THANH_TIEN_BH' => '14000.00']])),
        ]);

        $dong = $this->chay();
        $this->assertCount(2, $dong);
        $this->assertSame(['LK1', 'XML2', 'LK1|T01|202610221030', 'THANH_TIEN_BH', '15000.00', '14000.00'], $dong[1]);
    }

    public function test_dong_chi_co_mot_ben()
    {
        $this->ghi($this->his, 'HIS_1.xml', [
            'XML1' => XmlCon::chuoi('XML1', $this->xml1('LK1')),
            'XML2' => XmlCon::chuoi('XML2', $this->xml2('LK1', [['MA_THUOC' => 'T01'], ['MA_THUOC' => 'T02']])),
        ]);
        $this->ghi($this->qlbv, 'QLBV_1.xml', [
            'XML1' => XmlCon::chuoi('XML1', $this->xml1('LK1')),
            'XML2' => XmlCon::chuoi('XML2', $this->xml2('LK1', [['MA_THUOC' => 'T01'], ['MA_THUOC' => 'T03']])),
        ]);
        // ho so chi co o mot ben
        $this->ghi($this->his, 'HIS_2.xml', ['XML1' => XmlCon::chuoi('XML1', $this->xml1('LK_HIS'))]);
        $this->ghi($this->qlbv, 'QLBV_2.xml', ['XML1' => XmlCon::chuoi('XML1', $this->xml1('LK_QL'))]);

        $dong = $this->chay();
        $tap = [];
        foreach (array_slice($dong, 1) as $r) {
            $tap[] = $r[0] . '/' . $r[1] . '/' . $r[2] . '/' . $r[3];
        }
        sort($tap);
        $this->assertSame([
            'LK1/XML2/LK1|T02|202610221030/chi_co_his',
            'LK1/XML2/LK1|T03|202610221030/chi_co_qlbv',
            'LK_HIS/XML1/LK_HIS/chi_co_his',
            'LK_QL/XML1/LK_QL/chi_co_qlbv',
        ], $tap);
    }

    public function test_nhieu_tep_cung_ma_lk_lay_tep_moi_nhat()
    {
        $cu = ['XML1' => XmlCon::chuoi('XML1', $this->xml1('LK1', '1.00'))];
        $moi = ['XML1' => XmlCon::chuoi('XML1', $this->xml1('LK1', '15000.00'))];
        $this->ghi($this->his, 'HIS_1.xml', $moi);
        $this->ghi($this->qlbv, 'QLBV_cu.xml', $cu, false, time() - 3600);
        $this->ghi($this->qlbv, 'QLBV_moi.xml', $moi, false, time());

        $this->assertCount(1, $this->chay());
    }

    public function test_khoa_trung_trong_mot_ben_ghep_theo_so_lan_xuat_hien()
    {
        $hai = $this->xml2('LK1', [['SO_LUONG' => '1'], ['SO_LUONG' => '2']]);
        $this->ghi($this->his, 'HIS_1.xml', ['XML2' => XmlCon::chuoi('XML2', $hai)]);
        $this->ghi($this->qlbv, 'QLBV_1.xml', ['XML2' => XmlCon::chuoi('XML2', $hai)]);

        $this->assertCount(1, $this->chay());
    }
}

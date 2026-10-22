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

    private function xml4($maLk, array $dong)
    {
        $kq = [];
        foreach ($dong as $d) {
            $kq[] = array_merge([
                'MA_LK' => $maLk, 'STT' => '1', 'MA_DICH_VU' => 'DV1', 'MA_CHI_SO' => 'CS1',
                'GIA_TRI' => '5', 'NGAY_KQ' => '202610221030',
            ], $d);
        }

        return $kq;
    }

    private function xml5($maLk, array $dong)
    {
        $kq = [];
        foreach ($dong as $d) {
            $kq[] = array_merge([
                'MA_LK' => $maLk, 'STT' => '1', 'DIEN_BIEN_LS' => 'on', 'THOI_DIEM_DBLS' => '202610220800',
            ], $d);
        }

        return $kq;
    }

    private function cap(array $bangHis, array $bangQl)
    {
        $this->ghi($this->his, 'HIS_1.xml', $bangHis);
        $this->ghi($this->qlbv, 'QLBV_1.xml', $bangQl);
    }

    private function chayBang($bang)
    {
        $ma = Artisan::call('sinhxml3176:so-sanh', [
            '--his' => $this->his, '--qlbv' => $this->qlbv, '--ra' => $this->csv, '--bang' => $bang,
        ]);
        $this->assertSame(0, $ma, Artisan::output());

        return $this->docCsv();
    }

    private function docCsv()
    {
        $dong = [];
        $h = fopen('php://memory', 'r+');
        fwrite($h, substr(file_get_contents($this->csv), 3));
        rewind($h);
        while (($r = fgetcsv($h)) !== false) {
            $dong[] = $r;
        }
        fclose($h);

        return $dong;
    }

    public function test_xml4_xml5_ghep_dung_khoa()
    {
        $this->cap([
            'XML4' => XmlCon::chuoi('XML4', $this->xml4('LK1', [['GIA_TRI' => '5'], ['MA_CHI_SO' => 'CS2', 'GIA_TRI' => '7']])),
            'XML5' => XmlCon::chuoi('XML5', $this->xml5('LK1', [['DIEN_BIEN_LS' => 'a'], ['THOI_DIEM_DBLS' => '202610220900']])),
        ], [
            'XML4' => XmlCon::chuoi('XML4', $this->xml4('LK1', [['GIA_TRI' => '6'], ['MA_CHI_SO' => 'CS3', 'GIA_TRI' => '7']])),
            'XML5' => XmlCon::chuoi('XML5', $this->xml5('LK1', [['DIEN_BIEN_LS' => 'b'], ['THOI_DIEM_DBLS' => '202610220900']])),
        ]);

        $dong = $this->chayBang('XML4,XML5');
        $tap = [];
        foreach (array_slice($dong, 1) as $r) {
            $tap[] = implode('/', array_slice($r, 1, 3));
        }
        sort($tap);
        $this->assertSame([
            'XML4/LK1|DV1|CS1|202610221030/GIA_TRI',
            'XML4/LK1|DV1|CS2|202610221030/chi_co_his',
            'XML4/LK1|DV1|CS3|202610221030/chi_co_qlbv',
            'XML5/LK1|202610220800/DIEN_BIEN_LS',
        ], $tap);
    }

    public function test_so_nguyen_khong_bi_cat_so_0()
    {
        $xml = function ($tien) {
            return ['XML2' => XmlCon::chuoi('XML2', $this->xml2('LK1', [['THANH_TIEN_BH' => $tien]]))];
        };
        $this->cap($xml('17000'), $xml('17000.00'));
        $this->assertCount(1, $this->chayBang('XML2'));

        $this->cap($xml('17000'), $xml('17'));
        $dong = $this->chayBang('XML2');
        $this->assertCount(2, $dong);
        $this->assertSame(['17000', '17'], array_slice($dong[1], 4, 2));
    }

    public function test_rong_bang_0_o_cot_so()
    {
        $xml = function ($tien, $ghiChu = 'x') {
            return ['XML2' => XmlCon::chuoi('XML2', $this->xml2('LK1', [['THANH_TIEN_BH' => $tien, 'GHI_CHU' => $ghiChu]]))];
        };
        // cot so: rong == 0 == 0.00
        $this->cap($xml('', 'x'), $xml('0.00', 'x'));
        $this->assertCount(1, $this->chayBang('XML2'));

        // cot chu: rong != "0"
        $this->cap($xml('1', ''), $xml('1', '0'));
        $dong = $this->chayBang('XML2');
        $this->assertCount(2, $dong);
        $this->assertSame('GHI_CHU', $dong[1][3]);

        // phan tu vang mat == phan tu rong
        $a = $this->xml2('LK1', [[]]);
        $b = $this->xml2('LK1', [['GHI_CHU' => '']]);
        $this->cap(['XML2' => XmlCon::chuoi('XML2', $a)], ['XML2' => XmlCon::chuoi('XML2', $b)]);
        $this->assertCount(1, $this->chayBang('XML2'));
    }

    private function dungCsdl()
    {
        config([
            'database.default' => 'mysql',
            'database.connections.mysql' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => ''],
        ]);
        \Illuminate\Support\Facades\DB::purge('mysql');
        $s = \Illuminate\Support\Facades\Schema::connection('mysql');
        $s->create('xml3176_informations', function ($t) {
            $t->increments('id');
            $t->string('ma_lk');
            $t->timestamps();
        });
        $s->create('xml3176_xml1s', function ($t) {
            $t->increments('id');
            $t->string('ma_lk');
            $t->string('ho_ten')->nullable();
            $t->double('t_tongchi_bv')->nullable();
            $t->string('cot_la_chi_co_o_csdl')->nullable();
            $t->timestamps();
        });
        $s->create('xml3176_xml4s', function ($t) {
            $t->increments('id');
            $t->string('ma_lk');
            $t->integer('stt');
            $t->string('ma_dich_vu')->nullable();
            $t->string('ma_chi_so')->nullable();
            $t->string('gia_tri')->nullable();
            $t->string('ngay_kq')->nullable();
            $t->timestamps();
        });
    }

    public function test_che_do_csdl_doc_bang_tien_giam_dinh()
    {
        $this->dungCsdl();
        $db = \Illuminate\Support\Facades\DB::connection('mysql');
        $db->table('xml3176_informations')->insert(['ma_lk' => 'LK1']);
        $db->table('xml3176_xml1s')->insert([
            'ma_lk' => 'LK1', 'ho_ten' => 'Nguyen Van A', 't_tongchi_bv' => 15000, 'cot_la_chi_co_o_csdl' => 'z',
        ]);
        $db->table('xml3176_xml4s')->insert([
            ['ma_lk' => 'LK1', 'stt' => 1, 'ma_dich_vu' => 'DV1', 'ma_chi_so' => 'CS1', 'gia_tri' => '5', 'ngay_kq' => '202610221030'],
            ['ma_lk' => 'LK1', 'stt' => 2, 'ma_dich_vu' => 'DV1', 'ma_chi_so' => 'CS2', 'gia_tri' => '8', 'ngay_kq' => '202610221030'],
        ]);

        $this->ghi($this->qlbv, 'QLBV_1.xml', [
            'XML1' => XmlCon::chuoi('XML1', [['MA_LK' => 'LK1', 'HO_TEN' => 'Nguyen Van A', 'T_TONGCHI_BV' => '15000.00']]),
            'XML4' => XmlCon::chuoi('XML4', $this->xml4('LK1', [['GIA_TRI' => '5'], ['STT' => '2', 'MA_CHI_SO' => 'CS2', 'GIA_TRI' => '9']])),
        ]);
        // ho so khong co trong tien giam dinh
        $this->ghi($this->qlbv, 'QLBV_2.xml', ['XML1' => XmlCon::chuoi('XML1', $this->xml1('LK_MOI'))]);

        $ma = Artisan::call('sinhxml3176:so-sanh', ['--csdl' => true, '--qlbv' => $this->qlbv, '--ra' => $this->csv, '--bang' => 'XML1,XML4']);
        $this->assertSame(0, $ma, Artisan::output());
        $dong = $this->docCsv();
        $this->assertCount(3, $dong, json_encode($dong));
        $tap = array_slice($dong, 1);
        usort($tap, function ($x, $y) {
            return strcmp($x[0], $y[0]);
        });
        $this->assertSame(['LK1', 'XML4', 'LK1|DV1|CS2|202610221030', 'GIA_TRI', '8', '9'], $tap[0]);
        $this->assertSame(['LK_MOI', 'XML1', 'LK_MOI', 'chi_co_qlbv', '', ''], $tap[1]);
    }

    public function test_his_va_csdl_loai_tru_nhau()
    {
        $this->assertSame(1, Artisan::call('sinhxml3176:so-sanh', ['--his' => $this->his, '--csdl' => true, '--qlbv' => $this->qlbv, '--ra' => $this->csv]));
        $this->assertSame(1, Artisan::call('sinhxml3176:so-sanh', ['--qlbv' => $this->qlbv, '--ra' => $this->csv]));
    }

    public function test_csdl_thieu_bang_informations_thi_loi()
    {
        config([
            'database.default' => 'mysql',
            'database.connections.mysql' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => ''],
        ]);
        \Illuminate\Support\Facades\DB::purge('mysql');
        $this->ghi($this->qlbv, 'QLBV_1.xml', ['XML1' => XmlCon::chuoi('XML1', $this->xml1('LK1'))]);

        $ma = Artisan::call('sinhxml3176:so-sanh', ['--csdl' => true, '--qlbv' => $this->qlbv, '--ra' => $this->csv]);
        $this->assertSame(1, $ma);
        $this->assertContains('xml3176_informations', Artisan::output());
    }

    public function test_csdl_thieu_bang_xml4s_canh_bao_dung_mot_lan()
    {
        $this->dungCsdl();
        \Illuminate\Support\Facades\Schema::connection('mysql')->drop('xml3176_xml4s');
        $db = \Illuminate\Support\Facades\DB::connection('mysql');
        $db->table('xml3176_informations')->insert([['ma_lk' => 'LK1'], ['ma_lk' => 'LK2']]);
        foreach (['LK1', 'LK2'] as $lk) {
            $this->ghi($this->qlbv, 'QLBV_' . $lk . '.xml', ['XML4' => XmlCon::chuoi('XML4', $this->xml4($lk, [[]]))]);
        }

        $ma = Artisan::call('sinhxml3176:so-sanh', ['--csdl' => true, '--qlbv' => $this->qlbv, '--ra' => $this->csv, '--bang' => 'XML4']);
        $this->assertSame(0, $ma);
        $this->assertSame(1, substr_count(Artisan::output(), 'xml3176_xml4s'));
    }
}

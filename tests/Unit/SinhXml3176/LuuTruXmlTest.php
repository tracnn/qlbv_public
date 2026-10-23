<?php

namespace Tests\Unit\SinhXml3176;

use App\Models\SinhXml3176\LanSinh;
use App\Services\SinhXml3176\LuuTruXml;
use Illuminate\Support\Facades\Log;
use Tests\Support\DungBangHisSinhXml3176Sqlite;
use Tests\Support\HoSoHisSinhXml3176;
use Tests\TestCase;

class LuuTruXmlTest extends TestCase
{
    use DungBangHisSinhXml3176Sqlite;
    use HoSoHisSinhXml3176;

    private $goc;

    protected function setUp()
    {
        parent::setUp();
        $this->dungBangTheoDoi();
        $this->goc = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'sinhxml3176_luutru_' . uniqid();
        mkdir($this->goc, 0775, true);
    }

    protected function tearDown()
    {
        $this->xoaThuMuc($this->goc);
        parent::tearDown();
    }

    private function dong($id, $ten = 'QLBV_20261001110000_HS1.xml', $luc = '2026-10-01 11:00:00')
    {
        $d = new LanSinh();
        $d->treatment_id = $id;
        $d->treatment_code = 'HS' . $id;
        $d->loai_ban = 'chinh_thuc';
        $d->trang_thai = 'da_ghi';
        $d->ten_tep = $ten;
        $d->created_at = $luc;
        $d->updated_at = $luc;
        $d->save();

        return $d;
    }

    private function luuTru()
    {
        return new LuuTruXml($this->goc);
    }

    private function capNhatTepLuu(LanSinh $d, $tuongDoi)
    {
        $d->tep_luu = $tuongDoi;
        $d->save();

        return $d;
    }

    public function test_luu_theo_thu_muc_ngay_va_id()
    {
        $d = $this->dong(1);

        $tuongDoi = $this->luuTru()->luu($d, '<a/>');

        $this->assertSame('20261001' . DIRECTORY_SEPARATOR . $d->id . '_QLBV_20261001110000_HS1.xml', $tuongDoi);
        $this->assertSame('<a/>', file_get_contents($this->goc . DIRECTORY_SEPARATOR . $tuongDoi));
        $this->assertSame([], glob($this->goc . DIRECTORY_SEPARATOR . '20261001' . DIRECTORY_SEPARATOR . '*.tmp') ?: []);
        $this->assertTrue($this->luuTru()->coTep($this->capNhatTepLuu($d, $tuongDoi)));
        $this->assertSame('<a/>', $this->luuTru()->doc($d));
    }

    public function test_hai_lan_cung_giay_khac_ten()
    {
        $d1 = $this->dong(1);
        $d2 = $this->dong(1);

        $t1 = $this->luuTru()->luu($d1, '<lan1/>');
        $t2 = $this->luuTru()->luu($d2, '<lan2/>');

        $this->assertNotSame($t1, $t2);
        $this->assertSame('<lan1/>', file_get_contents($this->goc . DIRECTORY_SEPARATOR . $t1));
        $this->assertSame('<lan2/>', file_get_contents($this->goc . DIRECTORY_SEPARATOR . $t2));
    }

    public function test_goc_rong_khong_luu()
    {
        $d = $this->dong(1);

        $this->assertNull((new LuuTruXml(null))->luu($d, '<a/>'));
        $this->assertNull((new LuuTruXml('  '))->luu($d, '<a/>'));
        $this->assertFalse((new LuuTruXml(null))->coTep($d));
        $this->assertNull((new LuuTruXml(null))->doc($d));
    }

    public function test_duong_chua_cham_cham_bi_chan()
    {
        $ngoai = $this->goc . DIRECTORY_SEPARATOR . 'ngoai.txt';
        file_put_contents($ngoai, 'x');
        mkdir($this->goc . DIRECTORY_SEPARATOR . 'luu');
        $luu = new LuuTruXml($this->goc . DIRECTORY_SEPARATOR . 'luu');

        $this->assertNull($luu->duongTuyetDoi('..\\ngoai.txt'));
        $this->assertNull($luu->duongTuyetDoi('../ngoai.txt'));
        $this->assertNull($luu->duongTuyetDoi('20261001\\..\\..\\ngoai.txt'));
        $this->assertNull($luu->duongTuyetDoi('\\Windows\\win.ini'));
        $this->assertNull($luu->duongTuyetDoi('/etc/passwd'));
        $this->assertNull($luu->duongTuyetDoi('C:\\Windows\\win.ini'));
        $this->assertNull($luu->duongTuyetDoi(''));

        $d = $this->dong(1);
        $d->tep_luu = '..\\ngoai.txt';
        $this->assertNull($luu->doc($d));
        $this->assertFalse($luu->coTep($d));
        $this->assertFalse($luu->xoa($d, 5));
        $this->assertFileExists($ngoai);

        // Hai dau phan cach deu duoc chap nhan
        $this->assertNotNull($luu->duongTuyetDoi('20261001/1_a.xml'));
        $this->assertNotNull($luu->duongTuyetDoi('20261001\\1_a.xml'));
    }

    public function test_xoa_dien_nguoi_xoa_va_don_thu_muc_rong()
    {
        $d1 = $this->dong(1);
        $d2 = $this->dong(2, 'b.xml');
        $this->capNhatTepLuu($d1, $this->luuTru()->luu($d1, '<a/>'));
        $this->capNhatTepLuu($d2, $this->luuTru()->luu($d2, '<b/>'));
        $ngay = $this->goc . DIRECTORY_SEPARATOR . '20261001';

        $this->assertTrue($this->luuTru()->xoa($d1, 7));

        $d1 = LanSinh::find($d1->id);
        $this->assertSame(7, (int) $d1->xoa_luu_boi);
        $this->assertNotNull($d1->xoa_luu_luc);
        $this->assertFalse($this->luuTru()->coTep($d1));
        $this->assertDirectoryExists($ngay);

        $this->assertTrue($this->luuTru()->xoa($d2, 7));
        $this->assertDirectoryNotExists($ngay);
    }

    public function test_doc_tep_mat_tra_null()
    {
        $d = $this->dong(1);
        $d->tep_luu = '20261001' . DIRECTORY_SEPARATOR . '1_mat.xml';

        $this->assertNull($this->luuTru()->doc($d));
        $this->assertFalse($this->luuTru()->coTep($d));

        $d->tep_luu = null;
        $this->assertNull($this->luuTru()->doc($d));
    }

    public function test_goc_la_tep_ghi_loi_khong_nem()
    {
        $tep = $this->goc . DIRECTORY_SEPARATOR . 'la_tep';
        file_put_contents($tep, 'x');
        $d = $this->dong(1);
        Log::shouldReceive('warning')->once();

        $this->assertNull((new LuuTruXml($tep))->luu($d, '<a/>'));
    }
}

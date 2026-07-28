<?php

namespace Tests\Unit\Xml3176;

use Tests\TestCase;
use Tests\Support\LocComment;
use App\Services\Xml3176\Xml3176Importer;
use App\Services\Xml3176\Xml3176ImportResult;
use App\Services\Xml3176\Xml3176ImportFileResult;

class Xml3176NhieuHoSoTest extends TestCase
{
    use LocComment;

    private function xml($soHoSo, $khaiBao = null)
    {
        $khaiBao = $khaiBao === null ? $soHoSo : $khaiBao;
        $hoSo = str_repeat('<HOSO><FILEHOSO><LOAIHOSO>XML1</LOAIHOSO></FILEHOSO></HOSO>', $soHoSo);

        return simplexml_load_string(
            '<GIAMDINHHS><THONGTINHOSO><SOLUONGHOSO>' . $khaiBao . '</SOLUONGHOSO>'
            . '<DANHSACHHOSO>' . $hoSo . '</DANHSACHHOSO></THONGTINHOSO></GIAMDINHHS>'
        );
    }

    /** @test */
    public function dem_ho_so_dem_dung_so_the_hoso()
    {
        $this->assertSame(2, Xml3176Importer::demHoSo($this->xml(2)));
        $this->assertSame(1, Xml3176Importer::demHoSo($this->xml(1)));
    }

    /** @test */
    public function dem_ho_so_tra_khong_khi_thieu_the()
    {
        $trong = simplexml_load_string('<GIAMDINHHS></GIAMDINHHS>');

        $this->assertSame(0, Xml3176Importer::demHoSo($trong));
    }

    /** @test */
    public function moi_ho_so_thanh_cong_thi_file_thanh_cong()
    {
        $kq = Xml3176ImportFileResult::tu([
            Xml3176ImportResult::thanhCong('MA1', ['XML1']),
            Xml3176ImportResult::thanhCong('MA2', ['XML1']),
        ], 2, 2);

        $this->assertTrue($kq->thanhCong);
        $this->assertEquals(2, $kq->soThanhCong);
        $this->assertEquals(0, $kq->soThatBai);
        $this->assertEquals(['MA1', 'MA2'], $kq->dsMaLk);
        $this->assertNull($kq->lyDoThatBai);
    }

    /** @test */
    public function mot_ho_so_hong_khong_keo_ho_so_con_lai_xuong_theo()
    {
        $kq = Xml3176ImportFileResult::tu([
            Xml3176ImportResult::thanhCong('MA1', ['XML1']),
            Xml3176ImportResult::thatBai('Sai cau truc du lieu XML1'),
        ], 2, 2);

        $this->assertFalse($kq->thanhCong, 'File co ho so hong thi khong duoc bao thanh cong');
        $this->assertEquals(1, $kq->soThanhCong, 'Ho so chay duoc van phai duoc dem');
        $this->assertEquals(1, $kq->soThatBai);
        $this->assertEquals(['MA1'], $kq->dsMaLk);
        $this->assertContains('Ho so #2', $kq->lyDoThatBai);
    }

    /** @test */
    public function thuc_te_it_hon_khai_bao_thi_tu_choi_ca_file()
    {
        // File co the bi cat cut. Nhap mot phan roi bao thanh cong chinh la loi ma dot
        // nay di chua.
        $kq = Xml3176ImportFileResult::tu([
            Xml3176ImportResult::thanhCong('MA1', ['XML1']),
            Xml3176ImportResult::thanhCong('MA2', ['XML1']),
        ], 3, 2);

        $this->assertFalse($kq->thanhCong);
        $this->assertContains('3', $kq->lyDoThatBai);
        $this->assertContains('2', $kq->lyDoThatBai);
    }

    /** @test */
    public function thuc_te_nhieu_hon_khai_bao_thi_van_nhap()
    {
        // Metadata sai nhung du lieu du - chan o day la chan nham.
        $kq = Xml3176ImportFileResult::tu([
            Xml3176ImportResult::thanhCong('MA1', ['XML1']),
            Xml3176ImportResult::thanhCong('MA2', ['XML1']),
        ], 1, 2);

        $this->assertTrue($kq->thanhCong);
        $this->assertEquals(2, $kq->soThanhCong);
    }

    /** @test */
    public function that_bai_som_khong_co_ho_so_nao()
    {
        $kq = Xml3176ImportFileResult::thatBaiSom('Thieu MACSKCB trong noi dung XML');

        $this->assertFalse($kq->thanhCong);
        $this->assertEquals([], $kq->ketQua);
        $this->assertEquals(0, $kq->soThanhCong);
        $this->assertNotEmpty($kq->lyDoThatBai);
    }

    /** @test */
    public function importer_khong_con_duyet_thang_hoso_filehoso()
    {
        // Trong SimpleXML, ->HOSO tren mot tap nhieu phan tu TU LAY PHAN TU DAU - khong
        // canh bao, khong loi. Day chinh la hinh dang cua loi lam mat ho so thu hai.
        $ma = $this->maKhongComment(app_path('Services/Xml3176/Xml3176Importer.php'));

        $this->assertNotContains('HOSO->FILEHOSO', $ma,
            'Van duyet thang ->HOSO->FILEHOSO nen chi thay ho so dau tien');
    }
}

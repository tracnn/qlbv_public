<?php

namespace Tests\Unit\Xml3176;

use Tests\TestCase;
use Illuminate\Support\Collection;
use App\Services\Xml3176\Xml3176ErrorIndex;

class Xml3176ErrorIndexTest extends TestCase
{
    private function loi($xml, $stt, $moTa)
    {
        return (object) ['xml' => $xml, 'stt' => $stt, 'description' => $moTa];
    }

    private function chiMuc(array $loi)
    {
        return Xml3176ErrorIndex::tu(new Collection($loi));
    }

    /** @test */
    public function co_loi_phan_biet_dung_cap_xml_va_stt()
    {
        $ix = $this->chiMuc([
            $this->loi('XML2', 1, 'Sai ma thuoc'),
            $this->loi('XML3', 2, 'Sai ma dich vu'),
        ]);

        $this->assertTrue($ix->coLoi('XML2', 1));
        $this->assertFalse($ix->coLoi('XML2', 2), 'stt 2 khong co loi o XML2');
        $this->assertFalse($ix->coLoi('XML3', 1), 'khong duoc lan giua XML2 va XML3 cung stt');
        $this->assertTrue($ix->coLoi('XML3', 2));
    }

    /** @test */
    public function co_loi_khong_truyen_stt_hoi_o_muc_xml()
    {
        $ix = $this->chiMuc([$this->loi('XML13', 5, 'Thieu dien bien')]);

        $this->assertTrue($ix->coLoi('XML13'));
        $this->assertFalse($ix->coLoi('XML14'));
    }

    /** @test */
    public function mo_ta_noi_nhieu_loi_bang_dau_cham_phay_dung_thu_tu()
    {
        $ix = $this->chiMuc([
            $this->loi('XML2', 1, 'Loi mot'),
            $this->loi('XML2', 1, 'Loi hai'),
            $this->loi('XML2', 2, 'Loi khac'),
        ]);

        $this->assertEquals('Loi mot; Loi hai', $ix->moTa('XML2', 1));
        $this->assertEquals('Loi khac', $ix->moTa('XML2', 2));
    }

    /** @test */
    public function mo_ta_tra_chuoi_rong_khi_khong_co_loi()
    {
        $ix = $this->chiMuc([$this->loi('XML2', 1, 'Loi mot')]);

        $this->assertSame('', $ix->moTa('XML2', 9));
        $this->assertSame('', $ix->moTa('XML4', 1));
        $this->assertSame('', $this->chiMuc([])->moTa('XML2', 1));
    }

    /** @test */
    public function stt_so_nguyen_va_chuoi_van_khop_nhau()
    {
        // Driver PDO co the tra so nguyen duoi dang chuoi tuy cau hinh.
        $ix = $this->chiMuc([$this->loi('XML2', 7, 'Loi bay')]);

        $this->assertTrue($ix->coLoi('XML2', '7'));
        $this->assertTrue($ix->coLoi('XML2', 7));
        $this->assertEquals('Loi bay', $ix->moTa('XML2', '7'));
    }

    /** @test */
    public function dem_loi_dem_so_ban_ghi_khong_phai_so_dong()
    {
        $ix = $this->chiMuc([
            $this->loi('XML1', 1, 'Loi mot'),
            $this->loi('XML1', 1, 'Loi hai'),
            $this->loi('XML2', 1, 'Khong tinh'),
        ]);

        $this->assertEquals(2, $ix->demLoi('XML1'));
        $this->assertEquals(0, $ix->demLoi('XML5'));
    }

    /** @test */
    public function dem_theo_stt_nhan_danh_sach_so_stt_va_dem_so_dong_co_loi()
    {
        $ix = $this->chiMuc([
            $this->loi('XML2', 1, 'Loi mot'),
            $this->loi('XML2', 1, 'Loi hai'),   // cung dong -> van tinh 1
            $this->loi('XML2', 3, 'Loi ba'),
        ]);

        // Nhan thang danh sach stt (tu pluck), khong phai danh sach model: vo modal
        // khong con nap collection nen khong co $item->stt de doc.
        $this->assertEquals(2, $ix->demTheoStt([1, 2, 3], 'XML2'));
        $this->assertEquals(2, $ix->demTheoStt(new Collection(['1', '2', '3']), 'XML2'));
        $this->assertEquals(0, $ix->demTheoStt([2, 4], 'XML2'));
        $this->assertEquals(0, $ix->demTheoStt([], 'XML2'));
    }

    /** @test */
    public function dem_theo_xml_nhan_ca_so_nguyen_tu_with_count()
    {
        $ix = $this->chiMuc([$this->loi('XML13', 1, 'Loi')]);

        $this->assertEquals(5, $ix->demTheoXml(5, 'XML13'));
        $this->assertEquals(0, $ix->demTheoXml(5, 'XML14'));
    }

    /** @test */
    public function dem_theo_xml_tra_toan_bo_so_dong_khi_co_loi()
    {
        // Giu dung ngu nghia hien tai cua bay tab khong co cot stt: chi hoi
        // "bang nay co loi khong", nen moi dong deu duoc tinh.
        $items = new Collection([(object) ['a' => 1], (object) ['a' => 2]]);

        $coLoi = $this->chiMuc([$this->loi('XML13', 1, 'Loi')]);
        $this->assertEquals(2, $coLoi->demTheoXml($items, 'XML13'));

        $khongLoi = $this->chiMuc([$this->loi('XML14', 1, 'Loi')]);
        $this->assertEquals(0, $khongLoi->demTheoXml($items, 'XML13'));
    }

    /** @test */
    public function chi_muc_rong_khong_no_o_bat_ky_phuong_thuc_nao()
    {
        $ix = $this->chiMuc([]);
        $items = new Collection([(object) ['stt' => 1]]);

        $this->assertFalse($ix->coLoi('XML2'));
        $this->assertFalse($ix->coLoi('XML2', 1));
        $this->assertEquals(0, $ix->demLoi('XML2'));
        $this->assertEquals(0, $ix->demTheoStt([1], 'XML2'));
        $this->assertEquals(0, $ix->demTheoXml($items, 'XML2'));
    }
}

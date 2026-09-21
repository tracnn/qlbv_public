<?php

namespace Tests\Unit\Xml3176;

use Tests\TestCase;
use App\Services\Xml3176Xml3Checker;

/**
 * Bon phan TT_THAU cua VTYT (quyet dinh; goi thau; nhom; nam) so voi danh muc.
 *
 * Ban cu so bang == nen phan biet hoa thuong, trong khi TT_THAU cua THUOC o XML2 so bang
 * SQL LIKE (khong phan biet). Nay bo phan biet hoa thuong - nhung GIU phep so long, vi
 * '01' == '1' tung la khop va yeu cau khong doi dieu do.
 */
class Xml3TtThauKhopTest extends TestCase
{
    /** @test */
    public function khac_hoa_thuong_van_khop()
    {
        $this->assertTrue(Xml3176Xml3Checker::ttThauKhop(
            ['123/QĐ-BV', 'G1', 'N1', '2024'],
            ['123/qđ-bv', 'g1', 'n1', '2024']
        ));
    }

    /**
     * Giu ngu nghia cu: '01' == '1' la true trong PHP. Doi sang === se am tham sinh loi
     * MEDICAL_SUPPLY_NOT_IN_CATALOG moi cho ho so truoc day van qua.
     *
     * @test
     */
    public function so_dang_so_hoc_van_khop_nhu_cu()
    {
        $this->assertTrue(Xml3176Xml3Checker::ttThauKhop(
            ['123/QĐ-BV', '01', 'N1', '2024'],
            ['123/QĐ-BV', '1', 'N1', '2024']
        ));
    }

    /** @test */
    public function khac_noi_dung_thi_khong_khop()
    {
        $this->assertFalse(Xml3176Xml3Checker::ttThauKhop(
            ['123/QĐ-BV', 'G1', 'N1', '2024'],
            ['123/QĐ-BV', 'G2', 'N1', '2024']
        ));
    }

    /** @test */
    public function thieu_phan_thi_khong_khop()
    {
        $this->assertFalse(Xml3176Xml3Checker::ttThauKhop(
            ['123/QĐ-BV', 'G1', 'N1'],
            ['123/QĐ-BV', 'G1', 'N1', '2024']
        ));
    }
}

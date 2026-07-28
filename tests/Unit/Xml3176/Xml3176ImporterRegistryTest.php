<?php

namespace Tests\Unit\Xml3176;

use Tests\TestCase;
use App\Services\Xml3176Service;
use App\Services\Xml3176\Xml3176Importer;
use App\Services\Xml3176\Xml3176ImportResult;

class Xml3176ImporterRegistryTest extends TestCase
{
    /** @test */
    public function bang_dang_ky_phu_du_xml1_den_xml18()
    {
        // Hop cua HAI ban cu: controller xu ly XML1-15, command xu ly XML1-18.
        // Thieu mot ma o day la danh roi mot nhanh khi gop.
        $mongDoi = [];
        for ($i = 1; $i <= 18; $i++) {
            $mongDoi[] = 'XML' . $i;
        }

        $this->assertEquals($mongDoi, array_keys(Xml3176Importer::LOAI_XML));
    }

    /** @test */
    public function cac_loai_bo_qua_co_chu_dich_anh_xa_null_chu_khong_vang_mat()
    {
        // Phan biet "bo qua co chu dich" voi "khong co trong bang" chinh la thu da mat
        // khi hai ban lech nhau. Vang mat -> ghi canh bao; null -> im lang bo qua.
        foreach (['XML12', 'XML16', 'XML17', 'XML18'] as $loai) {
            $this->assertArrayHasKey($loai, Xml3176Importer::LOAI_XML);
            $this->assertNull(Xml3176Importer::LOAI_XML[$loai], "$loai phai la bo qua co chu dich");
        }
    }

    /** @test */
    public function moi_handler_deu_la_phuong_thuc_co_that_tren_service()
    {
        foreach (Xml3176Importer::LOAI_XML as $loai => $handler) {
            if ($handler === null) {
                continue;
            }

            $this->assertTrue(
                method_exists(Xml3176Service::class, $handler),
                "Xml3176Service khong co phuong thuc $handler (khai bao cho $loai)"
            );
        }
    }

    /** @test */
    public function tra_cuu_phan_biet_ba_trang_thai()
    {
        $this->assertTrue(Xml3176Importer::coTrongDangKy('XML2'));
        $this->assertEquals('storeXml3176Xml2', Xml3176Importer::handlerCho('XML2'));

        $this->assertTrue(Xml3176Importer::coTrongDangKy('XML12'));
        $this->assertNull(Xml3176Importer::handlerCho('XML12'));

        $this->assertFalse(Xml3176Importer::coTrongDangKy('XML99'));
        $this->assertFalse(Xml3176Importer::coTrongDangKy(''));
    }

    /** @test */
    public function ket_qua_nhap_mang_du_thong_tin_hai_ben_goi_can()
    {
        $ok = Xml3176ImportResult::thanhCong('MALK1', ['XML1', 'XML2']);
        $this->assertTrue($ok->thanhCong);
        $this->assertEquals('MALK1', $ok->maLk);
        $this->assertEquals(['XML1', 'XML2'], $ok->loaiDaXuLy);
        $this->assertNull($ok->lyDoThatBai);

        $hong = Xml3176ImportResult::thatBai('Thieu MACSKCB');
        $this->assertFalse($hong->thanhCong);
        $this->assertNull($hong->maLk);
        $this->assertEquals('Thieu MACSKCB', $hong->lyDoThatBai);
    }
}

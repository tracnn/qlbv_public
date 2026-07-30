<?php

namespace Tests\Unit;

use App\Services\FileCopyService;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Chung minh dieu kien co lap: bat/tat cong nay KHONG anh huong cong kia.
 *
 * Kiem bang hanh vi that tren Storage::fake(), khong cham dia that va khong cham mang.
 */
class FileCopyServiceCoLapTest extends TestCase
{
    const DUONG_DAN = '20260730/01929/2026.07.30_10.00.00_000001.xml';
    const TEN_TEP = '2026.07.30_10.00.00_000001.xml';

    protected function setUp()
    {
        parent::setUp();

        Storage::fake('exportXml3176');
        Storage::fake('trucDuLieuYTe');
        Storage::fake('congDuLieuYTeDienBien');

        Storage::disk('exportXml3176')->put(self::DUONG_DAN, '<XML>noi dung</XML>');
    }

    /** @param bool $truc @param bool $dienBien */
    protected function datCo($truc, $dienBien)
    {
        config([
            'organization.truc_du_lieu_y_te' => [
                'enabled' => $truc, 'disk' => 'trucDuLieuYTe',
            ],
            'organization.cong_du_lieu_y_te_dien_bien' => [
                'enabled' => $dienBien, 'disk' => 'congDuLieuYTeDienBien',
            ],
        ]);
    }

    protected function chay()
    {
        $s = new FileCopyService();

        return [
            'truc' => $s->copyExportXml3176ToTrucDuLieuYTe(self::DUONG_DAN),
            'dien_bien' => $s->copyExportXml3176ToCongDuLieuYTeDienBien(self::DUONG_DAN),
        ];
    }

    /** @test */
    public function tat_ca_hai_thi_khong_dia_nao_co_tep()
    {
        $this->datCo(false, false);
        $ra = $this->chay();

        // Chua bat thi tra true: khong lam gi la ket qua DUNG, khong phai loi.
        $this->assertTrue($ra['truc']);
        $this->assertTrue($ra['dien_bien']);

        Storage::disk('trucDuLieuYTe')->assertMissing(self::TEN_TEP);
        Storage::disk('congDuLieuYTeDienBien')->assertMissing(self::TEN_TEP);
    }

    /** @test */
    public function chi_bat_truc_thi_dien_bien_khong_bi_dung_toi()
    {
        $this->datCo(true, false);
        $this->chay();

        Storage::disk('trucDuLieuYTe')->assertExists(self::TEN_TEP);
        Storage::disk('congDuLieuYTeDienBien')->assertMissing(self::TEN_TEP);
    }

    /** @test */
    public function chi_bat_dien_bien_thi_truc_khong_bi_dung_toi()
    {
        $this->datCo(false, true);
        $this->chay();

        Storage::disk('trucDuLieuYTe')->assertMissing(self::TEN_TEP);
        Storage::disk('congDuLieuYTeDienBien')->assertExists(self::TEN_TEP);
    }

    /** @test */
    public function bat_ca_hai_thi_ca_hai_dia_deu_co_tep()
    {
        $this->datCo(true, true);
        $ra = $this->chay();

        $this->assertTrue((bool) $ra['truc']);
        $this->assertTrue((bool) $ra['dien_bien']);

        Storage::disk('trucDuLieuYTe')->assertExists(self::TEN_TEP);
        Storage::disk('congDuLieuYTeDienBien')->assertExists(self::TEN_TEP);
    }

    /**
     * Mot dia hong khong duoc lam chet export: copy() bat loi va ghi log, tra false.
     */
    /** @test */
    public function tep_nguon_khong_ton_tai_thi_tra_false_chu_khong_nem()
    {
        $this->datCo(true, true);

        $s = new FileCopyService();

        $this->assertFalse($s->copyExportXml3176ToTrucDuLieuYTe('khong/ton/tai.xml'));
        $this->assertFalse($s->copyExportXml3176ToCongDuLieuYTeDienBien('khong/ton/tai.xml'));
    }
}

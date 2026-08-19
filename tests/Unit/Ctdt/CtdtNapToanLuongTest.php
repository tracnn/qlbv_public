<?php

namespace Tests\Unit\Ctdt;

use Tests\TestCase;
use Tests\Support\DungBangCtdtSqlite;
use Tests\Support\GoiCtdtMau;
use App\Services\Ctdt\CtdtImporter;
use App\Services\Ctdt\CtdtLoaiRegistry;
use App\Models\BHYT\Ctdt\CtdtHoSo;
use App\Models\BHYT\Ctdt\CtdtChungTu;

/**
 * Luoi an toan cho ca Giai doan 2A: nap that, qua het chuoi, xuong CSDL that.
 */
class CtdtNapToanLuongTest extends TestCase
{
    use DungBangCtdtSqlite;
    use GoiCtdtMau;

    /** @var CtdtImporter */
    private $importer;

    protected function setUp()
    {
        parent::setUp();
        $this->chuanBiBangCtdt();
        $this->importer = new CtdtImporter();
        config(['organization.BHYT.ma_cskcb' => '01013']);
    }

    /**
     * Mot bo truong toi thieu de chung tu nao cung nap duoc: khoa nghiep vu neu loai do co.
     */
    private function truongToiThieu($lop)
    {
        $truong = $lop::truong();
        $bo = [];

        foreach (['MA_YTE', 'MA_GBT', 'MA_GCS'] as $khoa) {
            if (array_key_exists($khoa, $truong)) {
                $bo[$khoa] = 'KEY-' . $lop::maLoaiHoSo();
            }
        }

        // Mot truong van ban de chac chan anh xa the -> cot chay that, khong chi chay rong.
        if (array_key_exists('HO_TEN', $truong)) {
            $bo['HO_TEN'] = 'Nguoi Benh Test';
        } elseif (array_key_exists('HOTEN_NND', $truong)) {
            $bo['HOTEN_NND'] = 'Nguoi Me Test';
        }

        return $bo;
    }

    /** @test */
    public function bay_loai_tt25_deu_nap_duoc_va_xuong_dung_bang()
    {
        foreach (CtdtLoaiRegistry::cuaDichVu('CT2025') as $loai => $lop) {
            $xml = $this->goiCt2025([[$this->chungTu($loai, $this->truongToiThieu($lop))]]);

            $kq = $this->importer->nhapTuChuoi($xml, ['macskcb' => '01929']);

            $this->assertTrue($kq->thanhCong, $loai . ': ' . (string) $kq->lyDoThatBai);

            $tenModel = $lop::model();
            $this->assertSame(1, $tenModel::count(), $loai . ': chi tiet phai xuong bang ' . $lop::bang());

            // Don sach de vong sau dem lai tu dau.
            CtdtHoSo::query()->delete();
            $tenModel::query()->delete();
            CtdtChungTu::query()->delete();
        }
    }

    /** @test */
    public function ba_dich_vu_deu_nap_duoc_va_ghi_dung_loai_hs()
    {
        $this->importer->nhapTuChuoi(
            $this->goiCt2025([[$this->chungTu('CT03', ['MA_YTE' => 'YT001'])]]),
            ['macskcb' => '01929']
        );
        $this->importer->nhapTuChuoi($this->goiGbt(['MA_GBT' => 'GBT-1']));
        $this->importer->nhapTuChuoi($this->goiGcs(['MA_GCS' => 'GCS-1']));

        $this->assertSame(3, CtdtHoSo::count());

        $this->assertSame('39', CtdtHoSo::where('ma_ho_so', 'YT001')->first()->loai_hs);
        $this->assertSame('60', CtdtHoSo::where('ma_ho_so', 'GBT-1')->first()->loai_hs);
        $this->assertSame('61', CtdtHoSo::where('ma_ho_so', 'GCS-1')->first()->loai_hs);

        $this->assertSame('CT2025', CtdtHoSo::where('ma_ho_so', 'YT001')->first()->dich_vu);
        $this->assertSame('GBT', CtdtHoSo::where('ma_ho_so', 'GBT-1')->first()->dich_vu);
        $this->assertSame('GCS', CtdtHoSo::where('ma_ho_so', 'GCS-1')->first()->dich_vu);
    }

    /** @test */
    public function tep_nhieu_ho_so_nap_du_khong_sot_cai_nao()
    {
        $hoSo = [];

        for ($i = 1; $i <= 10; $i++) {
            $hoSo[] = [$this->chungTu('CT03', ['MA_YTE' => 'YT' . str_pad($i, 3, '0', STR_PAD_LEFT)])];
        }

        $kq = $this->importer->nhapTuChuoi($this->goiCt2025($hoSo), ['macskcb' => '01929']);

        $this->assertTrue($kq->thanhCong, (string) $kq->lyDoThatBai);
        $this->assertSame(10, $kq->soThanhCong);
        $this->assertSame(10, CtdtHoSo::count());
        $this->assertNotNull(CtdtHoSo::where('ma_ho_so', 'YT010')->first(), 'Ho so cuoi cung khong duoc bo sot');
    }

    /** @test */
    public function hai_ho_so_khong_co_ma_yte_trong_mot_tep_thanh_hai_ban_ghi()
    {
        $xml = $this->goiCt2025([
            [$this->chungTu('CT04', ['MA_CT' => 'CT-1'])],
            [$this->chungTu('CT04', ['MA_CT' => 'CT-2'])],
        ], ['id' => 'Id-abc']);

        $kq = $this->importer->nhapTuChuoi($xml, ['macskcb' => '01929']);

        $this->assertTrue($kq->thanhCong, (string) $kq->lyDoThatBai);
        $this->assertSame(2, CtdtHoSo::count(), 'Khoa lui phai kem chi so, khong duoc trung');
        $this->assertSame(['Id-abc#1', 'Id-abc#2'], $kq->dsMaHoSo);
    }

    /** @test */
    public function nap_lai_ba_lan_van_chi_mot_ban_ghi()
    {
        for ($i = 1; $i <= 3; $i++) {
            $kq = $this->importer->nhapTuChuoi($this->goiCt2025([[
                $this->chungTu('CT03', ['MA_YTE' => 'YT001', 'CHAN_DOAN' => 'Lan ' . $i]),
            ]]), ['macskcb' => '01929']);

            $this->assertTrue($kq->thanhCong, 'Lan ' . $i . ': ' . (string) $kq->lyDoThatBai);
        }

        $this->assertSame(1, CtdtHoSo::count());
        $this->assertSame(1, CtdtChungTu::count());
        $this->assertSame('Lan 3', \App\Models\BHYT\Ctdt\CtdtCt03::first()->chan_doan);
    }

    /** @test */
    public function noi_dung_goc_du_de_dung_lai_khoa_nghiep_vu()
    {
        // Khi cong bao 205, thu duy nhat de doi chieu la noi dung nguyen van. Neu no khong
        // du de tim lai ho so thi viec luu no chang giup duoc gi.
        $this->importer->nhapTuChuoi($this->goiCt2025([[
            $this->chungTu('CT03', ['MA_YTE' => 'YT001', 'HO_TEN' => 'Nguyen Van Test']),
        ]]), ['macskcb' => '01929']);

        $goc = CtdtChungTu::first()->noi_dung_goc;
        $lai = simplexml_load_string($goc);

        $this->assertNotFalse($lai, 'noi_dung_goc phai parse lai duoc');
        $this->assertSame('YT001', (string) $lai->MA_YTE);
        $this->assertSame('CT03', $lai->getName());
    }
}

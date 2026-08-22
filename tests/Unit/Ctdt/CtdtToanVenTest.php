<?php

namespace Tests\Unit\Ctdt;

use Tests\TestCase;
use Tests\Support\DungBangCtdtSqlite;
use Illuminate\Support\Facades\Schema;
use App\Services\Ctdt\CtdtLoaiRegistry;

/**
 * Luoi an toan cho ca Giai doan 1.
 *
 * Danh sach cot nam HAI NOI: mang trong migration va truong() trong lop loai. Hai noi
 * thi se lech nhau - do la chuyen thoi gian, khong phai chuyen co hay khong. Test nay
 * bat chung phai khop, va bat luon $fillable cua model.
 */
class CtdtToanVenTest extends TestCase
{
    use DungBangCtdtSqlite;

    protected function setUp()
    {
        parent::setUp();
        $this->chuanBiBangCtdt();
    }

    /** @test */
    public function moi_lop_loai_cai_dung_interface()
    {
        foreach (CtdtLoaiRegistry::tatCa() as $loai => $lop) {
            $this->assertContains(
                \App\Services\Ctdt\Loai\LoaiChungTu::class,
                class_implements($lop),
                $lop . ' phai cai LoaiChungTu'
            );
        }
    }

    /** @test */
    public function khoa_registry_trung_voi_maLoaiHoSo_cua_lop()
    {
        // Lech nhau thi cho('CT03') tra ve lop tu xung la 'CT04' - va khong ai biet.
        foreach (CtdtLoaiRegistry::tatCa() as $loai => $lop) {
            $this->assertSame($loai, $lop::maLoaiHoSo(),
                'Khoa registry "' . $loai . '" lech voi maLoaiHoSo() cua ' . $lop);
        }
    }

    /** @test */
    public function moi_cot_khai_trong_truong_deu_ton_tai_trong_bang()
    {
        foreach (CtdtLoaiRegistry::tatCa() as $loai => $lop) {
            $bang = $lop::bang();

            $this->assertTrue(Schema::hasTable($bang), $loai . ': thieu bang ' . $bang);

            foreach ($lop::truong() as $the => $cot) {
                $this->assertTrue(Schema::hasColumn($bang, $cot),
                    $loai . ': the ' . $the . ' anh xa toi cot ' . $cot . ' khong co trong ' . $bang);
            }
        }
    }

    /** @test */
    public function moi_cot_du_lieu_trong_bang_deu_duoc_khai_trong_truong()
    {
        // Chieu nguoc lai: cot co trong bang ma khong co trong truong() nghia la mot the
        // PL02 se khong bao gio duoc nap - im lang, va chi lo ra khi BHXH doi soat thieu.
        $cotKhung = ['id', 'chung_tu_id', 'created_at', 'updated_at'];

        foreach (CtdtLoaiRegistry::tatCa() as $loai => $lop) {
            $cotTrongBang = array_diff(Schema::getColumnListing($lop::bang()), $cotKhung);
            $cotKhai = array_values($lop::truong());

            $thieu = array_diff($cotTrongBang, $cotKhai);

            $this->assertEmpty($thieu,
                $loai . ': cot co trong bang nhung khong khai trong truong(): ' . implode(', ', $thieu));
        }
    }

    /** @test */
    public function fillable_cua_model_phu_du_cac_cot_khai_trong_truong()
    {
        foreach (CtdtLoaiRegistry::tatCa() as $loai => $lop) {
            $tenLop = $lop::model();
            $model = new $tenLop();
            $fillable = $model->getFillable();

            $this->assertContains('chung_tu_id', $fillable, $loai . ': model thieu chung_tu_id');

            foreach ($lop::truong() as $the => $cot) {
                $this->assertContains($cot, $fillable,
                    $loai . ': model thieu ' . $cot . ' trong $fillable - create() se bo qua im lang');
            }

            // Chieu nguoc lai: mot muc THUA hoac GO SAI trong $fillable tro toi cot
            // khong ton tai se lot qua vong assertContains o tren, va chi no thanh
            // SQLSTATE Unknown column giua vong nap o Giai doan 2 - nguy hiem nhat voi
            // CtdtGiayChungSinh vi $fillable cua no co 69 muc viet tay.
            $thua = array_diff($fillable, Schema::getColumnListing($lop::bang()));

            $this->assertEmpty($thua,
                $loai . ': $fillable co muc khong ung voi cot nao trong bang ' . $lop::bang()
                . ': ' . implode(', ', $thua));
        }
    }

    /** @test */
    public function model_tro_dung_bang_ma_lop_loai_khai()
    {
        foreach (CtdtLoaiRegistry::tatCa() as $loai => $lop) {
            $tenLop = $lop::model();
            $model = new $tenLop();

            $this->assertSame($lop::bang(), $model->getTable(),
                $loai . ': model tro bang khac voi bang() cua lop loai');
        }
    }

    /** @test */
    public function moi_lop_loai_thuoc_mot_dich_vu_co_khai_trong_config()
    {
        $dichVuHopLe = array_keys(config('ctdt.dich_vu'));

        foreach (CtdtLoaiRegistry::tatCa() as $loai => $lop) {
            $this->assertContains($lop::dichVu(), $dichVuHopLe,
                $loai . ': dich vu "' . $lop::dichVu() . '" khong co trong config ctdt.dich_vu');
        }
    }

    /** @test */
    public function khong_hai_lop_nao_dung_chung_mot_bang()
    {
        $bang = [];

        foreach (CtdtLoaiRegistry::tatCa() as $loai => $lop) {
            $this->assertNotContains($lop::bang(), $bang,
                $loai . ': bang ' . $lop::bang() . ' da duoc mot loai khac dung');

            $bang[] = $lop::bang();
        }
    }

    /** @test */
    public function rut_gon_luon_tra_dung_bay_khoa()
    {
        // Man danh sach doc mot bo cot duy nhat cho ca chin loai. Thieu mot khoa la
        // Undefined index luc nap - va chi lo ra voi dung loai chung tu do.
        foreach (CtdtLoaiRegistry::tatCa() as $loai => $lop) {
            $xml = simplexml_load_string('<' . $lop::theGoc() . '/>');
            $rutGon = $lop::rutGon($xml);

            $this->assertSame(
                ['ma_the', 'ho_ten', 'ngay_sinh', 'ngay_vao', 'ngay_ra', 'so_cccd', 'ma_bhxh'],
                array_keys($rutGon),
                $loai . ': rutGon() phai tra dung bay khoa theo dung thu tu'
            );
        }
    }

    /** @test */
    public function moi_the_ma_rut_gon_doc_deu_phai_co_trong_truong()
    {
        // rut_gon_luon_tra_dung_bay_khoa() o tren chi canh BAY KHOA tra ve (dua vao XML
        // RONG nen moi gia tri deu null) - no khong canh THE NGUON ma rutGon()/
        // maChungTu() doc. Chin khoi rutGon() duoc CO Y sao chep chin lan (quyet dinh
        // da chot); neu ai do doi ten mot the trong truong() va migration ma quen sua
        // rutGon(), cot do se TRONG VINH VIEN tren man danh sach ma khong test nao bat
        // duoc - vi rutGon() luon tra ve mang dung 5 khoa, chi gia tri la null.
        //
        // Cach bat: bom vao XML MOI the khai trong truong() mot gia tri danh dau la
        // chinh TEN THE, roi doi chieu nguoc - gia tri ma rutGon()/maChungTu() tra ve
        // phai la mot trong cac danh dau da bom, tuc the nguon chac chan nam trong
        // truong().
        //
        // CT04, CT06, CT07 CO Y khong co the khoa nghiep vu (khong co MA_YTE) nen
        // maChungTu() cua ba lop nay luon tra null bat ke XML co gi - dung nhu thiet ke,
        // khong phai loi can sua.
        $khongCoMaChungTu = ['CT04', 'CT06', 'CT07'];

        foreach (CtdtLoaiRegistry::tatCa() as $loai => $lop) {
            $theKhai = array_keys($lop::truong());

            $xml = new \SimpleXMLElement('<' . $lop::theGoc() . '/>');
            foreach ($theKhai as $the) {
                $xml->addChild($the, $the);
            }

            $rutGon = $lop::rutGon($xml);
            foreach ($rutGon as $khoa => $giaTri) {
                $this->assertNotNull($giaTri,
                    $loai . ': rutGon()[' . $khoa . '] tra ve null du XML co du moi the'
                    . ' khai trong truong() - the ma rutGon() doc co the da bi doi ten'
                    . ' hoac xoa khoi truong()');

                $this->assertContains($giaTri, $theKhai,
                    $loai . ': rutGon()[' . $khoa . '] doc the "' . $giaTri
                    . '" nhung the do khong co trong truong()');
            }

            $maChungTu = $lop::maChungTu($xml);

            if (in_array($loai, $khongCoMaChungTu, true)) {
                $this->assertNull($maChungTu,
                    $loai . ': maChungTu() phai tra ve null - loai nay khong co the khoa'
                    . ' nghiep vu, dung nhu thiet ke');
                continue;
            }

            $this->assertNotNull($maChungTu,
                $loai . ': maChungTu() tra ve null du XML co du moi the khai trong'
                . ' truong() - the ma maChungTu() doc co the da bi doi ten hoac xoa khoi'
                . ' truong()');

            $this->assertContains($maChungTu, $theKhai,
                $loai . ': maChungTu() doc the "' . $maChungTu . '" nhung the do khong co'
                . ' trong truong()');
        }
    }
}

<?php

namespace Tests\Unit\Ctdt;

use Tests\TestCase;
use Tests\Support\DungBangCtdtSqlite;
use App\Models\BHYT\Ctdt\CtdtHoSo;
use App\Models\BHYT\Ctdt\CtdtLichSuGui;

/**
 * Gui tu dong khong co nguoi ngoi nhin. Cot van ban tu do lich_su_gui khong tra loi duoc
 * cau hoi van hanh dau tien khi co su co: "dem qua lenh nen gui bao nhieu ho so, bao nhieu
 * cai bi tu choi".
 */
class CtdtLichSuGuiTest extends TestCase
{
    // KHONG DatabaseMigrations: trait do goi migrate:fresh, tuc DROP toan bo bang cua CSDL
    // phat trien. Da xay ra that ngay 2026-08-21. DungBangCtdtSqlite dung bang tren SQLite
    // bo nho va khong bao gio cham toi may chu that.
    use DungBangCtdtSqlite;

    protected function setUp()
    {
        parent::setUp();
        $this->chuanBiBangCtdt();
    }

    private function hoSo()
    {
        return CtdtHoSo::create([
            'ma_ho_so' => 'YT001', 'dich_vu' => 'CT2025', 'loai_hs' => '39',
            'macskcb'  => '01001',
        ]);
    }

    /** @test */
    public function ghi_duoc_mot_dong_nhat_ky()
    {
        $hoSo = $this->hoSo();

        CtdtLichSuGui::create([
            'ho_so_id'            => $hoSo->id,
            'ma_ho_so'            => $hoSo->ma_ho_so,
            'nguoi_gui'           => 'bsnguyen',
            'nguon'               => CtdtLichSuGui::NGUON_CONSOLE,
            'ma_gd'               => 'HS_CHUNGTU01929_094D388C-6DD7-4CA1-A3BE-6D5BF53FEE75',
            'ma_ket_qua'          => '200',
            'thoi_gian_tiep_nhan' => '20260821083000',
            'thanh_cong'          => true,
            'thong_diep'          => '{"MaKetQua":"200"}',
        ]);

        $dong = CtdtLichSuGui::where('ma_ho_so', 'YT001')->first();

        $this->assertNotNull($dong);
        $this->assertSame(CtdtLichSuGui::NGUON_CONSOLE, $dong->nguon);
        $this->assertTrue((bool) $dong->thanh_cong);
    }

    /** @test */
    public function ma_gd_dai_52_ky_tu_khong_bi_cat()
    {
        // Lan gui that dau tien (2026-08-20) cho thay MaGD cong tra ve dai 52 ky tu, trong
        // khi cot cu la VARCHAR(50). Cot moi phai rong tu dau chu khong doi mot lan gui that
        // nua moi biet.
        $hoSo = $this->hoSo();
        $maGd = 'HS_CHUNGTU01929_094D388C-6DD7-4CA1-A3BE-6D5BF53FEE75';

        CtdtLichSuGui::create([
            'ho_so_id' => $hoSo->id, 'ma_ho_so' => 'YT001',
            'nguon' => CtdtLichSuGui::NGUON_MAN_HINH,
            'ma_gd' => $maGd, 'thanh_cong' => true,
        ]);

        $this->assertSame($maGd, CtdtLichSuGui::where('ma_ho_so', 'YT001')->value('ma_gd'));
    }

    /** @test */
    public function khai_khoa_ngoai_cascade_ve_ctdt_ho_so()
    {
        $migration = file_get_contents(base_path(
            'database/migrations/2026_08_21_100001_create_ctdt_lich_su_gui_table.php'
        ));

        $this->assertContains("->onDelete('cascade')", $migration,
            'Dong nhat ky mo coi lam bang phinh mai va khong join nguoc ve ho so duoc');
    }

    /** @test */
    public function loi_ghi_nhat_ky_khong_duoc_lam_job_gui_that_bai()
    {
        // Nem o day lam job that bai SAU KHI cong da nhan ho so; hang doi gui lai va cong
        // nhan LAN HAI cung mot goi. PL02 khong co ma giao dich phia client nen cong khong
        // khu trung duoc - day dung la kieu loi da tung xay ra voi cot ma_gd bi tran.
        $nguon = file_get_contents(base_path('app/Jobs/SubmitCtdtJob.php'));

        $this->assertRegExp(
            '/try\s*\{\s*CtdtLichSuGui::create\(/s',
            $nguon,
            'Loi ghi nhat ky phai duoc nuot, khong duoc de nem ra khoi ghiKetQua()'
        );
    }
}

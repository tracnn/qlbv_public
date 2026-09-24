<?php

namespace Tests\Unit;

use App\Http\Controllers\BHYT\CheckHeinCardController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Tests\Support\DungBangLoiDotDieuTriSqlite;
use Tests\TestCase;

/**
 * Loc ngay cua man Ket qua tra cuu the phai "sargable": whereDate() sinh ra date(updated_at)
 * nen MySQL quet toan bang + filesort ~46 nghin dong o MOI lan tai (mac dinh chi xem hom
 * nay). Loc bang khoang tren chinh cot thi dung duoc index updated_at.
 */
class KetQuaTraCuuTheLocNgayTest extends TestCase
{
    use DungBangLoiDotDieuTriSqlite;

    protected function setUp()
    {
        parent::setUp();
        $this->chuanBiBangLoi();

        foreach (['2026-09-23 23:59:59', '2026-09-24 00:00:00', '2026-09-24 23:59:59', '2026-09-25 00:00:00'] as $i => $luc) {
            DB::table('check_hein_cards')->insert([
                'ma_lk' => 'HS' . $i, 'ma_tracuu' => '000', 'ma_kiemtra' => '00',
                'created_at' => $luc, 'updated_at' => $luc,
            ]);
        }
    }

    private function loc(array $q)
    {
        $m = new \ReflectionMethod(CheckHeinCardController::class, 'locTheoYeuCau');
        $m->setAccessible(true);

        return $m->invoke(app(CheckHeinCardController::class), Request::create('/x', 'GET', $q));
    }

    /** @test */
    public function loc_tron_ngay_dung_dang_nut_tai_du_lieu_gui_len()
    {
        // partials.load_data_button gui kem gio: 'YYYY-MM-DD HH:mm:ss'.
        $ma = $this->loc(['tu_ngay' => '2026-09-24 00:00:00', 'den_ngay' => '2026-09-24 23:59:59'])
            ->pluck('ma_lk')->sort()->values()->all();

        $this->assertSame(['HS1', 'HS2'], $ma);
    }

    /** @test */
    public function loc_tron_ngay_khi_chi_gui_ngay()
    {
        $this->assertSame(2, $this->loc(['tu_ngay' => '2026-09-24', 'den_ngay' => '2026-09-24'])->count());
    }

    /** @test */
    public function khong_boc_cot_trong_ham_date()
    {
        $sql = $this->loc(['tu_ngay' => '2026-09-24', 'den_ngay' => '2026-09-24'])->toSql();

        $this->assertNotContains('date(', strtolower($sql), 'Boc updated_at trong date() lam mat index');
        $this->assertContains('"updated_at" >=', $sql);
        $this->assertContains('"updated_at" <', $sql);
    }

    /** @test */
    public function ngay_hong_thi_bo_qua_bo_loc_khong_vo_trang()
    {
        $this->assertSame(4, $this->loc(['tu_ngay' => 'abc', 'den_ngay' => ''])->count());
    }
}

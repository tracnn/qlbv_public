<?php

namespace Tests\Unit\Ctdt;

use Tests\TestCase;
use Tests\Support\DungBangCtdtSqlite;
use App\Jobs\CheckCtdtJob;
use App\Models\BHYT\Ctdt\CtdtHoSo;
use App\Models\BHYT\Ctdt\CtdtChungTu;
use App\Models\BHYT\Ctdt\CtdtLoi;
use App\Models\BHYT\Ctdt\CtdtCt03;

class CheckCtdtJobTest extends TestCase
{
    use DungBangCtdtSqlite;

    protected function setUp()
    {
        parent::setUp();
        $this->chuanBiBangCtdt();
    }

    /**
     * Dung mot ho so CT03 voi cac gia tri truyen vao, tra ban ghi ho so.
     */
    private function hoSoCt03(array $chiTiet, array $ghiDeHoSo = [])
    {
        $hoSo = CtdtHoSo::create(array_merge([
            'ma_ho_so' => 'YT001', 'dich_vu' => 'CT2025', 'loai_hs' => '39',
            'macskcb' => '01929', 'so_chung_tu' => 1,
        ], $ghiDeHoSo));

        $chungTu = CtdtChungTu::create([
            'ho_so_id' => $hoSo->id, 'loai_ho_so' => 'CT03',
            'ma_chung_tu' => 'YT001', 'noi_dung_goc' => '<CT03/>',
        ]);

        CtdtCt03::create(array_merge(['chung_tu_id' => $chungTu->id], $chiTiet));

        return $hoSo->fresh();
    }

    private function chay($maHoSo = 'YT001')
    {
        (new CheckCtdtJob($maHoSo))->handle();
    }

    /** @test */
    public function ho_so_hop_le_khong_sinh_loi_va_so_loi_bang_khong()
    {
        $this->hoSoCt03([
            'ma_yte' => 'YT001', 'ho_ten' => 'Nguyen Van Test', 'ngay_sinh' => '19950914',
            'ngay_vao' => '201912121200', 'ngay_ra' => '201912180001', 'ma_the' => 'DN1',
        ]);

        $this->chay();

        $this->assertSame(0, CtdtLoi::count());
        $this->assertSame(0, (int) CtdtHoSo::first()->so_loi);
        $this->assertNotNull(CtdtHoSo::first()->checked_at);
    }

    /** @test */
    public function ghi_loi_kem_ho_so_id_va_chung_tu_id()
    {
        $this->hoSoCt03(['ma_yte' => 'YT001', 'ho_ten' => '', 'ngay_sinh' => '19950914',
            'ngay_vao' => '201912121200', 'ngay_ra' => '201912180001', 'ma_the' => 'DN1']);

        $this->chay();

        $loi = CtdtLoi::first();

        $this->assertNotNull($loi);
        $this->assertSame('CTDT001', $loi->ma_loi);
        $this->assertSame('HO_TEN', $loi->ten_truong);
        // ho_so_id PHAI co: CtdtLuuHoSo::xoaHoSoCu() xoa ctdt_loi theo ho_so_id. Ban ghi
        // loi khong co ho_so_id se song sot qua lan nap lai.
        $this->assertNotNull($loi->ho_so_id);
        $this->assertNotNull($loi->chung_tu_id);
    }

    /** @test */
    public function so_loi_chi_dem_muc_chan()
    {
        // Thieu ho ten (chan) + thieu ma the (canh bao) + co sai (canh bao) = 3 ban ghi
        // loi nhung so_loi phai la 1. so_loi la con so quyet dinh ho so co duoc gui hay
        // khong; dem ca canh bao vao se chan nham nhung ho so hop le.
        $this->hoSoCt03(['ma_yte' => 'YT001', 'ho_ten' => '', 'ngay_sinh' => '19950914',
            'ngay_vao' => '201912121200', 'ngay_ra' => '201912180001',
            'ma_the' => '', 'tekt' => '7']);

        $this->chay();

        $this->assertSame(3, CtdtLoi::count());
        $this->assertSame(1, (int) CtdtHoSo::first()->so_loi);
        $this->assertSame(2, CtdtLoi::where('muc_do', 'canh_bao')->count());
    }

    /** @test */
    public function chay_lai_khong_nhan_doi_loi()
    {
        // Job phai tu idempotent: hang doi co the giao lai sau khi that bai giua chung.
        $this->hoSoCt03(['ma_yte' => 'YT001', 'ho_ten' => '', 'ngay_sinh' => '19950914',
            'ngay_vao' => '201912121200', 'ngay_ra' => '201912180001', 'ma_the' => 'DN1']);

        $this->chay();
        $this->chay();
        $this->chay();

        $this->assertSame(1, CtdtLoi::count());
        $this->assertSame(1, (int) CtdtHoSo::first()->so_loi);
    }

    /** @test */
    public function sua_du_lieu_roi_chay_lai_thi_loi_cu_bien_mat()
    {
        $hoSo = $this->hoSoCt03(['ma_yte' => 'YT001', 'ho_ten' => '', 'ngay_sinh' => '19950914',
            'ngay_vao' => '201912121200', 'ngay_ra' => '201912180001', 'ma_the' => 'DN1']);

        $this->chay();
        $this->assertSame(1, CtdtLoi::count());

        CtdtCt03::first()->update(['ho_ten' => 'Nguyen Van Test']);
        $this->chay();

        $this->assertSame(0, CtdtLoi::count());
        $this->assertSame(0, (int) CtdtHoSo::first()->so_loi);
    }

    /** @test */
    public function truyen_ma_co_so_cua_ho_so_xuong_bo_kiem()
    {
        // Quy tac CTDT007 doi chieu MACSKCB trong chung tu voi ma co so cua HO SO. Truyen
        // nham gia tri o day thi quy tac do bat nham hoac khong bat gi.
        $hoSo = CtdtHoSo::create([
            'ma_ho_so' => 'GBT-1', 'dich_vu' => 'GBT', 'loai_hs' => '60',
            'macskcb' => '01929', 'so_chung_tu' => 1,
        ]);

        $chungTu = CtdtChungTu::create([
            'ho_so_id' => $hoSo->id, 'loai_ho_so' => 'GIAYBAOTU',
            'ma_chung_tu' => 'GBT-1', 'noi_dung_goc' => '<GIAYBAOTU/>',
        ]);

        \App\Models\BHYT\Ctdt\CtdtGiayBaoTu::create([
            'chung_tu_id' => $chungTu->id, 'ma_gbt' => 'GBT-1',
            'ho_ten' => 'Nguyen Van Test', 'ngay_sinh' => '20220101',
            'ngay_tv' => '202510070200', 'ma_the' => 'DN1',
            'macskcb' => '37470',
        ]);

        (new CheckCtdtJob('GBT-1'))->handle();

        $this->assertSame(1, CtdtLoi::where('ma_loi', 'CTDT007')->count());
    }

    /** @test */
    public function ho_so_khong_ton_tai_thi_khong_nem()
    {
        // Job co the nam cho trong hang doi rat lau; giua luc do ho so co the da bi xoa.
        // Nem o day chi lam job that bai va thu lai ba lan cho cung mot ket qua.
        $this->chay('KHONG_TON_TAI');

        $this->assertSame(0, CtdtLoi::count());
    }

    /** @test */
    public function chung_tu_khong_co_ban_ghi_chi_tiet_thi_bo_qua()
    {
        $hoSo = CtdtHoSo::create([
            'ma_ho_so' => 'YT001', 'dich_vu' => 'CT2025', 'loai_hs' => '39',
            'macskcb' => '01929', 'so_chung_tu' => 1,
        ]);

        CtdtChungTu::create([
            'ho_so_id' => $hoSo->id, 'loai_ho_so' => 'CT03',
            'ma_chung_tu' => 'YT001', 'noi_dung_goc' => '<CT03/>',
        ]);

        $this->chay();

        $this->assertSame(0, CtdtLoi::count());
        $this->assertNotNull(CtdtHoSo::first()->checked_at);
    }

    /** @test */
    public function loai_la_trong_CSDL_thi_bo_qua_khong_nem()
    {
        // Registry co the bi thu hep sau khi du lieu da duoc ghi. Nem o day thi mot loai
        // da go se lam moi lan kiem cua moi ho so cu deu that bai.
        $hoSo = CtdtHoSo::create([
            'ma_ho_so' => 'YT001', 'dich_vu' => 'CT2025', 'loai_hs' => '39',
            'macskcb' => '01929', 'so_chung_tu' => 1,
        ]);

        CtdtChungTu::create([
            'ho_so_id' => $hoSo->id, 'loai_ho_so' => 'LOAI_DA_GO',
            'noi_dung_goc' => '<LOAI_DA_GO/>',
        ]);

        $this->chay();

        $this->assertSame(0, CtdtLoi::count());
        $this->assertNotNull(CtdtHoSo::first()->checked_at);
    }
}

<?php

namespace Tests\Unit\Ctdt;

use Tests\TestCase;
use Tests\Support\DungBangCtdtSqlite;
use Tests\Support\GoiCtdtMau;
use App\Jobs\CheckCtdtJob;
use App\Services\Ctdt\CtdtImporter;
use App\Services\Ctdt\CtdtDanhSach;
use App\Services\Ctdt\CtdtTrangThaiGui;
use App\Models\BHYT\Ctdt\CtdtHoSo;
use App\Models\BHYT\Ctdt\CtdtLoi;

/**
 * Luoi an toan cho ca Giai doan 3: nap that -> kiem that -> con so hien dung o ca man
 * danh sach lan bo loc.
 *
 * KHONG dung Queue::fake() o day: day chinh la cho phai chay job THAT.
 */
class CtdtKiemToanLuongTest extends TestCase
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
        config(['organization.chung_tu_dien_tu.submit_enabled' => true]);
    }

    private function napVaKiem($xml)
    {
        $kq = $this->importer->nhapTuChuoi($xml, ['macskcb' => '01929']);

        foreach ($kq->dsMaHoSo as $maHoSo) {
            (new CheckCtdtJob($maHoSo))->handle();
        }

        return $kq;
    }

    /** @test */
    public function ho_so_du_truong_thi_khong_co_loi_chan()
    {
        // Bat bien: mot ho so hop le phai di het qua bo kiem ma khong bi bat loi oan -
        // bo kiem khong duoc "qua tay" bao loi cho ho so dung.
        $this->napVaKiem($this->goiCt2025([[
            $this->chungTu('CT03', [
                'MA_YTE' => 'YT001', 'MA_BHXH' => '0123456789', 'MA_KHOA' => 'K01',
                'HO_TEN' => 'Nguyen Van Test', 'NGAY_SINH' => '19950914', 'GIOI_TINH' => '1',
                'DIA_CHI' => 'Ha Noi', 'NGAY_VAO' => '201912121200',
                'NGAY_RA' => '201912180001', 'MA_THE' => 'DN1234567890',
            ]),
        ]]));

        $this->assertSame(0, (int) CtdtHoSo::first()->so_loi);
        $this->assertNotNull(CtdtHoSo::first()->checked_at);
    }

    /** @test */
    public function ho_so_thieu_truong_thi_so_loi_len_va_bo_loc_bat_duoc()
    {
        // Bat bien that: cot "So loi" va bo loc chi_con_loi phai noi cung mot chuyen - lech
        // nhau la nguoi dung loc ra mot danh sach khong khop voi con so ho vua nhin thay.
        $this->napVaKiem($this->goiCt2025([[
            $this->chungTu('CT03', ['MA_YTE' => 'YT001', 'MA_BHXH' => '0123456789', 'MA_THE' => 'DN1']),
        ]]));

        $hoSo = CtdtHoSo::first();

        $this->assertGreaterThan(0, (int) $hoSo->so_loi);
        $this->assertSame(1, CtdtDanhSach::truyVan(['chi_con_loi' => true])->count());
    }

    /** @test */
    public function trang_thai_gui_thanh_CON_LOI_khi_bo_kiem_bat_duoc_loi()
    {
        // Day la ly do ca Giai doan 3 ton tai: mot ho so con loi khong duoc di tiep sang
        // duong ky va gui.
        $this->napVaKiem($this->goiCt2025([[
            $this->chungTu('CT03', ['MA_YTE' => 'YT001', 'MA_BHXH' => '0123456789']),
        ]]));

        $hoSo = CtdtHoSo::first();
        $hoSo->update(['is_signed' => true]);

        $this->assertSame(CtdtTrangThaiGui::CON_LOI, CtdtTrangThaiGui::cua($hoSo->fresh()));
    }

    /** @test */
    public function nap_lai_ban_da_sua_thi_loi_cu_bien_mat()
    {
        // Nap lai xoa sach ctdt_loi va reset so_loi (CtdtLuuHoSo::xoaHoSoCu). Neu bo kiem
        // khong chay lai sau moi lan nap thi ho so hong vua nap lai se hien "0 loi" -
        // trong y het da duoc sua.
        $this->napVaKiem($this->goiCt2025([[
            $this->chungTu('CT03', ['MA_YTE' => 'YT001', 'MA_BHXH' => '0123456789']),
        ]]));

        $this->assertGreaterThan(0, (int) CtdtHoSo::first()->so_loi);

        $xmlDaSua = $this->goiCt2025([[
            $this->chungTu('CT03', [
                'MA_YTE' => 'YT001', 'MA_BHXH' => '0123456789', 'MA_KHOA' => 'K01',
                'HO_TEN' => 'Nguyen Van Test', 'NGAY_SINH' => '19950914', 'GIOI_TINH' => '1',
                'DIA_CHI' => 'Ha Noi', 'NGAY_VAO' => '201912121200',
                'NGAY_RA' => '201912180001', 'MA_THE' => 'DN1',
            ]),
        ]]);

        // Nap lai KHONG chay job - mo phong luc worker chet. CtdtImporter::nhapTuChuoi() tu
        // dispatch CheckCtdtJob, va phpunit.xml ep QUEUE_DRIVER=sync nen dispatch binh
        // thuong se chay job NGAY tai cho - khong that su mo phong duoc worker chet. Doi
        // sang driver 'null' (Laravel co san, khong can khai o config/queue.php) de job bi
        // nem di khong bao gio chay, roi tra driver ve nhu cu.
        //
        // Viec nap tu no phai don sach ctdt_loi; neu no dua vao job thi so_loi ve 0 trong
        // khi cac ban ghi loi cu con nguyen, va cot "So loi" se noi ho so sach trong khi
        // tab Loi van liet ke loi cu.
        $driverCu = config('queue.default');
        config(['queue.default' => 'null']);
        $this->importer->nhapTuChuoi($xmlDaSua, ['macskcb' => '01929']);
        config(['queue.default' => $driverCu]);

        $this->assertSame(0, CtdtLoi::count(), 'Nap lai phai tu xoa loi cu, khong duoc dua vao job');

        $this->napVaKiem($xmlDaSua);

        $this->assertSame(0, (int) CtdtHoSo::first()->so_loi);
        $this->assertSame(0, CtdtLoi::count());
    }

    /** @test */
    public function moi_ban_ghi_loi_deu_co_ho_so_id()
    {
        // Bat bien nay giu cho nap lai don sach duoc: CtdtLuuHoSo::xoaHoSoCu() xoa ctdt_loi
        // theo ho_so_id. Mot ban ghi loi khong co ho_so_id se song sot mai mai.
        $this->napVaKiem($this->goiCt2025([[
            $this->chungTu('CT03', ['MA_YTE' => 'YT001', 'MA_BHXH' => '0123456789']),
        ]]));

        $this->assertGreaterThan(0, CtdtLoi::count());
        $this->assertSame(0, CtdtLoi::whereNull('ho_so_id')->count());
    }

    /** @test */
    public function ba_dich_vu_deu_kiem_duoc()
    {
        // Bat bien: ca ba dich vu (CT2025, GBT, GCS) deu phai co ho so duoc kiem - khong
        // dich vu nao duoc bo qua bo kiem chi vi loai ho so cua no khac CT2025.
        $this->napVaKiem($this->goiCt2025([[$this->chungTu('CT03', ['MA_YTE' => 'YT001', 'MA_BHXH' => '0123456789'])]]));
        $this->napVaKiem($this->goiGbt(['MA_GBT' => 'GBT-1']));
        $this->napVaKiem($this->goiGcs(['MA_GCS' => 'GCS-1']));

        $this->assertSame(3, CtdtHoSo::count());

        foreach (CtdtHoSo::all() as $hoSo) {
            $this->assertNotNull($hoSo->checked_at, $hoSo->ma_ho_so . ' chua duoc kiem');
        }
    }

    /** @test */
    public function bay_loai_TT25_deu_chay_qua_bo_kiem_khong_nem()
    {
        // Mot loai lam bo kiem nem se lam ca job do, va moi ho so chua loai do khong bao
        // gio co ket qua kiem.
        foreach (\App\Services\Ctdt\CtdtLoaiRegistry::cuaDichVu('CT2025') as $loai => $lop) {
            $truong = $lop::truong();
            $bo = [];

            foreach (['MA_YTE', 'HO_TEN', 'HOTEN_NND'] as $the) {
                if (array_key_exists($the, $truong)) {
                    $bo[$the] = 'GT-' . $the;
                }
            }

            $kq = $this->napVaKiem($this->goiCt2025([[$this->chungTu($loai, $bo)]]));

            $this->assertTrue($kq->thanhCong, $loai . ': ' . (string) $kq->lyDoThatBai);

            foreach (CtdtHoSo::all() as $hoSo) {
                $this->assertNotNull($hoSo->checked_at, $loai . ': chua duoc kiem');
            }

            // PHP 7.4 khong cho $lop::model()::query() - phai qua bien trung gian.
            $tenModel = $lop::model();

            CtdtLoi::query()->delete();
            \App\Models\BHYT\Ctdt\CtdtChungTu::query()->delete();
            CtdtHoSo::query()->delete();
            $tenModel::query()->delete();
        }
    }
}

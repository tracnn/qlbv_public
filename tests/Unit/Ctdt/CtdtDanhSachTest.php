<?php

namespace Tests\Unit\Ctdt;

use Tests\TestCase;
use Tests\Support\DungBangCtdtSqlite;
use App\Services\Ctdt\CtdtDanhSach;
use App\Services\Ctdt\CtdtTrangThaiGui;
use App\Models\BHYT\Ctdt\CtdtHoSo;
use App\Models\BHYT\Ctdt\CtdtChungTu;

class CtdtDanhSachTest extends TestCase
{
    use DungBangCtdtSqlite;

    protected function setUp()
    {
        parent::setUp();
        $this->chuanBiBangCtdt();
        config(['organization.chung_tu_dien_tu.submit_enabled' => true]);
    }

    private function taoHoSo(array $hoSo, array $chungTu = [])
    {
        $ban = CtdtHoSo::create(array_merge([
            'ma_ho_so'    => 'YT001',
            'dich_vu'     => 'CT2025',
            'loai_hs'     => '39',
            'macskcb'     => '01929',
            'imported_at' => '2026-08-19 08:00:00',
            'so_chung_tu' => 1,
        ], $hoSo));

        CtdtChungTu::create(array_merge([
            'ho_so_id'     => $ban->id,
            'loai_ho_so'   => 'CT03',
            'ma_chung_tu'  => $ban->ma_ho_so,
            'ma_the'       => 'DN123',
            'ho_ten'       => 'Nguyen Van Test',
            'noi_dung_goc' => '<CT03/>',
        ], $chungTu));

        return $ban;
    }

    /** @test */
    public function khong_loc_thi_tra_tat_ca()
    {
        $this->taoHoSo(['ma_ho_so' => 'YT001']);
        $this->taoHoSo(['ma_ho_so' => 'YT002']);

        $this->assertSame(2, CtdtDanhSach::truyVan([])->count());
    }

    /** @test */
    public function loc_theo_khoang_ngay_nap()
    {
        $this->taoHoSo(['ma_ho_so' => 'YT001', 'imported_at' => '2026-08-01 10:00:00']);
        $this->taoHoSo(['ma_ho_so' => 'YT002', 'imported_at' => '2026-08-19 10:00:00']);

        $kq = CtdtDanhSach::truyVan(['tu_ngay' => '2026-08-15', 'den_ngay' => '2026-08-20'])->get();

        $this->assertCount(1, $kq);
        $this->assertSame('YT002', $kq->first()->ma_ho_so);
    }

    /** @test */
    public function den_ngay_bao_gom_ca_ngay_do()
    {
        // Loc <= '2026-08-19' tren cot datetime se BO HET ho so nap trong chinh ngay do tru dung
        // 00:00:00. Nguoi dung chon "den 19/8" thi mong doi thay ho so nap luc 15h ngay 19.
        $this->taoHoSo(['ma_ho_so' => 'YT001', 'imported_at' => '2026-08-19 15:30:00']);

        $kq = CtdtDanhSach::truyVan(['tu_ngay' => '2026-08-19', 'den_ngay' => '2026-08-19'])->get();

        $this->assertCount(1, $kq);
    }

    /** @test */
    public function loc_theo_dich_vu()
    {
        $this->taoHoSo(['ma_ho_so' => 'YT001', 'dich_vu' => 'CT2025']);
        $this->taoHoSo(['ma_ho_so' => 'GBT-1', 'dich_vu' => 'GBT']);

        $kq = CtdtDanhSach::truyVan(['dich_vu' => 'GBT'])->get();

        $this->assertCount(1, $kq);
        $this->assertSame('GBT-1', $kq->first()->ma_ho_so);
    }

    /** @test */
    public function loc_theo_ma_co_so()
    {
        $this->taoHoSo(['ma_ho_so' => 'YT001', 'macskcb' => '01929']);
        $this->taoHoSo(['ma_ho_so' => 'YT002', 'macskcb' => '01013']);

        $kq = CtdtDanhSach::truyVan(['macskcb' => '01013'])->get();

        $this->assertCount(1, $kq);
        $this->assertSame('YT002', $kq->first()->ma_ho_so);
    }

    /** @test */
    public function loc_theo_loai_chung_tu_di_qua_bang_chung_tu()
    {
        $this->taoHoSo(['ma_ho_so' => 'YT001'], ['loai_ho_so' => 'CT03']);
        $this->taoHoSo(['ma_ho_so' => 'YT002'], ['loai_ho_so' => 'CT04']);

        $kq = CtdtDanhSach::truyVan(['loai_ho_so' => 'CT04'])->get();

        $this->assertCount(1, $kq);
        $this->assertSame('YT002', $kq->first()->ma_ho_so);
    }

    /** @test */
    public function loc_theo_loai_khong_tra_ho_so_trung_lap()
    {
        // Mot ho so co HAI chung tu CT03 thi join se sinh hai dong. Man danh sach hien mot
        // ho so hai lan la loi de nguoi dung thay nhat.
        $ban = $this->taoHoSo(['ma_ho_so' => 'YT001'], ['loai_ho_so' => 'CT03']);

        CtdtChungTu::create([
            'ho_so_id'     => $ban->id,
            'loai_ho_so'   => 'CT03',
            'ma_chung_tu'  => 'YT001',
            'noi_dung_goc' => '<CT03/>',
        ]);

        $this->assertSame(1, CtdtDanhSach::truyVan(['loai_ho_so' => 'CT03'])->count());
    }

    /** @test */
    public function tim_theo_ma_ho_so()
    {
        $this->taoHoSo(['ma_ho_so' => 'YT001']);
        $this->taoHoSo(['ma_ho_so' => 'YT999']);

        $kq = CtdtDanhSach::truyVan(['tim' => 'YT999'])->get();

        $this->assertCount(1, $kq);
        $this->assertSame('YT999', $kq->first()->ma_ho_so);
    }

    /** @test */
    public function tim_theo_ma_the_va_ho_ten_cua_chung_tu()
    {
        $this->taoHoSo(['ma_ho_so' => 'YT001'], ['ma_the' => 'DN123', 'ho_ten' => 'Nguyen Van A']);
        $this->taoHoSo(['ma_ho_so' => 'YT002'], ['ma_the' => 'GD456', 'ho_ten' => 'Tran Thi B']);

        $this->assertSame('YT002', CtdtDanhSach::truyVan(['tim' => 'GD456'])->first()->ma_ho_so);
        $this->assertSame('YT002', CtdtDanhSach::truyVan(['tim' => 'Tran Thi'])->first()->ma_ho_so);
    }

    /** @test */
    public function chi_con_loi()
    {
        $this->taoHoSo(['ma_ho_so' => 'YT001', 'so_loi' => 0]);
        $this->taoHoSo(['ma_ho_so' => 'YT002', 'so_loi' => 3]);

        $kq = CtdtDanhSach::truyVan(['chi_con_loi' => true])->get();

        $this->assertCount(1, $kq);
        $this->assertSame('YT002', $kq->first()->ma_ho_so);
    }

    /** @test */
    public function loc_theo_trang_thai_chua_ky()
    {
        $this->taoHoSo(['ma_ho_so' => 'YT001', 'so_loi' => 0, 'is_signed' => false]);
        $this->taoHoSo(['ma_ho_so' => 'YT002', 'so_loi' => 0, 'is_signed' => true]);

        $kq = CtdtDanhSach::truyVan(['trang_thai_gui' => CtdtTrangThaiGui::CHUA_KY])->get();

        $this->assertCount(1, $kq);
        $this->assertSame('YT001', $kq->first()->ma_ho_so);
    }

    /** @test */
    public function loc_theo_trang_thai_cong_tu_choi()
    {
        $this->taoHoSo([
            'ma_ho_so' => 'YT001', 'so_loi' => 0, 'is_signed' => true,
            'ma_gd' => 'HS_1', 'ma_ket_qua' => '200',
        ]);
        $this->taoHoSo([
            'ma_ho_so' => 'YT002', 'so_loi' => 0, 'is_signed' => true,
            'ma_gd' => 'HS_2', 'ma_ket_qua' => '205',
        ]);

        $kq = CtdtDanhSach::truyVan(['trang_thai_gui' => CtdtTrangThaiGui::CONG_TU_CHOI])->get();

        $this->assertCount(1, $kq);
        $this->assertSame('YT002', $kq->first()->ma_ho_so);
    }

    /** @test */
    public function loc_rong_va_null_bi_bo_qua_khong_lam_mat_ket_qua()
    {
        // Form gui len chuoi rong cho moi o khong chon. Coi chuoi rong la mot gia tri loc
        // se lam man hinh trong tron ma khong ai hieu tai sao.
        $this->taoHoSo(['ma_ho_so' => 'YT001']);

        $loc = [
            'tu_ngay' => '', 'den_ngay' => null, 'dich_vu' => '', 'loai_ho_so' => '',
            'macskcb' => '', 'tim' => '   ', 'chi_con_loi' => false, 'trang_thai_gui' => '',
        ];

        $this->assertSame(1, CtdtDanhSach::truyVan($loc)->count());
    }

    /** @test */
    public function cac_bo_loc_trang_thai_loai_tru_nhau()
    {
        // Ho so A vua con loi vua chua ky: theo thu tu uu tien cua CtdtTrangThaiGui::cua(),
        // trang thai cua no la CON_LOI, khong phai CHUA_KY. Neu bo loc CHUA_KY khong loai
        // tru ho so con loi, ho so A se hien o CA HAI bo loc va tong cac bo loc se lon hon
        // tong so ho so - nguoi dung se khong tin man hinh nua.
        $hoSoConLoi = $this->taoHoSo(['ma_ho_so' => 'YT_CON_LOI', 'so_loi' => 5, 'is_signed' => false]);
        $hoSoChuaKy = $this->taoHoSo(['ma_ho_so' => 'YT_CHUA_KY', 'so_loi' => 0, 'is_signed' => false]);

        $kqChuaKy = CtdtDanhSach::truyVan(['trang_thai_gui' => CtdtTrangThaiGui::CHUA_KY])->get();
        $this->assertCount(
            1,
            $kqChuaKy,
            'Bo loc CHUA_KY phai loai tru ho so con loi, neu khong tong cac bo loc se lon hon tong so ho so'
        );
        $this->assertSame(
            'YT_CHUA_KY',
            $kqChuaKy->first()->ma_ho_so,
            'Ho so con loi (YT_CON_LOI) khong duoc xuat hien o bo loc CHUA_KY'
        );

        $kqConLoi = CtdtDanhSach::truyVan(['trang_thai_gui' => CtdtTrangThaiGui::CON_LOI])->get();
        $this->assertCount(1, $kqConLoi);
        $this->assertSame('YT_CON_LOI', $kqConLoi->first()->ma_ho_so);
    }

    /** @test */
    public function ho_so_chua_ky_khong_xuat_hien_o_bo_loc_da_gui_hay_cong_tu_choi()
    {
        // Tang thu hai cua tinh loai tru: ho so chua ky (so_loi = 0, is_signed = false) chi
        // duoc thuoc bo loc CHUA_KY, khong duoc lot qua cac bo loc "da ky" phia sau
        // (DA_GUI, CONG_TU_CHOI) vi cung ly do tong cac bo loc phai bang tong so ho so.
        $this->taoHoSo(['ma_ho_so' => 'YT_CHUA_KY', 'so_loi' => 0, 'is_signed' => false]);

        $kqDaGui = CtdtDanhSach::truyVan(['trang_thai_gui' => CtdtTrangThaiGui::DA_GUI])->get();
        $this->assertCount(
            0,
            $kqDaGui,
            'Ho so chua ky khong duoc xuat hien o bo loc DA_GUI'
        );

        $kqCongTuChoi = CtdtDanhSach::truyVan(['trang_thai_gui' => CtdtTrangThaiGui::CONG_TU_CHOI])->get();
        $this->assertCount(
            0,
            $kqCongTuChoi,
            'Ho so chua ky khong duoc xuat hien o bo loc CONG_TU_CHOI'
        );
    }
}

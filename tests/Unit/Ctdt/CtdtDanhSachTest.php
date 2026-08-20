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

    /**
     * Mac dinh la ho so DA di qua bo kiem (checked_at co gia tri). Test nao muon kich ban
     * "chua kiem" phai noi ro 'checked_at' => null: ho so chua kiem cung co so_loi = 0
     * nhung KHONG phai la ho so sach, va tron hai thu lam test noi sai su that.
     */
    private function taoHoSo(array $hoSo, array $chungTu = [])
    {
        $ban = CtdtHoSo::create(array_merge([
            'checked_at'  => '2026-08-20 08:00:00',
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
    public function loc_theo_trang_thai_chua_kiem()
    {
        // Hai ho so giong het nhau ve so_loi (deu 0) - chi khac checked_at. Neu bo loc
        // khong nhin checked_at thi ca hai deu ra "sach", va nguoi van hanh khong co cach
        // nao thay duoc worker JobCtdt da chet.
        $this->taoHoSo(['ma_ho_so' => 'YT_CHUA_KIEM', 'checked_at' => null, 'so_loi' => 0]);
        $this->taoHoSo(['ma_ho_so' => 'YT_DA_KIEM', 'so_loi' => 0]);

        $kq = CtdtDanhSach::truyVan(['trang_thai_gui' => CtdtTrangThaiGui::CHUA_KIEM])->get();

        $this->assertCount(1, $kq);
        $this->assertSame('YT_CHUA_KIEM', $kq->first()->ma_ho_so);
    }

    /** @test */
    public function ho_so_chua_kiem_khong_lot_vao_bo_loc_chua_ky()
    {
        // Ho so chua kiem cung co is_signed = false, nen neu bo loc CHUA_KY khong loai tru
        // "chua kiem" thi no se hien o day va nguoi dung se di ky mot ho so chua kiem.
        $this->taoHoSo(['ma_ho_so' => 'YT_CHUA_KIEM', 'checked_at' => null, 'so_loi' => 0, 'is_signed' => false]);

        $this->assertCount(
            0,
            CtdtDanhSach::truyVan(['trang_thai_gui' => CtdtTrangThaiGui::CHUA_KY])->get(),
            'Ho so chua kiem khong duoc xuat hien o bo loc CHUA_KY'
        );
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

    /**
     * @test
     *
     * Test TINH CHAT: bo loc SQL trong CtdtDanhSach va ham suy trang thai
     * CtdtTrangThaiGui::cua() la HAI CACH DIEN DAT cung mot quy tac. Kiem tung ca
     * roi rac (nhu hai test truoc) thi cu va cho nay lai ho cho khac - vong fix
     * round 1 da chung minh dieu do (bo qua ca so_loi=0,is_signed=false,ma_ket_qua=200).
     * O day, voi MOI ho so trong bo du lieu, tap ma_ho_so ma bo loc SQL tra ve cho tung
     * trang thai phai KHOP TUYET DOI voi tap ma_ho_so ma cua() gan cho trang thai do.
     */
    public function bo_loc_trang_thai_khop_voi_CtdtTrangThaiGui_cho_moi_ho_so()
    {
        config(['organization.chung_tu_dien_tu.submit_enabled' => true]);

        $this->taoHoSo(['ma_ho_so' => 'A_CON_LOI_CHUA_KY', 'so_loi' => 5, 'is_signed' => false]);
        $this->taoHoSo(['ma_ho_so' => 'B_CON_LOI_DA_KY_200', 'so_loi' => 5, 'is_signed' => true, 'ma_ket_qua' => '200']);
        $this->taoHoSo(['ma_ho_so' => 'C_CHUA_KY', 'so_loi' => 0, 'is_signed' => false]);
        $this->taoHoSo(['ma_ho_so' => 'D_CHUA_KY_CO_KET_QUA_200', 'so_loi' => 0, 'is_signed' => false, 'ma_ket_qua' => '200']);
        $this->taoHoSo(['ma_ho_so' => 'E_DA_KY_CHUA_CO_KET_QUA', 'so_loi' => 0, 'is_signed' => true]);
        $this->taoHoSo(['ma_ho_so' => 'F_DA_GUI', 'so_loi' => 0, 'is_signed' => true, 'ma_ket_qua' => '200']);
        $this->taoHoSo(['ma_ho_so' => 'G_CONG_TU_CHOI', 'so_loi' => 0, 'is_signed' => true, 'ma_ket_qua' => '205']);

        // I2: Giai doan 4 luu phan hoi cong co the co maKetQua RONG (chuoi rong hoac '0').
        // !empty($hoSo->ma_ket_qua) cua CtdtTrangThaiGui::cua() coi CA HAI la "chua co ket
        // qua" -> ho so nay phai la CHUA_GUI, khong phai CONG_TU_CHOI. Truoc khi sua, bo
        // loc SQL cua CONG_TU_CHOI khop ca chuoi rong/'0', nen hai ho so nay se lot vao bo
        // loc CONG_TU_CHOI trong khi cua() gan chung cho CHUA_GUI - vong lap kiem tra ben
        // duoi se bat duoc lech nay o nhanh CONG_TU_CHOI (mongDoi khong co, thuc te co).
        $this->taoHoSo(['ma_ho_so' => 'H_KET_QUA_RONG', 'so_loi' => 0, 'is_signed' => true, 'ma_ket_qua' => '']);
        $this->taoHoSo(['ma_ho_so' => 'I_KET_QUA_KHONG', 'so_loi' => 0, 'is_signed' => true, 'ma_ket_qua' => '0']);

        // Ba ho so CHUA KIEM, moi cai nguy trang thanh mot trang thai "sach" khac nhau:
        // neu bo loc khong loai tru chung, J se hien o CHUA_KY, K o CHUA_GUI va L o DA_GUI
        // - tuc man hinh bao "khong loi" cho ba ho so ma chua ai kiem bao gio.
        $this->taoHoSo(['ma_ho_so' => 'J_CHUA_KIEM_CHUA_KY', 'checked_at' => null, 'so_loi' => 0, 'is_signed' => false]);
        $this->taoHoSo(['ma_ho_so' => 'K_CHUA_KIEM_DA_KY', 'checked_at' => null, 'so_loi' => 0, 'is_signed' => true]);
        $this->taoHoSo(['ma_ho_so' => 'L_CHUA_KIEM_CO_KET_QUA', 'checked_at' => null, 'so_loi' => 0, 'is_signed' => true, 'ma_ket_qua' => '200']);

        $tatCaHoSo = CtdtHoSo::all();

        $cacTrangThaiCanKiem = [
            CtdtTrangThaiGui::CHUA_KIEM,
            CtdtTrangThaiGui::CON_LOI,
            CtdtTrangThaiGui::CHUA_KY,
            CtdtTrangThaiGui::DA_GUI,
            CtdtTrangThaiGui::CONG_TU_CHOI,
        ];

        foreach ($cacTrangThaiCanKiem as $trangThai) {
            $mongDoi = $tatCaHoSo
                ->filter(function ($hoSo) use ($trangThai) {
                    return CtdtTrangThaiGui::cua($hoSo) === $trangThai;
                })
                ->pluck('ma_ho_so')
                ->sort()
                ->values()
                ->all();

            $thucTe = CtdtDanhSach::truyVan(['trang_thai_gui' => $trangThai])
                ->pluck('ma_ho_so')
                ->sort()
                ->values()
                ->all();

            $this->assertSame(
                $mongDoi,
                $thucTe,
                "Bo loc SQL cho trang thai '{$trangThai}' phai tra ve dung tap ma_ho_so ma "
                . "CtdtTrangThaiGui::cua() gan cho trang thai do. Mong doi: "
                . implode(',', $mongDoi) . ' - Thuc te: ' . implode(',', $thucTe)
            );
        }

        // TONG cac bo loc phai bang TONG so ho so: khong trung (mot ho so hien o hai bo
        // loc) va khong sot (mot ho so khong thuoc bo loc nao, bien mat khi nguoi dung loc).
        //
        // GUI_TAT KHONG co trong danh sach nay mot cach co chu dich: no va CHUA_GUI dung
        // CHUNG mot dieu kien SQL (phan biet nhau bang CAU HINH, khong bang du lieu), nen
        // cong ca hai vao se dem hai lan cung mot tap.
        $cacBoLocRoiNhau = [
            CtdtTrangThaiGui::CHUA_KIEM,
            CtdtTrangThaiGui::CON_LOI,
            CtdtTrangThaiGui::CHUA_KY,
            CtdtTrangThaiGui::DA_GUI,
            CtdtTrangThaiGui::CONG_TU_CHOI,
            CtdtTrangThaiGui::CHUA_GUI,
        ];

        $daGap = [];

        foreach ($cacBoLocRoiNhau as $trangThai) {
            foreach (CtdtDanhSach::truyVan(['trang_thai_gui' => $trangThai])->pluck('ma_ho_so') as $ma) {
                $this->assertArrayNotHasKey(
                    $ma,
                    $daGap,
                    "Ho so {$ma} hien o hai bo loc trang thai (lan hai: {$trangThai}) - "
                    . 'tong cac bo loc se lon hon tong so ho so'
                );
                $daGap[$ma] = $trangThai;
            }
        }

        $thieu = array_diff($tatCaHoSo->pluck('ma_ho_so')->all(), array_keys($daGap));

        $this->assertEmpty(
            $thieu,
            'Ho so khong thuoc bo loc trang thai nao: ' . implode(',', $thieu)
        );
        $this->assertCount(
            $tatCaHoSo->count(),
            $daGap,
            'Tong so ho so cua tat ca cac bo loc trang thai phai bang tong so ho so'
        );
    }
    /** @test */
    public function loc_theo_nguoi_nap()
    {
        $this->taoHoSo(['ma_ho_so' => 'YT001', 'imported_by' => 'nguoia']);
        $this->taoHoSo(['ma_ho_so' => 'YT002', 'imported_by' => 'nguoib']);

        $kq = CtdtDanhSach::truyVan(['imported_by' => 'nguoib'])->get();

        $this->assertCount(1, $kq);
        $this->assertSame('YT002', $kq->first()->ma_ho_so);
    }

    /** @test */
    public function khoang_ngay_dang_co_gio_duoc_dung_nguyen_van()
    {
        // Bo chon khoang thoi gian (partials.date_range) gui len 'YYYY-MM-DD HH:mm:ss'.
        // Cu noi ' 00:00:00' vao thi thanh '2026-08-19 08:00:00 00:00:00' - MySQL doc
        // khong ra, tra ve rong, va man hinh trong tron ma khong bao gi ca.
        $this->taoHoSo(['ma_ho_so' => 'SOM', 'imported_at' => '2026-08-19 07:00:00']);
        $this->taoHoSo(['ma_ho_so' => 'TRONG', 'imported_at' => '2026-08-19 10:00:00']);
        $this->taoHoSo(['ma_ho_so' => 'MUON', 'imported_at' => '2026-08-19 20:00:00']);

        $kq = CtdtDanhSach::truyVan([
            'tu_ngay'  => '2026-08-19 08:00:00',
            'den_ngay' => '2026-08-19 12:00:00',
        ])->get();

        $this->assertCount(1, $kq, 'Chi ho so nap trong khung gio duoc chon');
        $this->assertSame('TRONG', $kq->first()->ma_ho_so);
    }

    /** @test */
    public function khoang_ngay_dang_ngay_tran_van_bao_gom_ca_ngay()
    {
        // Hai dang phai cung song: o ngay tran (khong co gio) van phai lay tron ngay.
        $this->taoHoSo(['ma_ho_so' => 'YT001', 'imported_at' => '2026-08-19 23:30:00']);

        $kq = CtdtDanhSach::truyVan(['tu_ngay' => '2026-08-19', 'den_ngay' => '2026-08-19'])->get();

        $this->assertCount(1, $kq);
    }
}

<?php

namespace Tests\Unit\Ctdt;

use Tests\TestCase;
use Tests\Support\DungBangCtdtSqlite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Queue;
use App\Http\Controllers\BHYT\BHYTCtdtController;
use App\Jobs\SignCtdtJob;
use App\Services\Ctdt\CtdtDuDieuKienGui;
use App\Services\Ctdt\CtdtXepHangKyGui;
use App\Models\BHYT\Ctdt\CtdtHoSo;
use App\Models\BHYT\Ctdt\CtdtChungTu;

/**
 * Nut "Ky va gui da chon" - gui HANG LOAT.
 *
 * DAY LA CHO NGUY HIEM NHAT CUA MODULE. Chung tu PL02 khong mang ma giao dich phia nguoi
 * gui, nen cong BHXH KHONG the nhan ra ban trung: mot ho so bi gui hai lan thanh hai chung
 * tu tren cong, va khong co duong rut lai. Moi test o day canh mot duong co the dan tới do.
 */
class CtdtGuiNhieuTest extends TestCase
{
    use DungBangCtdtSqlite;

    /** @var BHYTCtdtController */
    private $controller;

    protected function setUp()
    {
        parent::setUp();
        $this->chuanBiBangCtdt();
        Queue::fake();
        $this->controller = new BHYTCtdtController();

        config([
            'organization.chung_tu_dien_tu.sign_enabled'      => true,
            'organization.chung_tu_dien_tu.submit_enabled'     => true,
            'organization.chung_tu_dien_tu.sign_queue_name'    => 'JobSignCtdt',
            'organization.chung_tu_dien_tu.submit_queue_name'  => 'JobSubmitCtdt',
        ]);
    }

    /**
     * Ho so SACH, du dieu kien gui. Cac test doi mot thuoc tinh de tao tung nhanh bi chan.
     */
    private function hoSo($maHoSo = 'YT001', array $ghiDe = [])
    {
        $hoSo = CtdtHoSo::create(array_merge([
            'ma_ho_so' => $maHoSo, 'dich_vu' => 'CT2025', 'loai_hs' => '39',
            'macskcb' => '01929', 'so_chung_tu' => 1,
            'checked_at' => '2026-08-20 08:00:00', 'so_loi' => 0, 'is_signed' => true,
        ], $ghiDe));

        CtdtChungTu::create([
            'ho_so_id' => $hoSo->id, 'loai_ho_so' => 'CT03', 'ma_chung_tu' => $maHoSo,
            'noi_dung_goc' => '<CT03/>',
        ]);

        return $hoSo->fresh();
    }

    private function goi(array $maHoSo)
    {
        $request = Request::create('/bhyt/ctdt/ky-va-gui-nhieu', 'POST', ['ma_ho_so' => $maHoSo]);

        return json_decode($this->controller->kyVaGuiNhieu($request)->getContent(), true);
    }

    // ------------------------------------------------------------------ cua chan tung ho so

    /** @test */
    public function ho_so_sach_thi_duoc_xep_hang()
    {
        $this->hoSo('YT001');

        $kq = $this->goi(['YT001']);

        $this->assertTrue($kq['thanh_cong']);
        $this->assertSame(1, $kq['so_da_xep']);
        $this->assertSame(['YT001'], $kq['da_xep']);
        Queue::assertPushed(SignCtdtJob::class);
    }

    /** @test */
    public function ho_so_DA_CO_MA_GD_bi_bo_qua_du_duong_don_le_cho_gui_lai()
    {
        // KHAC BIET QUAN TRONG NHAT giua hai duong. Dieu kien hoi xac nhan cua duong don le
        // la "co lich_su_gui VA ma_gd rong", nen mot ho so CON GIU ma_gd khong bi hoi gi ca -
        // nut chi doi nhan thanh "Ky va gui lai" va nguoi bam dang nhin man chi tiet cua
        // dung ho so do. Trong mot lo 50 dong thi khong ai nhin, va no thanh gui lai im lang.
        $this->hoSo('YT001', ['ma_gd' => 'HS_CHUNGTU01929_ABC', 'ma_ket_qua' => '200']);

        $kq = $this->goi(['YT001']);

        $this->assertFalse($kq['thanh_cong']);
        $this->assertSame(0, $kq['so_da_xep']);
        $this->assertSame('YT001', $kq['bo_qua'][0]['ma_ho_so']);
        $this->assertContains('đã tiếp nhận', $kq['bo_qua'][0]['ly_do']);
        Queue::assertNotPushed(SignCtdtJob::class);
    }

    /** @test */
    public function ho_so_CAN_XAC_NHAN_bi_bo_qua_khong_co_duong_xac_nhan_ca_lo()
    {
        // Nap lai xoa ma_gd nhung giu lich_su_gui: cong CO THE da nhan ho so nay. Duong don
        // le hoi xac nhan tung ho so; duong hang loat khong duoc phep hoi mot lan cho ca lo,
        // vi nguoi bam chua doc lich su gui cua tung cai.
        $this->hoSo('YT001', ['lich_su_gui' => '2026-08-20 09:00 gui, ma_gd HS_ABC']);

        $kq = $this->goi(['YT001']);

        $this->assertSame(0, $kq['so_da_xep']);
        $this->assertContains('đã từng được gửi', $kq['bo_qua'][0]['ly_do']);
        Queue::assertNotPushed(SignCtdtJob::class);
    }

    /** @test */
    public function ho_so_CHUA_KIEM_bi_bo_qua()
    {
        // so_loi = 0 cua mot ho so CHUA KIEM khong co nghia la sach, no co nghia la chua ai
        // nhin. Bo qua nhanh nay la coi moi ho so vua nap la sach.
        $this->hoSo('YT001', ['checked_at' => null]);

        $kq = $this->goi(['YT001']);

        $this->assertSame(0, $kq['so_da_xep']);
        $this->assertContains('chưa kiểm', $kq['bo_qua'][0]['ly_do']);
        Queue::assertNotPushed(SignCtdtJob::class);
    }

    /** @test */
    public function ho_so_CON_LOI_CHAN_bi_bo_qua()
    {
        $this->hoSo('YT001', ['so_loi' => 3]);

        $kq = $this->goi(['YT001']);

        $this->assertSame(0, $kq['so_da_xep']);
        $this->assertContains('còn lỗi chặn', $kq['bo_qua'][0]['ly_do']);
        Queue::assertNotPushed(SignCtdtJob::class);
    }

    /** @test */
    public function chuc_nang_gui_TAT_thi_khong_ho_so_nao_duoc_xep()
    {
        config(['organization.chung_tu_dien_tu.submit_enabled' => false]);
        $this->hoSo('YT001');

        $kq = $this->goi(['YT001']);

        $this->assertSame(0, $kq['so_da_xep']);
        $this->assertContains('đang tắt', $kq['bo_qua'][0]['ly_do']);
        Queue::assertNotPushed(SignCtdtJob::class);
    }

    /** @test */
    public function chuc_nang_KY_tat_va_ho_so_chua_ky_thi_bi_bo_qua()
    {
        config(['organization.chung_tu_dien_tu.sign_enabled' => false]);
        $this->hoSo('YT001', ['is_signed' => false]);

        $kq = $this->goi(['YT001']);

        $this->assertSame(0, $kq['so_da_xep']);
        Queue::assertNotPushed(SignCtdtJob::class);
    }

    /** @test */
    public function ma_ho_so_khong_ton_tai_bi_bo_qua_chu_khong_lam_ca_lo_that_bai()
    {
        // Mot lo 50 ho so ma dung lai o ho so thu ba la buoc nguoi dung bam lai 47 lan.
        $this->hoSo('YT001');

        $kq = $this->goi(['YT001', 'KHONG-CO-THAT']);

        $this->assertTrue($kq['thanh_cong'], 'Mot ma sai khong duoc lam ca lo that bai');
        $this->assertSame(['YT001'], $kq['da_xep']);
        $this->assertSame('KHONG-CO-THAT', $kq['bo_qua'][0]['ma_ho_so']);
    }

    // ------------------------------------------------------------------ chong gui trung

    /** @test */
    public function ma_ho_so_trung_trong_cung_mot_lo_chi_xep_MOT_lan()
    {
        // Tich cung mot ho so hai lan (hai trang, hai lan bam) khong duoc thanh hai lan xep
        // hang - tuc hai lan POST that len cong.
        $this->hoSo('YT001');

        $kq = $this->goi(['YT001', 'YT001', 'YT001']);

        $this->assertSame(1, $kq['so_da_xep']);
        $this->assertSame(0, $kq['so_bo_qua'], 'Ban trung phai bi loai TRUOC vong lap, khong phai bao "dang xu ly"');
        Queue::assertPushed(SignCtdtJob::class, 1);
    }

    /** @test */
    public function ho_so_dang_co_khoa_bi_bo_qua()
    {
        // Lenh nen ctdt:import co the vua xep hang chinh ho so nay vai giay truoc.
        $this->hoSo('YT001');
        CtdtXepHangKyGui::giuKhoa('YT001');

        $kq = $this->goi(['YT001']);

        $this->assertSame(0, $kq['so_da_xep']);
        $this->assertContains('đang xử lý', $kq['bo_qua'][0]['ly_do']);
        Queue::assertNotPushed(SignCtdtJob::class);
    }

    // ------------------------------------------------------------------ tran va dau vao

    /** @test */
    public function tran_ghim_o_50()
    {
        // GHIM CON SO chu khong chi kiem quan he. Hai test tran ben duoi tu dung danh sach
        // TU CHINH hang so, nen chung troi theo no: noi tran len 500 thi ca hai VAN XANH -
        // da thu bang cach doi hang so that. Tran nay la mot quyet dinh an toan (mot lo hong
        // thi thiet hai gioi han o 50 ho so, va hang doi ky khong bi don qua lau), nen no
        // phai doi bang mot lan sua test co y thuc.
        $this->assertSame(50, BHYTCtdtController::TRAN_GUI_NHIEU);
    }

    /** @test */
    public function vuot_tran_thi_KHONG_xep_ho_so_nao()
    {
        // Tran kiem o SERVER: gioi han phia trinh duyet chi la tien nghi, ai goi thang
        // endpoint se lot qua het. Va vuot tran phai chan CA LO chu khong xep 50 cai dau roi
        // bo phan con lai - nguoi dung se tuong da gui het.
        $ds = [];

        for ($i = 1; $i <= BHYTCtdtController::TRAN_GUI_NHIEU + 1; $i++) {
            $ds[] = 'YT' . str_pad($i, 4, '0', STR_PAD_LEFT);
        }

        $request = Request::create('/bhyt/ctdt/ky-va-gui-nhieu', 'POST', ['ma_ho_so' => $ds]);
        $phanHoi = $this->controller->kyVaGuiNhieu($request);

        $this->assertSame(422, $phanHoi->getStatusCode());
        Queue::assertNotPushed(SignCtdtJob::class);
    }

    /** @test */
    public function dung_tran_thi_van_chay()
    {
        // Chan o dung 51 chu khong phai o 50: lech mot don vi lam nguoi dung chon dung tran
        // ma bi tu choi.
        $ds = [];

        for ($i = 1; $i <= BHYTCtdtController::TRAN_GUI_NHIEU; $i++) {
            $ds[] = 'YT' . str_pad($i, 4, '0', STR_PAD_LEFT);
        }

        $request = Request::create('/bhyt/ctdt/ky-va-gui-nhieu', 'POST', ['ma_ho_so' => $ds]);

        $this->assertSame(200, $this->controller->kyVaGuiNhieu($request)->getStatusCode());
    }

    /** @test */
    public function danh_sach_rong_bi_tu_choi()
    {
        $request = Request::create('/bhyt/ctdt/ky-va-gui-nhieu', 'POST', ['ma_ho_so' => []]);

        $this->assertSame(400, $this->controller->kyVaGuiNhieu($request)->getStatusCode());
        Queue::assertNotPushed(SignCtdtJob::class);
    }

    /** @test */
    public function chuoi_rong_va_khoang_trang_bi_loai_chu_khong_thanh_ma_ho_so()
    {
        $this->hoSo('YT001');

        $kq = $this->goi(['YT001', '', '   ']);

        $this->assertSame(1, $kq['so_da_xep']);
        $this->assertSame(0, $kq['so_bo_qua'], 'Chuoi rong khong duoc thanh mot dong "khong tim thay"');
    }

    // ------------------------------------------------------------------ lo hon hop

    /** @test */
    public function lo_hon_hop_xep_cai_duoc_va_bo_qua_cai_khong()
    {
        $this->hoSo('YT001');                              // sach
        $this->hoSo('YT002', ['so_loi' => 2]);             // con loi
        $this->hoSo('YT003');                              // sach
        $this->hoSo('YT004', ['checked_at' => null]);      // chua kiem

        $kq = $this->goi(['YT001', 'YT002', 'YT003', 'YT004']);

        $this->assertTrue($kq['thanh_cong']);
        $this->assertSame(2, $kq['so_da_xep']);
        $this->assertSame(2, $kq['so_bo_qua']);
        $this->assertSame(['YT001', 'YT003'], $kq['da_xep']);
        Queue::assertPushed(SignCtdtJob::class, 2);
    }

    /** @test */
    public function ca_lo_bi_bo_qua_thi_thanh_cong_la_FALSE()
    {
        // Khong thi man hinh hien dai mau xanh trong khi khong ho so nao duoc gui.
        $this->hoSo('YT001', ['so_loi' => 1]);
        $this->hoSo('YT002', ['so_loi' => 1]);

        $kq = $this->goi(['YT001', 'YT002']);

        $this->assertFalse($kq['thanh_cong']);
        $this->assertSame(0, $kq['so_da_xep']);
        Queue::assertNotPushed(SignCtdtJob::class);
    }

    // ------------------------------------------------------------------ lop cua chan

    /** @test */
    public function cua_chan_tra_dung_ma_cho_tung_tinh_huong()
    {
        // Mot bang duy nhat: doi mot nhanh ma quen nhanh khac se do o day, khong phai o mot
        // test HTTP xa tit.
        $this->assertSame(CtdtDuDieuKienGui::DUOC,
            CtdtDuDieuKienGui::cua($this->hoSo('A1')));

        $this->assertSame(CtdtDuDieuKienGui::CHUA_KIEM,
            CtdtDuDieuKienGui::cua($this->hoSo('A2', ['checked_at' => null])));

        $this->assertSame(CtdtDuDieuKienGui::CON_LOI,
            CtdtDuDieuKienGui::cua($this->hoSo('A3', ['so_loi' => 1])));

        $this->assertSame(CtdtDuDieuKienGui::CAN_XAC_NHAN,
            CtdtDuDieuKienGui::cua($this->hoSo('A4', ['lich_su_gui' => 'da gui'])));

        $this->assertSame(CtdtDuDieuKienGui::DA_CO_MA_GD,
            CtdtDuDieuKienGui::cua($this->hoSo('A5', ['ma_gd' => 'HS_ABC'])));

        config(['organization.chung_tu_dien_tu.sign_enabled' => false]);
        $this->assertSame(CtdtDuDieuKienGui::KY_TAT,
            CtdtDuDieuKienGui::cua($this->hoSo('A6', ['is_signed' => false])));

        config(['organization.chung_tu_dien_tu.submit_enabled' => false]);
        $this->assertSame(CtdtDuDieuKienGui::GUI_TAT,
            CtdtDuDieuKienGui::cua($this->hoSo('A7')));
    }

    /** @test */
    public function moi_ma_cua_chan_deu_co_ly_do_doc_duoc()
    {
        // Thieu mot dong trong bang $ly se lam nguoi dung nhan cau chung "Ho so chua du dieu
        // kien gui" - dung cau vo dung ma viec tach chin trang thai gui da tranh duoc.
        $ma = [
            CtdtDuDieuKienGui::GUI_TAT,
            CtdtDuDieuKienGui::CHUA_KIEM,
            CtdtDuDieuKienGui::CON_LOI,
            CtdtDuDieuKienGui::KY_TAT,
            CtdtDuDieuKienGui::CAN_XAC_NHAN,
            CtdtDuDieuKienGui::DA_CO_MA_GD,
        ];

        foreach ($ma as $m) {
            $this->assertNotSame(
                'Hồ sơ chưa đủ điều kiện gửi.',
                CtdtDuDieuKienGui::lyDo($m),
                $m . ' phai co ly do rieng, khong duoc roi ve cau chung'
            );
        }
    }
}

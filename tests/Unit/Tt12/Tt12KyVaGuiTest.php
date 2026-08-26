<?php

namespace Tests\Unit\Tt12;

use Tests\TestCase;
use Tests\Support\DungBangTt12Sqlite;
use Illuminate\Support\Facades\Queue;
use App\Models\BHYT\Tt12\Tt12HoSo;
use App\Jobs\SignTt12Job;
use App\Jobs\SubmitTt12Job;
use App\Http\Controllers\BHYT\BHYTTt12Controller;
use Illuminate\Http\Request;

class Tt12KyVaGuiTest extends TestCase
{
    use DungBangTt12Sqlite;

    protected function setUp()
    {
        parent::setUp();
        $this->dungBangTt12();
        Queue::fake();

        config([
            'organization.tt12.sign_enabled'   => true,
            'organization.tt12.submit_enabled' => true,
        ]);
    }

    private function tao($ma, array $ghiDe = array())
    {
        return Tt12HoSo::create(array_merge(array(
            'ma_ho_so' => $ma, 'mau' => 'MAU_01', 'loai_hs' => '70',
            'ma_cskcb' => '01929', 'ten_tep' => $ma . '.xlsx', 'so_dong' => 1,
            'id_danh_sach' => 'Id-' . $ma,
            'checked_at' => '2026-08-25 10:00:00', 'so_loi' => 0,
        ), $ghiDe));
    }

    /** @test */
    public function bam_ky_va_gui_mot_ho_so_sach_thi_day_job_ky()
    {
        $this->tao('A');

        $ct = new BHYTTt12Controller();
        $ct->kyVaGui(Request::create('/', 'POST'), 'A');

        Queue::assertPushed(SignTt12Job::class, 1);
    }

    /** @test */
    public function ho_so_con_loi_thi_KHONG_day_job_nao()
    {
        // Chan o TANG NAY chu khong chi trong job: nguoi dung phai duoc bao ngay tren man
        // hinh vi sao khong ky duoc, thay vi bam xong khong thay gi xay ra.
        $this->tao('A', array('so_loi' => 2));

        $ct = new BHYTTt12Controller();
        $ct->kyVaGui(Request::create('/', 'POST'), 'A');

        Queue::assertNotPushed(SignTt12Job::class);
    }

    /** @test */
    public function ho_so_da_ky_roi_thi_day_thang_job_gui()
    {
        $this->tao('A', array('is_signed' => true, 'duong_dan_da_ky' => 'x.xml'));

        $ct = new BHYTTt12Controller();
        $ct->kyVaGui(Request::create('/', 'POST'), 'A');

        Queue::assertNotPushed(SignTt12Job::class);
        Queue::assertPushed(SubmitTt12Job::class, 1);
    }

    /** @test */
    public function ho_so_da_duoc_tiep_nhan_thi_khong_day_job_nao()
    {
        $this->tao('A', array('is_signed' => true, 'ma_ket_qua' => '200', 'ma_gd' => 'GD1'));

        $ct = new BHYTTt12Controller();
        $ct->kyVaGui(Request::create('/', 'POST'), 'A');

        Queue::assertNotPushed(SignTt12Job::class);
        Queue::assertNotPushed(SubmitTt12Job::class);
    }

    /** @test */
    public function ky_va_gui_nhieu_chi_day_job_cho_ho_so_du_dieu_kien()
    {
        $this->tao('A');                                   // sach, chua ky  -> ky
        $this->tao('B', array('so_loi' => 1));             // con loi        -> bo qua
        $this->tao('C', array('is_signed' => true));       // da ky          -> gui
        $this->tao('D', array('is_signed' => true, 'ma_ket_qua' => '200')); // da nhan -> bo qua

        $ct = new BHYTTt12Controller();
        $ct->kyVaGuiNhieu(Request::create('/', 'POST', array(
            'ma_ho_so' => array('A', 'B', 'C', 'D'),
        )));

        Queue::assertPushed(SignTt12Job::class, 1);
        Queue::assertPushed(SubmitTt12Job::class, 1);
    }

    // -- Tran gui nhieu --------------------------------------------------------------

    /** @test */
    public function tran_gui_nhieu_la_50()
    {
        // GHIM CON SO chu khong chi kiem quan he. Hai test tran ben duoi tu dung danh sach
        // TU CHINH hang so, nen chung troi theo no: noi tran len 500 thi ca hai VAN XANH.
        // Tran nay la mot quyet dinh an toan (mot lo hong thi thiet hai gioi han o 50 ho so,
        // va hang doi ky khong bi don qua lau), nen no phai doi bang mot lan sua test co y
        // thuc chu khong troi theo mot dong sua hang so.
        $this->assertSame(50, BHYTTt12Controller::TRAN_GUI_NHIEU);
    }

    /** @test */
    public function vuot_tran_thi_KHONG_xep_ho_so_nao()
    {
        // Tran kiem o SERVER: gioi han phia trinh duyet chi la tien nghi, ai goi thang
        // endpoint se lot qua het. Va vuot tran phai chan CA LO chu khong xep 50 cai dau roi
        // bo phan con lai - nguoi dung se tuong da gui het.
        $ds = array();

        for ($i = 1; $i <= BHYTTt12Controller::TRAN_GUI_NHIEU + 1; $i++) {
            $ma = 'HS' . str_pad($i, 4, '0', STR_PAD_LEFT);
            $this->tao($ma);
            $ds[] = $ma;
        }

        $phanHoi = (new BHYTTt12Controller())
            ->kyVaGuiNhieu(Request::create('/', 'POST', array('ma_ho_so' => $ds)));

        $this->assertSame(422, $phanHoi->getStatusCode());
        Queue::assertNotPushed(SignTt12Job::class);
        Queue::assertNotPushed(SubmitTt12Job::class);
    }

    /** @test */
    public function dung_tran_thi_van_chay()
    {
        // Chan o dung 51 chu khong phai o 50: lech mot don vi lam nguoi dung chon dung tran
        // ma bi tu choi.
        $ds = array();

        for ($i = 1; $i <= BHYTTt12Controller::TRAN_GUI_NHIEU; $i++) {
            $ma = 'HS' . str_pad($i, 4, '0', STR_PAD_LEFT);
            $this->tao($ma);
            $ds[] = $ma;
        }

        $phanHoi = (new BHYTTt12Controller())
            ->kyVaGuiNhieu(Request::create('/', 'POST', array('ma_ho_so' => $ds)));

        $this->assertSame(200, $phanHoi->getStatusCode());
        Queue::assertPushed(SignTt12Job::class, BHYTTt12Controller::TRAN_GUI_NHIEU);
    }

    /** @test */
    public function ma_trung_nhau_duoc_gop_truoc_khi_do_tran()
    {
        // Dem con so THO thi 60 phan tu trung nhau bi tu choi oan, trong khi whereIn() phia
        // duoi von da gop trung - tuc chi co vai ho so that su duoc xep hang.
        $this->tao('A');

        $ds = array_fill(0, BHYTTt12Controller::TRAN_GUI_NHIEU + 10, 'A');

        $phanHoi = (new BHYTTt12Controller())
            ->kyVaGuiNhieu(Request::create('/', 'POST', array('ma_ho_so' => $ds)));

        $this->assertSame(200, $phanHoi->getStatusCode());
        Queue::assertPushed(SignTt12Job::class, 1);
    }

    /** @test */
    public function danh_sach_rong_bi_tu_choi()
    {
        // Truoc day tra 200 kem "Da day 0 ho so vao hang doi ky va 0 ho so vao hang doi gui"
        // - doc nhu mot lan gui thanh cong ma khong gui gi.
        $phanHoi = (new BHYTTt12Controller())
            ->kyVaGuiNhieu(Request::create('/', 'POST', array('ma_ho_so' => array())));

        $this->assertSame(400, $phanHoi->getStatusCode());
        Queue::assertNotPushed(SignTt12Job::class);
    }

    /** @test */
    public function ma_rong_va_khoang_trang_bi_loai_truoc_khi_do_tran()
    {
        $this->tao('A');

        $phanHoi = (new BHYTTt12Controller())->kyVaGuiNhieu(
            Request::create('/', 'POST', array('ma_ho_so' => array(' A ', '', '   ')))
        );

        $this->assertSame(200, $phanHoi->getStatusCode());
        Queue::assertPushed(SignTt12Job::class, 1);
    }

    /** @test */
    public function ho_so_da_duoc_tiep_nhan_thi_khong_xoa_duoc()
    {
        // Con dau vet doi soat voi co quan BHXH thi khong duoc xoa khoi he thong.
        $this->tao('A', array('is_signed' => true, 'ma_ket_qua' => '200', 'ma_gd' => 'GD1'));

        $ct = new BHYTTt12Controller();
        $ct->delete('A');

        $this->assertNotNull(Tt12HoSo::where('ma_ho_so', 'A')->first());
    }

    /** @test */
    public function ho_so_chua_gui_thanh_cong_thi_xoa_duoc()
    {
        $this->tao('A', array('ma_ket_qua' => '500'));

        $ct = new BHYTTt12Controller();
        $ct->delete('A');

        $this->assertNull(Tt12HoSo::where('ma_ho_so', 'A')->first());
    }
}

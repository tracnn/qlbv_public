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

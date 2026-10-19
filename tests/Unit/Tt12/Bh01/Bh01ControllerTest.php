<?php

namespace Tests\Unit\Tt12\Bh01;

use App\Http\Controllers\BHYT\BHYTBh01Controller;
use App\Jobs\SignBh01Job;
use App\Jobs\SubmitBh01Job;
use App\Models\BHYT\Tt12\Bh01Ban;
use App\Services\BHYT\DanhSachCoSo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\Support\DungBangBh01Sqlite;
use Tests\TestCase;

/**
 * Kiem THANG tren controller, khong qua HTTP (cung ly do voi Tt12ControllerTest): cai can kiem la
 * nhanh xu ly cua man 01/BH chu khong phai tang middleware/phien dang nhap.
 */
class Bh01ControllerTest extends TestCase
{
    use DungBangBh01Sqlite;

    public function setUp()
    {
        parent::setUp();
        $this->dungBangBh01();
        Queue::fake();
        Storage::fake('exportTt12');
        Cache::put(DanhSachCoSo::KHOA_CACHE, array('01929' => '01929 - Bach Mai', '37470' => '37470 - Ninh Binh'), 60);
        config(array('organization.tt12.submit_enabled' => true));
    }

    private function controller()
    {
        return new BHYTBh01Controller();
    }

    private function req(array $tham, $method = 'POST')
    {
        $r = Request::create('/x', $method, $tham);
        $r->headers->set('X-Requested-With', 'XMLHttpRequest');

        return $r;
    }

    private function xml1(array $ghiDe = array())
    {
        DB::table('xml3176_xml1s')->insert(array_merge(array(
            'ma_lk' => 'LK1', 'ho_ten' => 'NGUYEN VAN A', 'ngay_sinh' => '198001010000',
            'gioi_tinh' => '1', 'ma_the_bhyt' => 'DN4010000000001', 'ma_benh_chinh' => 'E11',
            'ngay_vao' => '202609010800', 'ngay_ra' => '202609010900',
            'ma_cskcb' => '01929', 'nam_qt' => '2026', 'thang_qt' => '09', 'ma_loai_kcb' => '01',
            't_tongchi_bv' => 100, 't_tongchi_bh' => 80, 't_bhtt' => 70,
            't_bncct' => 10, 't_bntt' => 20, 't_nguonkhac' => 0,
        ), $ghiDe));
    }

    private function ban($soBan, array $ghiDe = array())
    {
        return Bh01Ban::create(array_merge(array(
            'ma_ban' => sprintf('BH01_01929_202609_%02d', $soBan), 'ma_cskcb' => '01929',
            'nam_qt' => '2026', 'thang_qt' => '09', 'so_ban' => $soBan, 'so_dong' => 1,
            't_tongchi_bv' => 100, 't_bhtt' => 70,
            'id_ds_chitiet' => 'Id-' . $soBan, 'trang_thai_ky' => Bh01Ban::KY_XONG,
        ), $ghiDe));
    }

    /** @test */
    public function tao_khong_chon_co_so_thi_422()
    {
        $kq = $this->controller()->tao($this->req(array('nam' => 2026, 'thang' => 9)));

        $this->assertSame(422, $kq->getStatusCode());
        $this->assertSame(0, Bh01Ban::count());
    }

    /** @test */
    public function tao_co_so_ngoai_danh_sach_thi_422()
    {
        $this->xml1(array('ma_cskcb' => '99999'));
        $kq = $this->controller()->tao($this->req(array('ma_cskcb' => '99999', 'nam' => 2026, 'thang' => 9)));

        $this->assertSame(422, $kq->getStatusCode());
        $this->assertSame(0, Bh01Ban::count());
    }

    /** @test */
    public function tao_ky_khong_hop_le_thi_422()
    {
        $kq = $this->controller()->tao($this->req(array('ma_cskcb' => '01929', 'nam' => 2026, 'thang' => 3)));

        $this->assertSame(422, $kq->getStatusCode());
    }

    /** @test */
    public function tao_thanh_cong_xep_job_ky()
    {
        $this->xml1();
        $kq = $this->controller()->tao($this->req(array('ma_cskcb' => '01929', 'nam' => 2026, 'thang' => 9)));

        $this->assertSame(200, $kq->getStatusCode());
        $this->assertNotNull(Bh01Ban::first());
        $this->assertSame('BH01_01929_202609_01', $kq->getData()->ma_ban);
        Queue::assertPushed(SignBh01Job::class, 1);
    }

    /** @test */
    public function tao_ky_khong_co_ho_so_thi_422()
    {
        $kq = $this->controller()->tao($this->req(array('ma_cskcb' => '01929', 'nam' => 2026, 'thang' => 9)));

        $this->assertSame(422, $kq->getStatusCode());
        Queue::assertNotPushed(SignBh01Job::class);
    }

    /** @test */
    public function xem_truoc_tra_tong_hop()
    {
        $this->xml1();
        $kq = $this->controller()->xemTruoc($this->req(array('ma_cskcb' => '01929', 'nam' => 2026, 'thang' => 9), 'GET'));

        $this->assertSame(200, $kq->getStatusCode());
        $this->assertSame(1, $kq->getData()->so_ho_so);
    }

    /** @test */
    public function gui_ky_da_tiep_nhan_chua_xac_nhan_thi_409_can_xac_nhan()
    {
        $this->ban(1, array('ma_ket_qua' => '200', 'ma_gd' => 'GD1'));
        $this->ban(2);

        $kq = $this->controller()->gui($this->req(array()), 'BH01_01929_202609_02');

        $this->assertSame(409, $kq->getStatusCode());
        $d = $kq->getData();
        $this->assertTrue($d->can_xac_nhan);
        $this->assertContains('đã được cổng tiếp nhận ở bản 1', $d->thong_diep);
        $this->assertContains('GD1', $d->thong_diep);
        $this->assertContains('Gửi bản 2?', $d->thong_diep);
        Queue::assertNotPushed(SubmitBh01Job::class);
    }

    /** @test */
    public function gui_co_xac_nhan_thi_xep_job()
    {
        $this->ban(1, array('ma_ket_qua' => '200', 'ma_gd' => 'GD1'));
        $this->ban(2);

        $kq = $this->controller()->gui($this->req(array('xac_nhan' => 1)), 'BH01_01929_202609_02');

        $this->assertSame(200, $kq->getStatusCode());
        Queue::assertPushed(SubmitBh01Job::class, 1);
    }

    /** @test */
    public function gui_ky_chua_tiep_nhan_xep_job_khong_can_xac_nhan()
    {
        $this->ban(1);

        $kq = $this->controller()->gui($this->req(array()), 'BH01_01929_202609_01');

        $this->assertSame(200, $kq->getStatusCode());
        Queue::assertPushed(SubmitBh01Job::class, 1);
    }

    /** @test */
    public function gui_ban_chua_ky_thi_422()
    {
        $this->ban(1, array('trang_thai_ky' => Bh01Ban::KY_CHO));

        $kq = $this->controller()->gui($this->req(array()), 'BH01_01929_202609_01');

        $this->assertSame(422, $kq->getStatusCode());
        Queue::assertNotPushed(SubmitBh01Job::class);
    }

    /** @test */
    public function gui_khi_co_gui_tat_thi_422()
    {
        config(array('organization.tt12.submit_enabled' => false));
        $this->ban(1);

        $kq = $this->controller()->gui($this->req(array()), 'BH01_01929_202609_01');

        $this->assertSame(422, $kq->getStatusCode());
        $this->assertContains('đang tắt', $kq->getData()->thong_diep);
        Queue::assertNotPushed(SubmitBh01Job::class);
    }

    /** @test */
    public function gui_ban_dang_gui_thi_409()
    {
        $this->ban(1, array('dang_xu_ly_tu' => now()));

        $kq = $this->controller()->gui($this->req(array()), 'BH01_01929_202609_01');

        $this->assertSame(409, $kq->getStatusCode());
        $this->assertContains('đang được gửi', $kq->getData()->thong_diep);
    }

    /** @test */
    public function ky_lai_ban_da_gui_thi_409()
    {
        $this->ban(1, array('trang_thai_ky' => Bh01Ban::KY_LOI, 'submitted_at' => now()));

        $kq = $this->controller()->kyLai($this->req(array()), 'BH01_01929_202609_01');

        $this->assertSame(409, $kq->getStatusCode());
        Queue::assertNotPushed(SignBh01Job::class);
    }

    /** @test */
    public function ky_lai_ban_ky_loi_xep_job()
    {
        $this->ban(1, array('trang_thai_ky' => Bh01Ban::KY_LOI));

        $kq = $this->controller()->kyLai($this->req(array()), 'BH01_01929_202609_01');

        $this->assertSame(200, $kq->getStatusCode());
        Queue::assertPushed(SignBh01Job::class, 1);
    }

    /** @test */
    public function tai_tra_dung_noi_dung_tep_da_ky()
    {
        Storage::disk('exportTt12')->put('BH01/a.xml', '<x>noi dung da ky</x>');
        $this->ban(1, array('duong_dan_da_ky' => 'BH01/a.xml'));

        $kq = $this->controller()->tai('BH01_01929_202609_01');

        $this->assertSame(200, $kq->getStatusCode());
        ob_start();
        $kq->sendContent();
        $noiDung = ob_get_clean();
        $this->assertSame('<x>noi dung da ky</x>', $noiDung);
        $this->assertContains('BH01_01929_202609_01.xml', $kq->headers->get('content-disposition'));
    }

    /** @test */
    public function tai_ban_chua_ky_hoac_mat_tep_thi_404()
    {
        $this->ban(1, array('trang_thai_ky' => Bh01Ban::KY_CHO));
        $this->ban(2, array('duong_dan_da_ky' => 'BH01/mat.xml'));

        $this->assertSame(404, $this->controller()->tai('BH01_01929_202609_01')->getStatusCode());
        $this->assertSame(404, $this->controller()->tai('BH01_01929_202609_02')->getStatusCode());
    }

    /** @test */
    public function fetch_data_bao_du_lieu_da_doi_khi_xml1_thay_doi()
    {
        $this->xml1(array('ma_lk' => 'A'));
        $this->ban(1, array('so_dong' => 1));
        $this->ban(2, array('so_dong' => 1));
        // XML1 co them mot ho so sau khi chup ban
        $this->xml1(array('ma_lk' => 'B'));

        $kq = $this->controller()->fetchData($this->req(array('ma_cskcb' => '01929', 'nam' => 2026, 'thang' => 9), 'GET'));
        $hang = collect($kq->getData()->data)->keyBy('so_ban');

        $this->assertTrue($hang[2]->du_lieu_da_doi);
        // Chi ban moi nhat cua ky moi duoc so
        $this->assertFalse($hang[1]->du_lieu_da_doi);
    }

    /** @test */
    public function fetch_data_khong_bao_doi_khi_khop()
    {
        $this->xml1(array('ma_lk' => 'A'));
        $this->ban(1, array('so_dong' => 1));

        $kq = $this->controller()->fetchData($this->req(array(), 'GET'));
        $hang = $kq->getData()->data;

        $this->assertCount(1, $hang);
        $this->assertFalse($hang[0]->du_lieu_da_doi);
    }

    /** @test */
    public function route_tinh_khong_bi_tham_so_nuot()
    {
        $url = route('bhyt.tt12.bh01.xem-truoc', array(), false);
        $route = app('router')->getRoutes()->match(Request::create($url, 'GET'));

        $this->assertContains('@xemTruoc', $route->getActionName());
    }

    /** @test */
    public function blade_bien_dich_duoc()
    {
        foreach (array('index', 'dong') as $ten) {
            $duongDan = resource_path('views/bhyt/tt12/bh01/' . $ten . '.blade.php');
            $this->assertFileExists($duongDan);
            $php = app('blade.compiler')->compileString(file_get_contents($duongDan));
            $this->assertNotEmpty($php);
            // TOKEN_PARSE nem ParseError neu blade bien dich ra PHP sai cu phap. Khong render day du
            // duoc vi layout adminlte can nguoi dung dang nhap (AppServiceProvider goi hasRole()).
            token_get_all($php, TOKEN_PARSE);
        }
    }
}

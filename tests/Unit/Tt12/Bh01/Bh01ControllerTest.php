<?php

namespace Tests\Unit\Tt12\Bh01;

use App\Http\Controllers\BHYT\BHYTBh01Controller;
use App\Jobs\SignBh01Job;
use App\Jobs\SubmitBh01Job;
use App\Models\BHYT\Tt12\Bh01Ban;
use App\Services\BHYT\DanhSachCoSo;
use App\Services\Tt12\Bh01\Bh01DacTa;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\Support\DungBangBh01Sqlite;
use Tests\Support\TaoXlsxBh01;
use Tests\TestCase;

/**
 * Kiem THANG tren controller, khong qua HTTP (cung ly do voi Tt12ControllerTest): cai can kiem la
 * nhanh xu ly cua man 01/BH chu khong phai tang middleware/phien dang nhap.
 */
class Bh01ControllerTest extends TestCase
{
    use DungBangBh01Sqlite, TaoXlsxBh01;

    public function setUp()
    {
        parent::setUp();
        $this->dungBangBh01();
        Queue::fake();
        Storage::fake('exportTt12');
        Cache::put(DanhSachCoSo::KHOA_CACHE, array('01929' => '01929 - Bach Mai', '37470' => '37470 - Ninh Binh'), 60);
        config(array('organization.tt12.submit_enabled' => true, 'organization.tt12.sign_enabled' => true));
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
        $this->assertContains('Gửi bản 2 (nguồn XML3176)?', $d->thong_diep);
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
    public function ky_lai_ban_cho_ky_moi_thi_409()
    {
        $this->ban(1, array('trang_thai_ky' => Bh01Ban::KY_CHO));

        $kq = $this->controller()->kyLai($this->req(array()), 'BH01_01929_202609_01');

        $this->assertSame(409, $kq->getStatusCode());
        Queue::assertNotPushed(SignBh01Job::class);
    }

    /** @test */
    public function tao_tu_choi_khi_tat_ky()
    {
        config(array('organization.tt12.sign_enabled' => false));
        $this->xml1();

        $kq = $this->controller()->tao($this->req(array('ma_cskcb' => '01929', 'nam' => 2026, 'thang' => 9)));

        $this->assertSame(422, $kq->getStatusCode());
        $this->assertSame('Chức năng ký số đang tắt, chưa tạo bản.', $kq->getData()->thong_diep);
        $this->assertSame(0, Bh01Ban::count(), 'Tat ky thi khong tao ban - ban se nam cho_ky mai');
        Queue::assertNotPushed(SignBh01Job::class);
    }

    /** @test */
    public function fetch_data_co_co_the_ky_lai()
    {
        $this->ban(1, array('trang_thai_ky' => Bh01Ban::KY_LOI));
        $this->ban(2, array('trang_thai_ky' => Bh01Ban::KY_CHO));

        $kq = $this->controller()->fetchData($this->req(array(), 'GET'));
        $hang = collect($kq->getData()->data)->keyBy('so_ban');

        $this->assertTrue($hang[1]->co_the_ky_lai);
        $this->assertFalse($hang[2]->co_the_ky_lai);
    }

    /** @test */
    public function fetch_data_gioi_han_100_dong()
    {
        for ($i = 1; $i <= 101; $i++) {
            $this->ban($i);
        }

        $kq = $this->controller()->fetchData($this->req(array('length' => 100000), 'GET'));

        $this->assertCount(100, $kq->getData()->data);
        $this->assertSame(101, $kq->getData()->recordsTotal);
    }

    /** @test */
    public function tong_hop_lam_tron_tung_dong_khop_ban_vua_tao()
    {
        // Ban luu tong cua gia tri DA LAM TRON tung dong; so sanh phai tinh cung cach, khong thi
        // ban vua tao da bi bao "du lieu da doi".
        foreach (array('A', 'B', 'C') as $ma) {
            $this->xml1(array('ma_lk' => $ma, 't_tongchi_bv' => 10.004, 't_bhtt' => 7.006));
        }
        $this->assertSame(200, $this->controller()
            ->tao($this->req(array('ma_cskcb' => '01929', 'nam' => 2026, 'thang' => 9)))->getStatusCode());

        $kq = $this->controller()->fetchData($this->req(array(), 'GET'));
        $hang = $kq->getData()->data;

        $this->assertCount(1, $hang);
        $this->assertFalse($hang[0]->du_lieu_da_doi);
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

    private function tepExcelHopLe()
    {
        $mau = array(
            'STT' => '1', 'HO_TEN' => 'Nguyen Van A', 'NGAY_SINH' => '198001010000', 'GIOI_TINH' => '1',
            'MA_THE_BHYT' => 'GD4010000000001', 'MA_BENH_CHINH' => 'E11.4',
            'NGAY_VAO' => '202609010800', 'NGAY_VAO_NOI_TRU' => '', 'NGAY_RA' => '202609051000',
            'SO_NGAY_DTRI' => '4', 'MA_LOAI_KCB' => '03',
            'T_TONGCHI_BV' => '1000', 'T_TONGCHI_BH' => '800', 'T_BHTT' => '700', 'T_BNCCT' => '100',
            'T_BNTT' => '200', 'T_NGUONKHAC' => '0',
            'MA_CSKCB' => '01929', 'NAM_QT' => '2026', 'THANG_QT' => '09',
        );
        $tieuDe = array();
        $dong = array();
        foreach (Bh01DacTa::the() as $dt) {
            $tieuDe[] = $dt['the'];
            $dong[] = $mau[$dt['the']];
        }

        return $this->taoXlsx(array($tieuDe, $dong));
    }

    private function tepTai($duongDan, $ten, $loi = null)
    {
        return new UploadedFile($duongDan, $ten, null, null, $loi, true);
    }

    private function reqTep($tep)
    {
        $r = Request::create('/x', 'POST', array('ma_cskcb' => '01929', 'nam' => 2026, 'thang' => 9),
            array(), $tep === null ? array() : array('tep' => $tep));
        $r->headers->set('X-Requested-With', 'XMLHttpRequest');

        return $r;
    }

    /** @test */
    public function nhap_excel_thanh_cong_xep_job_ky()
    {
        $kq = $this->controller()->nhapExcel($this->reqTep($this->tepTai($this->tepExcelHopLe(), 'HSTH01BH.xlsx')));

        $this->assertSame(200, $kq->getStatusCode());
        $ban = Bh01Ban::first();
        $this->assertSame('excel', $ban->nguon);
        $this->assertSame('HSTH01BH.xlsx', $ban->ten_tep_nguon);
        $this->assertSame($ban->ma_ban, $kq->getData()->ma_ban);
        $tb = $kq->getData()->thong_diep;
        $this->assertContains($ban->ma_ban, $tb);
        $this->assertContains('1.000', $tb);
        $this->assertContains('700', $tb);
        Queue::assertPushed(SignBh01Job::class, 1);
    }

    /** @test */
    public function nhap_excel_tep_hong_thi_422()
    {
        $hong = dirname($this->taoXlsx(array(array('x')))) . DIRECTORY_SEPARATOR . 'hong.xlsx';
        file_put_contents($hong, 'day khong phai xlsx');
        $kq = $this->controller()->nhapExcel($this->reqTep($this->tepTai($hong, 'HSTH01BH.xlsx')));

        $this->assertSame(422, $kq->getStatusCode());
        $this->assertContains('Không đọc được tệp Excel', $kq->getData()->thong_diep);
        $this->assertSame(0, Bh01Ban::count());
    }

    /** @test */
    public function nhap_excel_qua_co_thi_422()
    {
        $tep = $this->tepTai($this->tepExcelHopLe(), 'HSTH01BH.xlsx', UPLOAD_ERR_INI_SIZE);
        $kq = $this->controller()->nhapExcel($this->reqTep($tep));

        $this->assertSame(422, $kq->getStatusCode());
        $this->assertContains('vượt cỡ', $kq->getData()->thong_diep);
    }

    /** @test */
    public function nhap_excel_sai_duoi_thi_422()
    {
        $kq = $this->controller()->nhapExcel($this->reqTep($this->tepTai($this->tepExcelHopLe(), 'HSTH01BH.csv')));

        $this->assertSame(422, $kq->getStatusCode());
        $this->assertContains('.xlsx', $kq->getData()->thong_diep);
    }

    /** @test */
    public function nhap_excel_khi_tat_ky_thi_422()
    {
        config(array('organization.tt12.sign_enabled' => false));
        $kq = $this->controller()->nhapExcel($this->reqTep($this->tepTai($this->tepExcelHopLe(), 'HSTH01BH.xlsx')));

        $this->assertSame(422, $kq->getStatusCode());
        $this->assertSame(0, Bh01Ban::count());
        Queue::assertNotPushed(SignBh01Job::class);
    }

    /** @test */
    public function nhap_excel_khong_co_tep_thi_422()
    {
        $kq = $this->controller()->nhapExcel($this->reqTep(null));

        $this->assertSame(422, $kq->getStatusCode());
        $this->assertSame('Chưa chọn tệp Excel.', $kq->getData()->thong_diep);
    }

    /** @test */
    public function ban_excel_khong_bao_du_lieu_da_doi()
    {
        $this->xml1(array('ma_lk' => 'A'));
        $this->ban(1, array('so_dong' => 1));
        $this->ban(2, array('so_dong' => 1, 'nguon' => 'excel'));
        $this->xml1(array('ma_lk' => 'B'));

        $kq = $this->controller()->fetchData($this->req(array('ma_cskcb' => '01929', 'nam' => 2026, 'thang' => 9), 'GET'));
        $hang = collect($kq->getData()->data)->keyBy('so_ban');

        $this->assertFalse($hang[2]->du_lieu_da_doi);
        $this->assertSame('excel', $hang[2]->nguon);
        // Ban xml3176 moi nhat la ban 1 (ban excel khong tinh): XML1 da doi thi van bao.
        $this->assertTrue($hang[1]->du_lieu_da_doi);
    }

    /** @test */
    public function gui_xac_nhan_neu_nguon_ca_hai_ban()
    {
        $this->ban(1, array('ma_ket_qua' => '200', 'ma_gd' => 'GD1'));
        $this->ban(2, array('nguon' => 'excel'));

        $kq = $this->controller()->gui($this->req(array()), 'BH01_01929_202609_02');

        $this->assertSame(409, $kq->getStatusCode());
        $this->assertContains('nguồn XML3176', $kq->getData()->thong_diep);
        $this->assertContains('nguồn Excel', $kq->getData()->thong_diep);
    }

    /** @test */
    public function xuat_excel_tra_tep_ten_ma_ban()
    {
        $this->ban(1);
        $kq = $this->controller()->xuatExcel('BH01_01929_202609_01');

        $this->assertSame(200, $kq->getStatusCode());
        $this->assertContains('BH01_01929_202609_01.xlsx', $kq->headers->get('content-disposition'));
        $this->assertSame(404, $this->controller()->xuatExcel('khong-co')->getStatusCode());
    }

    /** @test */
    public function tep_mau_tra_tep_ten_mau()
    {
        $kq = $this->controller()->tepMau();

        $this->assertContains('HSTH01BH_mau.xlsx', $kq->headers->get('content-disposition'));
    }

    /** @test */
    public function route_tinh_moi_khong_bi_tham_so_nuot()
    {
        $router = app('router')->getRoutes();
        $nhapExcel = $router->match(Request::create(route('bhyt.tt12.bh01.nhap-excel', array(), false), 'POST'));
        $tepMau = $router->match(Request::create(route('bhyt.tt12.bh01.tep-mau', array(), false), 'GET'));
        $xuat = $router->match(Request::create(route('bhyt.tt12.bh01.xuat-excel', array('ma_ban' => 'X'), false), 'GET'));

        $this->assertContains('@nhapExcel', $nhapExcel->getActionName());
        $this->assertContains('@tepMau', $tepMau->getActionName());
        $this->assertContains('@xuatExcel', $xuat->getActionName());
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

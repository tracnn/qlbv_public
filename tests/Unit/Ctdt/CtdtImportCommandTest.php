<?php

namespace Tests\Unit\Ctdt;

use ReflectionProperty;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Bus;
use Tests\TestCase;
use Tests\Support\DungBangCtdtSqlite;
use Tests\Support\GoiCtdtMau;
use App\Console\Commands\CtdtImport;
use App\Services\Ctdt\CtdtImporter;

/**
 * Lenh chay NEN, khong co nguoi ngoi nhin. Moi cai phanh o day deu la thu duy nhat dung
 * giua mot thu muc do nham va hang nghin lan POST that len cong BHXH.
 */
class CtdtImportCommandTest extends TestCase
{
    use DungBangCtdtSqlite;
    use GoiCtdtMau;

    /** @var array duong dan cac thu muc tam da tao, de don o tearDown() */
    private $thuMucTamDaTao = [];

    protected function setUp()
    {
        parent::setUp();
        $this->chuanBiBangCtdt();
        Queue::fake();
    }

    protected function tearDown()
    {
        foreach ($this->thuMucTamDaTao as $thuMuc) {
            $this->xoaThuMucDeQuy($thuMuc);
        }

        parent::tearDown();
    }

    /** @test */
    public function co_du_nam_tuy_chon_phanh()
    {
        $lenh = new CtdtImport();
        $dinhNghia = $lenh->getDefinition();

        foreach (['duong-dan', 'gioi-han', 'dry-run', 'khong-ky', 'khong-gui'] as $ten) {
            $this->assertTrue($dinhNghia->hasOption($ten), 'Thieu tuy chon --' . $ten);
        }
    }

    /** @test */
    public function ten_lenh_dung_tien_to_ctdt()
    {
        $this->assertSame('ctdt:import', (new CtdtImport())->getName());
    }

    /**
     * dsTep() la protected. Dung mot lop con lo no ra thay vi doi dsTep() thanh public
     * chi vi test - CtdtImportLoRa (khai bao ben duoi) la seam rieng cho test.
     *
     * @test
     */
    public function dsTep_bo_qua_thu_muc_con_da_nap_va_loi()
    {
        $thuMuc = $this->thuMucTam();

        file_put_contents($thuMuc . DIRECTORY_SEPARATOR . 'goc.xml', 'noi dung goc');

        $thuMucDaNap = $thuMuc . DIRECTORY_SEPARATOR . CtdtImport::THU_MUC_DA_NAP;
        mkdir($thuMucDaNap, 0775, true);
        file_put_contents($thuMucDaNap . DIRECTORY_SEPARATOR . 'cu.xml', 'da nap tu truoc');

        $thuMucLoi = $thuMuc . DIRECTORY_SEPARATOR . CtdtImport::THU_MUC_LOI;
        mkdir($thuMucLoi, 0775, true);
        file_put_contents($thuMucLoi . DIRECTORY_SEPARATOR . 'hong.xml', 'da hong tu truoc');

        $ds = (new CtdtImportLoRa())->dsTepCong($thuMuc);

        // Neu dsTep() quet de quy (hoac quen loai thu muc con), 'cu.xml' va 'hong.xml'
        // se lot vao day va bi nap lai - voi ho so da gui, do la mot lan POST that nua.
        $this->assertCount(1, $ds, 'dsTep() phai chi tra ve tep o thu muc goc');
        $this->assertSame('goc.xml', basename($ds[0]));
    }

    /**
     * --dry-run la cai phanh quan trong nhat cua lenh nay. Neu no am tham nap that, khong
     * co gi khac trong lenh bao ve duoc nguoi van hanh dang thu nghiem tren may that.
     *
     * @test
     */
    public function dry_run_khong_nap_va_khong_doi_tep()
    {
        $thuMuc = $this->thuMucTam();
        $tepMau = $thuMuc . DIRECTORY_SEPARATOR . 'mau.xml';

        file_put_contents($tepMau, $this->goiCt2025([
            [$this->chungTu('CT03', ['MA_YTE' => 'DRYRUN001', 'HO_TEN' => 'Nguyen Van Kho'])],
        ]));

        $maThoat = Artisan::call('ctdt:import', [
            '--dry-run'    => true,
            '--duong-dan'  => $thuMuc,
        ]);

        $this->assertSame(0, $maThoat);
        $this->assertFileExists($tepMau, 'Tep phai con nguyen cho cu sau --dry-run');
        $this->assertFalse(
            is_dir($thuMuc . DIRECTORY_SEPARATOR . CtdtImport::THU_MUC_DA_NAP),
            '--dry-run khong duoc tao thu muc da-nap'
        );
        $this->assertSame(0, \App\Models\BHYT\Ctdt\CtdtHoSo::count(), '--dry-run khong duoc ghi CSDL');
    }

    /**
     * --gioi-han uu tien tuy chon dong lenh hon cau hinh.
     *
     * @test
     */
    public function gioi_han_hieu_luc_uu_tien_tuy_chon_dong_lenh()
    {
        config(['organization.chung_tu_dien_tu.import_gioi_han' => 999]);

        $lenh = new CtdtImportLoRa();
        $lenh->ganInputTest(new ArrayInput(['--gioi-han' => '5'], $lenh->getDefinition()));

        $this->assertSame(5, $lenh->gioiHanHieuLucCong());
    }

    /**
     * Khong truyen --gioi-han thi lui ve cau hinh
     * organization.chung_tu_dien_tu.import_gioi_han.
     *
     * @test
     */
    public function gioi_han_hieu_luc_lui_ve_cau_hinh_khi_khong_truyen_tuy_chon()
    {
        config(['organization.chung_tu_dien_tu.import_gioi_han' => 7]);

        $lenh = new CtdtImportLoRa();
        $lenh->ganInputTest(new ArrayInput([], $lenh->getDefinition()));

        $this->assertSame(7, $lenh->gioiHanHieuLucCong());
    }

    /**
     * Khong co tuy chon dong lenh MA cung khong co cau hinh (hoac cau hinh la null) thi
     * lui ve 200. Day la ly do dung `?:` chu khong dung config($khoa, $macDinh): dang hai
     * tham so KHONG lui ve mac dinh khi khoa ton tai voi gia tri null.
     *
     * @test
     */
    public function gioi_han_hieu_luc_lui_ve_200_khi_khong_co_gi_ca()
    {
        config(['organization.chung_tu_dien_tu.import_gioi_han' => null]);

        $lenh = new CtdtImportLoRa();
        $lenh->ganInputTest(new ArrayInput([], $lenh->getDefinition()));

        $this->assertSame(200, $lenh->gioiHanHieuLucCong());
    }

    /**
     * CRITICAL: rename() trong chuyen() co the hong (AV khoa tep, thieu quyen ghi, o mang
     * chap). Truoc ban va, loi do chi vao Log::error roi bi lang quen: napMotTep() van tra
     * ve nhu thanh cong, quet() van bao "Nap xong" va thoat ma 0 - trong khi tep VAN NAM
     * o thu muc goc, cho luot sau nhat lai chinh no va nap trung mot ho so DA nam trong
     * CSDL.
     *
     * Gia lap that bai bang cach GHI DE chuyen() trong CtdtImportChuyenLuonThatBai (khai
     * bao ben duoi) thay vi pha he thong tep that - deu vao dung mot duong: chuyen() tra
     * ve false.
     *
     * @test
     */
    public function chuyen_that_bai_sau_khi_nap_thanh_cong_van_bao_loi_va_thoat_khac_khong()
    {
        $thuMuc = $this->thuMucTam();
        $tepMau = $thuMuc . DIRECTORY_SEPARATOR . 'mau.xml';

        file_put_contents($tepMau, $this->goiCt2025([
            [$this->chungTu('CT03', ['MA_YTE' => 'YT001', 'HO_TEN' => 'Nguyen Van Test'])],
        ]));

        $lenh = new CtdtImportChuyenLuonThatBai(new CtdtImporter());
        $lenh->setLaravel($this->app);

        $dauRa = new BufferedOutput();
        $maThoat = $lenh->run(
            new ArrayInput(['--duong-dan' => $thuMuc], $lenh->getDefinition()),
            $dauRa
        );

        $this->assertSame(
            1,
            $maThoat,
            'Tep da nap thanh cong nhung khong doi duoc phai lam lenh thoat khac 0'
        );
        $this->assertFileExists(
            $tepMau,
            'chuyen() gia lap that bai nen tep phai con nguyen o thu muc goc'
        );
        $this->assertSame(1, \App\Models\BHYT\Ctdt\CtdtHoSo::count(), 'Ho so van phai vao CSDL');
        $this->assertContains('KHONG DOI duoc', $dauRa->fetch());
    }

    /**
     * Duong hanh phuc that (KHONG override chuyen()): sau khi nap thanh cong, tep phai
     * that su duoc doi vao da-nap/ va lenh thoat ma 0. Day la test se bat DUNG mutation
     * "rename() luon hong": neu chuyen() luon tra false, tep se khong con o da-nap/ va
     * ma thoat se la 1 thay vi 0.
     *
     * @test
     */
    public function nap_thanh_cong_thi_tep_duoc_doi_that_vao_da_nap()
    {
        $thuMuc = $this->thuMucTam();
        $tepMau = $thuMuc . DIRECTORY_SEPARATOR . 'mau.xml';

        file_put_contents($tepMau, $this->goiCt2025([
            [$this->chungTu('CT03', ['MA_YTE' => 'YT002', 'HO_TEN' => 'Nguyen Van That'])],
        ]));

        $maThoat = Artisan::call('ctdt:import', [
            '--duong-dan' => $thuMuc,
        ]);

        $this->assertSame(0, $maThoat);
        $this->assertFileNotExists($tepMau, 'Tep phai roi khoi thu muc goc sau khi nap thanh cong');
        $this->assertFileExists(
            $thuMuc . DIRECTORY_SEPARATOR . CtdtImport::THU_MUC_DA_NAP . DIRECTORY_SEPARATOR . 'mau.xml',
            'Tep phai nam trong da-nap/'
        );
        $this->assertSame(1, \App\Models\BHYT\Ctdt\CtdtHoSo::count());
    }

    /** @test */
    public function KHONG_gui_khi_import_tu_dong_gui_dang_tat()
    {
        // Hai cong tac RIENG: submit_enabled cho phep NGUOI bam nut gui; import_tu_dong_gui
        // cho phep MAY gui khi khong co ai nhin. Bat cai thu nhat KHONG duoc keo theo cai
        // thu hai - do la khac biet giua "toi tin cai nut nay" va "toi tin de may tu chay
        // luc 2 gio sang".
        $nguon = file_get_contents(base_path('app/Console/Commands/CtdtImport.php'));

        $this->assertContains('import_tu_dong_gui', $nguon,
            'Lenh phai hoi khoa import_tu_dong_gui rieng, khong duoc chi dua vao submit_enabled');
    }

    /** @test */
    public function ho_so_chua_kiem_khong_duoc_xep_hang_ky()
    {
        // so_loi = 0 cua mot ho so CHUA KIEM khong co nghia la sach - no co nghia la chua ai
        // nhin. Lenh chay ngay sau khi nap, luc bo kiem con dang nam trong hang doi, nen day
        // KHONG phai truong hop hiem: no la truong hop THUONG GAP.
        $nguon = file_get_contents(base_path('app/Console/Commands/CtdtImport.php'));

        $this->assertContains('CtdtQuyetDinhGui::nenKy', $nguon,
            'Phai hoi CtdtQuyetDinhGui::nenKy() chu khong tu viet lai luat da-kiem-va-sach');
    }

    /** @test */
    public function nhat_ho_so_bang_TRUY_VAN_chu_khong_theo_danh_sach_vua_nap()
    {
        // Ho so vua nap gan nhu LUON o trang thai chua kiem - bo kiem con nam trong hang doi
        // JobCtdt. Nhat theo danh sach vua nap se truot gan het, va phai trong cho vong sau.
        // Truy van thang thi moi vong deu vet dung nhung ho so VUA MOI du dieu kien, ke ca
        // ho so nguoi ta sua tay tren man hinh.
        $nguon = file_get_contents(base_path('app/Console/Commands/CtdtImport.php'));

        $this->assertRegExp(
            '/function nhatVaXepHang\(\$thuMuc\)/',
            $nguon,
            'nhatVaXepHang() chi nhan thu muc, KHONG nhan danh sach ma ho so - no phai tu truy van'
        );
    }

    /** @test */
    public function nhat_ho_so_co_tran()
    {
        // Bat gui tren mot CSDL da co san hang nghin ho so sach se xep tat ca vao hang doi
        // trong MOT vong. Tran o day la thu duy nhat dung giua no va mot dot POST hang loat.
        $this->assertGreaterThan(0, CtdtImport::TRAN_NHAT);

        $nguon = file_get_contents(base_path('app/Console/Commands/CtdtImport.php'));

        $this->assertContains('TRAN_NHAT', $nguon);
    }

    /**
     * Phan biet duoc voi mutation2 (xoa nhanh goi CtdtQuyetDinhGui::nenKy() trong
     * nhatVaXepHang()) MA khong dua vao doc nguon: checked_at = '' (chuoi rong, KHAC NULL
     * ve mat SQL) lot qua duoc whereNotNull() o tang loc tho, nhung empty('') === true nen
     * nenKy() van coi la CHUA_KIEM. Neu nhanh goi nenKy() bi xoa, ho so nay se lot xuong
     * duoi va bi xep hang - day la truong hop DUY NHAT ma tang loc SQL va nenKy() lech
     * nhau, nen la ca duy nhat mot test hanh vi thuan tuy co the bat duoc mutation do (test
     * doc nguon o tren co the bi mot dong chu thich con sot lai lam "gia mu").
     *
     * @test
     */
    public function ho_so_checked_at_rong_lot_qua_loc_SQL_van_bi_nenKy_chan_lai()
    {
        Bus::fake();
        config(['organization.chung_tu_dien_tu.import_tu_dong_gui' => true]);

        \App\Models\BHYT\Ctdt\CtdtHoSo::create([
            'ma_ho_so' => 'YT-CHECKED-RONG', 'dich_vu' => 'CT2025', 'loai_hs' => '39',
            'macskcb' => '01001', 'imported_at' => now(),
            'checked_at' => '', 'so_loi' => 0,
        ]);

        $lenh = new CtdtImportLoRa();
        $lenh->ganInputTest(new ArrayInput([], $lenh->getDefinition()));
        $lenh->ganOutputTest(new BufferedOutput());
        $lenh->nhatVaXepHangCong(sys_get_temp_dir());

        Bus::assertNotDispatched(\App\Jobs\SignCtdtJob::class);
    }

    /**
     * Hanh vi that, khong chi doc nguon: ho so CHUA KIEM (checked_at null) khong duoc
     * xep hang, ho so DA KIEM VA SACH (checked_at co, so_loi = 0) thi duoc.
     *
     * @test
     */
    public function ho_so_da_kiem_va_sach_duoc_xep_hang_chua_kiem_thi_khong()
    {
        Bus::fake();
        config(['organization.chung_tu_dien_tu.import_tu_dong_gui' => true]);

        $hoSoChuaKiem = \App\Models\BHYT\Ctdt\CtdtHoSo::create([
            'ma_ho_so' => 'YT-CHUA-KIEM', 'dich_vu' => 'CT2025', 'loai_hs' => '39',
            'macskcb' => '01001', 'imported_at' => now(),
            'checked_at' => null, 'so_loi' => 0,
        ]);

        $hoSoSach = \App\Models\BHYT\Ctdt\CtdtHoSo::create([
            'ma_ho_so' => 'YT-SACH', 'dich_vu' => 'CT2025', 'loai_hs' => '39',
            'macskcb' => '01001', 'imported_at' => now(),
            'checked_at' => now(), 'so_loi' => 0,
        ]);

        $lenh = new CtdtImportLoRa();
        $lenh->ganInputTest(new ArrayInput([], $lenh->getDefinition()));
        $lenh->ganOutputTest(new BufferedOutput());
        $lenh->nhatVaXepHangCong(sys_get_temp_dir());

        Bus::assertDispatched(\App\Jobs\SignCtdtJob::class, function ($job) {
            return $this->maHoSoCuaJob($job) === 'YT-SACH';
        });
        Bus::assertNotDispatched(\App\Jobs\SignCtdtJob::class, function ($job) {
            return $this->maHoSoCuaJob($job) === 'YT-CHUA-KIEM';
        });
    }

    /**
     * Hanh vi that: import_tu_dong_gui tat thi khong xep hang bat ke ho so co sach hay
     * khong - cong tac nay la cua RIENG che do chay nen, khong lien quan submit_enabled.
     *
     * @test
     */
    public function import_tu_dong_gui_tat_thi_khong_xep_hang_gi_ca()
    {
        Bus::fake();
        config(['organization.chung_tu_dien_tu.import_tu_dong_gui' => false]);

        \App\Models\BHYT\Ctdt\CtdtHoSo::create([
            'ma_ho_so' => 'YT-SACH-2', 'dich_vu' => 'CT2025', 'loai_hs' => '39',
            'macskcb' => '01001', 'imported_at' => now(),
            'checked_at' => now(), 'so_loi' => 0,
        ]);

        $lenh = new CtdtImportLoRa();
        $lenh->ganInputTest(new ArrayInput([], $lenh->getDefinition()));
        $soXep = $lenh->nhatVaXepHangCong(sys_get_temp_dir());

        $this->assertSame(0, $soXep, 'import_tu_dong_gui tat phai khong xep hang gi ca');
        Bus::assertNotDispatched(\App\Jobs\SignCtdtJob::class);
    }

    /** SignCtdtJob::$maHoSo la protected va khong co getter cong khai. */
    private function maHoSoCuaJob($job)
    {
        $thuoc = new ReflectionProperty($job, 'maHoSo');
        $thuoc->setAccessible(true);

        return $thuoc->getValue($job);
    }

    /** Tao mot thu muc tam duoi storage/app, tu don o tearDown(). */
    private function thuMucTam()
    {
        $thuMuc = storage_path('app' . DIRECTORY_SEPARATOR . 'ctdt-test-' . uniqid());
        mkdir($thuMuc, 0775, true);
        $this->thuMucTamDaTao[] = $thuMuc;

        return $thuMuc;
    }

    private function xoaThuMucDeQuy($thuMuc)
    {
        if (!is_dir($thuMuc)) {
            return;
        }

        foreach (glob($thuMuc . DIRECTORY_SEPARATOR . '*') ?: [] as $con) {
            if (is_dir($con)) {
                $this->xoaThuMucDeQuy($con);
            } else {
                @unlink($con);
            }
        }

        @rmdir($thuMuc);
    }
}

/**
 * Seam rieng cho test: lo dsTep()/gioiHanHieuLuc() ra ngoai va cho phep gan InputInterface
 * thang vao ma khong can chay ca handle().
 */
class CtdtImportLoRa extends CtdtImport
{
    public function dsTepCong($thuMuc)
    {
        return $this->dsTep($thuMuc);
    }

    public function gioiHanHieuLucCong()
    {
        return $this->gioiHanHieuLuc();
    }

    public function nhatVaXepHangCong($thuMuc)
    {
        return $this->nhatVaXepHang($thuMuc);
    }

    /** Gan InputInterface ma khong goi run(), de doc option() ma khong kich hoat handle(). */
    public function ganInputTest($input)
    {
        $thuoc = new ReflectionProperty(\Illuminate\Console\Command::class, 'input');
        $thuoc->setAccessible(true);
        $thuoc->setValue($this, $input);
    }

    /** Gan OutputInterface ma khong goi run(), de goi $this->info()/warn() khong vo. */
    public function ganOutputTest($output)
    {
        $thuoc = new ReflectionProperty(\Illuminate\Console\Command::class, 'output');
        $thuoc->setAccessible(true);
        $thuoc->setValue($this, $output);
    }
}

/**
 * Seam rieng cho test CRITICAL: gia lap rename() trong chuyen() luon that bai, ma khong
 * dung toi he thong tep that (khoa quyen, AV...) von kho gia lap on dinh tren moi may.
 */
class CtdtImportChuyenLuonThatBai extends CtdtImport
{
    protected function chuyen($duongDan, $thuMuc, $thuMucCon)
    {
        return false;
    }
}

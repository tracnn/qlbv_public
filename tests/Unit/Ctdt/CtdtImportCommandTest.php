<?php

namespace Tests\Unit\Ctdt;

use ReflectionProperty;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\BufferedOutput;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;
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

        // TUONG MINH, khong dua vao config/organization.php cua may dang chay: duocGui() hoi
        // CA import_tu_dong_gui LAN submit_enabled, nen mot bo test khong noi ro submit_enabled
        // se xanh hay do tuy theo may. Cac test rieng ve tung cong tac tu ghi de lai khoa cua
        // minh.
        config(['organization.chung_tu_dien_tu.submit_enabled' => true]);
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
        //
        // CANH BAO cho nguoi doc sau: test doc-nguon nay MOT MINH KHONG DU - chuoi
        // 'CtdtQuyetDinhGui::nenKy' con xuat hien trong mot chu thich khac cua ham (giai
        // thich tang loc SQL), nen neu ai do xoa mat nhanh goi nenKy() thuc su, test nay VAN
        // XANH mot cach gia mu. Phan kiem hanh vi that nam o
        // ho_so_da_kiem_va_sach_duoc_xep_hang_chua_kiem_thi_khong() va dac biet la
        // ho_so_checked_at_rong_lot_qua_loc_SQL_van_bi_nenKy_chan_lai() - DUNG XOA hai test
        // do neu con giu test nay.
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
        //
        // CANH BAO cho nguoi doc sau: test doc-nguon nay MOT MINH KHONG DU - chuoi
        // 'TRAN_NHAT' con xuat hien o khai bao hang so va o cau canh bao warn() cham tran,
        // nen neu ai do xoa mat ->limit(self::TRAN_NHAT) that su khoi cau truy van, test nay
        // VAN XANH mot cach gia mu. Phan kiem hanh vi that nam o
        // nhat_ho_so_khong_vuot_tran_du_co_nhieu_ung_vien_hon() - DUNG XOA test do neu con
        // giu test nay.
        $this->assertGreaterThan(0, CtdtImport::TRAN_NHAT);

        $nguon = file_get_contents(base_path('app/Console/Commands/CtdtImport.php'));

        $this->assertContains('TRAN_NHAT', $nguon);
    }

    /**
     * Phan biet duoc voi mutation4 (bo ->limit(self::TRAN_NHAT)) MA khong dua vao doc
     * nguon: chuoi 'TRAN_NHAT' con nam o khai bao hang so va o cau canh bao warn() ngay ca
     * khi ->limit() bi xoa khoi cau truy van, nen test doc nguon co the bi "gia mu". Dung
     * TRAN_NHAT + 5 ho so du dieu kien va kiem dung SO LUONG job duoc xep hang, khong vuot
     * qua TRAN_NHAT.
     *
     * @test
     */
    public function nhat_ho_so_khong_vuot_tran_du_co_nhieu_ung_vien_hon()
    {
        Bus::fake();
        config(['organization.chung_tu_dien_tu.import_tu_dong_gui' => true]);

        for ($i = 0; $i < CtdtImport::TRAN_NHAT + 5; $i++) {
            \App\Models\BHYT\Ctdt\CtdtHoSo::create([
                'ma_ho_so' => 'YT-TRAN-' . $i, 'dich_vu' => 'CT2025', 'loai_hs' => '39',
                'macskcb' => '01001', 'imported_at' => now(),
                'checked_at' => now(), 'so_loi' => 0,
            ]);
        }

        $lenh = new CtdtImportLoRa();
        $lenh->ganInputTest(new ArrayInput([], $lenh->getDefinition()));
        $lenh->ganOutputTest(new BufferedOutput());
        $soXep = $lenh->nhatVaXepHangCong(sys_get_temp_dir());

        $this->assertLessThanOrEqual(CtdtImport::TRAN_NHAT, $soXep,
            'Khong duoc xep hang vuot qua TRAN_NHAT trong mot vong, du co nhieu ung vien hon');
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

    /**
     * Hanh vi that cua phanh tay: tep DUNG-GUI phai chan duocGui() du import_tu_dong_gui
     * DANG BAT - day la ly do coDung() phai duoc hoi TRUOC phep hoi config trong duocGui(),
     * khong phai chi ton tai o dau do trong ham.
     *
     * @test
     */
    public function tep_co_dung_chan_nhatVaXepHang_du_import_tu_dong_gui_dang_bat()
    {
        Bus::fake();
        config(['organization.chung_tu_dien_tu.import_tu_dong_gui' => true]);

        \App\Models\BHYT\Ctdt\CtdtHoSo::create([
            'ma_ho_so' => 'YT-CO-DUNG', 'dich_vu' => 'CT2025', 'loai_hs' => '39',
            'macskcb' => '01001', 'imported_at' => now(),
            'checked_at' => now(), 'so_loi' => 0,
        ]);

        $thuMuc = $this->thuMucTam();
        touch($thuMuc . DIRECTORY_SEPARATOR . CtdtImport::TEP_CO_DUNG);

        $lenh = new CtdtImportLoRa();
        $lenh->ganInputTest(new ArrayInput([], $lenh->getDefinition()));
        $lenh->ganOutputTest(new BufferedOutput());
        $soXep = $lenh->nhatVaXepHangCong($thuMuc);

        $this->assertSame(0, $soXep, 'Co tep DUNG-GUI thi khong duoc xep hang gi ca');
        Bus::assertNotDispatched(\App\Jobs\SignCtdtJob::class);
    }

    /**
     * Quy uoc CHUNG cua module (CtdtDanhSach.php, CtdtTrangThaiGui::cua()): ma_ket_qua =
     * '0' la "chua co ket qua", GIONG NULL va chuoi rong - vi PHP coi empty('0') === true.
     * Bo loc SQL cua nhatVaXepHang() phai khop dung quy uoc nay: neu thieu nhanh
     * ->orWhere('ma_ket_qua', '0'), mot ho so cong BHXH tra ve ma_ket_qua = '0' se bi coi la
     * "da co ket qua" va vinh vien khong duoc nhat lai, trong khi man danh sach van hien no
     * la "Cho gui"/"Gui that bai".
     *
     * @test
     */
    public function ho_so_ma_ket_qua_bang_0_van_duoc_coi_la_chua_co_ket_qua_va_duoc_xep_hang()
    {
        Bus::fake();
        config(['organization.chung_tu_dien_tu.import_tu_dong_gui' => true]);

        \App\Models\BHYT\Ctdt\CtdtHoSo::create([
            'ma_ho_so' => 'YT-MAKETQUA-0', 'dich_vu' => 'CT2025', 'loai_hs' => '39',
            'macskcb' => '01001', 'imported_at' => now(),
            'checked_at' => now(), 'so_loi' => 0, 'ma_ket_qua' => '0',
        ]);

        $lenh = new CtdtImportLoRa();
        $lenh->ganInputTest(new ArrayInput([], $lenh->getDefinition()));
        $lenh->ganOutputTest(new BufferedOutput());
        $lenh->nhatVaXepHangCong(sys_get_temp_dir());

        Bus::assertDispatched(\App\Jobs\SignCtdtJob::class, function ($job) {
            return $this->maHoSoCuaJob($job) === 'YT-MAKETQUA-0';
        });
    }

    /**
     * CRITICAL - vong gui lai vo han sau khi cong DA NHAN goi.
     *
     * Kich ban that: cong nhan goi roi mang chap luc doc phan hoi.
     * CtdtSubmitService::gui() nem, SubmitCtdtJob CO Y khong bat (de hang doi thu lai), het
     * 3 tries thi failed() ghi submit_error va nha khoa - nhung ma_ket_qua VAN NULL. Neu bo
     * loc cua nhatVaXepHang() khong loai ho so co submit_error, vong sau (mac dinh 5 giay)
     * nhat lai dung no va POST LAI, mai mai.
     *
     * Test nay chay THAT ca hai nua: SubmitCtdtJob::handle() voi FakeCtdtSubmitService nem
     * (offline, khong cham cong BHXH), roi failed(), roi hoi lai nhatVaXepHang().
     *
     * @test
     */
    public function job_gui_nem_roi_that_bai_thi_lenh_nen_KHONG_gui_lai_ho_so_do()
    {
        config(['organization.chung_tu_dien_tu.import_tu_dong_gui' => true]);

        $hoSo = \App\Models\BHYT\Ctdt\CtdtHoSo::create([
            'ma_ho_so' => 'YT-MANG-CHAP', 'dich_vu' => 'CT2025', 'loai_hs' => '39',
            'macskcb' => '01001', 'imported_at' => now(),
            'checked_at' => now(), 'so_loi' => 0, 'is_signed' => true,
            'duong_dan_da_ky' => 'da-ky/YT-MANG-CHAP.xml',
        ]);

        $gia = new \Tests\Support\FakeCtdtSubmitService();
        $gia->nem = new \RuntimeException('cURL error 28: Operation timed out');

        \Illuminate\Support\Facades\Storage::fake('exportCtdt');
        \Illuminate\Support\Facades\Storage::disk('exportCtdt')
            ->put('da-ky/YT-MANG-CHAP.xml', '<GIAMDINHHS/>');

        $job = new \App\Jobs\SubmitCtdtJob('YT-MANG-CHAP', null,
            \App\Models\BHYT\Ctdt\CtdtLichSuGui::NGUON_CONSOLE);
        $job->submitServiceGia = $gia;

        $daNem = null;

        try {
            $job->handle();
        } catch (\Exception $e) {
            $daNem = $e;
        }

        $this->assertNotNull($daNem, 'Job PHAI de ngoai le bay ra cho hang doi thu lai');
        $this->assertSame(1, $gia->soLanGoi, 'Cong da duoc goi dung mot lan');

        // Het luot thu: hang doi goi failed(). ma_ket_qua VAN NULL - do chinh la cai bay.
        $job->failed($daNem);

        $hoSo = $hoSo->fresh();
        $this->assertNull($hoSo->ma_ket_qua, 'Dieu kien cua bay: ma_ket_qua van trong');
        $this->assertNotEmpty($hoSo->submit_error, 'failed() phai ghi submit_error');

        Bus::fake();

        $lenh = new CtdtImportLoRa();
        $lenh->ganInputTest(new ArrayInput([], $lenh->getDefinition()));
        $lenh->ganOutputTest(new BufferedOutput());
        $soXep = $lenh->nhatVaXepHangCong(sys_get_temp_dir());

        $this->assertSame(0, $soXep, 'Ho so da mot lan goi cong that bai KHONG duoc lenh nen '
            . 'gui lai tu dong - gui lai la viec cua NGUOI bam nut tren man chi tiet');
        Bus::assertNotDispatched(\App\Jobs\SignCtdtJob::class);
    }

    /**
     * CRITICAL - lop chan thu hai: da co dong trong ctdt_lich_su_gui.
     *
     * Lop submit_error bat luc job KIP GHI. Lop nay bat mot thoi diem khac cua cung cau
     * chuyen: cong DA tra loi mot lan (ke ca tu choi), dong nhat ky duoc ghi ngay trong
     * ghiKetQua() - truoc ca khi ai kip doc ho so. Test nay chay SubmitCtdtJob::handle()
     * THAT voi FakeCtdtSubmitService tra ve mot ma tu choi, roi xoa submit_error di de CHI
     * CON lop nhat ky lam viec.
     *
     * @test
     */
    public function ho_so_da_co_dong_lich_su_gui_thi_lenh_nen_KHONG_gui_lai()
    {
        config(['organization.chung_tu_dien_tu.import_tu_dong_gui' => true]);

        $hoSo = \App\Models\BHYT\Ctdt\CtdtHoSo::create([
            'ma_ho_so' => 'YT-DA-GOI-CONG', 'dich_vu' => 'CT2025', 'loai_hs' => '39',
            'macskcb' => '01001', 'imported_at' => now(),
            'checked_at' => now(), 'so_loi' => 0, 'is_signed' => true,
            'duong_dan_da_ky' => 'da-ky/YT-DA-GOI-CONG.xml',
        ]);

        $gia = new \Tests\Support\FakeCtdtSubmitService();
        // Cong TRA LOI, nhung khong phai 200 va khong co ma_ket_qua ro rang - dung dang lam
        // ho so o lai voi ma_ket_qua trong.
        $gia->ketQua = [
            'ma_ket_qua' => null, 'ma_gd' => null, 'thoi_gian_tiep_nhan' => null,
            'thong_diep' => 'Cong tra ve khong doc duoc', 'nguyen_van' => '<html>502</html>',
        ];

        \Illuminate\Support\Facades\Storage::fake('exportCtdt');
        \Illuminate\Support\Facades\Storage::disk('exportCtdt')
            ->put('da-ky/YT-DA-GOI-CONG.xml', '<GIAMDINHHS/>');

        $job = new \App\Jobs\SubmitCtdtJob('YT-DA-GOI-CONG', null,
            \App\Models\BHYT\Ctdt\CtdtLichSuGui::NGUON_CONSOLE);
        $job->submitServiceGia = $gia;
        $job->handle();

        $this->assertSame(1, \App\Models\BHYT\Ctdt\CtdtLichSuGui::where(
            'ma_ho_so', 'YT-DA-GOI-CONG')->count(),
            'Mot lan goi cong = mot dong nhat ky');

        // Xoa submit_error di: chi con MOT lop chan lam viec, nen neu test nay xanh thi
        // dung la lop ctdt_lich_su_gui dang chan, khong phai lop kia.
        $hoSo->fresh()->update(['submit_error' => null]);
        $this->assertNull(\App\Models\BHYT\Ctdt\CtdtHoSo::where('ma_ho_so', 'YT-DA-GOI-CONG')
            ->first()->ma_ket_qua, 'Dieu kien cua bay: ma_ket_qua van trong');

        Bus::fake();

        $lenh = new CtdtImportLoRa();
        $lenh->ganInputTest(new ArrayInput([], $lenh->getDefinition()));
        $lenh->ganOutputTest(new BufferedOutput());
        $soXep = $lenh->nhatVaXepHangCong(sys_get_temp_dir());

        $this->assertSame(0, $soXep, 'Ho so cong DA TUNG duoc goi cho no (co dong trong '
            . 'ctdt_lich_su_gui) KHONG duoc lenh nen gui lai tu dong');
        Bus::assertNotDispatched(\App\Jobs\SignCtdtJob::class);
    }

    /**
     * I5 - ky hong cung ket vong lap y het: SubmitCtdtJob ghi submit_error "chua ky so",
     * ma_ket_qua van NULL, nen vong sau nhat lai. Mot dem rut nham USB token la hang chuc
     * nghin job rac.
     *
     * Ho so o day co signed_error nhung submit_error CON TRONG - dung tinh huong chua kip
     * qua job gui lan nao, tuc lop submit_error khong bat duoc.
     *
     * @test
     */
    public function ho_so_ky_hong_khong_duoc_xep_hang_lai()
    {
        Bus::fake();
        config(['organization.chung_tu_dien_tu.import_tu_dong_gui' => true]);

        \App\Models\BHYT\Ctdt\CtdtHoSo::create([
            'ma_ho_so' => 'YT-KY-HONG', 'dich_vu' => 'CT2025', 'loai_hs' => '39',
            'macskcb' => '01001', 'imported_at' => now(),
            'checked_at' => now(), 'so_loi' => 0,
            'is_signed' => false, 'signed_error' => 'Khong tim thay USB token',
        ]);

        $lenh = new CtdtImportLoRa();
        $lenh->ganInputTest(new ArrayInput([], $lenh->getDefinition()));
        $lenh->ganOutputTest(new BufferedOutput());
        $soXep = $lenh->nhatVaXepHangCong(sys_get_temp_dir());

        $this->assertSame(0, $soXep, 'Ho so ky hong phai cho NGUOI xu ly, khong duoc xep lai '
            . 'moi vong');
        Bus::assertNotDispatched(\App\Jobs\SignCtdtJob::class);
    }

    /**
     * I4 - submit_enabled TAT thi khong xep chuoi nao, du import_tu_dong_gui dang BAT.
     *
     * Neu duocGui() khong hoi khoa nay: moi vong xep toi TRAN_NHAT chuoi job,
     * SubmitCtdtJob tra KHONG_GUI va CO Y khong ghi gi ca, nen ma_ket_qua van NULL va vong
     * sau nhat lai y nguyen. Ngap hang doi, va 200 ho so ket o dau
     * orderBy('imported_at')->limit(200) chan vinh vien moi ho so moi.
     *
     * @test
     */
    public function submit_enabled_tat_thi_khong_xep_hang_gi_ca()
    {
        Bus::fake();
        config([
            'organization.chung_tu_dien_tu.import_tu_dong_gui' => true,
            'organization.chung_tu_dien_tu.submit_enabled'     => false,
        ]);

        \App\Models\BHYT\Ctdt\CtdtHoSo::create([
            'ma_ho_so' => 'YT-SUBMIT-TAT', 'dich_vu' => 'CT2025', 'loai_hs' => '39',
            'macskcb' => '01001', 'imported_at' => now(),
            'checked_at' => now(), 'so_loi' => 0,
        ]);

        $dauRa = new BufferedOutput();
        $lenh = new CtdtImportLoRa();
        $lenh->ganInputTest(new ArrayInput([], $lenh->getDefinition()));
        $lenh->ganOutputTest($dauRa);
        $soXep = $lenh->nhatVaXepHangCong(sys_get_temp_dir());

        $this->assertSame(0, $soXep, 'submit_enabled tat phai khong xep hang gi ca');
        Bus::assertNotDispatched(\App\Jobs\SignCtdtJob::class);
        $this->assertContains('submit_enabled', $dauRa->fetch(),
            'Phai canh bao DOC DUOC vi sao khong xep hang, khong im lang bo qua');
    }

    /**
     * I2/I3 - HANH VI that cua --khong-gui, khong phai chi "tuy chon co ton tai".
     *
     * --khong-gui dung CA chuoi ky-gui chu khong chi buoc gui: CtdtXepHangKyGui::xep() xep
     * SignCtdtJob -> SubmitCtdtJob nhu MOT KHOI, khong tach duoc o tang nay. Mo ta trong
     * $signature va tai lieu phai noi dung dieu do, va test nay la thu giu cho no dung.
     *
     * @test
     */
    public function khong_gui_dung_ca_buoc_KY_chu_khong_chi_buoc_gui()
    {
        Bus::fake();
        config(['organization.chung_tu_dien_tu.import_tu_dong_gui' => true]);

        \App\Models\BHYT\Ctdt\CtdtHoSo::create([
            'ma_ho_so' => 'YT-KHONG-GUI', 'dich_vu' => 'CT2025', 'loai_hs' => '39',
            'macskcb' => '01001', 'imported_at' => now(),
            'checked_at' => now(), 'so_loi' => 0,
        ]);

        $lenh = new CtdtImportLoRa();
        $lenh->ganInputTest(new ArrayInput(['--khong-gui' => true], $lenh->getDefinition()));
        $lenh->ganOutputTest(new BufferedOutput());
        $soXep = $lenh->nhatVaXepHangCong(sys_get_temp_dir());

        $this->assertSame(0, $soXep, '--khong-gui phai chan ca viec xep chuoi ky');
        // KHONG co SignCtdtJob nao: day moi la khac biet giua loi van cu ("Ky nhung khong
        // gui") va hanh vi that.
        Bus::assertNotDispatched(\App\Jobs\SignCtdtJob::class);
    }

    /**
     * Mo ta cua --khong-gui trong $signature phai KHOP hanh vi o test ngay tren.
     *
     * Ban cu ghi "Ky nhung khong gui len cong" - sai, va nguoi van hanh doc bang "nam cai
     * phanh" tuong ho so duoc ky san de chi con bam nut gui.
     *
     * @test
     */
    public function mo_ta_khong_gui_khong_duoc_hua_la_van_ky()
    {
        $mo = (new CtdtImport())->getDefinition()->getOption('khong-gui')->getDescription();

        $this->assertNotContains('Ky nhung khong gui', $mo,
            'Mo ta cu hua "van ky" - hanh vi that la khong ky ho so nao');
        $this->assertContains('khong ky', $mo,
            'Mo ta phai noi ro --khong-gui dung CA buoc ky');
    }

    /** @test */
    public function tep_co_dung_chan_ngay_buoc_gui()
    {
        // Mot tien trinh song mai GIU CONFIG TRONG BO NHO. Sua import_tu_dong_gui thanh
        // false KHONG an thua cho toi khi ai do nssm restart - va mot cai phanh chi an sau
        // khi khoi dong lai thi khong phai la phanh. Nguoi truc dem phai dung duoc bang mot
        // thao tac ho lam duoc: tao mot tep rong.
        $thuMuc = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'ctdt-thu-' . mt_rand();
        mkdir($thuMuc);

        $lenh = new CtdtImport();

        $this->assertFalse($lenh->coDung($thuMuc), 'Chua co tep co thi khong duoc dung');

        touch($thuMuc . DIRECTORY_SEPARATOR . CtdtImport::TEP_CO_DUNG);

        $this->assertTrue($lenh->coDung($thuMuc),
            'Co tep DUNG-GUI thi phai dung ngay, khong cho khoi dong lai dich vu');

        unlink($thuMuc . DIRECTORY_SEPARATOR . CtdtImport::TEP_CO_DUNG);
        rmdir($thuMuc);
    }

    /** @test */
    public function che_do_lien_tuc_co_TRAN_SO_VONG()
    {
        // PHP chay dai han o gioi han 128MB se phinh. Thoat chu dong de nssm dung lai ban
        // sach thi khac han bi OOM giet giua luc dang goi cong BHXH.
        $macDinh = (new CtdtImport())->getDefinition()->getOption('so-vong')->getDefault();

        $this->assertNotNull($macDinh, 'Phai co tran so vong');
        $this->assertGreaterThan(0, (int) $macDinh);
    }

    /** @test */
    public function che_do_lien_tuc_co_nghi_giua_hai_vong()
    {
        // Khong nghi la mot vong lap ban CPU va do log khong ngung.
        $macDinh = (new CtdtImport())->getDefinition()->getOption('nghi')->getDefault();

        $this->assertGreaterThan(0, (int) $macDinh);
    }

    /** @test */
    public function dry_run_KHONG_duoc_chay_lien_tuc()
    {
        // --dry-run la de nguoi ta NHIN mot lan roi quyet dinh. Gap voi --lien-tuc thi no do
        // log mai ma khong lam gi ca - va che mat dong log that.
        $nguon = file_get_contents(base_path('app/Console/Commands/CtdtImport.php'));

        $this->assertContains('dry-run', $nguon);
        $this->assertRegExp(
            '/lien-tuc.{0,400}dry-run|dry-run.{0,400}lien-tuc/s',
            $nguon,
            'Phai co cho tu choi ket hop --dry-run voi --lien-tuc'
        );
    }

    /**
     * Phanh khoa mo coi: --go-khoa phai go duoc Cache::KHOA_LUOT va THOAT NGAY, khong xep
     * hang ho so nao ca - du co ho so du dieu kien va import_tu_dong_gui dang bat. Neu
     * --go-khoa lai chay tiep sang nhat/xep hang thi no khong con la lenh cap cuu don gian
     * nua, ma la mot luot ctdt:import day du nguy trang bang mot co khac.
     *
     * @test
     */
    public function go_khoa_go_khoa_mo_coi_va_khong_xep_hang_gi_ca()
    {
        Bus::fake();
        Cache::put(CtdtImport::KHOA_LUOT, true, CtdtImport::KHOA_LUOT_PHUT);
        config(['organization.chung_tu_dien_tu.import_tu_dong_gui' => true]);

        \App\Models\BHYT\Ctdt\CtdtHoSo::create([
            'ma_ho_so' => 'YT-GO-KHOA', 'dich_vu' => 'CT2025', 'loai_hs' => '39',
            'macskcb' => '01001', 'imported_at' => now(),
            'checked_at' => now(), 'so_loi' => 0,
        ]);

        $maThoat = Artisan::call('ctdt:import', ['--go-khoa' => true]);

        $this->assertSame(0, $maThoat);
        $this->assertFalse(Cache::has(CtdtImport::KHOA_LUOT), 'Khoa phai duoc go that');
        Bus::assertNotDispatched(\App\Jobs\SignCtdtJob::class);
    }

    /**
     * Bit lo hong da biet cua che_do_lien_tuc_co_TRAN_SO_VONG(): test do CHI doc dinh nghia
     * tuy chon, khong chay lenh, nen mot mutation doi `for` thanh `while (true)` van xanh o
     * do. Day la test HANH VI thay the.
     *
     * SUA THEO CODE REVIEW: ban dau test nay goi Artisan::call() TRONG CUNG tien trinh
     * PHPUnit - neu mutation lam vong lap that su vo han, test khong FAIL ma TREO VINH VIEN,
     * va vi no nam trong tests/Unit/Ctdt (thu muc nghiem thu ca module moi lan chay) no keo
     * theo treo ca bo suite. Sua bang cach chay lenh trong MOT TIEN TRINH CON qua proc_open()
     * (khong can pcntl - PHPUnit 6 tren PHP 7.4 khong co enforceTimeLimit khi thieu pcntl,
     * va may nay chay Windows nen khong co pcntl), rồi doi co gioi han bang proc_get_status()
     * - qua han thi proc_terminate() va coi la TREO, bien "treo ca suite" thanh mot assertion
     * FAIL co kiem soat sau toi da 60 giay. Thu muc tam RONG nen tien trinh con khong dung
     * toi CSDL that (dsTep() tra ve mang rong, quet() thoat truoc khi cham CtdtHoSo).
     *
     * @test
     */
    public function che_do_lien_tuc_thoat_dung_so_vong_da_dat_khong_treo()
    {
        $thuMuc = $this->thuMucTam();

        // Cache::store('file') la cache THAT cua may, khong phai store 'array' ma
        // phpunit.xml tro CACHE_DRIVER vao. Neu no da co khoa TRUOC khi test chay thi moi
        // truong khong sach (mot dich vu nssm dang chay, hoac mot khoa mo coi con sot) -
        // noi ro ra thay vi de assertion cuoi cung do voi thong diep sai nguyen nhan.
        $this->assertFalse(Cache::store('file')->has(CtdtImport::KHOA_LUOT),
            'Cache THAT dang giu khoa ' . CtdtImport::KHOA_LUOT . ' TRUOC khi test chay - '
            . 'co tien trinh ctdt:import khac dang song, hoac con khoa mo coi. Go bang '
            . '`php artisan ctdt:import --go-khoa` roi chay lai.');

        $ketQua = $this->chayTienTrinhConCoHan([
            '--lien-tuc',
            '--so-vong=3',
            '--nghi=1',
            '--khong-ky',
            '--duong-dan=' . $thuMuc,
        ], 60);

        // Tien trinh con phai chay tren CACHE_DRIVER=array cua rieng no (xem $moiTruong
        // trong chayTienTrinhConCoHan()). Neu no roi ve .env that (CACHE_DRIVER=file) thi
        // nhanh "treo" o tren giet no bang tin hieu 9 - finally KHONG chay - va khoa nay o
        // lai tren cache THAT suot 24 gio, lam dich vu nssm ngung chay im lang ca ngay.
        $this->assertFalse(Cache::store('file')->has(CtdtImport::KHOA_LUOT),
            'Tien trinh con da dat khoa len cache THAT cua may - moi truong tuong minh '
            . 'truyen vao proc_open() khong con tac dung. Xem $moiTruong trong '
            . 'chayTienTrinhConCoHan().');

        $this->assertFalse($ketQua['treo'], 'Lenh phai TU THOAT trong 60 giay - neu vong lap '
            . 'khong tu thoat (vi du bi doi thanh while(true)) tien trinh con bi giet cuong '
            . 'buc va assertion nay FAIL co kiem soat, thay vi treo ca bo test.'
            . " Dau ra thu duoc truoc khi giet:\n" . $ketQua['dau_ra'] . $ketQua['loi']);
        $this->assertSame(0, $ketQua['ma_thoat'],
            'Lenh phai thoat ma 0 sau dung so vong. Loi: ' . $ketQua['loi']);
        $this->assertContains('Da chay du 3 vong', $ketQua['dau_ra'],
            'Phai in dung dong tong ket voi dung so vong da chay');
    }

    /**
     * Chay `php artisan ctdt:import <thamSo>` trong mot TIEN TRINH CON, doi toi da $hanGiay
     * giay. Khong dung pcntl (PHPUnit 6 / PHP 7.4, may Windows khong co pcntl) - poll bang
     * proc_get_status() moi 200ms, qua han thi proc_terminate() va bao 'treo' => true thay
     * vi de tien trinh con chay mai lam treo ca tien trinh PHPUnit cha.
     *
     * STDOUT/STDERR CUA TIEN TRINH CON DUOC DAN VAO TEP, KHONG PHAI ONG (pipe). Da tu kiem
     * thuc te tren may Windows nay: dat stream_set_blocking(false) roi doc bang
     * stream_get_contents() (co hoac khong gioi han do dai) van khong thoat khoi lan doc
     * DAU TIEN khi tien trinh con dang phun du lieu lien tuc khong ngung (dung mutation vong
     * lap vo han lam) - non-blocking read tren ong cua proc_open tren Windows la mot loi
     * PHP/Windows da biet tu lau, khong dang tin cay. Dan ra tep thi vong doi chi con
     * proc_get_status() - khong doc gi ca cho toi khi tien trinh da ket thuc hoac bi giet -
     * nen khong con phu thuoc hanh vi non-blocking cua ong tren Windows nua.
     *
     * @param  string[] $thamSo   vi du ['--lien-tuc', '--so-vong=3']
     * @param  int       $hanGiay
     * @return array{treo: bool, ma_thoat: int|null, dau_ra: string, loi: string}
     */
    private function chayTienTrinhConCoHan(array $thamSo, $hanGiay)
    {
        // Dang MANG, KHONG dang chuoi: proc_open() voi chuoi lenh tren Windows di qua
        // cmd.exe, va escapeshellarg() cua PHP tren Windows chi bao trong dau nhay kep chu
        // khong xu ly dung moi to hop dau \ - gap gia tri co dau \ (moi duong dan Windows
        // deu co) se ra loi he dieu hanh "The filename, directory name, or volume label
        // syntax is incorrect" thay vi chay lenh. Dang mang goi thang CreateProcess(), bo
        // qua ca cmd.exe lan escapeshellarg().
        $lenh = array_merge([PHP_BINARY, 'artisan', 'ctdt:import'], $thamSo);

        $tepDauRa = tempnam(sys_get_temp_dir(), 'ctdt-test-out-');
        $tepLoi = tempnam(sys_get_temp_dir(), 'ctdt-test-err-');

        $moTaOng = [
            0 => ['pipe', 'r'],
            1 => ['file', $tepDauRa, 'w'],
            2 => ['file', $tepLoi, 'w'],
        ];

        // MOI TRUONG TUONG MINH cho tien trinh con. TestCase::setUp() va phpunit.xml chi ap
        // cho tien trinh PHPUnit NAY - tien trinh con doc thang .env cua du an, tuc
        // DB_DATABASE=qlbv (CSDL phat trien THAT) va CACHE_DRIVER=file (cache THAT cua may).
        // Truoc day khong truyen gi ca, va an toan chi nho thu muc tam rong + --khong-ky -
        // khong assertion nao canh. Nang hon: nhanh "treo" goi proc_terminate(..., 9), bo
        // lai khoa ctdt:import:dang-chay tren cache THAT voi han 24 gio, lam dich vu nssm
        // ngung chay im lang ca ngay.
        //
        // proc_open($cmd, $ong, $pipes, $cwd, $env): truyen $env la THAY the toan bo moi
        // truong, khong phai gop them - nen phai mang theo ca PATH/SystemRoot, thieu chung
        // thi php.exe tren Windows khong khoi dong duoc.
        $moiTruong = [
            'DB_CONNECTION' => 'sqlite',
            'DB_DATABASE'   => ':memory:',
            'CACHE_DRIVER'  => 'array',
            'QUEUE_DRIVER'  => 'sync',
            'SESSION_DRIVER' => 'array',
            'APP_ENV'       => 'testing',
        ];

        foreach (['PATH', 'Path', 'SystemRoot', 'windir', 'TEMP', 'TMP', 'COMSPEC', 'ComSpec'] as $giu) {
            $giaTri = getenv($giu);

            if ($giaTri !== false) {
                $moiTruong[$giu] = $giaTri;
            }
        }

        $tienTrinh = proc_open($lenh, $moTaOng, $ong, base_path(), $moiTruong);

        if (!is_resource($tienTrinh)) {
            @unlink($tepDauRa);
            @unlink($tepLoi);
            $this->fail('Khong khoi dong duoc tien trinh con: ' . implode(' ', $lenh));
        }

        fclose($ong[0]);

        $batDau = microtime(true);
        $daTreo = false;

        while (true) {
            $trangThai = proc_get_status($tienTrinh);

            if (!$trangThai['running']) {
                break;
            }

            if (microtime(true) - $batDau > $hanGiay) {
                $daTreo = true;
                proc_terminate($tienTrinh, 9);
                // Cho toi da 3 giay de he dieu hanh don dep tien trinh sau khi giet, tranh
                // proc_close() khoa cung tien trinh chua kip thoat han.
                $choDon = microtime(true);
                do {
                    $trangThai = proc_get_status($tienTrinh);
                    usleep(50000);
                } while ($trangThai['running'] && microtime(true) - $choDon < 3);
                break;
            }

            usleep(200000);
        }

        $maThoat = proc_close($tienTrinh);

        // Chi giu 65536 byte cuoi cung: mutation vong lap vo han co the phun ra hang chuc MB
        // trong $hanGiay giay, vua du de doc dong tong ket / thong bao loi cuoi cho assertion.
        $dauRa = $this->docCuoiTep($tepDauRa, 65536);
        $loi = $this->docCuoiTep($tepLoi, 65536);
        @unlink($tepDauRa);
        @unlink($tepLoi);

        return [
            'treo'     => $daTreo,
            'ma_thoat' => $daTreo ? null : $maThoat,
            'dau_ra'   => $dauRa,
            'loi'      => $loi,
        ];
    }

    /** Doc toi da $soByte byte CUOI CUNG cua mot tep, khong nap ca tep neu no rat lon. */
    private function docCuoiTep($duongDan, $soByte)
    {
        if (!is_file($duongDan)) {
            return '';
        }

        $kichThuoc = filesize($duongDan);
        $tay = fopen($duongDan, 'rb');

        if ($tay === false) {
            return '';
        }

        if ($kichThuoc > $soByte) {
            fseek($tay, -$soByte, SEEK_END);
        }

        $noiDung = stream_get_contents($tay);
        fclose($tay);

        return (string) $noiDung;
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

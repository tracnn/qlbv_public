<?php

namespace Tests\Unit\Ctdt;

use Tests\TestCase;
use Tests\Support\DungBangCtdtSqlite;
use App\Models\BHYT\Ctdt\CtdtHoSo;
use App\Services\Ctdt\CtdtTrangThaiGui;
use App\Services\Dashboard\CtdtDashboardService;

/**
 * Cot "So loi" bang 0 khi worker JobCtdt khong chay trong Y HET nhu moi ho so deu sach.
 * Man hinh nay la thu duy nhat bat duoc chuyen do - nen chinh no khong duoc noi doi.
 */
class CtdtDashboardTest extends TestCase
{
    // KHONG DatabaseMigrations: trait do goi migrate:fresh, tuc DROP toan bo bang cua CSDL
    // phat trien. Da xay ra that ngay 2026-08-21.
    use DungBangCtdtSqlite;

    protected function setUp()
    {
        parent::setUp();
        $this->chuanBiBangCtdt();
    }

    private function hoSo(array $ghiDe = [])
    {
        return CtdtHoSo::create(array_merge([
            'ma_ho_so'    => 'YT' . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT),
            'dich_vu'     => 'CT2025',
            'loai_hs'     => '39',
            'macskcb'     => '01001',
            'imported_at' => '2026-08-20 08:00:00',
            'so_loi'      => 0,
        ], $ghiDe));
    }

    /** @test */
    public function liet_ke_DU_CHIN_trang_thai_ke_ca_cai_bang_khong()
    {
        // Trang thai bien mat khoi bieu do khi bang 0 la trang thai khong ai theo doi duoc.
        // "Ky so that bai: 0" la mot thong tin; mot cot vang la mot cau hoi.
        $kq = (new CtdtDashboardService())->sucKhoe([]);

        $this->assertCount(
            count(CtdtTrangThaiGui::NHAN),
            $kq['theo_trang_thai'],
            'Phai liet ke du moi trang thai, ke ca cai dang bang 0'
        );
    }

    /** @test */
    public function dem_theo_trang_thai_khop_voi_CtdtTrangThaiGui()
    {
        // Man hinh bao mot dang, bo loc ngay ben canh bao mot neo la nguoi dung thoi tin ca
        // hai. Day la ly do khong duoc viet ban SQL thu ba cho luat trang thai.
        $this->hoSo(['checked_at' => null]);                                   // CHUA_KIEM
        $this->hoSo(['checked_at' => '2026-08-20 08:00:00', 'so_loi' => 3]);   // CON_LOI

        $kq = (new CtdtDashboardService())->sucKhoe([]);
        $dem = [];

        foreach ($kq['theo_trang_thai'] as $dong) {
            $dem[$dong['ma']] = $dong['so_luong'];
        }

        $this->assertSame(1, $dem[CtdtTrangThaiGui::CHUA_KIEM]);
        $this->assertSame(1, $dem[CtdtTrangThaiGui::CON_LOI]);
    }

    /** @test */
    public function nhan_lay_tu_CtdtTrangThaiGui_chu_khong_go_lai()
    {
        $kq = (new CtdtDashboardService())->sucKhoe([]);

        foreach ($kq['theo_trang_thai'] as $dong) {
            $this->assertSame(
                CtdtTrangThaiGui::nhan($dong['ma']),
                $dong['nhan'],
                'Nhan phai lay tu CtdtTrangThaiGui, khong duoc go lai'
            );
        }
    }

    /** @test */
    public function liet_ke_du_ba_hang_doi()
    {
        // Thieu worker nao thi moi ho so dung khung o buoc do. Ba hang doi deu BAT BUOC, nen
        // ca ba deu phai co mat tren man hinh - ke ca khi khong dem duoc.
        $kq = (new CtdtDashboardService())->sucKhoe([]);

        $this->assertCount(3, $kq['hang_doi']);
    }

    /** @test */
    public function khong_dem_duoc_hang_doi_thi_tra_null_chu_KHONG_tra_0()
    {
        // Hang doi dung driver khac 'database' thi khong co bang jobs de dem. Tra 0 o day la
        // NOI DOI: nguoi doc se thay "0 job dang cho" va yen tam, trong khi that ra man hinh
        // khong biet gi ca.
        config(['queue.default' => 'sync']);

        $kq = (new CtdtDashboardService())->sucKhoe([]);

        foreach ($kq['hang_doi'] as $hd) {
            $this->assertNull($hd['so_job'],
                'Khong dem duoc phai tra null, khong duoc tra 0');
        }
    }

    /** @test */
    public function ton_dong_dem_ho_so_chua_len_duoc_cong()
    {
        // "Ho so cu nhat chua gui da nam bao lau" la cau hoi bat duoc mot hang doi chet
        // cham - thu ma bieu do so luong khong bao gio chi ra.
        $this->hoSo(['checked_at' => null, 'imported_at' => '2026-08-01 08:00:00']);

        $kq = (new CtdtDashboardService())->sucKhoe([]);

        $this->assertSame(1, $kq['ton_dong']['so_ho_so']);
        $this->assertNotNull($kq['ton_dong']['cu_nhat_ngay']);
    }

    /** @test */
    public function blade_dashboard_bien_dich_duoc()
    {
        // Trang blade chi vo khi co nguoi MO no. detail.blade.php da chet suot tu Giai doan
        // 2B vi mot @if lot vao trong chu thich JavaScript, va khong test nao bat duoc.
        $noiDung = file_get_contents(resource_path('views/dashboard/ctdt.blade.php'));

        \Illuminate\Support\Facades\Blade::compileString($noiDung);

        $this->assertTrue(true, 'compileString() nem thi test do o dong tren');
    }

    /** @test */
    public function trang_index_render_duoc_that_su_qua_HTTP_layer()
    {
        // compileString() chi bat loi CU PHAP. Loi RUNTIME (bien thieu, quan he chua nap,
        // route() khong ton tai, composer menu cua AppServiceProvider nem loi) chi lo ra
        // khi thuc su render qua dung duong HTTP - dung bai hoc Giai doan 5B: test chi
        // cham toi tang gan nhat (map()/compileString()) thi bo sot loi o tang xa hon
        // (query()/render() that).
        $response = $this->actingAs($this->nguoiDungGia())->get('/dashboard/ctdt');

        $response->assertStatus(200);
        $response->assertSee('chart-trang-thai', false);
    }

    /** @test */
    public function endpoint_suc_khoe_tra_du_ba_khoa_that_su_qua_HTTP_layer()
    {
        // Moi test khac goi thang new CtdtDashboardService(). Test nay di qua ca tang
        // Route + Middleware + Controller - dung mau hinh da can o Giai doan 5B: hai loi
        // Critical nam dung o tang khong test nao cham toi.
        $response = $this->actingAs($this->nguoiDungGia())->getJson('/dashboard/ctdt/suc-khoe');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'theo_trang_thai' => [['ma', 'nhan', 'so_luong']],
                'hang_doi'        => [['ten', 'so_job']],
                'ton_dong'        => ['so_ho_so', 'cu_nhat_ngay'],
            ]);

        $kq = $response->json();

        $this->assertCount(count(CtdtTrangThaiGui::NHAN), $kq['theo_trang_thai']);
        $this->assertCount(3, $kq['hang_doi']);
    }

    /** @test */
    public function san_luong_khong_bo_trong_ngay_khong_co_ho_so()
    {
        // GROUP BY chi tra ve ngay CO du lieu. Ve thang len bieu do duong thi mot ngay he
        // thong chet hoan toan se bien mat khoi truc - duong noi lien tu ngay truoc sang
        // ngay sau, trong y het nhu khong co gi xay ra.
        $this->hoSo(['imported_at' => '2026-08-01 08:00:00']);
        $this->hoSo(['imported_at' => '2026-08-03 08:00:00']);

        $kq = (new CtdtDashboardService())->sanLuong([
            'tu_ngay' => '2026-08-01', 'den_ngay' => '2026-08-03',
        ]);

        $this->assertSame(['2026-08-01', '2026-08-02', '2026-08-03'], $kq['ngay'],
            'Ngay khong co ho so van phai co mat, voi gia tri 0');
    }

    /** @test */
    public function san_luong_tach_theo_dich_vu()
    {
        // Ba dich vu CT2025 / GBT / GCS di ba duong khac nhau len cong. Gop chung mot duong
        // thi mot dich vu chet han cung khong nhin ra.
        $this->hoSo(['dich_vu' => 'CT2025', 'imported_at' => '2026-08-01 08:00:00']);
        $this->hoSo(['dich_vu' => 'GBT', 'imported_at' => '2026-08-01 09:00:00']);

        $kq = (new CtdtDashboardService())->sanLuong([
            'tu_ngay' => '2026-08-01', 'den_ngay' => '2026-08-01',
        ]);

        $ten = array_column($kq['chuoi'], 'ten');

        $this->assertContains('CT2025', $ten);
        $this->assertContains('GBT', $ten);
    }

    /** @test */
    public function san_luong_do_dai_moi_chuoi_bang_so_ngay()
    {
        // Lech mot phan tu la moi diem tu do tro di roi sai ngay tren truc - va bieu do van
        // trong hoan toan binh thuong.
        $this->hoSo(['imported_at' => '2026-08-02 08:00:00']);

        $kq = (new CtdtDashboardService())->sanLuong([
            'tu_ngay' => '2026-08-01', 'den_ngay' => '2026-08-05',
        ]);

        foreach ($kq['chuoi'] as $chuoi) {
            $this->assertCount(count($kq['ngay']), $chuoi['du_lieu']);
        }
    }

    /** @test */
    public function endpoint_san_luong_tra_du_hai_khoa_that_su_qua_HTTP_layer()
    {
        // Khong dang nhap duoc de kiem bang mat trong phien nay, nen bu lai bang mot test
        // di qua dung duong Route + Middleware + Controller - cung mau voi
        // endpoint_suc_khoe_tra_du_ba_khoa_that_su_qua_HTTP_layer() o tren.
        $this->hoSo(['imported_at' => '2026-08-01 08:00:00']);

        $response = $this->actingAs($this->nguoiDungGia())->getJson(
            '/dashboard/ctdt/san-luong?tu_ngay=2026-08-01&den_ngay=2026-08-01'
        );

        $response->assertStatus(200)
            ->assertJsonStructure([
                'ngay',
                'chuoi' => [['ten', 'du_lieu']],
            ]);
    }

    /**
     * User gia thoa CheckRole middleware ma khong dung bang roles trong DB - cung mau
     * FakeAdminUser cua tests/Feature/Dashboard/Xml3176DashboardControllerTest.php.
     */
    private function nguoiDungGia()
    {
        return new class extends \App\User {
            public function hasRole($role, $team = null, $requireAll = false) { return true; }
            public function can($permission, $team = null, $requireAll = false) { return true; }
        };
    }

    /** @test */
    public function ton_dong_dem_CA_ho_so_co_ma_ket_qua_la_chuoi_0()
    {
        // NGUYEN TAC: "chua co ket qua tu cong" chi co MOT dinh nghia, o
        // CtdtDanhSach::chuaCoKetQua() - NULL / '' / '0'. Cong BHXH la he ngoai, ta khong
        // kiem soat duoc no tra gia tri gi. PHP coi empty('0') === true, nen mot ban sao
        // quen '0' se bao thieu dung luc khoi ton dong can chinh xac nhat.
        $this->hoSo([
            'checked_at'  => '2026-08-01 08:00:00',
            'so_loi'      => 0,
            'is_signed'   => true,
            'ma_ket_qua'  => '0',
            'imported_at' => '2026-08-01 08:00:00',
        ]);

        $kq = (new CtdtDashboardService())->sucKhoe([]);

        $this->assertSame(1, $kq['ton_dong']['so_ho_so'],
            "Ho so ma_ket_qua = '0' phai duoc dem la chua co ket qua tu cong");
    }

    /** @test */
    public function hang_doi_driver_database_nhung_bang_jobs_chua_migrate_thi_tra_null_khong_nem()
    {
        // Nhanh de xay ra that: may chu moi cai chua chay queue:table. SQLite bo nho cua
        // DungBangCtdtSqlite chi dung 13 bang cua module CTDT, KHONG co bang jobs - dung
        // that nhanh catch nay ma khong can dung gia lap gi them.
        config(['queue.default' => 'database']);

        $kq = (new CtdtDashboardService())->sucKhoe([]);

        foreach ($kq['hang_doi'] as $hd) {
            $this->assertNull($hd['so_job'],
                'Bang jobs vang mat van phai tra null, khong duoc nem loi ra ngoai');
        }
    }

    private function ghiLoi(CtdtHoSo $hoSo, array $ghiDe = [])
    {
        return \App\Models\BHYT\Ctdt\CtdtLoi::create(array_merge([
            'ho_so_id' => $hoSo->id,
            'ma_loi'   => 'L01',
            'ten_truong' => 'ho_ten',
            'mo_ta'    => 'Sai ten',
            'muc_do'   => 'chan',
        ], $ghiDe));
    }

    /** @test */
    public function chat_luong_co_tran_so_dong()
    {
        // Xep hang khong tran thi mot he thong co 400 ma loi se do het ra bieu do va khong
        // ai doc duoc gi.
        //
        // Ban truoc cua test nay DOC VAN BAN ma nguon roi assertContains('limit(') - mot
        // test xanh gia: xoa CA HAI ->limit($soDong) di no van xanh, vi chuoi 'limit(' con
        // khop cai limit cua tran id ho so. Gieo du lieu that va dem so hang tra ve.
        $hoSo = $this->hoSo(['so_loi' => 20]);

        for ($i = 1; $i <= 20; $i++) {
            $this->ghiLoi($hoSo, ['ma_loi' => 'L' . str_pad($i, 2, '0', STR_PAD_LEFT)]);
        }

        $kq = (new CtdtDashboardService())->chatLuong([]);

        $this->assertLessThanOrEqual(15, count($kq['theo_ma_loi']),
            'Xep hang ma loi phai bi cat con toi da 15 hang');
    }

    /** @test */
    public function chat_luong_cham_tran_thi_giu_ho_so_MOI_NHAT_va_NOI_RA_la_da_cat()
    {
        // Khong co orderBy thi "lay 20000 ho so nao" la KHONG XAC DINH, va voi InnoDB thuc
        // te giu cai CU NHAT roi cat cai MOI nhat - nguoc han dieu can. Mot man chat luong
        // du lieu ma cat mat du lieu moi nhat la cat dung phan nguoi ta can nhin.
        $cu = $this->hoSo(['imported_at' => '2026-01-01 08:00:00', 'so_loi' => 1]);
        $giua = $this->hoSo(['imported_at' => '2026-06-01 08:00:00', 'so_loi' => 1]);
        $moi = $this->hoSo(['imported_at' => '2026-08-20 08:00:00', 'so_loi' => 1]);

        $this->ghiLoi($cu, ['ma_loi' => 'CU']);
        $this->ghiLoi($giua, ['ma_loi' => 'GIUA']);
        $this->ghiLoi($moi, ['ma_loi' => 'MOI']);

        // Tran = 2 tren ba ho so: bat buoc phai cat mot cai.
        $kq = (new CtdtDashboardService())->chatLuong(
            ['tu_ngay' => '2026-01-01', 'den_ngay' => '2026-08-20'], 15, 2
        );

        $maLoi = array_column($kq['theo_ma_loi'], 'ma_loi');

        $this->assertTrue(in_array('MOI', $maLoi, true), 'Phai giu ho so MOI nhat');
        $this->assertTrue(in_array('GIUA', $maLoi, true), 'Phai giu ho so moi thu hai');
        $this->assertFalse(in_array('CU', $maLoi, true),
            'Phai cat ho so CU nhat, khong duoc cat cai moi nhat');

        $this->assertTrue($kq['bi_cat'],
            'Cham tran thi phai NOI RA la so lieu chi con mot phan');
    }

    /** @test */
    public function chat_luong_theo_cskcb_cung_di_qua_tran_id_ho_so()
    {
        // theo_cskcb tung goi truyVan() LAN THU HAI, tuc khong di qua tran - hai bang xep
        // hang nam canh nhau dem tren hai tap ho so khac nhau, va nguoi doc khong co cach
        // nao biet.
        $cu = $this->hoSo(['imported_at' => '2026-01-01 08:00:00', 'macskcb' => 'CU', 'so_loi' => 1]);
        $moi = $this->hoSo(['imported_at' => '2026-08-20 08:00:00', 'macskcb' => 'MOI', 'so_loi' => 1]);

        $this->ghiLoi($cu);
        $this->ghiLoi($moi);

        $kq = (new CtdtDashboardService())->chatLuong(
            ['tu_ngay' => '2026-01-01', 'den_ngay' => '2026-08-20'], 15, 1
        );

        $cskcb = array_column($kq['theo_cskcb'], 'macskcb');

        $this->assertSame(['MOI'], $cskcb,
            'theo_cskcb phai dung dung tap id da qua tran, khong duoc truy van lai tu dau');
    }

    /** @test */
    public function chat_luong_khong_cham_tran_thi_bi_cat_la_false()
    {
        $hoSo = $this->hoSo(['so_loi' => 1]);
        $this->ghiLoi($hoSo);

        $kq = (new CtdtDashboardService())->chatLuong([]);

        $this->assertFalse($kq['bi_cat'], 'Chua cham tran thi khong duoc bao la da cat');
    }

    /** @test */
    public function hang_doi_bao_TUOI_job_cu_nhat_chu_khong_chi_so_luong()
    {
        // Chi mot con so job tai mot thoi diem thi khong phan biet duoc dung cai no sinh ra
        // de phan biet: worker chet ma chua ai nap ho so moi -> 0 job -> man hinh hien xanh
        // "trong", giong het mot hang doi khoe dang ranh. "Job cu nhat da cho 40 phut" thi
        // khong doc nham duoc.
        \Illuminate\Support\Facades\Schema::create('jobs', function ($bang) {
            $bang->increments('id');
            $bang->string('queue');
            $bang->text('payload');
            $bang->tinyInteger('attempts');
            $bang->unsignedInteger('reserved_at')->nullable();
            $bang->unsignedInteger('available_at');
            $bang->unsignedInteger('created_at');
        });

        config(['queue.default' => 'database']);

        $tenHangDoi = \App\Services\Ctdt\CtdtHangDoi::kiem();

        \Illuminate\Support\Facades\DB::table('jobs')->insert([
            'queue' => $tenHangDoi, 'payload' => '{}', 'attempts' => 0,
            'reserved_at' => null, 'available_at' => time() - 2400,
            'created_at' => time() - 2400,
        ]);

        $kq = (new CtdtDashboardService())->sucKhoe([]);

        $theoTen = [];
        foreach ($kq['hang_doi'] as $hd) {
            $theoTen[$hd['ten']] = $hd;
        }

        $this->assertSame(1, $theoTen[$tenHangDoi]['so_job']);
        $this->assertGreaterThanOrEqual(39, $theoTen[$tenHangDoi]['cho_lau_nhat_phut'],
            'Job nam 40 phut phai duoc bao la da cho 40 phut');

        // Hang doi rong: khong co job nao thi khong co tuoi nao ca - phai la null, khong
        // phai 0, vi "0 phut" doc nhu mot job vua vao.
        foreach ($kq['hang_doi'] as $hd) {
            if ($hd['ten'] !== $tenHangDoi) {
                $this->assertSame(0, $hd['so_job']);
                $this->assertNull($hd['cho_lau_nhat_phut']);
            }
        }

        \Illuminate\Support\Facades\Schema::drop('jobs');
    }

    /** @test */
    public function khong_dang_nhap_thi_KHONG_doc_duoc_so_lieu_ho_so_benh_nhan()
    {
        // Day la so lieu ho so benh nhan. Chieu CHAN khong can nguoi dung gia gi ca - chi
        // can khong dang nhap. Ba endpoint deu phai bi chan, khong chi mot cai.
        // Yeu cau JSON thi middleware auth tra 401 chu khong chuyen huong (no chi
        // redirect khi trinh duyet xin HTML) - kiem dung ma that, dung doan.
        foreach (['suc-khoe', 'san-luong', 'chat-luong'] as $duong) {
            $this->getJson('/dashboard/ctdt/' . $duong)->assertStatus(401);
        }

        $this->get('/dashboard/ctdt')->assertStatus(302);
    }

    /** @test */
    public function khung_ngay_cham_tran_366_thi_NOI_RA_da_rut_ngan()
    {
        // Tran 366 ngay cat truc AM THAM: ban ghi sau ngay thu 366 duoc gom vao bang tra
        // cuu roi ROI MAT o vong to khung, va man hinh khong noi gi - nguoi doc tuong minh
        // dang nhin ca khoang da chon.
        $kq = (new CtdtDashboardService())->sanLuong([
            'tu_ngay' => '2020-01-01', 'den_ngay' => '2026-08-20',
        ]);

        $this->assertCount(366, $kq['ngay'], 'Truc ngay phai bi cat con 366 diem');
        $this->assertTrue($kq['truc_bi_rut_ngan'],
            'Cat truc ngay thi phai noi ra, khong duoc cat im lang');
    }

    /** @test */
    public function khung_ngay_trong_tran_thi_khong_bao_rut_ngan()
    {
        $kq = (new CtdtDashboardService())->sanLuong([
            'tu_ngay' => '2026-08-01', 'den_ngay' => '2026-08-03',
        ]);

        $this->assertFalse($kq['truc_bi_rut_ngan']);
    }

    /** @test */
    public function chat_luong_tach_rieng_hang_theo_ma_loi_va_muc_do_dung_so_luong()
    {
        // Cham toi tang query() that: ba hang cung ma_loi nhung khac muc_do phai la HAI
        // hang rieng, khong duoc GROUP BY gop lai thanh mot - dung dieu Buoc 9 doi biet doi.
        $hoSo1 = $this->hoSo();
        $hoSo2 = $this->hoSo();

        $this->ghiLoi($hoSo1, ['ma_loi' => 'L01', 'ten_truong' => 'ho_ten', 'muc_do' => 'chan']);
        $this->ghiLoi($hoSo2, ['ma_loi' => 'L01', 'ten_truong' => 'ho_ten', 'muc_do' => 'chan']);
        $this->ghiLoi($hoSo1, ['ma_loi' => 'L01', 'ten_truong' => 'ho_ten', 'muc_do' => 'canh_bao']);

        $kq = (new CtdtDashboardService())->chatLuong([]);

        $chan = null;
        $canhBao = null;

        foreach ($kq['theo_ma_loi'] as $dong) {
            if ($dong['ma_loi'] === 'L01' && $dong['muc_do'] === 'chan') {
                $chan = $dong;
            }
            if ($dong['ma_loi'] === 'L01' && $dong['muc_do'] === 'canh_bao') {
                $canhBao = $dong;
            }
        }

        $this->assertNotNull($chan, 'Phai co hang L01 muc chan');
        $this->assertNotNull($canhBao, 'Phai co hang L01 muc canh bao');
        $this->assertSame(2, $chan['so_luong']);
        $this->assertSame(1, $canhBao['so_luong']);
    }

    /** @test */
    public function chat_luong_theo_cskcb_cong_dung_so_loi_va_so_ho_so()
    {
        $hoSo1 = $this->hoSo(['macskcb' => '01001', 'so_loi' => 2]);
        $hoSo2 = $this->hoSo(['macskcb' => '01001', 'so_loi' => 1]);
        $this->hoSo(['macskcb' => '02002', 'so_loi' => 0]); // khong loi, khong duoc tinh

        $this->ghiLoi($hoSo1);
        $this->ghiLoi($hoSo1);
        $this->ghiLoi($hoSo2);

        $kq = (new CtdtDashboardService())->chatLuong([]);

        $theoCskcb = [];
        foreach ($kq['theo_cskcb'] as $dong) {
            $theoCskcb[$dong['macskcb']] = $dong;
        }

        $this->assertArrayHasKey('01001', $theoCskcb);
        $this->assertArrayNotHasKey('02002', $theoCskcb,
            'Co so khong co loi nao thi khong duoc xep hang');
        $this->assertSame(3, $theoCskcb['01001']['so_loi']);
        $this->assertSame(2, $theoCskcb['01001']['so_ho_so']);
    }

    /** @test */
    public function chat_luong_ap_bo_loc_theo_ho_so_khong_ap_thang_len_bang_loi()
    {
        // Bo loc la bo loc theo HO SO (ngay nap, dich vu, co so) - phai loc truoc roi moi
        // join sang ctdt_loi, khong duoc de mot ho so ngoai khoang ngay lot vao ket qua.
        $hoSoTrongKhoang = $this->hoSo(['imported_at' => '2026-08-01 08:00:00']);
        $hoSoNgoaiKhoang = $this->hoSo(['imported_at' => '2026-07-01 08:00:00']);

        $this->ghiLoi($hoSoTrongKhoang, ['ma_loi' => 'TRONG']);
        $this->ghiLoi($hoSoNgoaiKhoang, ['ma_loi' => 'NGOAI']);

        $kq = (new CtdtDashboardService())->chatLuong([
            'tu_ngay' => '2026-08-01', 'den_ngay' => '2026-08-01',
        ]);

        $maLoi = array_column($kq['theo_ma_loi'], 'ma_loi');

        // Dung in_array($..., true) - so sanh CHAT - vi assertContains cua PHPUnit 6 so
        // sanh LONG va mot mang toan chuoi thi khong dinh bay, nhung tranh thoi quen xau.
        $this->assertTrue(in_array('TRONG', $maLoi, true), 'Phai co ma loi TRONG khoang ngay');
        $this->assertFalse(in_array('NGOAI', $maLoi, true), 'Loi cua ho so ngoai khoang ngay khong duoc lot vao');
    }

    /** @test */
    public function endpoint_chat_luong_tra_du_hai_khoa_that_su_qua_HTTP_layer()
    {
        // Khong dang nhap duoc de kiem bang mat trong phien nay, nen bu lai bang mot test
        // di qua dung duong Route + Middleware + Controller - cung mau voi cac endpoint
        // suc_khoe/san_luong o tren.
        $hoSo = $this->hoSo(['so_loi' => 1]);
        $this->ghiLoi($hoSo);

        $response = $this->actingAs($this->nguoiDungGia())->getJson('/dashboard/ctdt/chat-luong');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'theo_ma_loi' => [['ma_loi', 'ten_truong', 'muc_do', 'so_luong']],
                'theo_cskcb'  => [['macskcb', 'so_loi', 'so_ho_so']],
            ]);
    }

    /** @test */
    public function tong_chin_thanh_trang_thai_bang_dung_tong_so_ho_so()
    {
        // Tren man DANH SACH khong ai thay duoc loi nay - moi lan nguoi dung chon MOT bo
        // loc. Tren dashboard chin thanh nam canh nhau, nen mot ho so bi dem vao hai thanh
        // la mot con so sai bay ngay truoc mat. Tai lieu cua man nay khang dinh thang
        // "Tong phai khop man danh sach".
        $this->hoSo([
            'checked_at' => '2026-08-20 08:00:00', 'so_loi' => 0, 'is_signed' => true,
        ]);
        $this->hoSo(['checked_at' => null]);
        $this->hoSo(['checked_at' => '2026-08-20 08:00:00', 'so_loi' => 3]);

        foreach ([true, false] as $guiBat) {
            config(['organization.chung_tu_dien_tu.submit_enabled' => $guiBat]);

            $kq = (new CtdtDashboardService())->sucKhoe([]);
            $tong = array_sum(array_column($kq['theo_trang_thai'], 'so_luong'));

            $this->assertSame(
                CtdtHoSo::count(),
                $tong,
                'Tong chin thanh phai bang tong so ho so (submit_enabled = '
                . ($guiBat ? 'true' : 'false') . ')'
            );
        }
    }

    /** @test */
    public function ba_khoi_dung_CUNG_MOT_khoang_ngay_mac_dinh_khi_khong_co_tham_so()
    {
        // Ba moc ngay mac dinh khac nhau tren cung mot man hinh: sanLuong() tu bu 30 ngay,
        // sucKhoe()/chatLuong() khong bu gi (tuc toan thoi gian), o nhap trong view lai mac
        // dinh dau thang. Nguoi dung thay "co ho so con loi" o khoi tren va mot duong san
        // luong phang bang 0 ngay duoi - cung mot bo loc.
        //
        // Do cung la duong quet TOAN BANG tren may chu 128MB/120s.
        $gan = $this->hoSo([
            'imported_at' => now()->subDays(5)->format('Y-m-d H:i:s'),
            'checked_at'  => now()->subDays(5)->format('Y-m-d H:i:s'),
            'so_loi'      => 1,
        ]);
        $xa = $this->hoSo([
            'imported_at' => now()->subDays(200)->format('Y-m-d H:i:s'),
            'checked_at'  => now()->subDays(200)->format('Y-m-d H:i:s'),
            'so_loi'      => 1,
        ]);

        $this->ghiLoi($gan, ['ma_loi' => 'GAN']);
        $this->ghiLoi($xa, ['ma_loi' => 'XA']);

        $nguoiDung = $this->nguoiDungGia();

        $sucKhoe = $this->actingAs($nguoiDung)->getJson('/dashboard/ctdt/suc-khoe')->json();
        $sanLuong = $this->actingAs($nguoiDung)->getJson('/dashboard/ctdt/san-luong')->json();
        $chatLuong = $this->actingAs($nguoiDung)->getJson('/dashboard/ctdt/chat-luong')->json();

        $tongTrangThai = array_sum(array_column($sucKhoe['theo_trang_thai'], 'so_luong'));

        $tongSanLuong = 0;
        foreach ($sanLuong['chuoi'] as $chuoi) {
            $tongSanLuong += array_sum($chuoi['du_lieu']);
        }

        $maLoi = array_column($chatLuong['theo_ma_loi'], 'ma_loi');

        $this->assertSame(1, $tongTrangThai,
            'Khoi suc khoe phai chi thay ho so trong 30 ngay mac dinh');
        $this->assertSame(1, $tongSanLuong,
            'Khoi san luong phai thay dung mot ho so nhu khoi suc khoe');
        $this->assertTrue(in_array('GAN', $maLoi, true),
            'Khoi chat luong phai thay ho so trong khoang mac dinh');
        $this->assertFalse(in_array('XA', $maLoi, true),
            'Khoi chat luong phai bo ho so 200 ngay truoc, giong hai khoi kia');
    }
}

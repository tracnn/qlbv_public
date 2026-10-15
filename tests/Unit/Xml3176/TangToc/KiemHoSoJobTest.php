<?php

namespace Tests\Unit\Xml3176\TangToc;

use App\Jobs\CheckCompleteXml3176RecordJob;
use App\Jobs\CheckXml3176TypeJob;
use App\Jobs\ExportXml3176Job;
use App\Jobs\KiemHoSoXml3176Job;
use App\Jobs\SignXml3176Job;
use App\Jobs\SubmitXml3176Job;
use App\Services\Xml3176\DuLieuHoSo;
use App\Services\Xml3176\KiemHoSoXml3176;
use App\Services\Xml3176\Xml3176CheckTypes;
use App\Services\Xml3176\Xml3176ChuoiXuLy;
use App\Services\Xml3176CompleteChecker;
use App\Services\Xml3176ErrorService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Tests\Support\Xml3176RuleTestSupport;
use Tests\TestCase;

/**
 * Mot job kiem cho moi ho so (spec 3.3): thay chuoi "kiem tung loai -> kiem tong the" bang
 * KiemHoSoXml3176Job, so token truoc moi lan xoa/ghi loi.
 */
class KiemHoSoJobTest extends TestCase
{
    use Xml3176RuleTestSupport;

    /** @var array Nhat ky: [muc, dang gom?] moi lan checker gia duoc goi */
    public static $nhatKy = [];

    /** @var callable|null Goi ben trong checker gia (vd doi token giua chung) */
    public static $khiKiem = null;

    protected function setUp()
    {
        parent::setUp();
        $this->bootXml3176Sqlite([
            '2026_01_09_152817_create_xml3176_xml1s_table.php',
            '2026_01_09_152826_create_xml3176_xml2s_table.php',
            '2026_01_09_152832_create_xml3176_xml3s_table.php',
            '2026_01_09_152838_create_xml3176_xml4s_table.php',
            '2026_01_09_152843_create_xml3176_xml5s_table.php',
            '2026_01_09_152854_create_xml3176_xml7s_table.php',
            '2026_01_09_152900_create_xml3176_xml8s_table.php',
            '2026_01_09_152906_create_xml3176_xml9s_table.php',
            '2026_01_09_152912_create_xml3176_xml10s_table.php',
            '2026_01_09_152918_create_xml3176_xml11s_table.php',
            '2026_01_09_152930_create_xml3176_xml13s_table.php',
            '2026_01_09_152936_create_xml3176_xml14s_table.php',
            '2026_01_09_161708_create_xml3176_error_catalogs_table.php',
            '2026_01_09_161902_create_xml3176_error_results_table.php',
            '2026_01_09_162044_create_xml3176_informations_table.php',
            '2026_03_28_201021_add_sign_method_to_xml3176_informations.php',
            '2026_09_28_100000_them_checked_at_vao_xml3176_informations.php',
            '2026_09_29_120000_them_chain_token_va_signed_file_path_vao_xml3176_informations.php',
        ]);
        config(['organization.xml_3176_not_check' => false]);
        self::$nhatKy = [];
        self::$khiKiem = null;

        // ErrorService that, mot thuc the cho ca job va checker gia.
        $this->app->instance(Xml3176ErrorService::class, new Xml3176ErrorService());

        DB::table('xml3176_informations')->insert(['ma_lk' => 'LK1', 'macskcb' => '01929', 'chain_token' => 'MA_DUNG']);
        DB::table('xml3176_xml1s')->insert(['ma_lk' => 'LK1', 'stt' => 1]);
        DB::table('xml3176_xml2s')->insert(['ma_lk' => 'LK1', 'stt' => 1]);
        DB::table('xml3176_xml2s')->insert(['ma_lk' => 'LK1', 'stt' => 2]);

        foreach (Xml3176CheckTypes::LOAI as $loai => $cauHinh) {
            $this->app->bind($cauHinh['checker'], function () use ($loai) {
                return new CheckerLoaiGia($loai);
            });
        }
        $this->app->instance(Xml3176CompleteChecker::class, new CheckerTongTheGia());
    }

    private function loiCu($xml)
    {
        DB::table('xml3176_error_results')->insert([
            'ma_lk' => 'LK1', 'xml' => $xml, 'stt' => 1,
            'error_code' => 'CU', 'description' => 'loi cu', 'critical_error' => 0,
        ]);
    }

    /** Ma loi hien co cua mot loai, sap tang. */
    private function maLoi($xml)
    {
        $ds = DB::table('xml3176_error_results')->where('ma_lk', 'LK1')->where('xml', $xml)
            ->pluck('error_code')->all();
        sort($ds);

        return $ds;
    }

    private function checkedAt()
    {
        return DB::table('xml3176_informations')->where('ma_lk', 'LK1')->value('checked_at');
    }

    private function thuocTinh($doiTuong, $ten)
    {
        $p = new \ReflectionProperty($doiTuong, $ten);
        $p->setAccessible(true);

        return $p->getValue($doiTuong);
    }

    // ─── Job moi ──────────────────────────────────────────────────────────

    /** @test */
    public function kiem_du_loai_va_tong_the_ghi_loi_gom()
    {
        foreach (['XML1', 'XML2', 'XML3', 'XMLComplete'] as $xml) {
            $this->loiCu($xml);
        }
        $job = new KiemHoSoXml3176Job('LK1', ['XML2', 'XML1'], 'MA_DUNG');
        $job->chained = ['xuat'];

        $job->handle();

        $this->assertSame(['F_XML1'], $this->maLoi('XML1'), 'Loai XML1: xoa loi cu cua minh roi ghi moi');
        $this->assertSame(['F_XML2', 'F_XML2'], $this->maLoi('XML2'), 'Loai XML2: moi dong mot loi');
        $this->assertSame(['CU'], $this->maLoi('XML3'), 'Loai khong trong danh sach: khong dung toi');
        $this->assertSame(['F_XMLComplete'], $this->maLoi('XMLComplete'), 'XMLComplete cu bi xoa truoc khi ghi');

        // Thu tu Xml3176CheckTypes::LOAI, moi lan goi checker deu dang o che do gom.
        $this->assertSame([
            ['XML1', true], ['XML2', true], ['XML2', true], ['XMLComplete', true],
        ], self::$nhatKy);
        $this->assertFalse(app(Xml3176ErrorService::class)->dangGom(), 'Phai ketThucGom sau moi buoc');
        $this->assertNotNull($this->checkedAt());
        $this->assertSame(['xuat'], $job->chained);
    }

    /** @test */
    public function kiem_tong_the_nhan_du_lieu_ho_so_nap_san()
    {
        (new KiemHoSoXml3176Job('LK1', [], 'MA_DUNG'))->handle();

        $du = app(Xml3176CompleteChecker::class)->duNhan;
        $this->assertInstanceOf(DuLieuHoSo::class, $du);
        $this->assertSame('LK1', $du->maLk());
    }

    /** @test */
    public function token_doi_giua_chung_thi_dung()
    {
        foreach (['XML1', 'XML2', 'XMLComplete'] as $xml) {
            $this->loiCu($xml);
        }
        // Ho so bi nap lai (token moi) trong luc dang kiem XML1.
        self::$khiKiem = function ($muc) {
            if ($muc === 'XML1') {
                DB::table('xml3176_informations')->update(['chain_token' => 'MA_MOI']);
            }
        };
        $job = new KiemHoSoXml3176Job('LK1', ['XML1', 'XML2'], 'MA_DUNG');
        $job->chained = ['xuat'];

        $job->handle();

        $this->assertSame(['CU'], $this->maLoi('XML2'), 'Loai sau khi doi token: khong xoa, khong ghi');
        $this->assertSame(['CU'], $this->maLoi('XMLComplete'), 'Kiem tong the khong chay sau khi doi token');
        $this->assertSame([['XML1', true]], self::$nhatKy);
        $this->assertNull($this->checkedAt(), 'Khong dong dau du lieu moi chua kiem');
        $this->assertSame([], $job->chained, 'Chuoi cu phai bi cat');
    }

    /** @test */
    public function service_dung_khi_nguoi_canh_tra_false_lan_hai()
    {
        foreach (['XML1', 'XML2', 'XMLComplete'] as $xml) {
            $this->loiCu($xml);
        }
        $lan = 0;
        $canh = function () use (&$lan) {
            return ++$lan === 1;
        };

        $kq = app(KiemHoSoXml3176::class)->chay('LK1', ['XML1', 'XML2'], $canh);

        $this->assertFalse($kq);
        $this->assertSame(['F_XML1'], $this->maLoi('XML1'));
        $this->assertSame(['CU'], $this->maLoi('XML2'));
        $this->assertSame(['CU'], $this->maLoi('XMLComplete'));
    }

    /** @test */
    public function danh_sach_loai_rong_van_kiem_tong_the_va_dong_dau()
    {
        $job = new KiemHoSoXml3176Job('LK1', [], 'MA_DUNG');
        $job->chained = ['xuat'];

        $job->handle();

        $this->assertSame([['XMLComplete', true]], self::$nhatKy);
        $this->assertSame(['F_XMLComplete'], $this->maLoi('XMLComplete'));
        $this->assertNotNull($this->checkedAt());
        $this->assertSame(['xuat'], $job->chained);
    }

    /** @test */
    public function dong_dau_checked_at_co_dieu_kien_token()
    {
        // Token lech ngay tu dau: khong lam gi.
        $this->loiCu('XML1');
        $job = new KiemHoSoXml3176Job('LK1', ['XML1'], 'MA_CU');
        $job->chained = ['xuat'];
        $job->handle();
        $this->assertSame([], self::$nhatKy);
        $this->assertSame(['CU'], $this->maLoi('XML1'));
        $this->assertNull($this->checkedAt());
        $this->assertSame([], $job->chained);

        // Token doi trong luc kiem tong the (sau lan so cuoi): van khong dong dau.
        self::$khiKiem = function ($muc) {
            if ($muc === 'XMLComplete') {
                DB::table('xml3176_informations')->update(['chain_token' => 'MA_MOI']);
            }
        };
        (new KiemHoSoXml3176Job('LK1', [], 'MA_DUNG'))->handle();
        $this->assertNull($this->checkedAt(), 'Dong dau phai co dieu kien chain_token');
    }

    /** @test */
    public function job_khong_token_giu_quy_tac_job_cu()
    {
        // Ho so da thuoc chuoi moi (co chain_token): job khong token thoi han, khong dong dau.
        $this->loiCu('XML1');
        (new KiemHoSoXml3176Job('LK1', ['XML1']))->handle();
        $this->assertSame([], self::$nhatKy);
        $this->assertSame(['CU'], $this->maLoi('XML1'));
        $this->assertNull($this->checkedAt());

        // Ho so chua co ma phien: kiem va dong dau nhu cu.
        DB::table('xml3176_informations')->update(['chain_token' => null]);
        (new KiemHoSoXml3176Job('LK1', ['XML1']))->handle();
        $this->assertSame(['F_XML1'], $this->maLoi('XML1'));
        $this->assertSame(['F_XMLComplete'], $this->maLoi('XMLComplete'));
        $this->assertNotNull($this->checkedAt());
    }

    /** @test */
    public function failed_ghi_export_error_khi_con_hieu_luc()
    {
        (new KiemHoSoXml3176Job('LK1', ['XML1'], 'MA_DUNG'))->failed(new \RuntimeException('mat ket noi'));

        $loi = DB::table('xml3176_informations')->value('export_error');
        $this->assertContains('Không xuất: bước kiểm hồ sơ lỗi', $loi);
        $this->assertContains('mat ket noi', $loi);

        DB::table('xml3176_informations')->update(['export_error' => null]);
        (new KiemHoSoXml3176Job('LK1', ['XML1'], 'MA_CU'))->failed(new \RuntimeException('x'));
        $this->assertNull(DB::table('xml3176_informations')->value('export_error'), 'Chuoi cu khong ghi');
    }

    /** @test */
    public function xml_3176_not_check_bo_qua_tong_the_van_dong_dau()
    {
        config(['organization.xml_3176_not_check' => true]);
        $this->loiCu('XMLComplete');
        $job = new KiemHoSoXml3176Job('LK1', ['XML1'], 'MA_DUNG');
        $job->chained = ['xuat'];

        $job->handle();

        $this->assertSame([['XML1', true]], self::$nhatKy, 'Co chi tat kiem tong the, loai XML van kiem');
        $this->assertSame(['CU'], $this->maLoi('XMLComplete'), 'Tat kiem tong the thi khong xoa XMLComplete');
        $this->assertNotNull($this->checkedAt());
        $this->assertSame(['xuat'], $job->chained);
    }

    /** @test */
    public function checker_nem_thi_phan_da_gom_van_ghi_va_job_nem()
    {
        self::$khiKiem = function ($muc) {
            if ($muc === 'XML2') {
                throw new \RuntimeException('hong');
            }
        };

        try {
            (new KiemHoSoXml3176Job('LK1', ['XML1', 'XML2'], 'MA_DUNG'))->handle();
            $this->fail('Phai nem de hang doi thu lai');
        } catch (\RuntimeException $e) {
            $this->assertSame('hong', $e->getMessage());
        }

        $this->assertSame(['F_XML1'], $this->maLoi('XML1'));
        $this->assertFalse(app(Xml3176ErrorService::class)->dangGom(), 'ketThucGom trong finally');
        $this->assertNull($this->checkedAt());
    }

    /** @test */
    public function che_do_khong_ghi_giu_bo_dem_khong_cham_csdl()
    {
        foreach (['XML1', 'XMLComplete'] as $xml) {
            $this->loiCu($xml);
        }
        $loi = app(Xml3176ErrorService::class);
        $loi->batDauGom();

        $kq = app(KiemHoSoXml3176::class)->chay('LK1', ['XML1'], function () {
            return true;
        }, false);

        $this->assertTrue($kq);
        $this->assertTrue($loi->dangGom(), 'Lenh do giu che do gom');
        $this->assertSame(['XML1', 'XMLComplete'], array_column($loi->boDemGom(), 'xml'));
        $this->assertSame(['CU'], $this->maLoi('XML1'));
        $this->assertSame(['CU'], $this->maLoi('XMLComplete'));
        $this->assertNull($this->checkedAt());
        $loi->xoaBoDemGom();
        $loi->ketThucGom();
    }

    /** @test */
    public function hang_doi_va_thoi_han()
    {
        $job = new KiemHoSoXml3176Job('LK1', [], 'M');

        $this->assertSame(2, $job->tries);
        $this->assertSame(240, $job->timeout);
    }

    // ─── Job cu con trong hang doi ────────────────────────────────────────

    /** @test */
    public function job_cu_van_chay()
    {
        $this->loiCu('XML1');

        $loai = unserialize(serialize(new CheckXml3176TypeJob('LK1', 'XML1', 'MA_DUNG')));
        $loai->handle();
        $this->assertSame(['F_XML1'], $this->maLoi('XML1'));

        $tong = unserialize(serialize(new CheckCompleteXml3176RecordJob('LK1', 'MA_DUNG')));
        $tong->handle(app(Xml3176CompleteChecker::class));
        $this->assertSame(['F_XMLComplete'], $this->maLoi('XMLComplete'));
        $this->assertNotNull($this->checkedAt());
    }

    // ─── Chuoi moi ────────────────────────────────────────────────────────

    private function chuoiDaDay()
    {
        $dau = Queue::pushed(KiemHoSoXml3176Job::class)->first();
        $this->assertNotNull($dau, 'Khong co KiemHoSoXml3176Job nao duoc day');

        return array_map(function ($job) {
            $loai = $job instanceof KiemHoSoXml3176Job ? implode(',', $this->thuocTinh($job, 'loaiXml')) : '-';

            return class_basename($job) . '|' . $loai . '|' . $job->queue . '|' . $this->thuocTinh($job, 'chainToken');
        }, array_merge([$dau], array_map('unserialize', $dau->chained)));
    }

    /** @test */
    public function chuoi_moi_thu_tu()
    {
        config(['xml3176.export_xml3176_enabled' => true]);
        Queue::fake();

        Xml3176ChuoiXuLy::xepSauNap('LK1', 'M1', ['XML2', 'XML1', 'XML2', 'KHONG_CO_CHECKER'], true);

        $this->assertSame([
            'KiemHoSoXml3176Job|XML1,XML2|JobXml3176|M1',
            'ExportXml3176Job|-|JobExportXml3176|M1',
            'SignXml3176Job|-|JobSignXml3176|M1',
            'SubmitXml3176Job|-|JobSubmitXml3176|M1',
        ], $this->chuoiDaDay());
        Queue::assertNotPushed(CheckXml3176TypeJob::class);
        Queue::assertNotPushed(CheckCompleteXml3176RecordJob::class);
    }

    /** @test */
    public function chuoi_moi_khong_xuat_chi_co_kiem_ho_so()
    {
        Queue::fake();

        Xml3176ChuoiXuLy::xepSauNap('LK1', 'M1', ['XML1'], false);

        $this->assertSame(['KiemHoSoXml3176Job|XML1|JobXml3176|M1'], $this->chuoiDaDay());
    }
}

/** Checker loai gia: moi dong mot loi F_<loai>, ghi nhat ky che do gom. */
class CheckerLoaiGia
{
    private $loai;

    public function __construct($loai)
    {
        $this->loai = $loai;
    }

    public function checkErrors($d)
    {
        $loi = app(Xml3176ErrorService::class);
        KiemHoSoJobTest::$nhatKy[] = [$this->loai, $loi->dangGom()];
        if (KiemHoSoJobTest::$khiKiem) {
            call_user_func(KiemHoSoJobTest::$khiKiem, $this->loai);
        }
        $loi->saveErrors($this->loai, $d->ma_lk, (int) $d->stt, collect([(object) [
            'error_code' => 'F_' . $this->loai, 'description' => 'moi', 'critical_error' => true,
        ]]));
    }
}

/** Checker tong the gia: mot loi F_XMLComplete, nho DuLieuHoSo nhan duoc. */
class CheckerTongTheGia extends Xml3176CompleteChecker
{
    public $duNhan;

    public function __construct()
    {
    }

    public function checkErrors($ma_lk, DuLieuHoSo $du = null): void
    {
        $this->duNhan = $du;
        $loi = app(Xml3176ErrorService::class);
        KiemHoSoJobTest::$nhatKy[] = ['XMLComplete', $loi->dangGom()];
        if (KiemHoSoJobTest::$khiKiem) {
            call_user_func(KiemHoSoJobTest::$khiKiem, 'XMLComplete');
        }
        $loi->saveErrors('XMLComplete', $ma_lk, 1, collect([(object) [
            'error_code' => 'F_XMLComplete', 'description' => 'moi', 'critical_error' => true,
        ]]));
    }
}

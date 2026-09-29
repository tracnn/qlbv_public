<?php

namespace Tests\Unit\Xml3176\Chuoi;

use App\Jobs\ExportXml3176Job;
use App\Services\Xml3176Service;
use Illuminate\Support\Facades\DB;
use Tests\Support\Xml3176RuleTestSupport;
use Tests\TestCase;

/**
 * Buoc XUAT trong chuoi: mot cua kiem roi ghi tep cho ky.
 *
 * Truoc day (28/09/2026) job cho kiem xong theo thoi gian - 15s x 10 lan - va ngay nap lo
 * 29/09 hang doi kiem ton toi 90 phut nen 5.043/5.443 ho so het luot cho, 1.934 ho so sach
 * khong len cong. Nay job chi chay khi chuoi da di qua buoc kiem tong the.
 */
class ExportXml3176JobTest extends TestCase
{
    use Xml3176RuleTestSupport;

    protected function setUp()
    {
        parent::setUp();
        $this->bootXml3176Sqlite([
            '2026_01_09_152817_create_xml3176_xml1s_table.php',
            '2026_01_09_161902_create_xml3176_error_results_table.php',
            '2026_01_09_162044_create_xml3176_informations_table.php',
            '2026_03_28_201021_add_sign_method_to_xml3176_informations.php',
            '2026_09_28_100000_them_checked_at_vao_xml3176_informations.php',
            '2026_09_29_120000_them_chain_token_va_signed_file_path_vao_xml3176_informations.php',
        ]);
        DB::table('xml3176_xml1s')->insert(['ma_lk' => 'LK1', 'stt' => 1, 'ngay_ra' => '202001010800']);
        DB::table('xml3176_informations')->insert([
            'ma_lk' => 'LK1', 'macskcb' => '01929',
            'chain_token' => 'MA_DUNG', 'checked_at' => '2026-09-29 09:00:00',
        ]);
        config(['organization.export_xml_not_check' => false]);
    }

    /** Service gia: dem so lan xuatTepChoKy() duoc goi. */
    private function service($ketQua = true)
    {
        return new class($ketQua) extends Xml3176Service {
            public $soLanXuat = 0;
            private $kq;
            public function __construct($kq) { $this->kq = $kq; }
            public function xuatTepChoKy($ma) { $this->soLanXuat++; return $this->kq; }
        };
    }

    private function chay($maPhien = 'MA_DUNG', $service = null)
    {
        $service = $service ?: $this->service();
        $job = new ExportXml3176Job('LK1', $maPhien);
        $job->chained = ['ky', 'gui'];
        $job->handle($service);

        return [$job, $service];
    }

    private function loiXuat()
    {
        return DB::table('xml3176_informations')->where('ma_lk', 'LK1')->value('export_error');
    }

    private function loiNghiemTrong($soDong)
    {
        for ($i = 1; $i <= $soDong; $i++) {
            DB::table('xml3176_error_results')->insert([
                'ma_lk' => 'LK1', 'xml' => 'XML1', 'stt' => $i,
                'error_code' => 'X' . $i, 'description' => 'x', 'critical_error' => 1,
            ]);
        }
    }

    /** @test */
    public function sach_va_da_kiem_thi_xuat_va_giu_chuoi()
    {
        list($job, $s) = $this->chay();

        $this->assertSame(1, $s->soLanXuat);
        $this->assertSame(['ky', 'gui'], $job->chained);
    }

    /** @test */
    public function con_loi_nghiem_trong_thi_khong_xuat_ghi_so_loi_va_cat_chuoi()
    {
        // Truoc day viec chan nay IM LANG: nguoi van hanh khong phan biet duoc "bi chan" voi
        // "chua toi luot".
        $this->loiNghiemTrong(2);

        list($job, $s) = $this->chay();

        $this->assertSame(0, $s->soLanXuat);
        $this->assertSame('Không xuất: còn 2 lỗi nghiêm trọng', $this->loiXuat());
        $this->assertSame([], $job->chained);
    }

    /** @test */
    public function bo_kiem_loi_nghiem_trong_thi_van_xuat()
    {
        config(['organization.export_xml_not_check' => true]);
        $this->loiNghiemTrong(2);

        list($job, $s) = $this->chay();

        $this->assertSame(1, $s->soLanXuat);
        $this->assertSame(['ky', 'gui'], $job->chained);
    }

    /** @test */
    public function chua_kiem_xong_thi_khong_xuat()
    {
        // Trong chuoi dieu nay khong xay ra; nhung lenh cuu --ma-lk co the chi dinh ho so
        // chua kiem. Hong thi dong.
        DB::table('xml3176_informations')->update(['checked_at' => null]);

        list($job, $s) = $this->chay();

        $this->assertSame(0, $s->soLanXuat);
        $this->assertSame('Không xuất: hồ sơ chưa kiểm xong', $this->loiXuat());
        $this->assertSame([], $job->chained);
    }

    /** @test */
    public function ngay_ra_o_tuong_lai_thi_khong_xuat_va_ghi_ro()
    {
        // Truoc day job thoat IM LANG - ho so khong bao gio duoc xuat va khong ai biet.
        DB::table('xml3176_xml1s')->update(['ngay_ra' => '209912312359']);

        list($job, $s) = $this->chay();

        $this->assertSame(0, $s->soLanXuat);
        $this->assertContains('209912312359', $this->loiXuat());
        $this->assertContains('sau thời điểm xuất', $this->loiXuat());
        $this->assertSame([], $job->chained);
    }

    /** @test */
    public function khong_dung_duoc_xml_thi_ghi_loi_va_cat_chuoi()
    {
        list($job, $s) = $this->chay('MA_DUNG', $this->service(false));

        $this->assertSame(1, $s->soLanXuat);
        $this->assertContains('Xuất lỗi', $this->loiXuat());
        $this->assertSame([], $job->chained);
    }

    /** @test */
    public function ma_lech_thi_khong_lam_gi_khong_ghi_gi()
    {
        $this->loiNghiemTrong(1);

        list($job, $s) = $this->chay('MA_CU');

        $this->assertSame(0, $s->soLanXuat);
        $this->assertNull($this->loiXuat(), 'Chuoi cu khong duoc ghi de trang thai cua chuoi moi');
        $this->assertSame([], $job->chained);
    }

    /** @test */
    public function job_cu_dang_cho_thi_thoat_khong_xuat()
    {
        // Job dang cu (vong cho 15s x 10) con nam trong hang doi luc nang cap. Phia sau no
        // khong con buoc nao; lenh xml3176:chay-lai-tu-xuat gom ho so lai.
        $s = $this->service();

        (new ExportXml3176Job('LK1'))->handle($s);

        $this->assertSame(0, $s->soLanXuat);
    }

    /** @test */
    public function hong_het_luot_thi_ghi_ly_do_neu_ma_con_khop()
    {
        (new ExportXml3176Job('LK1', 'MA_DUNG'))->failed(new \RuntimeException('dia day'));
        $this->assertSame('Xuất lỗi — dia day', $this->loiXuat());

        DB::table('xml3176_informations')->update(['export_error' => null]);
        (new ExportXml3176Job('LK1', 'MA_CU'))->failed(new \RuntimeException('dia day'));
        $this->assertNull($this->loiXuat());
    }

    /** @test */
    public function khong_con_vong_cho_theo_thoi_gian()
    {
        $this->assertFalse(defined(ExportXml3176Job::class . '::SO_LAN_CHO_TOI_DA'),
            'Cho theo thoi gian da lam 1.934 ho so sach khong len cong ngay 29/09/2026');
        $this->assertFalse(method_exists(ExportXml3176Job::class, 'phaiChoKiemLoi'));
    }
}

<?php

namespace Tests\Unit\Xml3176\VatPhamKemDvkt;

use App\Jobs\HocVatPhamKemDvktJob;
use App\Models\BHYT\Xml3176VatPhamKemDvkt;
use App\Models\BHYT\Xml3176VpkdLanHoc;
use App\Models\BHYT\Xml3176VpkdUngVien;
use App\Services\Xml3176\VatPhamKemDvktHocLaiService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Tests\Support\Xml3176RuleTestSupport;
use Tests\TestCase;

class HocLaiServiceTest extends TestCase
{
    use Xml3176RuleTestSupport;

    protected function setUp()
    {
        parent::setUp();
        $this->bootXml3176Sqlite([
            '2014_10_12_000000_create_users_table.php',
            '2026_01_09_152826_create_xml3176_xml2s_table.php',
            '2026_01_09_152832_create_xml3176_xml3s_table.php',
            '2026_10_13_090000_create_xml3176_vat_pham_kem_dvkt_table.php',
            '2026_10_14_090000_them_nguoi_sua_vao_xml3176_vat_pham_kem_dvkt.php',
            '2026_10_14_090100_create_xml3176_vpkd_lan_hoc_table.php',
            '2026_10_14_090200_create_xml3176_vpkd_ung_vien_table.php',
        ]);
        // 10 dong, 9 gan -> vao bang tra; 10 dong, 6 gan -> ung vien
        for ($i = 0; $i < 10; $i++) {
            DB::table('xml3176_xml3s')->insert([
                ['ma_lk' => 'A' . $i, 'stt' => 1, 'ma_vat_tu' => 'N08.00.150.0001.000.0001', 'ten_vat_tu' => 'Buồng',
                 'ma_dich_vu' => $i < 9 ? '12.0448.1187' : null, 't_bhtt' => 1000, 'so_luong' => 1, 'ngay_yl' => '202609010800'],
                ['ma_lk' => 'A' . $i, 'stt' => 2, 'ma_vat_tu' => 'N03.03.030.0001.000.0001', 'ten_vat_tu' => 'Kim',
                 'ma_dich_vu' => $i < 6 ? '22.0126.0092' : null, 't_bhtt' => 1000, 'so_luong' => 1, 'ngay_yl' => '202609010800'],
            ]);
        }
        DB::table('users')->insert(['id' => 7, 'name' => 'tracnn', 'email' => 'a@b.c', 'password' => 'x']);
    }

    private function sv(): VatPhamKemDvktHocLaiService
    {
        return app(VatPhamKemDvktHocLaiService::class);
    }

    /** @test */
    public function yeu_cau_tao_luot_cho_va_day_job_dung_ket_noi_hang_doi()
    {
        Queue::fake();

        $r = $this->sv()->yeuCau(7);

        $this->assertTrue($r['moi']);
        $this->assertSame(Xml3176VpkdLanHoc::CHO, $r['lan_hoc']->trang_thai);
        $this->assertSame(7, $r['lan_hoc']->user_id);
        Queue::assertPushedOn('JobXuatTepXml3176', HocVatPhamKemDvktJob::class, function ($job) use ($r) {
            return $job->lanHocId === $r['lan_hoc']->id && $job->connection === 'xuat_tep';
        });
    }

    /** @test */
    public function dang_co_luot_chay_thi_khong_day_them()
    {
        Queue::fake();
        $cu = Xml3176VpkdLanHoc::create(['user_id' => 7, 'trang_thai' => Xml3176VpkdLanHoc::DANG_CHAY, 'bat_dau_luc' => Carbon::now()]);

        $r = $this->sv()->yeuCau(8);

        $this->assertFalse($r['moi']);
        $this->assertSame($cu->id, $r['lan_hoc']->id);
        Queue::assertNotPushed(HocVatPhamKemDvktJob::class);
    }

    /** @test */
    public function danh_dau_treo_sau_60_phut_khong_dung_luot_moi()
    {
        $treo = Xml3176VpkdLanHoc::create(['trang_thai' => Xml3176VpkdLanHoc::DANG_CHAY, 'bat_dau_luc' => Carbon::now()->subMinutes(61)]);
        $moi = Xml3176VpkdLanHoc::create(['trang_thai' => Xml3176VpkdLanHoc::DANG_CHAY, 'bat_dau_luc' => Carbon::now()->subMinutes(5)]);
        $choCu = Xml3176VpkdLanHoc::create(['trang_thai' => Xml3176VpkdLanHoc::CHO]);
        DB::table('xml3176_vpkd_lan_hoc')->where('id', $choCu->id)->update(['created_at' => Carbon::now()->subMinutes(61)]);

        $this->assertSame(2, $this->sv()->danhDauTreo());

        $this->assertSame(Xml3176VpkdLanHoc::LOI, $treo->fresh()->trang_thai);
        $this->assertSame('Treo quá 60 phút', $treo->fresh()->loi);
        $this->assertSame(Xml3176VpkdLanHoc::LOI, $choCu->fresh()->trang_thai);
        $this->assertSame('Quá 60 phút chưa được chạy (hàng đợi bận)', $choCu->fresh()->loi);
        $this->assertSame(Xml3176VpkdLanHoc::DANG_CHAY, $moi->fresh()->trang_thai);
    }

    /** @test */
    public function job_chay_luot_ghi_xong_bang_tra_va_ung_vien()
    {
        $lan = Xml3176VpkdLanHoc::create(['user_id' => 7, 'trang_thai' => Xml3176VpkdLanHoc::CHO]);

        (new HocVatPhamKemDvktJob($lan->id))->handle();

        $lan = $lan->fresh();
        $this->assertSame(Xml3176VpkdLanHoc::XONG, $lan->trang_thai);
        $this->assertSame(1, $lan->ket_qua['them']);
        $this->assertSame(1, $lan->ket_qua['so_ung_vien']);
        $this->assertSame(['VTYT' => 1, 'THUOC' => 0], $lan->ket_qua['so_cap']);
        $this->assertNotNull($lan->xong_luc);
        $this->assertSame(1, Xml3176VatPhamKemDvkt::count());
        $this->assertSame($lan->id, Xml3176VpkdUngVien::first()->lan_hoc_id);
    }

    /** @test */
    public function job_bo_qua_luot_khong_o_trang_thai_cho()
    {
        $lan = Xml3176VpkdLanHoc::create(['trang_thai' => Xml3176VpkdLanHoc::XONG]);

        (new HocVatPhamKemDvktJob($lan->id))->handle();

        $this->assertSame(0, Xml3176VatPhamKemDvkt::count());
    }

    /** @test */
    public function loi_khi_chay_ghi_luot_loi_va_nem_lai()
    {
        $lan = Xml3176VpkdLanHoc::create(['trang_thai' => Xml3176VpkdLanHoc::CHO]);
        DB::statement('DROP TABLE xml3176_vpkd_ung_vien');

        try {
            (new HocVatPhamKemDvktJob($lan->id))->handle();
            $this->fail('Phai nem lai loi');
        } catch (\Throwable $e) {
            $this->assertNotInstanceOf(\PHPUnit\Framework\AssertionFailedError::class, $e);
        }

        $this->assertSame(Xml3176VpkdLanHoc::LOI, $lan->fresh()->trang_thai);
        $this->assertStringStartsWith('Lỗi khi học lại — xem log ứng dụng', $lan->fresh()->loi);
        $this->assertSame(0, Xml3176VatPhamKemDvkt::count(), 'Giao dich phai rollback ca bang tra');
    }

    /** @test */
    public function mo_ta_luot_xong_co_nguoi_va_so_lieu()
    {
        $lan = Xml3176VpkdLanHoc::create(['user_id' => 7, 'trang_thai' => Xml3176VpkdLanHoc::CHO]);
        (new HocVatPhamKemDvktJob($lan->id))->handle();

        $m = $this->sv()->moTa($this->sv()->ganNhat());

        $this->assertFalse($m['dang_chay']);
        $this->assertContains('bởi tracnn: thêm 1, cập nhật 0, tắt 0, bật lại 0, ứng viên 1', $m['nhan']);
        $this->assertNull($this->sv()->moTa(null));
    }

    /** @test */
    public function lenh_hoc_khong_thu_ghi_luot_va_ung_vien()
    {
        Artisan::call('xml3176:hoc-vat-pham-kem-dvkt');

        $lan = Xml3176VpkdLanHoc::first();
        $this->assertNull($lan->user_id);
        $this->assertSame(Xml3176VpkdLanHoc::XONG, $lan->trang_thai);
        $this->assertSame(1, Xml3176VpkdUngVien::count());
        $this->assertContains('Ứng viên: 1', Artisan::output());
    }

    /** @test */
    public function lenh_hoc_che_do_thu_khong_ghi_luot()
    {
        Artisan::call('xml3176:hoc-vat-pham-kem-dvkt', ['--thu' => true]);

        $this->assertSame(0, Xml3176VpkdLanHoc::count());
        $this->assertSame(0, Xml3176VpkdUngVien::count());
    }

    /** @test */
    public function lenh_hoc_tu_choi_khi_dang_co_luot_hoc_chay()
    {
        Xml3176VpkdLanHoc::create(['user_id' => 7, 'trang_thai' => Xml3176VpkdLanHoc::DANG_CHAY, 'bat_dau_luc' => Carbon::now()]);

        $ma = Artisan::call('xml3176:hoc-vat-pham-kem-dvkt');

        $this->assertSame(1, $ma);
        $this->assertContains('Đang có lượt học #', Artisan::output());
        $this->assertSame(0, Xml3176VatPhamKemDvkt::count());
        $this->assertSame(1, Xml3176VpkdLanHoc::count());
    }
}

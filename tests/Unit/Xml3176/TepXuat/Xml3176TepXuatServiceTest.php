<?php

namespace Tests\Unit\Xml3176\TepXuat;

use App\Jobs\XuatTepLoiXml3176Job;
use App\Models\BHYT\Xml3176TepXuat;
use App\Services\Xml3176\Xml3176TepXuatService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\Support\Xml3176RuleTestSupport;
use Tests\TestCase;

class Xml3176TepXuatServiceTest extends TestCase
{
    use Xml3176RuleTestSupport;

    /** @var Xml3176TepXuatService */
    private $s;

    protected function setUp()
    {
        parent::setUp();
        $this->bootXml3176Sqlite(['2026_09_30_100000_create_xml3176_tep_xuat_table.php']);
        Queue::fake();
        Storage::fake('local');
        Carbon::setTestNow(Carbon::create(2026, 9, 30, 9, 0, 0));
        $this->s = new Xml3176TepXuatService();
    }

    protected function tearDown()
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function boLoc(array $ghiDe = [])
    {
        return array_merge([
            'date_from' => '2026-09-29 00:00:00', 'date_to' => '2026-09-29 23:59:59',
            'date_type' => 'date_payment', 'hein_card_filter' => 'has_hein_card', 'ma_khoa' => null,
        ], $ghiDe);
    }

    private function yeuCau(array $gia)
    {
        return Xml3176TepXuat::create(array_merge([
            'user_id' => 1, 'loai' => Xml3176TepXuat::LOAI_LOI, 'bo_loc' => $this->boLoc(),
            'trang_thai' => Xml3176TepXuat::CHO,
        ], $gia));
    }

    // ─── Tao yeu cau ─────────────────────────────────────────────────────

    /** @test */
    public function tao_yeu_cau_ghi_dong_cho_va_day_dung_job_dung_ket_noi_dung_hang_doi()
    {
        $kq = $this->s->taoYeuCau(1, $this->boLoc());

        $this->assertFalse($kq['trung']);
        $this->assertSame(Xml3176TepXuat::CHO, $kq['yeuCau']->trang_thai);
        $this->assertSame(1, $kq['yeuCau']->user_id);
        Queue::assertPushedOn('JobXuatTepXml3176', XuatTepLoiXml3176Job::class, function ($job) use ($kq) {
            return $job->connection === 'xuat_tep' && $job->yeuCauId === $kq['yeuCau']->id;
        });
    }

    /** @test */
    public function bam_trung_cung_bo_loc_khi_dang_chay_thi_tra_yeu_cau_cu_khong_day_them()
    {
        $dau = $this->s->taoYeuCau(1, $this->boLoc())['yeuCau'];
        $dau->update(['trang_thai' => Xml3176TepXuat::DANG_TAO, 'bat_dau_luc' => Carbon::now()]);

        // Cung bo loc nhung thu tu khoa khac van la trung.
        $kq = $this->s->taoYeuCau(1, array_reverse($this->boLoc(), true));

        $this->assertTrue($kq['trung']);
        $this->assertSame($dau->id, $kq['yeuCau']->id);
        $this->assertSame(1, Xml3176TepXuat::count());
        Queue::assertPushed(XuatTepLoiXml3176Job::class, 1);
    }

    /** @test */
    public function bo_loc_khac_hoac_nguoi_khac_hoac_yeu_cau_cu_da_xong_thi_tao_moi()
    {
        $this->s->taoYeuCau(1, $this->boLoc());
        $this->s->taoYeuCau(1, $this->boLoc(['ma_khoa' => 'K01']));
        $this->s->taoYeuCau(2, $this->boLoc());
        Xml3176TepXuat::query()->update(['trang_thai' => Xml3176TepXuat::XONG]);
        $this->s->taoYeuCau(1, $this->boLoc());

        $this->assertSame(4, Xml3176TepXuat::count());
    }

    // ─── Danh sach ───────────────────────────────────────────────────────

    /** @test */
    public function danh_sach_chi_cua_minh_moi_nhat_truoc_kem_so_yeu_cau_dung_truoc()
    {
        $a = $this->yeuCau(['user_id' => 2]);                         // cho, cua nguoi khac, dung truoc
        $b = $this->yeuCau(['user_id' => 1]);                         // cho, cua minh
        $c = $this->yeuCau(['user_id' => 1, 'trang_thai' => Xml3176TepXuat::XONG, 'kich_thuoc' => 12345]);

        $ds = $this->s->danhSachCua(1);

        $this->assertSame([$c->id, $b->id], array_column($ds, 'id'));
        $this->assertSame(1, $ds[1]['so_truoc'], 'Yeu cau cho cua nguoi khac dung truoc van phai dem');
        $this->assertSame(12345, $ds[0]['kich_thuoc']);
        $this->assertContains('29/09/2026', $ds[0]['bo_loc']);
    }

    /** @test */
    public function dang_tao_qua_90_phut_bi_danh_dau_loi_khi_lay_danh_sach()
    {
        $treo = $this->yeuCau(['trang_thai' => Xml3176TepXuat::DANG_TAO, 'bat_dau_luc' => Carbon::now()->subMinutes(91)]);
        $moi = $this->yeuCau(['trang_thai' => Xml3176TepXuat::DANG_TAO, 'bat_dau_luc' => Carbon::now()->subMinutes(30)]);

        $this->s->danhSachCua(1);

        $this->assertSame(Xml3176TepXuat::LOI, $treo->fresh()->trang_thai);
        $this->assertContains('Quá thời gian', $treo->fresh()->loi);
        $this->assertSame(Xml3176TepXuat::DANG_TAO, $moi->fresh()->trang_thai);
    }

    /** @test */
    public function yeu_cau_cu_hon_7_ngay_bi_xoa_cung_tep()
    {
        $cu = $this->yeuCau(['trang_thai' => Xml3176TepXuat::XONG]);
        $cu->created_at = Carbon::now()->subDays(8);
        $cu->save();
        $cu->update(['duong_dan' => Xml3176TepXuatService::duongDanTep($cu)]);
        Storage::disk('local')->put($cu->duong_dan, 'x');
        $moi = $this->yeuCau(['trang_thai' => Xml3176TepXuat::XONG]);

        $this->s->danhSachCua(1);

        $this->assertNull(Xml3176TepXuat::find($cu->id));
        $this->assertFalse(Storage::disk('local')->exists('xml3176-tep-xuat/' . $cu->id . '.xlsx'));
        $this->assertNotNull(Xml3176TepXuat::find($moi->id));
    }

    // ─── Tai ─────────────────────────────────────────────────────────────

    /** @test */
    public function chi_tai_duoc_yeu_cau_cua_minh_da_xong_va_con_tep()
    {
        $xong = $this->yeuCau(['trang_thai' => Xml3176TepXuat::XONG]);
        $xong->update(['duong_dan' => Xml3176TepXuatService::duongDanTep($xong)]);
        Storage::disk('local')->put($xong->duong_dan, 'x');
        $chuaXong = $this->yeuCau([]);
        $matTep = $this->yeuCau(['trang_thai' => Xml3176TepXuat::XONG, 'duong_dan' => 'xml3176-tep-xuat/khong-co.xlsx']);

        $this->assertSame($xong->id, $this->s->timDeTai(1, $xong->id)->id);
        $this->assertNull($this->s->timDeTai(2, $xong->id), 'Nguoi khac khong tai duoc');
        $this->assertNull($this->s->timDeTai(1, $chuaXong->id));
        $this->assertNull($this->s->timDeTai(1, $matTep->id));
        $this->assertNull($this->s->timDeTai(1, 999));
    }

    /** @test */
    public function ten_tep_va_duong_dan()
    {
        $y = $this->yeuCau([]);

        $this->assertSame('xml3176-tep-xuat/' . $y->id . '.xlsx', Xml3176TepXuatService::duongDanTep($y));
        $this->assertSame('xml3176_loi_20260929_20260930090000.xlsx', Xml3176TepXuatService::tenTepTai($y));
    }
}

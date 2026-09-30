<?php

namespace Tests\Feature;

use App\Jobs\XuatTepLoiXml3176Job;
use App\Models\BHYT\Xml3176TepXuat;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\Support\UserGiaCoQuyen;
use Tests\Support\Xml3176RuleTestSupport;
use Tests\TestCase;

class Xml3176TepXuatControllerTest extends TestCase
{
    use Xml3176RuleTestSupport;

    protected function setUp()
    {
        parent::setUp();
        $this->bootXml3176Sqlite(['2026_09_30_100000_create_xml3176_tep_xuat_table.php']);
        Queue::fake();
        Storage::fake('local');
    }

    private function thamSo()
    {
        return [
            'date_from' => '2026-09-29 00:00:00', 'date_to' => '2026-09-29 23:59:59',
            'date_type' => 'date_payment', 'hein_card_filter' => 'has_hein_card',
        ];
    }

    /** @test */
    public function bam_xuat_tao_yeu_cau_va_day_job()
    {
        $r = $this->actingAs(UserGiaCoQuyen::tao(7))
            ->postJson(route('bhyt.xml3176.tep-xuat.tao'), $this->thamSo())
            ->assertStatus(200)
            ->json();

        $this->assertFalse($r['trung']);
        $this->assertSame('cho', $r['trang_thai']);
        $this->assertSame(7, Xml3176TepXuat::find($r['id'])->user_id);
        $this->assertSame('has_hein_card', Xml3176TepXuat::find($r['id'])->bo_loc['hein_card_filter']);
        Queue::assertPushed(XuatTepLoiXml3176Job::class, 1);
    }

    /** @test */
    public function danh_sach_tra_json_cua_nguoi_dang_nhap()
    {
        Xml3176TepXuat::create(['user_id' => 7, 'loai' => 'loi', 'bo_loc' => $this->thamSo(), 'trang_thai' => 'cho']);
        Xml3176TepXuat::create(['user_id' => 8, 'loai' => 'loi', 'bo_loc' => $this->thamSo(), 'trang_thai' => 'cho']);

        $r = $this->actingAs(UserGiaCoQuyen::tao(7))
            ->getJson(route('bhyt.xml3176.tep-xuat.danh-sach'))
            ->assertStatus(200)
            ->json();

        $this->assertCount(1, $r['data']);
    }

    /** @test */
    public function tai_tep_cua_minh_duoc_nguoi_khac_404()
    {
        $y = Xml3176TepXuat::create(['user_id' => 7, 'loai' => 'loi', 'bo_loc' => $this->thamSo(), 'trang_thai' => 'xong']);
        $y->update(['duong_dan' => 'xml3176-tep-xuat/' . $y->id . '.xlsx']);
        Storage::disk('local')->put($y->duong_dan, 'NOI DUNG');

        $this->actingAs(UserGiaCoQuyen::tao(8))
            ->get(route('bhyt.xml3176.tep-xuat.tai', ['id' => $y->id]))
            ->assertStatus(404);

        $r = $this->actingAs(UserGiaCoQuyen::tao(7))
            ->get(route('bhyt.xml3176.tep-xuat.tai', ['id' => $y->id]));
        $r->assertStatus(200);
        $this->assertContains('xml3176_loi_20260929_', $r->headers->get('content-disposition'));
    }

    /** @test */
    public function tai_yeu_cau_chua_xong_404()
    {
        $y = Xml3176TepXuat::create(['user_id' => 7, 'loai' => 'loi', 'bo_loc' => $this->thamSo(), 'trang_thai' => 'dang_tao']);

        $this->actingAs(UserGiaCoQuyen::tao(7))
            ->get(route('bhyt.xml3176.tep-xuat.tai', ['id' => $y->id]))
            ->assertStatus(404);
    }

    /** @test */
    public function route_xuat_cu_chuyen_ve_man_danh_sach_khong_xuat_dong_bo()
    {
        $r = $this->actingAs(UserGiaCoQuyen::tao(7))
            ->get(route('bhyt.xml3176.export-xml3176-xml-errors', $this->thamSo()));

        $r->assertRedirect(route('bhyt.xml3176.index'));
        Queue::assertNothingPushed();
        $this->assertSame(0, Xml3176TepXuat::count());
    }
}

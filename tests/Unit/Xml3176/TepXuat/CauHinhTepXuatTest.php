<?php

namespace Tests\Unit\Xml3176\TepXuat;

use App\Models\BHYT\Xml3176TepXuat;
use Illuminate\Support\Facades\Schema;
use Tests\Support\Xml3176RuleTestSupport;
use Tests\TestCase;

class CauHinhTepXuatTest extends TestCase
{
    use Xml3176RuleTestSupport;

    protected function setUp()
    {
        parent::setUp();
        $this->bootXml3176Sqlite(['2026_09_30_100000_create_xml3176_tep_xuat_table.php']);
    }

    /** @test */
    public function bang_co_du_cot()
    {
        foreach (['id', 'user_id', 'loai', 'bo_loc', 'trang_thai', 'duong_dan', 'kich_thuoc', 'loi',
                  'bat_dau_luc', 'xong_luc', 'created_at', 'updated_at'] as $cot) {
            $this->assertTrue(Schema::hasColumn('xml3176_tep_xuat', $cot), "Thieu cot $cot");
        }
    }

    /** @test */
    public function model_luu_bo_loc_dang_mang()
    {
        $y = Xml3176TepXuat::create([
            'user_id' => 1, 'loai' => Xml3176TepXuat::LOAI_LOI,
            'bo_loc' => ['date_from' => '2026-09-29 00:00:00', 'ma_khoa' => 'K01'],
            'trang_thai' => Xml3176TepXuat::CHO,
        ]);

        $this->assertSame(['date_from' => '2026-09-29 00:00:00', 'ma_khoa' => 'K01'], $y->fresh()->bo_loc);
        $this->assertSame(['cho', 'dang_tao', 'xong', 'loi'],
            [Xml3176TepXuat::CHO, Xml3176TepXuat::DANG_TAO, Xml3176TepXuat::XONG, Xml3176TepXuat::LOI]);
    }

    /** @test */
    public function ket_noi_hang_doi_rieng_cho_viec_chay_lau()
    {
        // Job xuat chay 12-30 phut. Dung chung ket noi 'database' (retry_after 300) thi sau 5
        // phut hang doi coi job da chet va giao lai.
        $this->assertSame('database', config('queue.connections.xuat_tep.driver'));
        $this->assertSame('jobs', config('queue.connections.xuat_tep.table'));
        $this->assertGreaterThanOrEqual(3600, config('queue.connections.xuat_tep.retry_after'));
        $this->assertSame(300, config('queue.connections.database.retry_after'),
            'Ket noi database dung cho chuoi kiem-xuat-ky-gui, khong duoc doi');
    }

    /** @test */
    public function khoa_cau_hinh_xuat_tep()
    {
        $this->assertSame('xuat_tep', config('xml3176.xuat_tep_connection'));
        $this->assertSame('JobXuatTepXml3176', config('xml3176.xuat_tep_queue_name'));
        $this->assertSame(7, config('xml3176.xuat_tep_giu_ngay'));
        $this->assertSame(90, config('xml3176.xuat_tep_treo_phut'));
    }
}

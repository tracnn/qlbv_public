<?php

namespace Tests\Unit\Xml3176\Chuoi;

use App\Services\Xml3176Service;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\Support\Xml3176RuleTestSupport;
use Tests\TestCase;

/**
 * Hai cot moi cua chuoi kiem -> xuat -> ky -> gui, va viec nap lai xoa trang thai chuoi.
 *
 * chain_token: ma phien xu ly - moi lan dung chuoi moi (nap hoac lenh cuu) sinh ma moi,
 * job cua chuoi cu so ma thay lech thi tu thoi.
 * signed_file_path: buoc ky ghi, buoc gui doc - thay cho tham so duong dan truoc day.
 */
class CotChuoiXuLyTest extends TestCase
{
    use Xml3176RuleTestSupport;

    protected function setUp()
    {
        parent::setUp();
        $this->bootXml3176Sqlite([
            '2026_01_09_162044_create_xml3176_informations_table.php',
            '2026_03_28_201021_add_sign_method_to_xml3176_informations.php',
            '2026_09_28_100000_them_checked_at_vao_xml3176_informations.php',
            '2026_09_29_120000_them_chain_token_va_signed_file_path_vao_xml3176_informations.php',
        ]);
    }

    /** @test */
    public function migration_them_hai_cot()
    {
        $this->assertTrue(Schema::hasColumn('xml3176_informations', 'chain_token'));
        $this->assertTrue(Schema::hasColumn('xml3176_informations', 'signed_file_path'));
    }

    /** @test */
    public function model_cho_ghi_hai_cot()
    {
        $fillable = (new \App\Models\BHYT\Xml3176Information())->getFillable();

        $this->assertContains('chain_token', $fillable);
        $this->assertContains('signed_file_path', $fillable);
    }

    /** @test */
    public function co_ten_hang_doi_ky_rieng()
    {
        $this->assertSame('JobSignXml3176', config('xml3176.sign_queue_name'));
    }

    /** @test */
    public function nap_lai_xoa_trang_thai_chuoi_nhung_giu_dau_vet_da_gui()
    {
        DB::table('xml3176_informations')->insert([
            'ma_lk' => 'LK1', 'macskcb' => '01929',
            'checked_at' => '2026-09-29 10:00:00',
            'exported_at' => '2026-09-29 10:01:00',
            'signed_file_path' => '01929/2026.09.29_10.01.00_LK1.xml',
            'submitted_at' => '2026-09-29 10:02:00',
        ]);

        (new Xml3176Service())->storeXml3176Information('LK1', '01929', 'import');

        $r = DB::table('xml3176_informations')->where('ma_lk', 'LK1')->first();
        $this->assertNull($r->checked_at, 'Khong xoa thi chuoi moi dung dau kiem cua lan nap truoc');
        $this->assertNull($r->exported_at);
        $this->assertNull($r->signed_file_path, 'Khong xoa thi buoc gui co the gui tep cua lan nap truoc');
        // submitted_at la dau vet ho so da tung len cong, can cho doi soat (chot 28/09/2026).
        $this->assertSame('2026-09-29 10:02:00', $r->submitted_at);
    }
}

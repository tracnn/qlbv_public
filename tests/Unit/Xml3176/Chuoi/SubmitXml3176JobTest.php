<?php

namespace Tests\Unit\Xml3176\Chuoi;

use App\Jobs\SubmitXml3176Job;
use App\Services\Xml3176Service;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\Support\Xml3176RuleTestSupport;
use Tests\TestCase;

class SubmitXml3176JobTest extends TestCase
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
        DB::table('xml3176_informations')->insert([
            'ma_lk' => 'LK1', 'macskcb' => '01929', 'chain_token' => 'MA_DUNG',
            'signed_file_path' => '01929/2026.09.29_10.00.00_LK1.xml',
        ]);
        Storage::fake('exportXml3176');
        Storage::disk('exportXml3176')->put('01929/2026.09.29_10.00.00_LK1.xml', '<DAKY/>');
        config(['organization.BHYT.submit_xml_3176_enabled' => true]);
    }

    /** Dich vu gui gia: ghi lai noi dung va ma co so nhan duoc, tra ket qua 200. */
    private function dichVuGia()
    {
        return new class {
            public $noiDung = null;
            public $maCoSo = null;
            public function submitXml($xml, $url, $loai, $maTinh, $maCoSo)
            {
                $this->noiDung = $xml;
                $this->maCoSo = $maCoSo;
                return ['maKetQua' => '200', 'maGiaoDich' => 'GD1', 'thongDiep' => 'OK'];
            }
        };
    }

    private function chay(SubmitXml3176Job $job)
    {
        $gia = $this->dichVuGia();
        $job->submitServiceGia = $gia;
        $job->handle(new Xml3176Service());

        return $gia;
    }

    private function cot($ten)
    {
        return DB::table('xml3176_informations')->where('ma_lk', 'LK1')->value($ten);
    }

    /** @test */
    public function trong_chuoi_doc_duong_dan_tep_da_ky_tu_ho_so()
    {
        $gia = $this->chay(new SubmitXml3176Job('LK1', 'MA_DUNG'));

        $this->assertSame('<DAKY/>', $gia->noiDung);
        $this->assertSame('01929', $gia->maCoSo, 'Ma co so lay tu chinh ho so');
        $this->assertNotNull($this->cot('submitted_at'));
    }

    /** @test */
    public function ma_lech_thi_khong_gui()
    {
        $job = new SubmitXml3176Job('LK1', 'MA_CU');
        $job->chained = ['x'];

        $gia = $this->chay($job);

        $this->assertNull($gia->noiDung);
        $this->assertSame([], $job->chained);
    }

    /** @test */
    public function khong_co_duong_dan_tep_thi_ghi_loi_gui()
    {
        DB::table('xml3176_informations')->update(['signed_file_path' => null]);

        $gia = $this->chay(new SubmitXml3176Job('LK1', 'MA_DUNG'));

        $this->assertNull($gia->noiDung);
        $this->assertContains('không tìm thấy tệp đã ký', $this->cot('submit_error'));
    }

    /** @test */
    public function job_cu_mang_san_duong_dan_van_gui_nhu_truoc()
    {
        // Job serialize boi ma cu (truoc chuoi): tep da qua du cua kiem theo ma cu.
        $job = new SubmitXml3176Job('LK1');
        foreach (['xmlFilePath' => '01929/2026.09.29_10.00.00_LK1.xml', 'macskcb' => '01929'] as $ten => $giaTri) {
            $p = new \ReflectionProperty(SubmitXml3176Job::class, $ten);
            $p->setAccessible(true);
            $p->setValue($job, $giaTri);
        }

        $gia = $this->chay($job);

        $this->assertSame('<DAKY/>', $gia->noiDung);
    }

    /** @test */
    public function tat_gui_thi_khong_gui()
    {
        config(['organization.BHYT.submit_xml_3176_enabled' => false]);

        $gia = $this->chay(new SubmitXml3176Job('LK1', 'MA_DUNG'));

        $this->assertNull($gia->noiDung);
    }

    /** @test */
    public function hong_het_luot_thi_ghi_loi_gui_neu_ma_con_khop()
    {
        (new SubmitXml3176Job('LK1', 'MA_DUNG'))->failed(new \RuntimeException('mang chap'));
        $this->assertSame('Gửi lỗi — mang chap', $this->cot('submit_error'));

        DB::table('xml3176_informations')->update(['submit_error' => null]);
        (new SubmitXml3176Job('LK1', 'MA_CU'))->failed(new \RuntimeException('x'));
        $this->assertNull($this->cot('submit_error'));
    }
}

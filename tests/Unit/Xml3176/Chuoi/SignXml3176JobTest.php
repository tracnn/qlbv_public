<?php

namespace Tests\Unit\Xml3176\Chuoi;

use App\Jobs\SignXml3176Job;
use App\Services\Xml3176Service;
use Illuminate\Support\Facades\DB;
use Tests\Support\Xml3176RuleTestSupport;
use Tests\TestCase;

/**
 * Buoc KY trong chuoi. Quyet dinh chuoi co di tiep toi buoc GUI hay khong - thay cho viec
 * truoc day processExportXml() hoi QuyetDinhGui roi moi dispatch job gui.
 */
class SignXml3176JobTest extends TestCase
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
        DB::table('xml3176_informations')->insert(['ma_lk' => 'LK1', 'macskcb' => '01929', 'chain_token' => 'MA_DUNG']);
        config(['organization.BHYT.submit_xml_3176_enabled' => true]);
    }

    /** Service gia: kyVaGhiTep() tra ket qua dung san. */
    private function service($ketQua)
    {
        return new class($ketQua) extends Xml3176Service {
            public $soLanKy = 0;
            private $kq;
            public function __construct($kq) { $this->kq = $kq; }
            public function kyVaGhiTep($ma) { $this->soLanKy++; return $this->kq; }
        };
    }

    private function chay($ketQua, $maPhien = 'MA_DUNG')
    {
        $s = $this->service($ketQua);
        $job = new SignXml3176Job('LK1', $maPhien);
        $job->chained = ['gui'];
        $job->handle($s);

        return [$job, $s];
    }

    private function cot($ten)
    {
        return DB::table('xml3176_informations')->where('ma_lk', 'LK1')->value($ten);
    }

    /** @test */
    public function ky_duoc_va_bat_gui_thi_chuoi_di_tiep()
    {
        list($job, $s) = $this->chay(['isSigned' => true, 'macskcb' => '01929']);

        $this->assertSame(1, $s->soLanKy);
        $this->assertSame(['gui'], $job->chained);
    }

    /** @test */
    public function chua_ky_thi_ghi_nhan_va_khong_gui()
    {
        // Chua ky ma bo qua im lang thi nguoi dung khong biet vi sao ho so khong di.
        list($job,) = $this->chay(['isSigned' => false, 'macskcb' => '01929']);

        $this->assertSame('Hồ sơ chưa ký số, không gửi lên cổng BHXH', $this->cot('submit_error'));
        $this->assertSame([], $job->chained);
    }

    /** @test */
    public function tat_gui_thi_cat_chuoi_va_khong_ghi_gi()
    {
        // Tat gui thi khong co lan gui nao dien ra - ghi submit_error la BIA.
        config(['organization.BHYT.submit_xml_3176_enabled' => false]);

        list($job,) = $this->chay(['isSigned' => true, 'macskcb' => '01929']);

        $this->assertNull($this->cot('submit_error'));
        $this->assertSame([], $job->chained);
    }

    /** @test */
    public function khong_co_tep_cho_ky_thi_ghi_loi_ky_va_cat_chuoi()
    {
        list($job,) = $this->chay(false);

        $this->assertContains('không tìm thấy tệp chờ ký', $this->cot('signed_error'));
        $this->assertSame([], $job->chained);
    }

    /** @test */
    public function ma_lech_thi_khong_ky()
    {
        list($job, $s) = $this->chay(['isSigned' => true, 'macskcb' => '01929'], 'MA_CU');

        $this->assertSame(0, $s->soLanKy);
        $this->assertSame([], $job->chained);
    }

    /** @test */
    public function hong_het_luot_thi_ghi_loi_ky_neu_ma_con_khop()
    {
        (new SignXml3176Job('LK1', 'MA_DUNG'))->failed(new \RuntimeException('HSM khong phan hoi'));
        $this->assertSame('Ký lỗi — HSM khong phan hoi', $this->cot('signed_error'));

        DB::table('xml3176_informations')->update(['signed_error' => null]);
        (new SignXml3176Job('LK1', 'MA_CU'))->failed(new \RuntimeException('x'));
        $this->assertNull($this->cot('signed_error'));
    }
}

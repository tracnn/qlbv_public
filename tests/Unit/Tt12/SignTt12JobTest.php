<?php

namespace Tests\Unit\Tt12;

use Tests\TestCase;
use Tests\Support\DungBangTt12Sqlite;
use Illuminate\Support\Facades\Storage;
use App\Models\BHYT\Tt12\Tt12HoSo;
use App\Models\BHYT\Tt12\Tt12Dong;
use App\Jobs\SignTt12Job;
use App\Services\XMLSignService;

class SignTt12JobTest extends TestCase
{
    use DungBangTt12Sqlite;

    protected function setUp()
    {
        parent::setUp();
        $this->dungBangTt12();

        Storage::fake('exportTt12');

        config(['organization.tt12.sign_enabled' => true]);
    }

    private function hoSo(array $ghiDe = array())
    {
        $hoSo = Tt12HoSo::create(array_merge(array(
            'ma_ho_so' => 'TT12_MAU_01_01929_20260825_001',
            'mau' => 'MAU_01', 'loai_hs' => '70', 'ma_cskcb' => '01929',
            'ten_tep' => 'x.xlsx', 'so_dong' => 1, 'id_danh_sach' => 'Id-abc',
            'checked_at' => '2026-08-25 10:00:00', 'so_loi' => 0,
        ), $ghiDe));

        Tt12Dong::create(array(
            'ho_so_id' => $hoSo->id, 'stt' => 1,
            'du_lieu' => array('STT' => '1', 'MA_KHOA' => 'K01', 'TEN_KHOA' => 'Khám bệnh',
                               'TU_NGAY' => '20260101', 'MA_CSKCB' => '01929'),
        ));

        return $hoSo;
    }

    /** Dich vu ky gia, ke thua lop that de giu dung chu ky phuong thuc */
    private function dichVuKy($thanhCong = true)
    {
        return new class($thanhCong) extends XMLSignService {
            public $thanhCong;
            public $xmlNhanDuoc;

            public function __construct($thanhCong)
            {
                // Bo qua constructor cha de khong khoi tao Guzzle that.
                $this->thanhCong = $thanhCong;
            }

            public function signXml($xmlContent)
            {
                $this->xmlNhanDuoc = $xmlContent;

                if (!$this->thanhCong) {
                    return array('isSigned' => false, 'data' => $xmlContent,
                                 'error' => 'HSM khong phan hoi', 'method' => 'HSM');
                }

                return array('isSigned' => true, 'method' => 'HSM',
                             'data' => str_replace('<CHUKYDONVI/>',
                                 '<CHUKYDONVI><Signature/></CHUKYDONVI>', $xmlContent));
            }
        };
    }

    /** @test */
    public function ky_thanh_cong_thi_ghi_tep_va_danh_dau_da_ky()
    {
        $hoSo = $this->hoSo();
        $ky = $this->dichVuKy(true);

        (new SignTt12Job($hoSo->ma_ho_so))->handle($ky);

        $hoSo = $hoSo->fresh();

        $this->assertTrue((bool) $hoSo->is_signed);
        $this->assertSame('HSM', $hoSo->sign_method);
        $this->assertNotNull($hoSo->signed_at);
        $this->assertNull($hoSo->signed_error);
        $this->assertNotEmpty($hoSo->duong_dan_da_ky);

        Storage::disk('exportTt12')->assertExists($hoSo->duong_dan_da_ky);
    }

    /** @test */
    public function ky_that_bai_thi_KHONG_ghi_tep()
    {
        // Ghi mot tep chua ky vao duong "da ky" la de lai mot qua bom: lan gui sau doc
        // dung tep do va gui len cong mot goi khong co chu ky.
        $hoSo = $this->hoSo();

        (new SignTt12Job($hoSo->ma_ho_so))->handle($this->dichVuKy(false));

        $hoSo = $hoSo->fresh();

        $this->assertFalse((bool) $hoSo->is_signed);
        $this->assertContains('HSM khong phan hoi', $hoSo->signed_error);
        $this->assertNull($hoSo->duong_dan_da_ky);
        $this->assertSame(array(), Storage::disk('exportTt12')->allFiles());
    }

    /** @test */
    public function ho_so_con_loi_thi_khong_ky()
    {
        $hoSo = $this->hoSo(array('so_loi' => 2));
        $ky = $this->dichVuKy(true);

        (new SignTt12Job($hoSo->ma_ho_so))->handle($ky);

        $this->assertNull($ky->xmlNhanDuoc, 'Khong duoc goi dich vu ky');
        $this->assertFalse((bool) $hoSo->fresh()->is_signed);
    }

    /** @test */
    public function ho_so_chua_kiem_thi_khong_ky()
    {
        $hoSo = $this->hoSo(array('checked_at' => null));
        $ky = $this->dichVuKy(true);

        (new SignTt12Job($hoSo->ma_ho_so))->handle($ky);

        $this->assertNull($ky->xmlNhanDuoc);
    }

    /** @test */
    public function chuc_nang_ky_dang_tat_thi_khong_ghi_signed_error()
    {
        // Ghi signed_error khi chuc nang dang tat la bia: nguoi doc se tuong da thu ky
        // va that bai.
        config(['organization.tt12.sign_enabled' => false]);

        $hoSo = $this->hoSo();
        $ky = $this->dichVuKy(true);

        (new SignTt12Job($hoSo->ma_ho_so))->handle($ky);

        // Chi kiem signed_error la null thi khong phan biet duoc "job dung dung cho"
        // voi "job chay het va ky thanh cong" - ca hai deu cho signed_error = null. Phai
        // kiem them dich vu ky KHONG duoc goi va ho so KHONG bi danh dau da ky.
        $this->assertNull($ky->xmlNhanDuoc, 'Khong duoc goi dich vu ky');

        $hoSo = $hoSo->fresh();
        $this->assertFalse((bool) $hoSo->is_signed);
        $this->assertNull($hoSo->signed_at);
        $this->assertNull($hoSo->signed_error);
        $this->assertSame(array(), Storage::disk('exportTt12')->allFiles());
    }

    /** @test */
    public function ho_so_da_bi_xoa_thi_job_ket_thuc_em_tham()
    {
        $ky = $this->dichVuKy(true);

        (new SignTt12Job('KHONG_TON_TAI'))->handle($ky);

        $this->assertNull($ky->xmlNhanDuoc);
    }
}

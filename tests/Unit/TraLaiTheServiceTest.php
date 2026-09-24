<?php

namespace Tests\Unit;

use App\Jobs\jobKtTheBHYT;
use App\Services\BHYT\TraLaiThe;
use App\Services\OrderCheck\TreatmentProfileService;
use Illuminate\Support\Facades\Queue;
use Tests\Support\DungBangHoSoHisSqlite;
use Tests\TestCase;

class TraLaiTheServiceTest extends TestCase
{
    use DungBangHoSoHisSqlite;

    const MA = '01013250800123';

    protected function setUp()
    {
        parent::setUp();
        $this->chuanBiBangHoSo();
        Queue::fake();
        config(['organization.BHYT_CO_SO' => ['01001' => ['username' => 'u']]]);
    }

    private function gui($ma)
    {
        return app(TraLaiThe::class)->gui($ma);
    }

    private function thamSoJob($job)
    {
        $r = new \ReflectionProperty(get_class($job), 'params');
        $r->setAccessible(true);

        return $r->getValue($job);
    }

    /** @test */
    public function ma_rong()
    {
        $this->assertSame(['ok' => false, 'message' => 'Chưa nhập mã điều trị'], $this->gui('  '));
        Queue::assertNotPushed(jobKtTheBHYT::class);
    }

    /** @test */
    public function khong_co_ho_so()
    {
        $this->assertSame(['ok' => false, 'message' => 'Không tìm thấy hồ sơ với mã này trên HIS'], $this->gui('KHONG-CO'));
    }

    /** @test */
    public function his_loi()
    {
        $this->app->instance(TreatmentProfileService::class, new class extends TreatmentProfileService {
            public function cua($treatmentCode)
            {
                throw new \Exception('Oracle sap');
            }
        });

        $this->assertSame(['ok' => false, 'message' => 'Không lấy được thông tin từ HIS'], $this->gui(self::MA));
    }

    /** @test */
    public function thieu_the()
    {
        $this->themHoSo(['tdl_hein_card_number' => null]);
        $this->assertSame(['ok' => false, 'message' => 'Hồ sơ không có mã thẻ BHYT'], $this->gui(self::MA));
    }

    /** @test */
    public function thieu_gioi_tinh()
    {
        $this->themHoSo(['tdl_patient_gender_id' => null]);
        $this->assertSame(['ok' => false, 'message' => 'Hồ sơ thiếu giới tính'], $this->gui(self::MA));
    }

    /** @test */
    public function co_so_khong_co_trong_cau_hinh()
    {
        config(['organization.BHYT_CO_SO' => ['09999' => ['username' => 'u']]]);
        $this->themHoSo();
        $this->assertSame(['ok' => false, 'message' => 'Không xác định được cơ sở của hồ sơ'], $this->gui(self::MA));
        Queue::assertNotPushed(jobKtTheBHYT::class);
    }

    /** @test */
    public function hop_le_day_dung_mot_job_dung_hang_doi_dung_tham_so()
    {
        $this->themHoSo();

        $kq = $this->gui(self::MA);

        $this->assertTrue($kq['ok']);
        $this->assertSame('Đã gửi yêu cầu tra lại thẻ, bấm Tra cứu lại sau ít giây để xem kết quả', $kq['message']);
        Queue::assertPushed(jobKtTheBHYT::class, 1);
        Queue::assertPushedOn('JobKtTheBHYT', jobKtTheBHYT::class);
        Queue::assertPushed(jobKtTheBHYT::class, function ($job) {
            $p = $this->thamSoJob($job);
            $this->assertSame(2, $p['gioiTinh']);          // HIS 1 = Nam -> cong 2
            $this->assertSame('01001', $p['maCskcb']);
            $this->assertSame('01005', $p['maDkbd']);
            $this->assertSame('DN4010112345678', $p['maThe']);
            $this->assertSame('20/02/1979', $p['ngaySinh']);

            return true;
        });
    }

    /** @test */
    public function the_tam_van_day_job_de_xoa_ket_qua_loi_cu()
    {
        // Job gap the tam se xoa dong loi cu thay vi goi cong: nut bam cung la cach don tung dong.
        $this->themHoSo(['tdl_hein_card_number' => 'TE1010000012345', 'tdl_hein_medi_org_code' => '01000']);

        $kq = $this->gui(self::MA);

        $this->assertTrue($kq['ok']);
        $this->assertSame('Thẻ tạm trẻ sơ sinh (nơi ĐKBĐ 01000), không tra cổng BHXH — đã gửi yêu cầu xoá kết quả lỗi cũ', $kq['message']);
        Queue::assertPushedOn('JobKtTheBHYT', jobKtTheBHYT::class);
    }
}

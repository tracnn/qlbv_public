<?php

namespace Tests\Feature;

use App\Jobs\jobKtTheBHYT;
use Illuminate\Support\Facades\Queue;
use Tests\Support\DungBangHoSoHisSqlite;
use Tests\Support\DungBangLoiDotDieuTriSqlite;
use Tests\TestCase;

/** Nguoi dung gia: hasRole() tra true dung cho danh sach role duoc cap. */
class NguoiDungCoRole extends \App\User
{
    public $roles = [];

    public function hasRole($role, $team = null, $requireAll = false)
    {
        return in_array($role, $this->roles);
    }

    public function can($permission, $team = null, $requireAll = false)
    {
        return false;
    }
}

class TraCuuLoiHoSoTest extends TestCase
{
    use DungBangLoiDotDieuTriSqlite;
    use DungBangHoSoHisSqlite;

    const MA = '01013250800123';

    protected function setUp()
    {
        parent::setUp();
        $this->chuanBiBangLoi();
        $this->chuanBiBangHoSo();
    }

    protected function nguoiDung(array $roles)
    {
        $u = new NguoiDungCoRole();
        $u->id = 1;
        $u->roles = $roles;

        return $u;
    }

    protected function traCuu($ma, array $roles = ['tra-cuu-loi-ho-so'])
    {
        return $this->actingAs($this->nguoiDung($roles))
            ->getJson('/khth/tra-cuu-loi-ho-so/tra-cuu?treatment_code=' . urlencode($ma));
    }

    /** @test */
    public function khong_co_role_thi_403()
    {
        $this->actingAs($this->nguoiDung(['xml-man']))
            ->get('/khth/tra-cuu-loi-ho-so')
            ->assertStatus(403);
    }

    /** @test */
    public function co_role_thi_mo_duoc_man_hinh()
    {
        $this->actingAs($this->nguoiDung(['tra-cuu-loi-ho-so']))
            ->get('/khth/tra-cuu-loi-ho-so')
            ->assertStatus(200);
    }

    /** @test */
    public function man_hinh_hien_thi_giao_dien_tra_cuu_that_khong_phai_khung_rong()
    {
        $this->actingAs($this->nguoiDung(['tra-cuu-loi-ho-so']))
            ->get('/khth/tra-cuu-loi-ho-so')
            ->assertStatus(200)
            ->assertSee('id="ma-dieu-tri"', false)
            ->assertSee('id="khoi-order-check"', false)
            ->assertSee('id="khoi-hein-card"', false)
            ->assertSee('id="khoi-xml3176"', false)
            ->assertSee('id="btn-camera"', false);
    }

    /** @test */
    public function ho_so_co_loi_tra_du_ho_so_va_ba_nhom()
    {
        $this->themHoSo();
        $this->themViPham(['treatment_code' => self::MA, 'severity' => 'critical']);

        $this->traCuu(self::MA)
            ->assertStatus(200)
            ->assertJson([
                'profile' => ['patient_name' => 'Nguyễn Văn A', 'ma_cskcb' => '01001'],
                'summary' => ['order_check' => 1, 'has_error' => true],
            ])
            ->assertJsonStructure([
                'profile', 'profile_error',
                'data' => ['treatment_code', 'order_check', 'hein_card', 'xml3176'],
                'summary' => ['total', 'critical', 'has_error'],
            ]);
    }

    /** @test */
    public function ho_so_sach_van_tra_200_va_khong_co_loi()
    {
        $this->themHoSo();

        $res = $this->traCuu(self::MA)->assertStatus(200);

        $res->assertJson(['summary' => ['total' => 0, 'has_error' => false]]);
        $this->assertSame([], $res->json()['data']['order_check']);
    }

    /** @test */
    public function khong_co_ho_so_tren_his_nhung_van_tra_loi_cua_mysql()
    {
        $this->themViPham(['treatment_code' => self::MA]);

        $res = $this->traCuu(self::MA)->assertStatus(200);

        $this->assertNull($res->json()['profile']);
        $this->assertCount(1, $res->json()['data']['order_check']);
    }

    /** @test */
    public function ma_rong_tra_422()
    {
        $this->traCuu('')
            ->assertStatus(422)
            ->assertJson(['message' => 'Chưa nhập mã điều trị']);
    }

    /** @test */
    public function his_hong_van_tra_200_kem_profile_error_va_loi_cua_mysql()
    {
        $this->themViPham(['treatment_code' => self::MA]);

        // Gia lap Oracle (HIS) hong: ep TreatmentProfileService nem exception de
        // kiem tra nhanh catch trong controller, khong phai nhanh "khong tim thay".
        $this->app->instance(\App\Services\OrderCheck\TreatmentProfileService::class, new class extends \App\Services\OrderCheck\TreatmentProfileService {
            public function cua($treatmentCode)
            {
                throw new \Exception('Oracle sap');
            }
        });

        $res = $this->traCuu(self::MA)->assertStatus(200);

        $this->assertNull($res->json()['profile']);
        $this->assertSame('Không lấy được thông tin từ HIS', $res->json()['profile_error']);
        $this->assertCount(1, $res->json()['data']['order_check']);
    }

    /** @test */
    public function trang_in_hien_ho_so_va_loi()
    {
        $this->themHoSo();
        $this->themViPham(['treatment_code' => self::MA, 'message' => 'Loi y lenh in thu']);

        $res = $this->actingAs($this->nguoiDung(['tra-cuu-loi-ho-so']))
            ->get('/khth/tra-cuu-loi-ho-so/in?treatment_code=' . self::MA);

        $res->assertStatus(200);
        $res->assertSee('Nguyễn Văn A');
        $res->assertSee('Loi y lenh in thu');
    }

    /** @test */
    public function trang_in_thieu_ma_thi_422()
    {
        $this->actingAs($this->nguoiDung(['tra-cuu-loi-ho-so']))
            ->get('/khth/tra-cuu-loi-ho-so/in')
            ->assertStatus(422);
    }

    protected function traLaiThe($ma)
    {
        return $this->actingAs($this->nguoiDung(['tra-cuu-loi-ho-so']))
            ->postJson('/khth/tra-cuu-loi-ho-so/tra-lai-the', ['treatment_code' => $ma]);
    }

    /** @test */
    public function tra_lai_the_dispatch_job_mot_lan_voi_tham_so_dung()
    {
        Queue::fake();
        config(['organization.BHYT_CO_SO' => ['01001' => ['username' => 'u']]]);
        $this->themHoSo();

        $this->traLaiThe(self::MA)->assertStatus(200);

        Queue::assertPushed(jobKtTheBHYT::class, 1);
        Queue::assertPushed(jobKtTheBHYT::class, function ($job) {
            $p = $this->thamSoJob($job);

            // gender_code cua ho so mau la '1' (Nam); cong BHXH dung quy uoc nguoc lai
            // nen phai gui 2. Sai cho nay thi cong tra ve ket qua sai gioi tinh.
            $this->assertSame(2, $p['gioiTinh']);
            $this->assertSame('01001', $p['maCskcb']);     // co so dieu tri, tu his_branch
            $this->assertSame('01005', $p['maDkbd']);      // noi DKBD, tu the benh nhan
            $this->assertSame('DN4010112345678', $p['maThe']);
            $this->assertSame(self::MA, $p['ma_lk']);
            $this->assertFalse($this->coDungKetQuaCu($job));

            return true;
        });
    }

    /** Doc thuoc tinh protected cua job de kiem tham so da dong goi. */
    protected function thamSoJob($job)
    {
        $r = new \ReflectionProperty(get_class($job), 'params');
        $r->setAccessible(true);

        return $r->getValue($job);
    }

    protected function coDungKetQuaCu($job)
    {
        $r = new \ReflectionProperty(get_class($job), 'checkOldValue');
        $r->setAccessible(true);

        return $r->getValue($job);
    }

    /** @test */
    public function tra_lai_the_chan_khi_co_so_khong_nam_trong_cau_hinh()
    {
        Queue::fake();
        config(['organization.BHYT_CO_SO' => ['09999' => ['username' => 'u']]]);
        $this->themHoSo();

        $this->traLaiThe(self::MA)->assertStatus(422);

        Queue::assertNotPushed(jobKtTheBHYT::class);
    }

    /** @test */
    public function tra_lai_the_chan_khi_thieu_ma_the()
    {
        Queue::fake();
        config(['organization.BHYT_CO_SO' => ['01001' => ['username' => 'u']]]);
        $this->themHoSo(['treatment_code' => 'HS-KHONG-THE', 'tdl_hein_card_number' => null]);

        $this->traLaiThe('HS-KHONG-THE')->assertStatus(422);

        Queue::assertNotPushed(jobKtTheBHYT::class);
    }

    /** @test */
    public function tra_lai_the_chan_khi_thieu_gioi_tinh()
    {
        Queue::fake();
        config(['organization.BHYT_CO_SO' => ['01001' => ['username' => 'u']]]);
        $this->themHoSo(['treatment_code' => 'HS-KHONG-GT', 'tdl_patient_gender_id' => null]);

        $this->traLaiThe('HS-KHONG-GT')->assertStatus(422);

        Queue::assertNotPushed(jobKtTheBHYT::class);
    }

    /** @test */
    public function tra_lai_the_chan_khi_khong_co_ho_so()
    {
        Queue::fake();

        $this->traLaiThe('KHONG-CO')->assertStatus(422);

        Queue::assertNotPushed(jobKtTheBHYT::class);
    }
}

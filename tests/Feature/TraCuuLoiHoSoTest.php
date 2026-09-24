<?php

namespace Tests\Feature;

use App\Jobs\jobKtTheBHYT;
use Illuminate\Support\Facades\Queue;
use Tests\Support\DungBangHoSoHisSqlite;
use Tests\Support\DungBangLoiDotDieuTriSqlite;
use Tests\TestCase;

/**
 * Nguoi dung gia: hasRole() tra true dung cho danh sach role duoc cap.
 * Ten rieng cho feature nay (khong dat NguoiDungCoRole chung chung) de test feature khac
 * trong cung namespace Tests\Feature khong dung ten lop va va cham.
 */
class NguoiDungTraCuuLoiHoSo extends \App\User
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
        $u = new NguoiDungTraCuuLoiHoSo();
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
            ->assertSee('id="bang-order-check"', false)
            ->assertSee('id="bang-hein-card"', false)
            ->assertSee('id="bang-xml3176"', false);
    }

    /**
     * Ba bang loi phai la DataTable. Thead phai co san trong HTML: DataTables doc cau
     * truc cot tu thead, thieu no thi bang khong khoi tao duoc.
     *
     * @test
     */
    public function ba_bang_loi_co_thead_de_datatables_khoi_tao()
    {
        $res = $this->actingAs($this->nguoiDung(['tra-cuu-loi-ho-so']))
            ->get('/khth/tra-cuu-loi-ho-so')
            ->assertStatus(200);

        $res->assertSee('DataTable(', false);

        foreach (['Mức độ', 'Mã tra cứu', 'Mã lỗi'] as $tieuDe) {
            $res->assertSee('<th>' . $tieuDe . '</th>', false);
        }
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
    public function mysql_hong_van_tra_200_kem_data_error_va_ho_so_cua_his()
    {
        $this->themHoSo();

        // Gia lap MySQL hong: ep TreatmentIssueService nem exception de kiem tra nhanh
        // catch rieng trong controller (Finding 5) - truoc day nhanh nay khong duoc bat,
        // MySQL hong se lam ca request 500 dong theo ca ho so doc tu Oracle.
        $this->app->instance(\App\Services\OrderCheck\TreatmentIssueService::class, new class extends \App\Services\OrderCheck\TreatmentIssueService {
            public function cua($treatmentCode = null, array $tuyChon = [])
            {
                throw new \Exception('MySQL sap');
            }
        });

        $res = $this->traCuu(self::MA)->assertStatus(200);

        $this->assertSame('Nguyễn Văn A', $res->json()['profile']['patient_name']);
        $this->assertSame([], $res->json()['data']['order_check']);
        $this->assertNotNull($res->json()['data_error']);
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
    public function trang_in_bao_loi_oracle_khong_noi_khong_tim_thay_ho_so()
    {
        $this->themViPham(['treatment_code' => self::MA]);

        // Finding 2: Oracle hong khac han "khong tim thay". Truoc day in() nuot loi va
        // phieu in ghi mot cau sai su that "Khong tim thay ho so voi ma nay tren HIS".
        $this->app->instance(\App\Services\OrderCheck\TreatmentProfileService::class, new class extends \App\Services\OrderCheck\TreatmentProfileService {
            public function cua($treatmentCode)
            {
                throw new \Exception('Oracle sap khi in');
            }
        });

        $res = $this->actingAs($this->nguoiDung(['tra-cuu-loi-ho-so']))
            ->get('/khth/tra-cuu-loi-ho-so/in?treatment_code=' . self::MA);

        $res->assertStatus(200);
        $res->assertSee('Không lấy được thông tin từ HIS');
        $res->assertDontSee('Không tìm thấy hồ sơ với mã này trên HIS');
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

        // Ten queue la load-bearing: mot service Windows va mot worker docker rieng chi
        // lang nghe dung ten 'JobKtTheBHYT'. assertPushed khong kiem ten queue - thieu
        // dong nay thi ai do xoa ->onQueue() ma moi test van xanh, nut bam se lang le
        // khong lam gi trong production.
        Queue::assertPushedOn('JobKtTheBHYT', jobKtTheBHYT::class);

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
    public function tra_lai_the_chan_the_tam_so_sinh_va_bao_ro_ly_do()
    {
        // Job se bo qua the tam; khong chan o day thi bam nut xong "khong co gi xay ra".
        Queue::fake();
        config(['organization.BHYT_CO_SO' => ['01001' => ['username' => 'u']]]);
        $this->themHoSo([
            'treatment_code' => 'HS-THE-TAM',
            'tdl_hein_card_number' => 'TE1010000012345',
            'tdl_hein_medi_org_code' => '01000',
        ]);

        $this->traLaiThe('HS-THE-TAM')
            ->assertStatus(422)
            ->assertJsonFragment(['message' => 'Thẻ tạm trẻ sơ sinh (nơi ĐKBĐ 01000), không tra cổng BHXH']);

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

    /**
     * Spec muc 8 diem 6: toan bo lap luan bao mat cho nut doi trang thai dua vao viec
     * route khth.order-check-index/update-status nam trong nhom checkrole:order-check.
     * Nguoi chi co tra-cuu-loi-ho-so KHONG duoc phep doi trang thai du man hinh nay goi
     * thang vao route do (xem muc 6.1 cua spec) - test nay chot lai dieu do, khong gi
     * khac pin no.
     *
     * @test
     */
    public function co_tra_cuu_loi_ho_so_nhung_thieu_order_check_thi_403_khi_doi_trang_thai()
    {
        $this->themHoSo();
        $this->themViPham(['treatment_code' => self::MA, 'status' => 'new']);

        $id = \App\Models\OrderCheck\OrderCheckViolation::where('treatment_code', self::MA)->value('id');

        $res = $this->actingAs($this->nguoiDung(['tra-cuu-loi-ho-so']))
            ->postJson('/khth/order-check-index/update-status', [
                'id' => $id,
                'status' => 'seen',
            ]);

        $res->assertStatus(403);

        $this->assertSame(
            'new',
            \App\Models\OrderCheck\OrderCheckViolation::find($id)->status
        );
    }
}

<?php

namespace Tests\Feature;

use App\Jobs\jobKtTheBHYT;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Tests\Support\DungBangHoSoHisSqlite;
use Tests\Support\DungBangLoiDotDieuTriSqlite;
use Tests\TestCase;

/** Nguoi dung gia: hasRole() dung cho role duoc cap (factory that bi CheckRole chan 403). */
class NguoiDungKetQuaTraThe extends \App\User
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

class KetQuaTraCuuTheTraLaiTest extends TestCase
{
    use DungBangLoiDotDieuTriSqlite;
    use DungBangHoSoHisSqlite;

    protected function setUp()
    {
        parent::setUp();
        $this->chuanBiBangLoi();
        $this->chuanBiBangHoSo();
    }

    private function nguoiDung(array $roles = ['xml-man'])
    {
        $u = new NguoiDungKetQuaTraThe();
        $u->id = 1;
        $u->roles = $roles;

        return $u;
    }

    private function themKetQua(array $ghiDe = [])
    {
        DB::table('check_hein_cards')->insert(array_merge([
            'ma_lk' => '01013250800123', 'ma_cskcb' => '01001', 'ma_tracuu' => '050', 'ma_kiemtra' => '11',
            'ghi_chu' => 'Thẻ không tồn tại!', 'ma_the' => null, 'ho_ten' => null, 'ngay_sinh' => null,
            'ma_the_gui' => 'DN4010112345678', 'ho_ten_gui' => 'Nguyễn Văn A', 'ngay_sinh_gui' => '20/02/1979',
            'created_at' => '2026-09-24 08:00:00', 'updated_at' => '2026-09-24 08:00:00',
        ], $ghiDe));
    }

    private function fetch(array $q = [])
    {
        return $this->actingAs($this->nguoiDung())
            ->getJson(route('bhyt.check-hein-card.fetch-data', array_merge([
                'draw' => 1, 'start' => 0, 'length' => 10,
                'tu_ngay' => '2026-09-01', 'den_ngay' => '2026-09-30',
            ], $q)))
            ->assertStatus(200)
            ->json();
    }

    /** @test */
    public function tra_lai_can_quyen_xml_man()
    {
        $this->actingAs($this->nguoiDung(['khac']))
            ->postJson(route('bhyt.check-hein-card.tra-lai'), ['ma_lk' => '01013250800123'])
            ->assertStatus(403);
    }

    /** @test */
    public function tra_lai_day_dung_mot_job_dung_hang_doi()
    {
        Queue::fake();
        config(['organization.BHYT_CO_SO' => ['01001' => ['username' => 'u']]]);
        $this->themHoSo();

        $this->actingAs($this->nguoiDung())
            ->postJson(route('bhyt.check-hein-card.tra-lai'), ['ma_lk' => '01013250800123'])
            ->assertStatus(200)
            ->assertJsonFragment(['message' => 'Đã gửi yêu cầu tra lại thẻ, bấm Tra cứu lại sau ít giây để xem kết quả']);

        Queue::assertPushed(jobKtTheBHYT::class, 1);
        Queue::assertPushedOn('JobKtTheBHYT', jobKtTheBHYT::class);
    }

    /** @test */
    public function tra_lai_thieu_ma_tra_422()
    {
        Queue::fake();

        $this->actingAs($this->nguoiDung())
            ->postJson(route('bhyt.check-hein-card.tra-lai'), [])
            ->assertStatus(422)
            ->assertJsonFragment(['message' => 'Chưa nhập mã điều trị']);

        Queue::assertNotPushed(jobKtTheBHYT::class);
    }

    /** @test */
    public function fetch_hien_gia_tri_da_gui_khi_cong_bo_trong()
    {
        $this->themKetQua();

        $d = $this->fetch()['data'][0];

        $this->assertSame('DN4010112345678', $d['hien_ma_the']);
        $this->assertSame('gui', $d['nguon_ma_the']);
        $this->assertSame('Nguyễn Văn A', $d['hien_ho_ten']);
        $this->assertSame('20/02/1979', $d['hien_ngay_sinh']);
    }

    /** @test */
    public function fetch_uu_tien_gia_tri_cong()
    {
        $this->themKetQua(['ma_the' => 'DN4010199999999', 'ma_tracuu' => '000', 'ma_kiemtra' => '09']);

        $d = $this->fetch()['data'][0];

        $this->assertSame('DN4010199999999', $d['hien_ma_the']);
        $this->assertSame('cong', $d['nguon_ma_the']);
    }

    /** @test */
    public function tim_theo_ho_ten_da_gui()
    {
        $this->themKetQua();
        $this->themKetQua(['ma_lk' => 'HS-KHAC', 'ho_ten_gui' => 'Trần Thị B', 'ma_the_gui' => 'HT3010000000001']);

        $this->assertSame(1, $this->fetch(['tim' => 'Trần Thị'])['recordsFiltered']);
        $this->assertSame(1, $this->fetch(['tim' => 'HT301000'])['recordsFiltered']);
    }
}

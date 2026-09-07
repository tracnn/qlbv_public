<?php

namespace Tests\Feature;

use App\Models\Mcct\McctChiPhi;
use App\Models\Mcct\McctTraCuu;
use Tests\TestCase;

class McctApiTest extends TestCase
{
    const TOKEN = 'token-thu-nghiem';
    const MA_THE = 'HT9999999999999';

    /** @var array id cac ban ghi da tao, de don dep */
    protected $daTao = [];

    protected function setUp()
    {
        parent::setUp();

        config(['organization.api.access_token' => hash('sha256', self::TOKEN)]);
        config(['mcct.khoang_cho_lam_moi' => 900]);
    }

    /**
     * Don dep theo dung id minh tao ra.
     *
     * KHONG dung RefreshDatabase: ngay 2026-08-21 mot test dung DatabaseMigrations da DROP
     * sach CSDL phat trien.
     */
    protected function tearDown()
    {
        foreach ($this->daTao as $id) {
            McctChiPhi::where('tra_cuu_id', $id)->delete();
            McctTraCuu::where('id', $id)->delete();
        }

        parent::tearDown();
    }

    protected function goi(array $thamSo, $token = self::TOKEN)
    {
        return $this->getJson(
            '/api/mcct/tra-cuu?' . http_build_query($thamSo),
            ['Authorization' => 'Bearer ' . $token]
        );
    }

    /** Tao mot phien tra cuu thanh cong da luu, kem mot dot KCB */
    protected function taoBanGhi($traLuc = '2026-09-07 11:45:11')
    {
        $ban = McctTraCuu::create([
            'ma_cskcb' => '01929',
            'ma_the' => self::MA_THE,
            'ho_ten' => 'NGUYEN VAN TEST',
            'ngay_sinh' => '01/01/1980',
            'ma_ket_qua' => '200',
            'ghi_chu' => 'Nguồn DL ... tính đến: 14/08/2026 14:41',
            'the_ho_ten' => 'Nguyễn Văn Test',
            'the_ngay_sinh' => '01/01/1980',
            'the_ngay_ket_thuc' => '2027-06-30',
            'the_ma_bhxh' => '0100000009',
            'luy_ke_lon_nhat' => 1396758,
            'nguong_ap_dung' => 15180000,
            'du_dieu_kien_mien' => 0,
            'nguon' => 'thu_cong',
            'tra_boi' => 'kiemthu',
            'tra_luc' => $traLuc,
        ]);

        $this->daTao[] = $ban->id;

        McctChiPhi::create([
            'tra_cuu_id' => $ban->id,
            'id_cong' => 3090063339,
            'ma_the' => self::MA_THE,
            'ma_cskcb' => '01929',
            'ngay_vao' => '2026-06-12',
            'ngay_ra' => '2026-06-23',
            'ma_doi_tuong_kcb' => '1.5',
            't_bn_cct_mcct' => 1120157,
            't_bn_cct_luy_ke' => 1396758,
            'ngay_nhan_cong' => '2026-06-23',
            'ngay_nhan' => '2026-06-23',
            'ngay_tra_cuu' => '2026-09-07',
        ]);

        return $ban;
    }

    /** @test */
    public function thieu_token_thi_tra_401()
    {
        $this->getJson('/api/mcct/tra-cuu?ma_the=' . self::MA_THE)
            ->assertStatus(401)
            ->assertJson(['success' => false]);
    }

    /** @test */
    public function sai_token_thi_tra_401()
    {
        $this->goi(['ma_the' => self::MA_THE], 'token-sai')
            ->assertStatus(401)
            ->assertJson(['success' => false]);
    }

    /** @test */
    public function thieu_ma_the_thi_tra_422_dung_khuon()
    {
        $this->goi([])
            ->assertStatus(422)
            ->assertJson([
                'success' => false,
                'error' => ['code' => 'VALIDATION_ERROR'],
            ]);
    }

    /** @test */
    public function ma_the_sai_do_dai_thi_tra_422()
    {
        $this->goi(['ma_the' => 'ABC123'])
            ->assertStatus(422)
            ->assertJson(['success' => false, 'error' => ['code' => 'VALIDATION_ERROR']]);
    }

    /**
     * lam_moi=1 doi ba tham so kia. Chan o day chu khong de cong tra 400 - vua tiet kiem
     * luot goi, vua bao loi dung cho sai.
     */
    /** @test */
    public function lam_moi_ma_thieu_ho_ten_thi_tra_422()
    {
        $this->goi([
            'ma_the' => self::MA_THE,
            'lam_moi' => 1,
            'ngay_sinh' => '01/01/1980',
            'ma_cskcb' => '01929',
        ])->assertStatus(422)
          ->assertJson(['success' => false, 'error' => ['code' => 'VALIDATION_ERROR']]);
    }

    /** Chua tung tra: KHONG phai loi. data = null, kem trang thai trong meta. */
    /** @test */
    public function chua_tung_tra_thi_tra_data_null()
    {
        $this->goi(['ma_the' => 'HT0000000000000'])
            ->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => null,
                'meta' => ['trang_thai' => 'chua_tra_lan_nao'],
            ]);
    }

    /** @test */
    public function co_ban_ghi_thi_tra_du_khuon_data()
    {
        $this->taoBanGhi();

        $ra = $this->goi(['ma_the' => self::MA_THE])->assertStatus(200);

        $ra->assertJson([
            'success' => true,
            'data' => [
                'ma_the' => self::MA_THE,
                'nguon' => 'da_luu',
                'tra_luc' => '2026-09-07 11:45:11',
            ],
        ]);

        // Laravel 5.5: TestResponse::json() KHONG nhan tham so khoa (khac 5.6+), luon tra ca
        // response. Dung array_get() de lay dung nhanh 'data' theo dung khuon.
        $data = array_get($ra->json(), 'data');

        foreach (['ma_the', 'nguon', 'tra_luc', 'ghi_chu', 'thong_tin_the',
            'luy_ke_cung_chi_tra', 'nguong_ca_nam', 'con_thieu',
            'du_nguong_6_thang_luong', 'can_kiem_5_nam_lien_tuc', 'chi_tiet'] as $khoa) {
            $this->assertArrayHasKey($khoa, $data, "Thieu khoa $khoa trong data");
        }

        $this->assertContains('tính đến: 14/08/2026 14:41', $data['ghi_chu']);
        $this->assertCount(1, $data['chi_tiet']);
        $this->assertEquals(1396758, $data['luy_ke_cung_chi_tra']);
    }

    /**
     * Dieu kien mien gom HAI ve, API chi kiem duoc mot. Ten truong phai la
     * du_nguong_6_thang_luong chu KHONG phai du_dieu_kien_mien, va co
     * can_kiem_5_nam_lien_tuc phai luon co mat - neu khong, ben goi se hien thang cho can bo
     * la "du dieu kien", dung cai sai vua sua tren man hinh qlbv.
     */
    /** @test */
    public function khong_duoc_co_truong_du_dieu_kien_mien()
    {
        $this->taoBanGhi();

        // Xem chu thich o test co_ban_ghi_thi_tra_du_khuon_data ve vi sao dung array_get().
        $data = array_get($this->goi(['ma_the' => self::MA_THE])->json(), 'data');

        $this->assertArrayNotHasKey('du_dieu_kien_mien', $data);
        $this->assertTrue($data['can_kiem_5_nam_lien_tuc']);
        $this->assertFalse($data['du_nguong_6_thang_luong']);
    }

    /**
     * lam_moi=1 nhung vua tra xong: bo qua, tra ban da luu kem co bao ro. Tra DU LIEU chu
     * khong tra 429 - mot vong lap hong ben goi se khong sinh them vong thu lai.
     *
     * Test nay KHONG cham cong: moc tra_luc dat ngay bay gio nen nhanh goi cong khong bao gio
     * duoc chay toi.
     */
    /** @test */
    public function lam_moi_trong_khau_do_thi_bo_qua_va_khong_goi_cong()
    {
        $this->taoBanGhi(date('Y-m-d H:i:s'));

        $ra = $this->goi([
            'ma_the' => self::MA_THE,
            'lam_moi' => 1,
            'ho_ten' => 'NGUYEN VAN TEST',
            'ngay_sinh' => '01/01/1980',
            'ma_cskcb' => '01929',
        ])->assertStatus(200);

        $ra->assertJson([
            'success' => true,
            'data' => ['nguon' => 'da_luu'],
            'meta' => ['bo_qua_lam_moi' => true],
        ]);

        $this->assertGreaterThan(0, array_get($ra->json(), 'meta.lam_moi_duoc_sau'));
    }
}

<?php

namespace Tests\Unit;

use App\Jobs\jobKtTheBHYT;
use Illuminate\Support\Facades\DB;
use Tests\Support\DungBangLoiDotDieuTriSqlite;
use Tests\TestCase;

/**
 * Cong bao loi (vd 050 "The khong ton tai!") khong tra so the/ho ten/ngay sinh: dong loi chi
 * con ma ho so. Job phai luu gia tri MINH DA GUI de man ket qua con cai de nhin.
 */
class JobKtTheBHYTGhiThongTinGuiTest extends TestCase
{
    use DungBangLoiDotDieuTriSqlite;

    protected function setUp()
    {
        parent::setUp();
        $this->chuanBiBangLoi();
    }

    private function ketQuaCong(array $ghiDe = [])
    {
        $k = array_fill_keys(['maKetQua', 'ghiChu', 'maThe', 'hoTen', 'ngaySinh', 'diaChi', 'maTheCu',
            'maTheMoi', 'maDKBD', 'cqBHXH', 'gioiTinh', 'gtTheTu', 'gtTheDen', 'maKV', 'ngayDu5Nam',
            'maSoBHXH', 'gtTheTuMoi', 'gtTheDenMoi', 'maDKBDMoi', 'tenDKBDMoi'], null);

        return array_merge($k, $ghiDe);
    }

    private function ghi(array $ketQua)
    {
        $job = new jobKtTheBHYT([
            'maThe' => 'DN4010112345678', 'hoTen' => 'Nguyễn Văn A', 'ngaySinh' => '20/02/1979',
            'ma_lk' => 'HS1', 'maCskcb' => '01929', 'maDkbd' => '01005', 'gioiTinh' => 2,
        ]);
        $m = new \ReflectionMethod($job, 'addCheckHeinCard');
        $m->setAccessible(true);
        $m->invoke($job, 'HS1', $ketQua['maKetQua'], '11', $ketQua);

        return DB::table('check_hein_cards')->where('ma_lk', 'HS1')->first();
    }

    /** @test */
    public function cong_bao_loi_van_luu_gia_tri_da_gui()
    {
        $r = $this->ghi($this->ketQuaCong(['maKetQua' => '050', 'ghiChu' => 'Thẻ không tồn tại!']));

        $this->assertNull($r->ma_the);
        $this->assertSame('DN4010112345678', $r->ma_the_gui);
        $this->assertSame('Nguyễn Văn A', $r->ho_ten_gui);
        $this->assertSame('20/02/1979', $r->ngay_sinh_gui);
        $this->assertSame('01005', $r->ma_dkbd_gui);
    }

    /** @test */
    public function luu_duoc_han_the_moi()
    {
        $r = $this->ghi($this->ketQuaCong([
            'maKetQua' => '000', 'gtTheTuMoi' => '01/10/2026', 'gtTheDenMoi' => '30/09/2027',
        ]));

        $this->assertSame('01/10/2026', $r->gt_the_tumoi);
        $this->assertSame('30/09/2027', $r->gt_the_denmoi);
    }
}

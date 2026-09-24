<?php

namespace Tests\Unit;

use App\Jobs\jobKtTheBHYT;
use Illuminate\Support\Facades\DB;
use Tests\Support\DungBangLoiDotDieuTriSqlite;
use Tests\TestCase;

/**
 * The tam tre so sinh (TE1 + DKBD XX000) khong tra cong BHXH: cong luon tra 050/11 "The
 * khong ton tai!" - loi gia. Job la diem chung cua ca 3 noi dispatch (lenh quet HIS, nap
 * XML, nut Tra lai the) nen chan o day.
 */
class JobKtTheBHYTTheTamTest extends TestCase
{
    use DungBangLoiDotDieuTriSqlite;

    protected function setUp()
    {
        parent::setUp();
        $this->chuanBiBangLoi();
        config(['organization.BHYT.enableCheck' => true]);
    }

    private function thamSo(array $ghiDe = [])
    {
        return array_merge([
            'maThe' => 'TE1373700012345', 'hoTen' => 'BE SO SINH', 'ngaySinh' => '22/07/2026',
            'ma_lk' => '000006850061', 'maCskcb' => '01929', 'maDkbd' => '37000', 'gioiTinh' => 1,
        ], $ghiDe);
    }

    private function themKetQua($maLk, $traCuu, $kiemTra)
    {
        DB::table('check_hein_cards')->insert([
            'ma_lk' => $maLk, 'ma_tracuu' => $traCuu, 'ma_kiemtra' => $kiemTra, 'ghi_chu' => 'The khong ton tai!',
        ]);
    }

    /** @test */
    public function the_tam_khong_goi_cong_va_khong_tao_ket_qua()
    {
        // Neu job khong thoat som, no se dung BHYTLoginService va goi mang - test se vo
        // hoac treo, chu khong lang le xanh.
        (new jobKtTheBHYT($this->thamSo()))->handle();

        $this->assertSame(0, DB::table('check_hein_cards')->count());
    }

    /** @test */
    public function the_tam_xoa_ket_qua_loi_cu_cua_ho_so()
    {
        $this->themKetQua('000006850061', '050', '11');
        $this->themKetQua('HS-KHAC', '050', '11');

        (new jobKtTheBHYT($this->thamSo()))->handle();

        $this->assertSame(0, DB::table('check_hein_cards')->where('ma_lk', '000006850061')->count());
        $this->assertSame(1, DB::table('check_hein_cards')->where('ma_lk', 'HS-KHAC')->count(),
            'Chi duoc xoa ket qua cua dung ho so dang xet');
    }

    /** @test */
    public function the_tam_giu_ket_qua_cu_hop_le()
    {
        $this->themKetQua('000006850061', '000', '00');

        (new jobKtTheBHYT($this->thamSo(), false))->handle();

        $this->assertSame(1, DB::table('check_hein_cards')->where('ma_lk', '000006850061')->count());
    }

    /** @test */
    public function tat_kiem_tra_thi_khong_dong_toi_ket_qua_cu()
    {
        config(['organization.BHYT.enableCheck' => false]);
        $this->themKetQua('000006850061', '050', '11');

        (new jobKtTheBHYT($this->thamSo()))->handle();

        $this->assertSame(1, DB::table('check_hein_cards')->count());
    }
}

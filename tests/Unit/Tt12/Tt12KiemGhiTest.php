<?php

namespace Tests\Unit\Tt12;

use Tests\TestCase;
use Tests\Support\DungBangTt12Sqlite;
use App\Models\BHYT\Tt12\Tt12HoSo;
use App\Models\BHYT\Tt12\Tt12Dong;
use App\Models\BHYT\Tt12\Tt12Loi;
use App\Services\Tt12\Kiem\Tt12Kiem;

class Tt12KiemGhiTest extends TestCase
{
    use DungBangTt12Sqlite;

    protected function setUp()
    {
        parent::setUp();
        $this->dungBangTt12();
    }

    private function hoSoVoiDong(array $cacDuLieu)
    {
        $hoSo = Tt12HoSo::create(array(
            'ma_ho_so' => 'TT12_MAU_01_01929_20260825_001',
            'mau' => 'MAU_01', 'loai_hs' => '70', 'ma_cskcb' => '01929',
            'ten_tep' => 'x.xlsx', 'so_dong' => count($cacDuLieu), 'id_danh_sach' => 'Id-x',
        ));

        foreach ($cacDuLieu as $i => $duLieu) {
            Tt12Dong::create(array(
                'ho_so_id' => $hoSo->id,
                'stt'      => $i + 1,
                'du_lieu'  => $duLieu,
            ));
        }

        return $hoSo;
    }

    private function dong(array $ghiDe = array())
    {
        return array_merge(array(
            'STT' => '1', 'MA_KHOA' => 'K01', 'TEN_KHOA' => 'Khám bệnh',
            'BAN_KHAM' => '3', 'GIUONG_PD' => '0', 'GIUONG_TK' => '0',
            'GIUONG_HSTC' => '0', 'GIUONG_HSCC' => '0',
            'TU_NGAY' => '20260101', 'DEN_NGAY' => '', 'MA_CSKCB' => '01929',
        ), $ghiDe);
    }

    /** @test */
    public function ho_so_sach_co_so_loi_bang_khong_va_da_duoc_danh_dau_kiem()
    {
        $hoSo = $this->hoSoVoiDong(array($this->dong(), $this->dong(array('MA_KHOA' => 'K02'))));

        $soLoi = (new Tt12Kiem())->kiem($hoSo);

        $this->assertSame(0, $soLoi);
        $this->assertSame(0, (int) $hoSo->fresh()->so_loi);
        $this->assertNotNull($hoSo->fresh()->checked_at);
        $this->assertSame(0, Tt12Loi::count());
    }

    /** @test */
    public function loi_duoc_ghi_kem_so_dong_va_ten_cot()
    {
        $hoSo = $this->hoSoVoiDong(array($this->dong(array('MA_KHOA' => ''))));

        (new Tt12Kiem())->kiem($hoSo);

        $loi = Tt12Loi::first();

        $this->assertSame('THIEU_BAT_BUOC', $loi->ma_loi);
        $this->assertSame('MA_KHOA', $loi->cot);
        $this->assertSame(1, (int) $loi->stt_dong);
        $this->assertSame('loi', $loi->muc_do);
    }

    /** @test */
    public function kiem_lai_khong_cong_don_loi()
    {
        $hoSo = $this->hoSoVoiDong(array($this->dong(array('MA_KHOA' => ''))));

        $kiem = new Tt12Kiem();
        $kiem->kiem($hoSo);
        $lanDau = Tt12Loi::count();

        $kiem->kiem($hoSo->fresh());

        $this->assertSame($lanDau, Tt12Loi::count(), 'Kiem lai phai xoa loi cu truoc');
        $this->assertSame($lanDau, (int) $hoSo->fresh()->so_loi);
    }

    /** @test */
    public function so_loi_chi_dem_muc_loi_khong_dem_canh_bao()
    {
        $hoSo = $this->hoSoVoiDong(array($this->dong()));

        Tt12Loi::create(array(
            'ho_so_id' => $hoSo->id, 'ma_loi' => 'TU_TAO',
            'muc_do' => 'canh_bao', 'mo_ta' => 'Cảnh báo thử',
        ));

        // Kiem lai se xoa canh bao tu tao o tren - dung y do: Tt12Kiem la nguon duy nhat
        // sinh loi cho mot ho so.
        $soLoi = (new Tt12Kiem())->kiem($hoSo);

        $this->assertSame(0, $soLoi);
        $this->assertSame(0, Tt12Loi::where('ho_so_id', $hoSo->id)->count());
    }
}

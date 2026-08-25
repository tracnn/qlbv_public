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

    /** Ho so MAU_03: mau duy nhat cung MAU_04 co cap cot thoi han hop dong TU/DEN_NGAY_HD */
    private function hoSoMau03(array $cacDuLieu)
    {
        $hoSo = Tt12HoSo::create(array(
            'ma_ho_so' => 'TT12_MAU_03_01929_20260825_001',
            'mau' => 'MAU_03', 'loai_hs' => '10', 'ma_cskcb' => '01929',
            'ten_tep' => 'thuoc.xlsx', 'so_dong' => count($cacDuLieu), 'id_danh_sach' => 'Id-y',
        ));

        foreach ($cacDuLieu as $i => $duLieu) {
            Tt12Dong::create(array(
                'ho_so_id' => $hoSo->id, 'stt' => $i + 1, 'du_lieu' => $duLieu,
            ));
        }

        return $hoSo;
    }

    private function dongMau03(array $ghiDe = array())
    {
        return array_merge(array(
            'STT' => '1', 'MA_THUOC' => 'T001', 'TEN_THUOC' => 'Paracetamol 500mg',
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
        // Phai dung mot luat THAT sinh canh bao, khong tu tao ban ghi tt12_loi: ghi() xoa
        // sach tt12_loi truoc khi dem, nen ban ghi tu tao khong bao gio den duoc phep dem.
        // Ban truoc cua test nay lam dung the va vi vay hai khang dinh cua no dung BAT KE
        // laLoi() tra gi - doi 'if ($mot->laLoi())' thanh 'if (true)' van xanh.
        //
        // DEN_HD_TRUOC_TU_HD la canh bao dung nghia: sai thoi han hop dong KHONG lam XML
        // sai cau truc nen khong dang chan ky.
        $hoSo = $this->hoSoMau03(array($this->dongMau03(array(
            'TU_NGAY_HD'  => '20260601',
            'DEN_NGAY_HD' => '20260101',
        ))));

        $soLoi = (new Tt12Kiem())->kiem($hoSo);

        $this->assertSame(0, $soLoi, 'Canh bao khong duoc lam tang so_loi');
        $this->assertSame(0, (int) $hoSo->fresh()->so_loi);

        $cacLoi = Tt12Loi::where('ho_so_id', $hoSo->id)->get();

        $this->assertCount(1, $cacLoi, 'Canh bao phai duoc GHI de nguoi dung nhin thay');
        $this->assertSame('DEN_HD_TRUOC_TU_HD', $cacLoi[0]->ma_loi);
        $this->assertSame('canh_bao', $cacLoi[0]->muc_do);
    }

    /** @test */
    public function ho_so_mang_mau_la_bi_chan_bang_mot_loi()
    {
        // Cong chan duy nhat khi tt12_ho_so.mau mang ma khong nam trong dang ky - khong co
        // lop dac ta nao de dung XML.
        $hoSo = $this->hoSoVoiDong(array($this->dong()));
        $hoSo->update(array('mau' => 'MAU_99'));

        $soLoi = (new Tt12Kiem())->kiem($hoSo->fresh());

        $this->assertSame(1, $soLoi);

        $loi = Tt12Loi::where('ho_so_id', $hoSo->id)->first();

        $this->assertSame('MAU_LA', $loi->ma_loi);
        $this->assertSame('loi', $loi->muc_do);
        $this->assertContains('MAU_99', $loi->mo_ta);
    }

    /** @test */
    public function vuot_tran_thi_ngung_ghi_nhung_so_loi_van_dem_dung_tong_that()
    {
        // MOT thao tac Excel sai (de trong ca cot TU_NGAY) tren tep 30.000 dong sinh 30.000
        // doi tuong loi, moi cai mang mot chuoi mo ta - job chet tren may chu 128 MB va ho
        // so ket o CHUA_KIEM. Dat tran so ban ghi ghi lai, nhung so_loi van phai la TONG
        // THAT chu khong phai so da ghi: nguoi dung can biet quy mo that de quyet dinh sua
        // tep hay sua quy trinh.
        $cacDong = array();

        for ($i = 0; $i < 5; $i++) {
            $cacDong[] = $this->dong(array('MA_KHOA' => '', 'TEN_KHOA' => ''));
        }

        $hoSo = $this->hoSoVoiDong($cacDong);

        $soLoi = (new Tt12Kiem(4))->kiem($hoSo);

        $this->assertSame(10, $soLoi, 'So loi phai la tong that: 5 dong x 2 cot thieu');
        $this->assertSame(10, (int) $hoSo->fresh()->so_loi);

        $cacLoi = Tt12Loi::where('ho_so_id', $hoSo->id)->get();

        $this->assertCount(5, $cacLoi, '4 ban ghi loi + 1 ban ghi tong ket');

        $tongKet = Tt12Loi::where('ho_so_id', $hoSo->id)
            ->where('ma_loi', 'VUOT_TRAN_LOI')->first();

        $this->assertNotNull($tongKet, 'Phai co mot ban ghi tong ket cho phan bi cat');
        $this->assertContains('6', $tongKet->mo_ta, 'Phai noi ro con bao nhieu loi khong liet ke');
        $this->assertSame('canh_bao', $tongKet->muc_do,
            'Ban ghi tong ket khong duoc lam phong so_loi');
    }

    /** @test */
    public function khong_vuot_tran_thi_khong_co_ban_ghi_tong_ket()
    {
        $hoSo = $this->hoSoVoiDong(array($this->dong(array('MA_KHOA' => ''))));

        (new Tt12Kiem(4))->kiem($hoSo);

        $this->assertSame(0, Tt12Loi::where('ho_so_id', $hoSo->id)
            ->where('ma_loi', 'VUOT_TRAN_LOI')->count());
    }
}

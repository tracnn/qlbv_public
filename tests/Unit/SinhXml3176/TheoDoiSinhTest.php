<?php

namespace Tests\Unit\SinhXml3176;

use App\Services\SinhXml3176\CauHinhSinhXml3176;
use App\Services\SinhXml3176\TheoDoiSinh;
use Tests\Support\DungBangHisSinhXml3176Sqlite;
use Tests\TestCase;

class TheoDoiSinhTest extends TestCase
{
    use DungBangHisSinhXml3176Sqlite;

    private $td;

    protected function setUp()
    {
        parent::setUp();
        $this->dungBangTheoDoi();
        $this->td = new TheoDoiSinh();
    }

    private function ghiChinhThuc($trangThai, $feeLock, $id = 1)
    {
        $this->td->ghi([
            'treatment_id'   => $id,
            'treatment_code' => '000' . $id,
            'loai_ban'       => CauHinhSinhXml3176::CHINH_THUC,
            'fee_lock_time'  => $feeLock,
            'trang_thai'     => $trangThai,
        ]);
    }

    public function test_moc_mac_dinh_0_va_luu_duoc()
    {
        $this->assertSame(0, $this->td->docMoc(CauHinhSinhXml3176::CHINH_THUC));
        $this->td->datMoc(CauHinhSinhXml3176::CHINH_THUC, 20261022100000);
        $this->assertSame(20261022100000, $this->td->docMoc(CauHinhSinhXml3176::CHINH_THUC));
        $this->td->datMoc(CauHinhSinhXml3176::CHINH_THUC, 20261022120000);
        $this->assertSame(20261022120000, $this->td->docMoc(CauHinhSinhXml3176::CHINH_THUC));
        $this->assertSame(0, $this->td->docMoc(CauHinhSinhXml3176::THONG_TUYEN));
    }

    public function test_co_moc_phan_biet_chua_co_dong_voi_moc_0()
    {
        $ct = CauHinhSinhXml3176::CHINH_THUC;
        $this->assertFalse($this->td->coMoc($ct));
        $this->td->datMoc($ct, 0);
        $this->assertTrue($this->td->coMoc($ct));
        $this->assertSame(0, $this->td->docMoc($ct));
        $this->assertFalse($this->td->coMoc(CauHinhSinhXml3176::THONG_TUYEN));
    }

    public function test_can_xet_khi_chua_co_dong()
    {
        $this->assertTrue($this->td->canXet(1, CauHinhSinhXml3176::CHINH_THUC, 20261022100000));
        $this->assertNull($this->td->dongCuoi(1, CauHinhSinhXml3176::CHINH_THUC));
    }

    public function test_khong_xet_khi_modify_time_bang()
    {
        $this->ghiCoModify(20261022100000, 'abc');
        $this->assertFalse($this->td->canXet(1, CauHinhSinhXml3176::CHINH_THUC, 20261022100000));
        $this->assertFalse($this->td->canXet(1, CauHinhSinhXml3176::CHINH_THUC, '20261022100000'));
    }

    public function test_xet_khi_modify_time_khac()
    {
        $this->ghiCoModify(20261022100000, 'abc');
        $this->assertTrue($this->td->canXet(1, CauHinhSinhXml3176::CHINH_THUC, 20261022110000));
    }

    public function test_xet_khi_dong_cu_chua_co_modify_time()
    {
        $this->ghiChinhThuc('da_ghi', 20261022100000);
        $dong = $this->td->dongCuoi(1, CauHinhSinhXml3176::CHINH_THUC);
        $this->assertNotNull($dong);
        $this->assertNull($dong->modify_time);
        $this->assertNull($dong->noi_dung_hash);
        $this->assertTrue($this->td->canXet(1, CauHinhSinhXml3176::CHINH_THUC, 20261022100000));
    }

    public function test_cap_nhat_modify_khong_them_dong()
    {
        $this->ghiCoModify(20261022100000, 'abc');
        $dong = $this->td->dongCuoi(1, CauHinhSinhXml3176::CHINH_THUC);
        $this->td->capNhatModify($dong, 20261022120000);

        $this->assertSame(1, \App\Models\SinhXml3176\LanSinh::count());
        $sau = $this->td->dongCuoi(1, CauHinhSinhXml3176::CHINH_THUC);
        $this->assertSame(20261022120000, (int) $sau->modify_time);
        $this->assertSame('abc', $sau->noi_dung_hash);
        $this->assertFalse($this->td->canXet(1, CauHinhSinhXml3176::CHINH_THUC, 20261022120000));
    }

    public function test_ghi_luu_hash_va_modify_time()
    {
        $hash = sha1('noi dung');
        $this->ghiCoModify(20261022100000, $hash);
        $dong = $this->td->dongCuoi(1, CauHinhSinhXml3176::CHINH_THUC);
        $this->assertSame($hash, $dong->noi_dung_hash);
        $this->assertSame(20261022100000, (int) $dong->modify_time);
    }

    private function ghiCoModify($modify, $hash, $id = 1)
    {
        $this->td->ghi([
            'treatment_id'   => $id,
            'treatment_code' => '000' . $id,
            'loai_ban'       => CauHinhSinhXml3176::CHINH_THUC,
            'fee_lock_time'  => 20261022100000,
            'trang_thai'     => 'da_ghi',
            'modify_time'    => $modify,
            'noi_dung_hash'  => $hash,
        ]);
    }
}

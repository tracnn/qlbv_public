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

    public function test_chua_tung_sinh_thi_sinh()
    {
        $this->assertTrue($this->td->canSinh(1, CauHinhSinhXml3176::CHINH_THUC, 20261022100000, null));
    }

    public function test_da_ghi_cung_fee_lock_thi_khong_sinh()
    {
        $this->ghiChinhThuc('da_ghi', 20261022100000);
        $this->assertFalse($this->td->canSinh(1, CauHinhSinhXml3176::CHINH_THUC, 20261022100000, null));
    }

    public function test_khoa_lai_fee_lock_doi_thi_sinh_lai()
    {
        $this->ghiChinhThuc('da_ghi', 20261022100000);
        $this->assertTrue($this->td->canSinh(1, CauHinhSinhXml3176::CHINH_THUC, 20261022150000, null));
    }

    public function test_bi_loai_cung_moc_thi_khong_thu_lai()
    {
        $this->ghiChinhThuc('bi_loai', 20261022100000);
        $this->assertFalse($this->td->canSinh(1, CauHinhSinhXml3176::CHINH_THUC, 20261022100000, null));
    }

    public function test_bi_loai_moc_doi_thi_thu_lai()
    {
        $this->ghiChinhThuc('bi_loai', 20261022100000);
        $this->assertTrue($this->td->canSinh(1, CauHinhSinhXml3176::CHINH_THUC, 20261022150000, null));
    }

    public function test_dong_moi_nhat_la_dong_quyet_dinh()
    {
        $this->ghiChinhThuc('da_ghi', 20261022100000);
        $this->ghiChinhThuc('da_ghi', 20261022150000);
        $this->assertFalse($this->td->canSinh(1, CauHinhSinhXml3176::CHINH_THUC, 20261022150000, null));
        $this->assertTrue($this->td->canSinh(1, CauHinhSinhXml3176::CHINH_THUC, 20261022100000, null));
    }

    public function test_thong_tuyen_so_theo_out_time()
    {
        $this->td->ghi([
            'treatment_id'   => 5,
            'treatment_code' => '0005',
            'loai_ban'       => CauHinhSinhXml3176::THONG_TUYEN,
            'fee_lock_time'  => 20261022100000,
            'out_time'       => 20261022090000,
            'trang_thai'     => 'da_ghi',
        ]);
        $tt = CauHinhSinhXml3176::THONG_TUYEN;
        $this->assertFalse($this->td->canSinh(5, $tt, 20261022999999, 20261022090000));
        $this->assertTrue($this->td->canSinh(5, $tt, 20261022100000, 20261022110000));
        // khac loai_ban thi doc lap
        $this->assertTrue($this->td->canSinh(5, CauHinhSinhXml3176::CHINH_THUC, 20261022100000, null));
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
}

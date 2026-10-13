<?php

namespace Tests\Unit\Xml3176\VatPhamKemDvkt;

use App\Models\BHYT\Xml3176VatPhamKemDvkt;
use App\Services\Xml3176\VatPhamKemDvktQuanTri;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\Support\Xml3176RuleTestSupport;
use Tests\TestCase;

class QuanTriServiceTest extends TestCase
{
    use Xml3176RuleTestSupport;

    protected function setUp()
    {
        parent::setUp();
        $this->bootXml3176Sqlite([
            '2024_06_12_142339_create_danh_muc_thuoc_table.php',
            '2024_06_12_143949_create_danh_muc_vat_tu_table.php',
            '2024_06_12_145200_create_danh_muc_dich_vu_table.php',
            '2026_01_09_152826_create_xml3176_xml2s_table.php',
            '2026_01_09_152832_create_xml3176_xml3s_table.php',
            '2026_10_13_090000_create_xml3176_vat_pham_kem_dvkt_table.php',
            '2026_10_14_090000_them_nguoi_sua_vao_xml3176_vat_pham_kem_dvkt.php',
        ]);
    }

    private function cotBatBuocDichVu(): array
    {
        return ['don_gia' => 0, 'quy_trinh' => '', 'tu_ngay' => ''];
    }

    private function sv(): VatPhamKemDvktQuanTri
    {
        return new VatPhamKemDvktQuanTri();
    }

    private function dong(array $f = []): Xml3176VatPhamKemDvkt
    {
        return Xml3176VatPhamKemDvkt::create($f + [
            'loai' => 'VTYT', 'ma_vat_pham' => 'N08.00.150', 'ten_khop' => 'buồng tiêm truyền',
            'ma_dvkt' => '12.0448.1187', 'nguon' => 'HOC', 'hieu_luc' => true,
        ]);
    }

    private function loi(callable $f): string
    {
        try {
            $f();
        } catch (\InvalidArgumentException $e) {
            return $e->getMessage();
        }
        $this->fail('Khong nem InvalidArgumentException');
    }

    /** @test */
    public function tat_bat_buoc_ly_do_va_ghi_vet()
    {
        $m = $this->dong();

        $this->assertSame('Cần ghi lý do khi tắt', $this->loi(fn() => $this->sv()->datHieuLuc($m, false, '  ', 7)));

        $this->sv()->datHieuLuc($m, false, 'Cặp sai chuyên môn', 7);
        $m = $m->fresh();
        $this->assertFalse($m->hieu_luc);
        $this->assertSame('Cặp sai chuyên môn', $m->ghi_chu);
        $this->assertSame(7, $m->nguoi_sua_id);
        $this->assertNotNull($m->sua_luc);

        $this->sv()->datHieuLuc($m, true, '', 8);
        $this->assertTrue($m->fresh()->hieu_luc);
        $this->assertNull($m->fresh()->ghi_chu);
    }

    /** @test */
    public function tat_bang_ghi_chu_tu_dong_cua_lenh_hoc_bi_tu_choi()
    {
        $m = $this->dong();
        $this->assertSame(
            'Hãy ghi lý do tắt của bạn (không dùng ghi chú tự động của lệnh học)',
            $this->loi(fn() => $this->sv()->datHieuLuc($m, false, Xml3176VatPhamKemDvkt::GHI_CHU_TU_TAT, 7))
        );
        $this->assertTrue($m->fresh()->hieu_luc);
    }

    /** @test */
    public function bat_lai_dong_hoc_bi_tu_tat_chuyen_thanh_tay()
    {
        $m = $this->dong(['hieu_luc' => false, 'ghi_chu' => Xml3176VatPhamKemDvkt::GHI_CHU_TU_TAT]);
        $this->sv()->datHieuLuc($m, true, '', 7);
        $this->assertTrue($m->fresh()->hieu_luc);
        $this->assertSame('TAY', $m->fresh()->nguon);
    }

    /** @test */
    public function bat_lai_dong_hoc_bi_nguoi_dung_tat_van_la_hoc()
    {
        $m = $this->dong(['hieu_luc' => false, 'ghi_chu' => 'Cặp sai chuyên môn']);
        $this->sv()->datHieuLuc($m, true, '', 7);
        $this->assertTrue($m->fresh()->hieu_luc);
        $this->assertSame('HOC', $m->fresh()->nguon);
    }

    /** @test */
    public function ghi_chu_rong_thanh_null_va_gioi_han_do_dai()
    {
        $m = $this->dong(['ghi_chu' => 'cu']);

        $this->sv()->datGhiChu($m, '', 7);
        $this->assertNull($m->fresh()->ghi_chu);
        $this->assertSame('Ghi chú tối đa 1024 ký tự', $this->loi(fn() => $this->sv()->datGhiChu($m, str_repeat('a', 1025), 7)));
    }

    /** @test */
    public function chi_xoa_duoc_dong_tay()
    {
        $hoc = $this->dong();
        $tay = $this->dong(['ma_dvkt' => '12.0448.1188', 'nguon' => 'TAY']);

        $this->assertSame('Chỉ xoá được dòng bổ sung tay (TAY); dòng học tự động hãy tắt hiệu lực', $this->loi(fn() => $this->sv()->xoa($hoc)));
        $this->sv()->xoa($tay);
        $this->assertSame(1, Xml3176VatPhamKemDvkt::count());
    }

    /** @test */
    public function them_dong_tay_nhieu_dvkt_chuan_hoa_va_lay_ten_danh_muc()
    {
        DB::table('service_catalogs')->insert(['ma_dich_vu' => '20.0053.0105', 'ten_dich_vu' => 'Nội soi đặt stent'] + $this->cotBatBuocDichVu());

        $kq = $this->sv()->themDongTay('vtyt', ' N06.02.100 ', 'Stent đại tràng', ['20.0053.0105', '20.0054.0105_GT'], [], 'thêm', 7);

        $this->assertSame(['20.0053.0105', '20.0054.0105'], $kq['them']);
        $m = Xml3176VatPhamKemDvkt::where('ma_dvkt', '20.0053.0105')->first();
        $this->assertSame('TAY', $m->nguon);
        $this->assertSame('N06.02.100', $m->ma_vat_pham);
        $this->assertSame('stent đại tràng', $m->ten_khop);
        $this->assertSame('Nội soi đặt stent', $m->ten_dvkt);
        $this->assertSame(0, $m->so_lan_gan);
        $this->assertSame(7, $m->nguoi_sua_id);
    }

    /** @test */
    public function them_dong_tay_kiem_dau_vao()
    {
        $sv = $this->sv();
        $this->assertSame('Loại không hợp lệ (VTYT hoặc THUOC)', $this->loi(fn() => $sv->themDongTay('X', 'a', '', ['b'], [], null, 7)));
        $this->assertSame('Mã vật phẩm phải từ 1 đến 100 ký tự', $this->loi(fn() => $sv->themDongTay('THUOC', ' ', '', ['b'], [], null, 7)));
        $this->assertSame('VTYT mã nhóm chung cần tên vật phẩm đúng như trên XML', $this->loi(fn() => $sv->themDongTay('VTYT', 'N08.00.150', '', ['b'], [], null, 7)));
        $this->assertSame('Mã DVKT phải từ 1 đến 50 ký tự', $this->loi(fn() => $sv->themDongTay('THUOC', '40.644', '', [str_repeat('9', 51)], [], null, 7)));
        $this->assertSame('Chưa chọn DVKT mẹ', $this->loi(fn() => $sv->themDongTay('THUOC', '40.644', '', [], [], null, 7)));
    }

    /** @test */
    public function them_trung_mot_phan_va_trung_toan_bo()
    {
        $this->dong();

        $kq = $this->sv()->themDongTay('VTYT', 'N08.00.150', 'Buồng tiêm truyền', ['12.0448.1187', '12.0448.1188'], [], null, 7);
        $this->assertSame(['12.0448.1188'], $kq['them']);
        $this->assertSame(['12.0448.1187'], $kq['da_co']);

        $this->assertSame('Tất cả cặp đã có trong bảng tra: 12.0448.1187, 12.0448.1188',
            $this->loi(fn() => $this->sv()->themDongTay('VTYT', 'N08.00.150', 'Buồng tiêm truyền', ['12.0448.1187', '12.0448.1188'], [], null, 7)));
    }

    /** @test */
    public function kiem_khop_tra_ten_tren_xml_12_thang()
    {
        $bayGio = Carbon::create(2026, 10, 14);
        $dong = function ($lk, $stt, $ten, $bhtt, $ngay) {
            return ['ma_lk' => $lk, 'stt' => $stt, 'ma_vat_tu' => 'N04.04.010', 'ten_vat_tu' => $ten,
                't_bhtt' => $bhtt, 'so_luong' => 1, 'ngay_yl' => $ngay];
        };
        DB::table('xml3176_xml3s')->insert([
            $dong('A', 1, 'Ống dẫn lưu đuôi lợn', 1000, '202609010800'),
            $dong('A', 2, 'Ống dẫn lưu đuôi lợn', 0, '202609010800'),
            $dong('B', 1, 'Ống dẫn lưu đuôi lợn', 1000, '202608010800'),
            $dong('C', 1, 'Catheter 4 cực', 1000, '202609010800'),
            $dong('D', 1, 'Ống dẫn lưu đuôi lợn', 1000, '202509010800'), // ngoai 12 thang
        ]);

        $kq = $this->sv()->kiemKhop('VTYT', 'N04.04.010', $bayGio);

        $this->assertTrue($kq['ma_nhom_chung']);
        $this->assertSame('Ống dẫn lưu đuôi lợn', $kq['ds'][0]['ten']);
        $this->assertSame('ống dẫn lưu đuôi lợn', $kq['ds'][0]['ten_khop']);
        $this->assertSame(3, $kq['ds'][0]['so_dong']);
        $this->assertSame(2, $kq['ds'][0]['so_dong_bhyt']);
        $this->assertSame(2, $kq['ds'][0]['so_ho_so']);
        $this->assertCount(2, $kq['ds']);
    }

    /** @test */
    public function tim_vat_pham_va_dvkt_tu_danh_muc()
    {
        DB::table('medical_supply_catalogs')->insert(['ma_vat_tu' => 'N08.00.150', 'ten_vat_tu' => 'Buồng tiêm truyền', 'nhom_vat_tu' => '', 'tt_thau' => '']);
        DB::table('medicine_catalogs')->insert(['ma_thuoc' => '40.644', 'ten_thuoc' => 'Omnipaque', 'ten_hoat_chat' => '', 'don_vi_tinh' => '', 'ham_luong' => '', 'duong_dung' => '', 'ma_duong_dung' => '', 'dang_bao_che' => '', 'so_dang_ky' => '']);
        DB::table('service_catalogs')->insert(['ma_dich_vu' => '12.0448.1187', 'ten_dich_vu' => 'Đặt buồng tiêm truyền'] + $this->cotBatBuocDichVu());

        $this->assertSame([['id' => 'N08.00.150', 'text' => 'N08.00.150 — Buồng tiêm truyền']], $this->sv()->timVatPham('VTYT', 'buồng'));
        $this->assertSame('40.644', $this->sv()->timVatPham('THUOC', '40.6')[0]['id']);
        $this->assertSame('12.0448.1187', $this->sv()->timDvkt('12.0448')[0]['id']);
        $this->assertSame([], $this->sv()->timDvkt(' '));
    }
}

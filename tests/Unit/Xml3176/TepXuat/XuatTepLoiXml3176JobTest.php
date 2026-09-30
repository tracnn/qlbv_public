<?php

namespace Tests\Unit\Xml3176\TepXuat;

use App\Exports\Xml3176ErrorMultiSheetExport;
use App\Jobs\XuatTepLoiXml3176Job;
use App\Models\BHYT\Xml3176TepXuat;
use App\Services\BHYT\DanhSachCoSo;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DefaultValueBinder;
use PhpOffice\PhpSpreadsheet\Cell\StringValueBinder;
use Tests\Support\Xml3176RuleTestSupport;
use Tests\TestCase;

class XuatTepLoiXml3176JobTest extends TestCase
{
    use Xml3176RuleTestSupport;

    protected function setUp()
    {
        parent::setUp();
        $this->bootXml3176Sqlite(['2026_09_30_100000_create_xml3176_tep_xuat_table.php']);
        Storage::fake('local');
        // Khong cham HIS: DanhSachCoSo doc qua cache.
        Cache::put(DanhSachCoSo::KHOA_CACHE, ['01929' => 'BV A'], 60);
    }

    protected function tearDown()
    {
        Cell::setValueBinder(new DefaultValueBinder());
        parent::tearDown();
    }

    private function yeuCau(array $gia = [])
    {
        return Xml3176TepXuat::create(array_merge([
            'user_id' => 1, 'loai' => Xml3176TepXuat::LOAI_LOI,
            'bo_loc' => ['date_from' => '2026-09-29 00:00:00', 'date_to' => '2026-09-29 23:59:59', 'ma_cskcb' => '01929'],
            'trang_thai' => Xml3176TepXuat::CHO,
        ], $gia));
    }

    /** @test */
    public function chay_xong_thi_luu_tep_dung_cho_bang_bo_loc_da_luu_va_danh_dau_xong()
    {
        Excel::fake();
        $y = $this->yeuCau();

        (new XuatTepLoiXml3176Job($y->id))->handle();

        $duongDan = 'xml3176-tep-xuat/' . $y->id . '.xlsx';
        Excel::assertStored($duongDan, 'local', function ($export) {
            return $export instanceof Xml3176ErrorMultiSheetExport;
        });
        $y = $y->fresh();
        $this->assertSame(Xml3176TepXuat::XONG, $y->trang_thai);
        $this->assertSame($duongDan, $y->duong_dan);
        $this->assertNotNull($y->bat_dau_luc);
        $this->assertNotNull($y->xong_luc);
        $this->assertNull($y->loi);
    }

    /** @test */
    public function yeu_cau_khong_con_hoac_khong_o_cho_thi_khong_lam_gi()
    {
        Excel::fake();
        $daXong = $this->yeuCau(['trang_thai' => Xml3176TepXuat::XONG]);

        (new XuatTepLoiXml3176Job(999))->handle();
        (new XuatTepLoiXml3176Job($daXong->id))->handle();

        // Laravel Excel 3.1.25 khong co assertNotStored(): kiem qua trang thai - neu job da
        // xu ly thi bat_dau_luc duoc ghi.
        $daXong = $daXong->fresh();
        $this->assertSame(Xml3176TepXuat::XONG, $daXong->trang_thai);
        $this->assertNull($daXong->bat_dau_luc);
    }

    /** @test */
    public function sau_khi_xuat_bo_gan_gia_tri_tro_ve_mac_dinh()
    {
        // Hai sheet danh muc dat StringValueBinder vao bien TINH va khong tra lai. Worker chay
        // nhieu lan xuat noi tiep: thieu buoc tra lai thi cac lan sau ghi moi o thanh chuoi.
        Excel::fake();
        Cell::setValueBinder(new StringValueBinder());
        $y = $this->yeuCau();

        (new XuatTepLoiXml3176Job($y->id))->handle();

        $this->assertInstanceOf(DefaultValueBinder::class, Cell::getValueBinder());
        $this->assertNotInstanceOf(StringValueBinder::class, Cell::getValueBinder());
    }

    /** @test */
    public function hong_thi_danh_dau_loi_va_xoa_tep_do()
    {
        $y = $this->yeuCau(['trang_thai' => Xml3176TepXuat::DANG_TAO]);
        Storage::disk('local')->put('xml3176-tep-xuat/' . $y->id . '.xlsx', 'do dang');

        (new XuatTepLoiXml3176Job($y->id))->failed(new \RuntimeException('het bo nho'));

        $y = $y->fresh();
        $this->assertSame(Xml3176TepXuat::LOI, $y->trang_thai);
        $this->assertContains('Tạo tệp lỗi, xem nhật ký máy chủ', $y->loi);
        $this->assertNotContains('het bo nho', $y->loi, 'Khong lo noi dung ngoai le cho nguoi dung');
        $this->assertFalse(Storage::disk('local')->exists('xml3176-tep-xuat/' . $y->id . '.xlsx'));
    }

    /** @test */
    public function worker_chet_giua_chung_thi_bao_loi_tieng_viet_de_hieu()
    {
        $y = $this->yeuCau(['trang_thai' => Xml3176TepXuat::DANG_TAO]);

        (new XuatTepLoiXml3176Job($y->id))->failed(new \Illuminate\Queue\MaxAttemptsExceededException('has been attempted too many times'));

        $y = $y->fresh();
        $this->assertContains('Dịch vụ xuất đã dừng giữa chừng', $y->loi);
        $this->assertNotContains('attempted', $y->loi);
    }

    /** @test */
    public function excel_store_tra_false_thi_nem_ngoai_le_va_khong_danh_dau_xong()
    {
        Excel::swap(new class {
            public function store(...$tham)
            {
                return false;
            }
        });
        $y = $this->yeuCau();

        try {
            (new XuatTepLoiXml3176Job($y->id))->handle();
            $this->fail('Phai nem RuntimeException');
        } catch (\RuntimeException $e) {
            $this->assertContains('Không ghi được tệp', $e->getMessage());
        }

        $y = $y->fresh();
        $this->assertSame(Xml3176TepXuat::DANG_TAO, $y->trang_thai);
        $this->assertNull($y->duong_dan);
    }

    /** @test */
    public function thu_mot_lan_va_handle_khong_nhan_tham_so()
    {
        $this->assertSame(1, (new XuatTepLoiXml3176Job(1))->tries);
        $this->assertSame(0, (new \ReflectionMethod(XuatTepLoiXml3176Job::class, 'handle'))->getNumberOfParameters(),
            'Khong nhan dich vu qua type-hint cua handle() (bay tiem container Laravel 5.5)');
    }
}

<?php

namespace Tests\Unit\Ctdt;

use Tests\TestCase;
use Tests\Support\DungBangCtdtSqlite;
use Illuminate\Support\Facades\Schema;
use App\Models\BHYT\Ctdt\CtdtHoSo;
use App\Models\BHYT\Ctdt\CtdtChungTu;
use App\Models\BHYT\Ctdt\CtdtLoi;

/**
 * Canh ba bang khung: ho so (don vi ky/gui), chung tu (noi dung), loi (ket qua kiem).
 */
class CtdtBangKhungTest extends TestCase
{
    use DungBangCtdtSqlite;

    protected function setUp()
    {
        parent::setUp();
        $this->chuanBiBangCtdt();
    }

    /** @test */
    public function ba_bang_khung_duoc_tao()
    {
        foreach (['ctdt_ho_so', 'ctdt_chung_tu', 'ctdt_loi'] as $bang) {
            $this->assertTrue(Schema::hasTable($bang), 'Thieu bang ' . $bang);
        }
    }

    /** @test */
    public function ctdt_ho_so_co_du_cot_trang_thai()
    {
        $cot = [
            'ma_ho_so', 'id_goi_xml', 'dich_vu', 'loai_hs', 'macskcb', 'ngay_lap',
            'so_luong_ho_so', 'so_chung_tu', 'duong_dan_goc', 'duong_dan_da_ky',
            'imported_at', 'imported_by', 'import_error',
            'checked_at', 'so_loi',
            'is_signed', 'sign_method', 'signed_at', 'signed_error',
            'submitted_at', 'submitted_by', 'submit_error', 'submitted_message',
            'ma_gd', 'ma_ket_qua', 'thoi_gian_tiep_nhan', 'lich_su_gui',
        ];

        foreach ($cot as $ten) {
            $this->assertTrue(Schema::hasColumn('ctdt_ho_so', $ten), 'ctdt_ho_so thieu cot ' . $ten);
        }
    }

    /** @test */
    public function ma_ho_so_la_duy_nhat()
    {
        // Ghi de ban cu phai khoa duoc vao mot cot. Khong unique thi nap lai se de
        // ra hai ban ghi ma khong bao gi ca.
        CtdtHoSo::create(['ma_ho_so' => 'HS001', 'dich_vu' => 'CT2025', 'loai_hs' => '39', 'macskcb' => '01929']);

        $this->expectException(\Illuminate\Database\QueryException::class);

        CtdtHoSo::create(['ma_ho_so' => 'HS001', 'dich_vu' => 'GBT', 'loai_hs' => '60', 'macskcb' => '01929']);
    }

    /** @test */
    public function ho_so_co_nhieu_chung_tu()
    {
        $hoSo = CtdtHoSo::create(['ma_ho_so' => 'HS002', 'dich_vu' => 'CT2025', 'loai_hs' => '39', 'macskcb' => '01929']);

        CtdtChungTu::create(['ho_so_id' => $hoSo->id, 'loai_ho_so' => 'CT03', 'ma_chung_tu' => 'YT001', 'noi_dung_goc' => '<CT03/>']);
        CtdtChungTu::create(['ho_so_id' => $hoSo->id, 'loai_ho_so' => 'CT04', 'ma_chung_tu' => 'YT001', 'noi_dung_goc' => '<CT04/>']);

        $this->assertCount(2, $hoSo->fresh()->chungTu);
        $this->assertSame('HS002', CtdtChungTu::first()->hoSo->ma_ho_so);
    }

    /** @test */
    public function chung_tu_giu_cot_rut_gon_de_loc_danh_sach()
    {
        // Cot rut gon la trung lap CO CHU DICH: khong co chung thi man danh sach phai
        // UNION 9 bang chi tiet vi ten truong khac nhau tung loai.
        foreach (['ma_the', 'ho_ten', 'ngay_sinh', 'ngay_vao', 'ngay_ra'] as $ten) {
            $this->assertTrue(Schema::hasColumn('ctdt_chung_tu', $ten), 'ctdt_chung_tu thieu cot rut gon ' . $ten);
        }
    }

    /** @test */
    public function loi_gan_duoc_vao_ho_so_va_chung_tu()
    {
        $hoSo = CtdtHoSo::create(['ma_ho_so' => 'HS003', 'dich_vu' => 'CT2025', 'loai_hs' => '39', 'macskcb' => '01929']);
        $chungTu = CtdtChungTu::create(['ho_so_id' => $hoSo->id, 'loai_ho_so' => 'CT03', 'noi_dung_goc' => '<CT03/>']);

        CtdtLoi::create([
            'ho_so_id'    => $hoSo->id,
            'chung_tu_id' => $chungTu->id,
            'ma_loi'      => 'CTDT002',
            'ten_truong'  => 'NGAY_VAO',
            'mo_ta'       => 'Sai dinh dang ngay',
            'muc_do'      => 'chan',
        ]);

        $this->assertCount(1, $hoSo->fresh()->loi);
        $this->assertSame('chan', CtdtLoi::first()->muc_do);
    }
}

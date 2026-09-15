<?php

namespace Tests\Unit\Xml3176;

use App\Exports\Xml3176ErrorSheetExport;
use App\Services\BHYT\Xml3176LocDanhSach;
use Tests\TestCase;

/**
 * Spec muc 4.1, 5, 7.1. Chi dung truy van, KHONG thuc thi.
 */
class Xml3176ErrorSheetExportTest extends TestCase
{
    private function loc(array $ghiDe = [])
    {
        return array_merge(
            array_fill_keys(Xml3176LocDanhSach::KHOA, null),
            ['date_from' => '2026-09-01 00:00:00', 'date_to' => '2026-09-30 23:59:59', 'date_type' => 'date_in'],
            $ghiDe
        );
    }

    private function sheet($loai, array $ghiDe = [])
    {
        return new Xml3176ErrorSheetExport($loai, $this->loc($ghiDe), []);
    }

    /** SQL bo dau nhay de so khop khong phu thuoc grammar (MySQL dung backtick). */
    private function sql($loai)
    {
        return str_replace(['`', '"'], '', $this->sheet($loai)->query()->toSql());
    }

    /** Tim mot join theo ten bang (ke ca bi danh "bang as ten"). */
    private function join($loai, $bang)
    {
        foreach ((array) $this->sheet($loai)->query()->getQuery()->joins as $j) {
            if (strpos($j->table, $bang) === 0) {
                return $j;
            }
        }

        return null;
    }

    /** Cac cot xuat hien trong dieu kien ON cua mot join. */
    private function cotTrongJoin($j)
    {
        $cot = [];

        foreach ((array) $j->wheres as $w) {
            foreach (['first', 'second'] as $k) {
                if (isset($w[$k])) {
                    $cot[] = $w[$k];
                }
            }
        }

        return $cot;
    }

    /** @test */
    public function tieu_de_19_cot_ma_khoa_o_vi_tri_thu_5()
    {
        $h = $this->sheet('XML3')->headings();

        $this->assertCount(19, $h);
        $this->assertSame('Mã Liên Kết', $h[3]);
        $this->assertSame('Mã Khoa', $h[4]);
        $this->assertSame('Mã Bệnh Nhân', $h[5]);
    }

    /** @test */
    public function ten_sheet_la_loai_xml()
    {
        $this->assertSame('XML7', $this->sheet('XML7')->title());
        $this->assertSame('XMLComplete', $this->sheet('XMLComplete')->title());
    }

    /** @test */
    public function do_rong_du_19_cot_va_cot_ngay_khop_tieu_de()
    {
        // Chen cot Ma Khoa lam dich moi cot tu E. Test nay chan viec dinh dang so ap nham
        // cot sau khi dich - loi im lang, file van mo duoc.
        $h = $this->sheet('XML3')->headings();

        $this->assertCount(19, Xml3176ErrorSheetExport::DO_RONG);
        $this->assertSame(range('A', 'S'), array_keys(Xml3176ErrorSheetExport::DO_RONG));

        $tenCotNgay = [];
        foreach (Xml3176ErrorSheetExport::COT_NGAY as $chu) {
            $tenCotNgay[] = $h[ord($chu) - ord('A')];
        }

        $this->assertSame(
            ['Ngày Sinh', 'Ngày Vào', 'Ngày Ra', 'Ngày T.Toán', 'Ngày Y Lệnh', 'Ngày Kết Quả'],
            $tenCotNgay
        );
    }

    /** @test */
    public function chi_lay_dong_loi_cua_dung_loai_xml()
    {
        $q = $this->sheet('XML4')->query();

        $this->assertContains('xml3176_error_results.xml = ?', str_replace(['`', '"'], '', $q->toSql()));
        $this->assertContains('XML4', $q->getBindings());
    }

    /** @test */
    public function cat_theo_tap_ho_so_cua_man_danh_sach()
    {
        $sql = $this->sql('XML2');
        $this->assertContains('xml3176_error_results.ma_lk in (select xml3176_xml1s.ma_lk', $sql);

        // Bo loc cua man danh sach phai theo vao truy van con.
        $q = (new Xml3176ErrorSheetExport('XML2', $this->loc(['ma_khoa' => 'K01']), []))->query();
        $this->assertContains('K01', $q->getBindings());
    }

    /** @test */
    public function noi_danh_muc_ma_loi_theo_ca_xml_lan_error_code()
    {
        $j = $this->join('XML3', 'xml3176_error_catalogs');

        $this->assertNotNull($j, 'Khong noi danh muc ma loi');
        $this->assertSame('left', $j->type, 'Phai LEFT JOIN: ma loi chua co trong danh muc khong duoc lam mat dong');

        $cot = $this->cotTrongJoin($j);
        $this->assertContains('xml3176_error_catalogs.xml', $cot);
        $this->assertContains('xml3176_error_catalogs.error_code', $cot);
    }

    /** @test */
    public function xml3_noi_bang_nguon_theo_ma_lk_va_stt()
    {
        $j = $this->join('XML3', 'xml3176_xml3s');

        $this->assertNotNull($j, 'XML3 phai noi bang nguon de lay ma khoa cua dong');
        $this->assertSame('left', $j->type);

        $cot = $this->cotTrongJoin($j);
        $this->assertContains('khoa_nguon.ma_lk', $cot);
        $this->assertContains('khoa_nguon.stt', $cot);

        $this->assertContains("COALESCE(NULLIF(khoa_nguon.ma_khoa, ''), xml3176_xml1s.ma_khoa) as ma_khoa_xuat", $this->sql('XML3'));
    }

    /** @test */
    public function xml7_noi_theo_ma_lk_khong_theo_stt_va_dung_ma_khoa_rv()
    {
        $j = $this->join('XML7', 'xml3176_xml7s');

        $this->assertNotNull($j);

        $cot = $this->cotTrongJoin($j);
        $this->assertContains('khoa_nguon.ma_lk', $cot);
        $this->assertNotContains('khoa_nguon.stt', $cot, 'Bang XML7 khong co cot stt');

        $this->assertContains("COALESCE(NULLIF(khoa_nguon.ma_khoa_rv, ''), xml3176_xml1s.ma_khoa) as ma_khoa_xuat", $this->sql('XML7'));
    }

    /** @test */
    public function loai_dung_khoa_ho_so_khong_noi_bang_nguon()
    {
        foreach (['XML1', 'XML4', 'XMLComplete'] as $loai) {
            $this->assertNull($this->join($loai, 'xml3176_xml4s'), "$loai khong duoc noi XML4");
            $this->assertNotContains('khoa_nguon', $this->sql($loai), "$loai khong duoc noi bang nguon");
            $this->assertContains('xml3176_xml1s.ma_khoa as ma_khoa_xuat', $this->sql($loai));
        }
    }

    /** @test */
    public function informations_la_left_join()
    {
        // Bat bien spec muc 8: tong dong cac sheet = so dong loi. Inner join se lam mat dong
        // loi cua ho so thieu dong informations.
        $j = $this->join('XML3', 'xml3176_informations');

        $this->assertNotNull($j);
        $this->assertSame('left', $j->type);
    }

    /** @test */
    public function map_dat_ma_khoa_o_cot_thu_5_va_danh_so_tu_1()
    {
        $sheet = $this->sheet('XML3');

        $dong = (object) [
            'xml' => 'XML3', 'stt' => 7, 'ma_lk' => 'LK1', 'ma_khoa_xuat' => 'K01',
            'ma_bn' => 'BN1', 'ho_ten' => 'A', 'ngay_sinh' => '19800101', 'ma_the_bhyt' => 'T',
            'ngay_vao' => '1', 'ngay_ra' => '2', 'ngay_ttoan' => '3', 'ngay_yl' => '4', 'ngay_kq' => '5',
            'error_code' => 'XML3_X', 'catalog_error_name' => 'Ten loi', 'description' => 'Mo ta',
            'critical_error' => 1, 'imported_by' => 'u1', 'exported_by' => 'u2',
        ];

        $ra = $sheet->map($dong);

        $this->assertCount(19, $ra);
        $this->assertSame(1, $ra[0]);
        $this->assertSame('K01', $ra[4]);
        $this->assertSame('Ten loi', $ra[14]);
        $this->assertSame('Nghiêm trọng', $ra[16]);

        $this->assertSame(2, $sheet->map($dong)[0], 'STT tang tren tung sheet');
    }

    /** @test */
    public function ma_loi_chua_co_trong_danh_muc_thi_hien_ma_thay_vi_o_trong()
    {
        $dong = (object) [
            'xml' => 'XML3', 'stt' => 1, 'ma_lk' => 'LK1', 'ma_khoa_xuat' => null,
            'ma_bn' => null, 'ho_ten' => null, 'ngay_sinh' => null, 'ma_the_bhyt' => null,
            'ngay_vao' => null, 'ngay_ra' => null, 'ngay_ttoan' => null, 'ngay_yl' => null, 'ngay_kq' => null,
            'error_code' => 'XML3_MOI', 'catalog_error_name' => null, 'description' => null,
            'critical_error' => 0, 'imported_by' => null, 'exported_by' => null,
        ];

        $ra = $this->sheet('XML3')->map($dong);

        $this->assertSame('XML3_MOI', $ra[14]);
        $this->assertSame('Cảnh báo', $ra[16]);
    }
}

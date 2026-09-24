<?php

namespace Tests\Unit;

use App\Exports\KetQuaTraCuuTheExport;
use App\Models\CheckBHYT\check_hein_card;
use Tests\TestCase;

class ManKetQuaTraCuuTheGiaoDienTest extends TestCase
{
    private function blade()
    {
        return file_get_contents(base_path('resources/views/bhyt/check-hein-card/index.blade.php'));
    }

    /** @test */
    public function ba_cot_quan_sat_lay_tu_cot_hien_va_khong_sap_xep()
    {
        $b = $this->blade();

        foreach (['hien_ma_the', 'hien_ho_ten', 'hien_ngay_sinh'] as $cot) {
            $this->assertContains('"data": "' . $cot . '"', $b);
        }
        $this->assertContains('Theo HIS (cổng không trả về)', $b);
    }

    /** @test */
    public function co_nut_tra_lai_goi_dung_route_va_tai_lai_giu_trang()
    {
        $b = $this->blade();

        $this->assertContains("route('bhyt.check-hein-card.tra-lai')", $b);
        $this->assertContains('nut-tra-lai', $b);
        $this->assertContains('table.ajax.reload(null, false)', $b);
        $this->assertContains('csrf_token()', $b);
    }

    /** @test */
    public function nut_tra_lai_doc_ma_lk_tu_data_attribute_khong_tra_row_bang_tr()
    {
        $b = $this->blade();

        $this->assertContains('data-ma-lk', $b);
        $this->assertContains("nut.data('ma-lk')", $b);
        $this->assertNotContains('table.row(nut.closest(', $b);
    }

    /** @test */
    public function modal_co_bon_dong_da_gui()
    {
        $b = $this->blade();

        foreach (['ma_the_gui', 'ho_ten_gui', 'ngay_sinh_gui', 'ma_dkbd_gui'] as $cot) {
            $this->assertContains("['" . $cot . "'", $b);
        }
    }

    /** @test */
    public function xuat_excel_so_tieu_de_khop_so_cot_va_co_cot_da_gui()
    {
        // Khong doc bang that: dung mot dong dung tay.
        $x = new KetQuaTraCuuTheExport(check_hein_card::query());
        $r = new check_hein_card(['ma_lk' => 'HS1', 'ma_the_gui' => 'DN4010112345678', 'ma_dkbd_gui' => '01005']);

        $this->assertSame(count($x->headings()), count($x->map($r)));
        $this->assertSame(['Số thẻ đã gửi', 'Họ tên đã gửi', 'Ngày sinh đã gửi', 'Nơi ĐKBĐ đã gửi'],
            array_slice($x->headings(), -4));
        $this->assertSame('DN4010112345678', array_slice($x->map($r), -4)[0]);
    }
}

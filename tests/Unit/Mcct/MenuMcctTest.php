<?php

namespace Tests\Unit\Mcct;

use Tests\TestCase;

class MenuMcctTest extends TestCase
{
    const TEN_NHOM = 'Thẻ BHYT';
    const TEN_MUC = 'Tra cứu tiền cùng chi trả';

    /** Menu goc doc thang tu config, chua qua bo loc quyen */
    protected function menu()
    {
        return config('adminlte.menu');
    }

    /** Cac muc con cua nhom "The BHYT"; mang rong neu khong tim thay nhom */
    protected function mucConCuaNhom()
    {
        foreach ($this->menu() as $item) {
            if (is_array($item) && isset($item['text']) && $item['text'] === self::TEN_NHOM) {
                return isset($item['submenu']) ? $item['submenu'] : [];
            }
        }

        return [];
    }

    /** @test */
    public function nam_trong_nhom_the_bhyt()
    {
        $ten = [];

        foreach ($this->mucConCuaNhom() as $m) {
            if (isset($m['text'])) {
                $ten[] = $m['text'];
            }
        }

        $this->assertContains(self::TEN_MUC, $ten,
            'Khong tim thay muc "' . self::TEN_MUC . '" trong nhom "' . self::TEN_NHOM . '"');
    }

    /** @test */
    public function tro_dung_route_va_chi_xuat_hien_mot_lan()
    {
        $dem = 0;

        foreach ($this->mucConCuaNhom() as $m) {
            if (isset($m['text']) && $m['text'] === self::TEN_MUC) {
                $dem++;
                $this->assertSame('insurance.mcct', $m['route']);
                $this->assertSame(['insurance/mcct*'], $m['active']);
            }
        }

        $this->assertSame(1, $dem, 'Muc phai xuat hien dung mot lan');
    }
}

<?php

namespace Tests\Unit;

use Tests\TestCase;

/**
 * Chot muc menu cap 1 "Tra cứu lỗi hồ sơ", cung khuon voi MenuOrderCheckTest: neu ai do
 * xoa nham checkrole hoac doi route thi test do phai bao, khong phai nguoi dung phat hien
 * ra menu bi mo cong khai hoac tro sai trang.
 */
class MenuTraCuuLoiHoSoTest extends TestCase
{
    const TEN_MUC = 'Tra cứu lỗi hồ sơ';

    protected function menu()
    {
        return config('adminlte.menu');
    }

    protected function muc()
    {
        foreach ($this->menu() as $item) {
            if (is_array($item) && isset($item['text']) && $item['text'] === self::TEN_MUC) {
                return $item;
            }
        }

        return null;
    }

    /** @test */
    public function la_muc_cap_1_dung_mot_lan()
    {
        $dem = 0;
        foreach ($this->menu() as $item) {
            if (is_array($item) && isset($item['text']) && $item['text'] === self::TEN_MUC) {
                $dem++;
            }
        }

        $this->assertSame(1, $dem, 'Muc "' . self::TEN_MUC . '" phai xuat hien dung mot lan o cap 1');
    }

    /** @test */
    public function dung_quyen_route_va_icon()
    {
        $muc = $this->muc();

        $this->assertNotNull($muc, 'Khong tim thay muc "' . self::TEN_MUC . '"');
        $this->assertSame('tra-cuu-loi-ho-so', $muc['checkrole']);
        $this->assertSame('khth.tra-cuu-loi-ho-so', $muc['route']);
        $this->assertSame(['khth/tra-cuu-loi-ho-so*'], $muc['active']);
        $this->assertArrayNotHasKey('submenu', $muc,
            'Muc nay la muc cap 1 doc lap, khong phai muc con cua nhanh khac');
    }
}

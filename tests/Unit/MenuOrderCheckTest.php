<?php

namespace Tests\Unit;

use Tests\TestCase;

class MenuOrderCheckTest extends TestCase
{
    const TEN_MUC = 'Kiểm tra sai sót y lệnh';
    const TEN_XML = 'Hồ sơ XML';

    /** Menu goc doc thang tu config, chua qua bo loc quyen */
    protected function menu()
    {
        return config('adminlte.menu');
    }

    /** Chi so cua mot muc CAP 1 theo text; -1 neu khong co */
    protected function viTriCap1($text)
    {
        foreach (array_values($this->menu()) as $i => $item) {
            if (is_array($item) && isset($item['text']) && $item['text'] === $text) {
                return $i;
            }
        }

        return -1;
    }

    /** @test */
    public function la_muc_cap_1_va_dung_tren_ho_so_xml()
    {
        $viTri = $this->viTriCap1(self::TEN_MUC);
        $viTriXml = $this->viTriCap1(self::TEN_XML);

        $this->assertNotSame(-1, $viTri, 'Khong tim thay muc cap 1 "' . self::TEN_MUC . '"');
        $this->assertNotSame(-1, $viTriXml, 'Khong tim thay muc cap 1 "' . self::TEN_XML . '"');
        $this->assertLessThan($viTriXml, $viTri, 'Muc order-check phai nam TREN "' . self::TEN_XML . '"');
    }

    /** @test */
    public function chi_xuat_hien_dung_mot_lan_o_cap_1()
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
    public function ca_muc_cha_va_3_muc_con_deu_dung_quyen_order_check()
    {
        $muc = null;

        foreach ($this->menu() as $item) {
            if (is_array($item) && isset($item['text']) && $item['text'] === self::TEN_MUC) {
                $muc = $item;
                break;
            }
        }

        $this->assertNotNull($muc);
        $this->assertSame('order-check', $muc['checkrole']);
        $this->assertCount(3, $muc['submenu']);

        // Man xem vi pham mo cho ca role order-check; hai man CAU HINH (sua danh muc gioi
        // han, bat/tat bo luat) chi danh cho superadministrator vi chung doi hanh vi bat
        // loi cua ca he thong.
        $this->assertSame(
            ['order-check', 'superadministrator', 'superadministrator'],
            array_column($muc['submenu'], 'checkrole')
        );

        $this->assertSame(
            ['khth.order-check-index', 'khth.order-check-ref-index', 'khth.order-check-rule-index'],
            array_column($muc['submenu'], 'route')
        );
    }

    /** @test */
    public function khong_con_dau_vet_trong_ke_hoach_tong_hop()
    {
        $khth = null;

        foreach ($this->menu() as $item) {
            if (is_array($item) && isset($item['text']) && $item['text'] === 'Kế hoạch tổng hợp') {
                $khth = $item;
                break;
            }
        }

        $this->assertNotNull($khth, 'Khong tim thay muc "Ke hoach tong hop"');
        $this->assertSame([], $this->routeOrderCheck($khth),
            'Van con muc order-check nam trong "Ke hoach tong hop"');
    }

    /** Gom moi 'route' bat dau bang khth.order-check trong mot nhanh menu */
    protected function routeOrderCheck($item)
    {
        $ra = [];

        if (isset($item['route']) && strpos($item['route'], 'khth.order-check') === 0) {
            $ra[] = $item['route'];
        }

        if (isset($item['submenu']) && is_array($item['submenu'])) {
            foreach ($item['submenu'] as $con) {
                $ra = array_merge($ra, $this->routeOrderCheck($con));
            }
        }

        return $ra;
    }
}

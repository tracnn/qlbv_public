<?php

namespace Tests\Unit;

use Tests\TestCase;

class MenuDanhMucBhytTest extends TestCase
{
    /** Tra ve mang cac muc con cua khoi 'BHYT' trong menu quan ly danh muc */
    protected function khoiBhyt()
    {
        foreach (config('adminlte.menu') as $cap1) {
            if (!is_array($cap1) || !isset($cap1['submenu'])) {
                continue;
            }

            foreach ($cap1['submenu'] as $cap2) {
                if (is_array($cap2) && isset($cap2['text']) && $cap2['text'] === 'BHYT') {
                    return $cap2['submenu'];
                }
            }
        }

        return null;
    }

    /** Chi so cua mot muc theo text; -1 neu khong co */
    protected function viTri(array $muc, $text)
    {
        foreach (array_values($muc) as $i => $x) {
            if (isset($x['text']) && $x['text'] === $text) {
                return $i;
            }
        }

        return -1;
    }

    /** @test */
    public function co_du_ba_muc_moi()
    {
        $khoi = $this->khoiBhyt();

        $this->assertNotNull($khoi, 'Khong tim thay khoi BHYT trong menu');

        foreach (['DM Đơn vị hành chính', 'DM Cơ sở KCB', 'DM Nghề nghiệp'] as $ten) {
            $this->assertNotSame(-1, $this->viTri($khoi, $ten), "Thieu muc menu \"$ten\"");
        }
    }

    /** @test */
    public function ba_muc_moi_dat_sau_trang_thiet_bi_va_truoc_dm_loi_xml()
    {
        $khoi = $this->khoiBhyt();

        $tb = $this->viTri($khoi, 'DM Trang thiết bị');
        $loi = $this->viTri($khoi, 'DM lỗi Xml 4750');

        $this->assertNotSame(-1, $tb);
        $this->assertNotSame(-1, $loi);

        foreach (['DM Đơn vị hành chính', 'DM Cơ sở KCB', 'DM Nghề nghiệp'] as $ten) {
            $i = $this->viTri($khoi, $ten);

            $this->assertGreaterThan($tb, $i, "\"$ten\" phai nam sau DM Trang thiet bi");
            $this->assertLessThan($loi, $i, "\"$ten\" phai nam truoc DM loi Xml 4750");
        }
    }

    /** @test */
    public function ba_muc_moi_tro_dung_route()
    {
        $khoi = $this->khoiBhyt();

        $mong = [
            'DM Đơn vị hành chính' => 'category-bhyt.administrative-unit',
            'DM Cơ sở KCB' => 'category-bhyt.medical-organization',
            'DM Nghề nghiệp' => 'category-bhyt.job-category',
        ];

        foreach ($mong as $ten => $route) {
            $i = $this->viTri($khoi, $ten);

            $this->assertSame($route, $khoi[$i]['route'], "Muc \"$ten\" tro sai route");
        }
    }
}

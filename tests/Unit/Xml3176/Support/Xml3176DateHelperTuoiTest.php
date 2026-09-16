<?php

namespace Tests\Unit\Xml3176\Support;

use App\Services\Xml3176\Support\Xml3176DateHelper;
use Tests\TestCase;

class Xml3176DateHelperTuoiTest extends TestCase
{
    /** @test */
    public function tuoi_du_nam_tinh_theo_ngay_sinh_nhat()
    {
        $this->assertSame(17, Xml3176DateHelper::tuoiDuNam('20080916', '20260915'));
        $this->assertSame(18, Xml3176DateHelper::tuoiDuNam('20080916', '20260916'));
    }

    /** @test */
    public function chi_dung_8_ky_tu_dau_cua_chuoi_ngay_gio()
    {
        $this->assertSame(18, Xml3176DateHelper::tuoiDuNam('200809160000', '202609161230'));
    }

    /** @test */
    public function ngay_khong_doc_duoc_thi_null()
    {
        // XML3176 ghi ngay sinh khong ro ngay/thang bang 00.
        $this->assertNull(Xml3176DateHelper::tuoiDuNam('20080000', '20260915'));
        $this->assertNull(Xml3176DateHelper::tuoiDuNam('', '20260915'));
        $this->assertNull(Xml3176DateHelper::tuoiDuNam(null, '20260915'));
        $this->assertNull(Xml3176DateHelper::tuoiDuNam('20080916', ''));
        $this->assertNull(Xml3176DateHelper::tuoiDuNam('20080231', '20260915'));
    }

    /** @test */
    public function ngay_sinh_sau_ngay_moc_thi_null()
    {
        $this->assertNull(Xml3176DateHelper::tuoiDuNam('20270101', '20260915'));
    }
}

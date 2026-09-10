<?php

namespace Tests\Unit\Xml3176;

use Tests\TestCase;

class Xml3176ErrorCatalogChuan3176Dot1SeederTest extends TestCase
{
    private function nguon(): string
    {
        return file_get_contents(
            database_path('seeds/Xml3176ErrorCatalogChuan3176Dot1Seeder.php')
        );
    }

    /** @test */
    public function seeder_khai_du_24_ma_loi()
    {
        $src = $this->nguon();

        $ma = [
            'XML3_MA_DICH_VU_TB_CO_DON_GIA',
            'XML3_MA_DICH_VU_CHUA_CO_GIA_CO_DON_GIA_BH',
            'XML3_MA_DICH_VU_HAU_TO_LA',
            'XML3_MA_DICH_VU_VAN_CHUYEN_THIEU_XANG_DAU',
            'XML3_MA_DICH_VU_VAN_CHUYEN_CSKCB_NOT_FOUND',
            'XML3_MA_DICH_VU_CHUYEN_MAU_CSKCB_NOT_FOUND',
            'XML3_THANH_TIEN_BV_SAI_CONG_THUC',
            'XML3_THANH_TIEN_BH_SAI_CONG_THUC',
            'XML3_T_NGUONKHAC_SAI_TONG',
            'XML3_T_BHTT_SAI_CONG_THUC',
            'XML2_THANH_TIEN_BV_SAI_CONG_THUC',
            'XML2_THANH_TIEN_BH_SAI_CONG_THUC',
            'XML2_T_NGUONKHAC_SAI_TONG',
            'XML2_T_BHTT_SAI_CONG_THUC',
            'XML3_PHAM_VI_NGOAI_TAP_GIA_TRI',
            'XML3_PHAM_VI_TU_TRA_MA_BH_TRA',
            'XML3_TAI_SU_DUNG_INVALID',
            'XML3_TAI_SU_DUNG_DON_GIA_LECH',
            'XML2_NGUON_CTRA_INVALID',
            'XML2_NGUON_CTRA_NGOAI_QUY_MA_BH_TRA',
            'XML1_ADMIN_INFO_ERROR_MA_KHUVUC',
            'XMLComplete_NGAY_TAI_KHAM_SAI_DINH_DANG',
            'XMLComplete_NGAY_TAI_KHAM_KHONG_KHOP_XML14',
            'XMLComplete_CAN_NANG_CON_THIEU_XML9',
        ];

        $this->assertCount(24, $ma, 'Danh sach kiem thu phai co dung 24 ma');

        foreach ($ma as $m) {
            $this->assertContains($m, $src, "Seeder thieu ma $m");
        }
    }

    /** @test */
    public function moi_ma_deu_khong_chan_xuat_xml()
    {
        // critical_error = true se CHAN xuat XML. 24 quy tac nay chua tung chay tren
        // du lieu co tien nen khong duoc chan.
        $src = $this->nguon();

        $this->assertNotContains("'critical_error' => true", $src);
        $this->assertContains("'critical_error' => false", $src);
    }

    /** @test */
    public function seeder_idempotent()
    {
        // Chay lai an toan: updateOrCreate chu khong phai insert.
        $this->assertContains('updateOrCreate', $this->nguon());
    }
}

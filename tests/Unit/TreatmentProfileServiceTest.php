<?php

namespace Tests\Unit;

use App\Services\OrderCheck\TreatmentProfileService;
use Tests\Support\DungBangHoSoHisSqlite;
use Tests\TestCase;

class TreatmentProfileServiceTest extends TestCase
{
    use DungBangHoSoHisSqlite;

    protected function setUp()
    {
        parent::setUp();
        $this->chuanBiBangHoSo();
    }

    protected function service()
    {
        return new TreatmentProfileService();
    }

    /** @test */
    public function tra_du_thong_tin_ho_so()
    {
        $this->themHoSo();

        $ho = $this->service()->cua('01013250800123');

        $this->assertSame('01013250800123', $ho['treatment_code']);
        $this->assertSame('Nguyễn Văn A', $ho['patient_name']);
        $this->assertSame('20/02/1979', $ho['patient_dob_text']);
        $this->assertSame('Nam', $ho['gender_name']);
        $this->assertSame('1', (string) $ho['gender_code']);
        $this->assertSame('DN4010112345678', $ho['hein_card_number']);
        $this->assertSame('Khoa Nội', $ho['department_name']);
        $this->assertSame('Nội trú', $ho['treatment_type_name']);
        $this->assertSame('05/08/2026 08:30', $ho['in_time_text']);
        $this->assertNull($ho['out_time_text']);
    }

    /**
     * Canh quay lai loi cu: lay ma co so tu tdl_hein_medi_org_code (noi DKBD cua benh
     * nhan) thay vi tu his_branch (co so dieu tri).
     *
     * @test
     */
    public function ma_cskcb_lay_tu_his_branch_khong_phai_noi_dkbd()
    {
        $this->themHoSo();

        $ho = $this->service()->cua('01013250800123');

        $this->assertSame('01001', $ho['ma_cskcb']);
        $this->assertSame('01005', $ho['hein_medi_org_code']);
    }

    /** @test */
    public function ho_so_thieu_khoa_va_gioi_tinh_van_tra_ve()
    {
        $this->themHoSo([
            'treatment_code'        => 'HS-KHUYET',
            'last_department_id'    => null,
            'tdl_patient_gender_id' => null,
            'tdl_treatment_type_id' => null,
            'branch_id'             => null,
        ]);

        $ho = $this->service()->cua('HS-KHUYET');

        $this->assertNotNull($ho);
        $this->assertSame('HS-KHUYET', $ho['treatment_code']);
        $this->assertNull($ho['department_name']);
        $this->assertNull($ho['gender_code']);
        $this->assertNull($ho['ma_cskcb']);
    }

    /** @test */
    public function khong_tim_thay_thi_tra_null()
    {
        $this->assertNull($this->service()->cua('KHONG-CO'));
    }

    /** @test */
    public function ma_rong_tra_null_va_khong_truy_van()
    {
        $this->themHoSo();

        $this->assertNull($this->service()->cua(''));
        $this->assertNull($this->service()->cua('   '));
        $this->assertNull($this->service()->cua(null));
    }

    /** @test */
    public function ma_duoc_trim_truoc_khi_tra()
    {
        $this->themHoSo();

        $this->assertNotNull($this->service()->cua('  01013250800123  '));
    }
}

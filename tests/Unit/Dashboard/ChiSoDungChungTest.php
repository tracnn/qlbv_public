<?php

namespace Tests\Unit\Dashboard;

use Tests\TestCase;
use App\Services\Dashboard\ChiSoDungChung;

/**
 * Kiem tra truy van nen dung chung giua trang chu va Dashboard lanh dao qua
 * wheres/getBindings, KHONG thuc thi truy van.
 */
class ChiSoDungChungTest extends TestCase
{
    const TU = '20261001000000';
    const DEN = '20261019235959';

    /** Tim where theo cot (khop ca dang co tien to bang) va loai. */
    protected function timWhere($query, $cot, $type = null)
    {
        foreach ((array) $query->wheres as $w) {
            if (!isset($w['column'])) {
                continue;
            }
            $c = $w['column'];
            if ($c !== $cot && substr($c, -strlen('.' . $cot)) !== '.' . $cot) {
                continue;
            }
            if ($type === null || $w['type'] === $type) {
                return $w;
            }
        }

        return null;
    }

    /** @test */
    public function noi_tru_ra_vien_loc_out_time_va_loai_3()
    {
        $q = ChiSoDungChung::noiTruRaVien(self::TU, self::DEN, [], []);

        $between = $this->timWhere($q, 'out_time', 'between');
        $this->assertNotNull($between, 'Thieu where between tren out_time');
        // between khong luu gia tri trong wheres (Laravel 5.5), chi co trong bindings
        $this->assertEquals([self::TU, self::DEN], array_slice($q->getBindings(), 0, 2));

        $loai = $this->timWhere($q, 'tdl_treatment_type_id', 'Basic');
        $this->assertNotNull($loai, 'Thieu where tdl_treatment_type_id');
        $this->assertEquals(3, $loai['value']);

        $this->assertNull($this->timWhere($q, 'branch_id'));
    }

    /** @test */
    public function loc_branch_khi_co()
    {
        $q = ChiSoDungChung::noiTruRaVien(self::TU, self::DEN, [], [81]);

        $in = $this->timWhere($q, 'branch_id', 'In');
        $this->assertNotNull($in, 'Thieu where In tren branch_id');
        $this->assertEquals('his_treatment.branch_id', $in['column']);
        $this->assertEquals([81], $in['values']);
    }

    /** @test */
    public function loai_tru_doi_tuong()
    {
        $q = ChiSoDungChung::noiTruRaVien(self::TU, self::DEN, [5], []);

        $notIn = $this->timWhere($q, 'tdl_patient_type_id', 'NotIn');
        $this->assertNotNull($notIn, 'Thieu where NotIn tren tdl_patient_type_id');
        $this->assertEquals([5], $notIn['values']);

        $khong = ChiSoDungChung::noiTruRaVien(self::TU, self::DEN, [], []);
        $this->assertNull($this->timWhere($khong, 'tdl_patient_type_id'));
    }

    /** @test */
    public function benh_nhan_moi_va_tai_kham_giu_dieu_kien_cu()
    {
        $moi = ChiSoDungChung::benhNhanMoi(self::TU, self::DEN, [5], [1]);
        $tao = $this->timWhere($moi, 'create_time', 'between');
        $this->assertNotNull($tao, 'BN moi thieu between tren his_patient.create_time');
        $this->assertEquals('his_patient.create_time', $tao['column']);
        $this->assertEquals([self::TU, self::DEN, self::TU, self::DEN], array_slice($moi->getBindings(), 0, 4));
        $this->assertNotNull($this->timWhere($moi, 'in_time', 'between'));
        $this->assertEquals(0, $this->timWhere($moi, 'is_delete', 'Basic')['value']);
        $this->assertEquals('his_patient.is_delete', $this->timWhere($moi, 'is_delete', 'Basic')['column']);
        $this->assertEquals([1], $this->timWhere($moi, 'branch_id', 'In')['values']);
        $this->assertEquals([5], $this->timWhere($moi, 'tdl_patient_type_id', 'NotIn')['values']);

        $cu = ChiSoDungChung::taiKham(self::TU, self::DEN, [], []);
        $nho = $this->timWhere($cu, 'create_time', 'Basic');
        $this->assertNotNull($nho, 'Tai kham thieu where < tren create_time');
        $this->assertEquals('<', $nho['operator']);
        $this->assertEquals(self::TU, $nho['value']);
        $this->assertEquals('his_treatment.is_delete', $this->timWhere($cu, 'is_delete', 'Basic')['column']);
        $this->assertNotNull($this->timWhere($cu, 'in_time', 'between'));
        $this->assertNull($this->timWhere($cu, 'branch_id'));
    }

    /** @test */
    public function bieu_thuc_so_ngay_khong_doi()
    {
        $this->assertContains('< 4 THEN 0', ChiSoDungChung::SQL_SO_NGAY_DIEU_TRI);
        $this->assertContains('FLOOR(', ChiSoDungChung::SQL_SO_NGAY_DIEU_TRI);
    }
}

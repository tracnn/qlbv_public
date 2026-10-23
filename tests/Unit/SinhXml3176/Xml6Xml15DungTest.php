<?php

namespace Tests\Unit\SinhXml3176;

use App\Services\SinhXml3176\Bang\Xml15Dung;
use App\Services\SinhXml3176\Bang\Xml6Dung;
use App\Services\SinhXml3176\DuLieuHoSo;
use App\Services\SinhXml3176\Nguon\HisDanhMuc;
use App\Services\SinhXml3176\Nguon\HisNapHoSo;
use Tests\Support\DungBangHisSinhXml3176Sqlite;
use Tests\TestCase;

class Xml6Xml15DungTest extends TestCase
{
    use DungBangHisSinhXml3176Sqlite;

    const TRUONG_XML6 = [
        'MA_LK', 'MA_THE_BHYT', 'SO_CCCD', 'NGAY_SINH', 'GIOI_TINH', 'DIA_CHI', 'MATINH_CU_TRU', 'MAHUYEN_CU_TRU',
        'MAXA_CU_TRU', 'NGAYKD_HIV', 'NOI_LAY_MAU_XN', 'NOI_XN_KD', 'NOI_BDDT_ARV', 'BDDT_ARV',
        'MA_PHAC_DO_DIEU_TRI_BD', 'MA_BAC_PHAC_DO_BD', 'MA_LYDO_DTRI', 'LOAI_DTRI_LAO', 'SANG_LOC_LAO',
        'PHACDO_DTRI_LAO', 'NGAYBD_DTRI_LAO', 'NGAYKT_DTRI_LAO', 'KQ_DTRI_LAO', 'MA_LYDO_XNTL_VR', 'NGAY_XN_TLVR',
        'KQ_XNTL_VR', 'NGAY_KQ_XN_TLVR', 'MA_LOAI_BN', 'GIAI_DOAN_LAM_SANG', 'NHOM_DOI_TUONG', 'MA_TINH_TRANG_DK',
        'LAN_XN_PCR', 'NGAY_XN_PCR', 'NGAY_KQ_XN_PCR', 'MA_KQ_XN_PCR', 'NGAY_NHAN_TT_MANG_THAI',
        'NGAY_BAT_DAU_DT_CTX', 'MA_XU_TRI', 'NGAY_BAT_DAU_XU_TRI', 'NGAY_KET_THUC_XU_TRI', 'MA_PHAC_DO_DIEU_TRI',
        'MA_BAC_PHAC_DO', 'SO_NGAY_CAP_THUOC_ARV', 'NGAY_CHUYEN_PHAC_DO', 'LY_DO_CHUYEN_PHAC_DO', 'MA_CSKCB',
        'DU_PHONG',
    ];

    const TRUONG_XML15 = [
        'MA_LK', 'STT', 'MA_BN', 'HO_TEN', 'SO_CCCD', 'PHANLOAI_LAO_VITRI', 'PHANLOAI_LAO_TS', 'PHANLOAI_LAO_HIV',
        'PHANLOAI_LAO_VK', 'PHANLOAI_LAO_KT', 'LOAI_DTRI_LAO', 'NGAYBD_DTRI_LAO', 'PHACDO_DTRI_LAO',
        'NGAYKT_DTRI_LAO', 'KET_QUA_DTRI_LAO', 'MA_CSKCB', 'NGAYKD_HIV', 'BDDT_ARV', 'NGAY_BAT_DAU_DT_CTX',
        'DU_PHONG',
    ];

    protected function setUp()
    {
        parent::setUp();
        $this->dungBangHis();
    }

    private function dm()
    {
        return HisDanhMuc::napChoLuot('HISPro');
    }

    private function hoSo(array $ghi = [])
    {
        return (object) array_merge(array_fill_keys(HisNapHoSo::COT_HO_SO, null), [
            'id' => 5, 'treatment_code' => 'HS5', 'tdl_patient_code' => 'BN5', 'tdl_patient_name' => 'Nguyen Van A',
            'tdl_patient_dob' => 19800102000000, 'tdl_patient_gender_id' => 1, 'tdl_patient_address' => 'Dien Bien',
            'tdl_patient_province_code' => '11', 'tdl_patient_district_code' => '110', 'tdl_patient_commune_code' => '03199',
            'tdl_hein_card_number' => 'DN4010000000001', 'hein_medi_org_code' => '01929',
        ], $ghi);
    }

    private function hiv(array $ghi = [])
    {
        return (object) array_merge(array_fill_keys(HisNapHoSo::COT_HIV, null), [
            'id' => 20, 'treatment_id' => 5, 'hiv_infection_date' => 20200315000000, 'test_sample_place' => 'Noi A',
            'begin_regimen_level' => 1, 'tuberculosis_screening' => 2, 'clinical_stage' => 3,
            'regimen_hiv_date' => 20260101000000, 'regimen_hiv_reason' => 4, 'hiv_treatment_code' => 'XT1',
        ], $ghi);
    }

    private function lao(array $ghi = [])
    {
        return (object) array_merge(array_fill_keys(HisNapHoSo::COT_LAO, null), [
            'id' => 30, 'treatment_id' => 5, 'tuberculosis_classify_location' => '1', 'tuberculosis_treatment_type' => '2',
            'begin_time' => 20260101080000, 'end_time' => 20260630000000, 'tuberculosis_treatmen_result' => '3',
            'hiv_kd_date' => 20200315000000,
        ], $ghi);
    }

    private function du(array $ghiHoSo = [], $hiv = null, $lao = null)
    {
        $d = new DuLieuHoSo($this->hoSo($ghiHoSo));
        $d->hivTreatment = $hiv;
        $d->dieuTriLao = $lao;

        return $d;
    }

    public function test_xml6_mot_dong_tu_hiv()
    {
        $x = new Xml6Dung();
        $this->assertSame('XML6', $x->loai());
        $d = $this->du([], $this->hiv());
        $this->assertTrue($x->coTrongHoSo($d));
        $ra = $x->dung($d, $this->dm(), []);
        $this->assertCount(1, $ra);
        $r = $ra[0];
        $this->assertSame('HS5', $r['MA_LK']);
        $this->assertSame('DN4010000000001', $r['MA_THE_BHYT']);
        $this->assertSame('198001020000', $r['NGAY_SINH']);
        $this->assertSame('2', $r['GIOI_TINH']);
        $this->assertSame('20200315', $r['NGAYKD_HIV']);
        $this->assertSame('Noi A', $r['NOI_LAY_MAU_XN']);
        $this->assertSame('1', $r['MA_BAC_PHAC_DO_BD']);
        $this->assertSame('2', $r['SANG_LOC_LAO']);
        $this->assertSame('3', $r['GIAI_DOAN_LAM_SANG']);
        $this->assertSame('20260101', $r['NGAY_CHUYEN_PHAC_DO']);
        $this->assertSame('4', $r['LY_DO_CHUYEN_PHAC_DO']);
        $this->assertSame('XT1', $r['MA_XU_TRI']);
        $this->assertSame('01929', $r['MA_CSKCB']);
        $this->assertSame('', $r['BDDT_ARV']);
        $this->assertSame('', $r['DU_PHONG']);
    }

    public function test_xml6_khong_hiv_tra_rong()
    {
        $x = new Xml6Dung();
        $d = $this->du();
        $this->assertFalse($x->coTrongHoSo($d));
        $this->assertSame([], $x->dung($d, $this->dm(), []));
    }

    public function test_xml6_dieu_kien_treatment_id_khac_id_giu_nhu_dll()
    {
        $x = new Xml6Dung();
        // TREATMENT_ID == ID tinh co trung => bo im lang nhu DLL ([AX]:832, can xac nhan)
        $d = $this->du([], $this->hiv(['id' => 5, 'treatment_id' => 5]));
        $this->assertFalse($x->coTrongHoSo($d));
        $this->assertSame([], $x->dung($d, $this->dm(), []));
    }

    public function test_xml6_ngay_sinh_khong_ngay_va_thieu_khong_lam_vo()
    {
        $x = new Xml6Dung();
        $ra = $x->dung($this->du(['tdl_patient_is_has_not_day_dob' => 1, 'tdl_patient_gender_id' => 2], $this->hiv()), $this->dm(), []);
        $this->assertSame('198000000000', $ra[0]['NGAY_SINH']);
        $this->assertSame('1', $ra[0]['GIOI_TINH']);
        $ra = $x->dung($this->du(['tdl_patient_dob' => null, 'tdl_patient_gender_id' => null], $this->hiv()), $this->dm(), []);
        $this->assertSame('', $ra[0]['NGAY_SINH']);
        $this->assertSame('3', $ra[0]['GIOI_TINH']);
    }

    public function test_xml15_mot_dong_stt_1()
    {
        $x = new Xml15Dung();
        $this->assertSame('XML15', $x->loai());
        $d = $this->du([], null, $this->lao());
        $this->assertTrue($x->coTrongHoSo($d));
        $ra = $x->dung($d, $this->dm(), []);
        $this->assertCount(1, $ra);
        $r = $ra[0];
        $this->assertSame('1', $r['STT']);
        $this->assertSame('HS5', $r['MA_LK']);
        $this->assertSame('BN5', $r['MA_BN']);
        $this->assertSame('Nguyen Van A', $r['HO_TEN']);
        $this->assertSame('1', $r['PHANLOAI_LAO_VITRI']);
        $this->assertSame('20260101', $r['NGAYBD_DTRI_LAO']);
        $this->assertSame('20260630', $r['NGAYKT_DTRI_LAO']);
        $this->assertSame('3', $r['KET_QUA_DTRI_LAO']);
        $this->assertSame('01929', $r['MA_CSKCB']);
        $this->assertSame('20200315', $r['NGAYKD_HIV']);
        $this->assertSame('', $r['BDDT_ARV']);
    }

    public function test_xml15_khong_lao_tra_rong()
    {
        $x = new Xml15Dung();
        $this->assertFalse($x->coTrongHoSo($this->du()));
        $this->assertSame([], $x->dung($this->du(), $this->dm(), []));
        // ID == 0 coi nhu khong co ([AX]:1347)
        $d = $this->du([], null, $this->lao(['id' => 0]));
        $this->assertFalse($x->coTrongHoSo($d));
        $this->assertSame([], $x->dung($d, $this->dm(), []));
    }

    public function test_so_cccd_roi_ve_cmnd_ho_chieu()
    {
        foreach ([[new Xml6Dung(), 'hiv'], [new Xml15Dung(), 'lao']] as $c) {
            list($x, $nguon) = $c;
            $du = function (array $ghi) use ($nguon) {
                return $this->du($ghi, $nguon === 'hiv' ? $this->hiv() : null, $nguon === 'lao' ? $this->lao() : null);
            };
            $cccd = $x->dung($du(['tdl_patient_cccd_number' => '001', 'tdl_patient_cmnd_number' => '002', 'tdl_patient_passport_number' => 'P3']), $this->dm(), []);
            $this->assertSame('001', $cccd[0]['SO_CCCD']);
            $cmnd = $x->dung($du(['tdl_patient_cccd_number' => '', 'tdl_patient_cmnd_number' => '002', 'tdl_patient_passport_number' => 'P3']), $this->dm(), []);
            $this->assertSame('002', $cmnd[0]['SO_CCCD']);
            $hc = $x->dung($du(['tdl_patient_passport_number' => 'P3', 'tdl_social_insurance_number' => '9999']), $this->dm(), []);
            $this->assertSame('P3', $hc[0]['SO_CCCD']);
            // IS_3176 bo bac cuoi ma BHXH
            $khong = $x->dung($du(['tdl_social_insurance_number' => '9999']), $this->dm(), []);
            $this->assertSame('', $khong[0]['SO_CCCD']);
        }
    }

    public function test_thu_tu_khoa_dung_chi_tieu()
    {
        $r6 = (new Xml6Dung())->dung($this->du([], $this->hiv()), $this->dm(), [])[0];
        $this->assertSame(self::TRUONG_XML6, array_keys($r6));
        $r15 = (new Xml15Dung())->dung($this->du([], null, $this->lao()), $this->dm(), [])[0];
        $this->assertSame(self::TRUONG_XML15, array_keys($r15));
    }
}

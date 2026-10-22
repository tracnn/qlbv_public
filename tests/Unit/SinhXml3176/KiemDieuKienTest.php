<?php

namespace Tests\Unit\SinhXml3176;

use App\Services\SinhXml3176\CauHinhSinhXml3176;
use App\Services\SinhXml3176\DuLieuHoSo;
use App\Services\SinhXml3176\KiemDieuKien;
use App\Services\SinhXml3176\Nguon\HisDanhMuc;
use Illuminate\Support\Facades\DB;
use Tests\Support\DungBangHisSinhXml3176Sqlite;
use Tests\TestCase;

class KiemDieuKienTest extends TestCase
{
    use DungBangHisSinhXml3176Sqlite;

    const CT = CauHinhSinhXml3176::CHINH_THUC;
    const TT = CauHinhSinhXml3176::THONG_TUYEN;

    private $dm;

    protected function setUp()
    {
        parent::setUp();
        $this->dungBangHis();
        $his = DB::connection('HISPro');
        $his->table('his_patient_type')->insert([
            ['id' => 1, 'patient_type_code' => 'BHYT', 'patient_type_name' => 'BHYT'],
            ['id' => 2, 'patient_type_code' => 'VP', 'patient_type_name' => 'VP'],
        ]);
        $his->table('his_config')->insert([
            ['id' => 1, 'key' => 'MOS.HIS_PATIENT_TYPE.PATIENT_TYPE_CODE.BHYT', 'value' => 'BHYT', 'is_active' => 1],
        ]);
        $his->table('his_icd')->insert([
            ['id' => 1, 'icd_code' => 'A15', 'is_latent_tuberculosis' => 1, 'do_not_use_hein' => 0],
            ['id' => 2, 'icd_code' => 'Z00', 'is_latent_tuberculosis' => 0, 'do_not_use_hein' => 1],
        ]);
        $his->table('his_employee')->insert([
            ['id' => 1, 'loginname' => 'bs1', 'diploma' => 'CCHN1'],
            ['id' => 2, 'loginname' => 'bs2', 'diploma' => ''],
        ]);
        $this->dm = HisDanhMuc::napChoLuot('HISPro');
    }

    private function hoSo(array $ghi = [])
    {
        return (object) array_merge([
            'id' => 5, 'treatment_code' => 'HS5',
            'tdl_patient_commune_code' => '00001', 'tdl_patient_province_code' => '01',
            'ht_commune_code' => null, 'ht_province_code' => null,
            'tdl_patient_mps_national_code' => '000',
            'fee_lock_time' => 20261010120000, 'hein_lock_time' => null,
            'in_time' => 20261001080000, 'clinical_in_time' => null, 'out_time' => 20261005080000,
            'end_department_id' => 3, 'treatment_end_type_id' => 1, 'treatment_result_id' => 1,
            'icd_code' => 'J00',
            'tuberculosis_issued_org_name' => null, 'tuberculosis_issued_date' => null,
        ], $ghi);
    }

    private function dichVu(array $ghi = [])
    {
        return (object) array_merge([
            'id' => 1, 'patient_type_id' => 1, 'tdl_service_type_id' => 2, 'tdl_hein_service_type_id' => 3,
            'tdl_is_main_exam' => null, 'tdl_hein_service_bhyt_name' => 'DV', 'amount' => 1, 'is_expend' => null,
            'is_no_execute' => null, 'icd_code' => 'J00', 'tdl_service_req_code' => 'YL1',
            'tdl_intruction_time' => 20261002080000, 'execute_loginname' => 'bs1', 'request_loginname' => null,
            'vir_price' => 100, 'vir_total_hein_price' => 80, 'other_source_price' => null,
            'active_ingr_bhyt_code' => null, 'tdl_hein_service_bhyt_code' => null, 'tutorial' => null,
        ], $ghi);
    }

    private function dien(array $ghi = [])
    {
        return (object) array_merge(['id' => 1, 'patient_type_id' => 1, 'hein_treatment_type_code' => 'KB'], $ghi);
    }

    private function du(array $hoSo = [], array $dichVu = [], array $dien = [])
    {
        $d = new DuLieuHoSo($this->hoSo($hoSo));
        $d->dienDieuTri = [$this->dien($dien)];
        $d->dichVu = [$this->dichVu($dichVu)];

        return $d;
    }

    private function ly(DuLieuHoSo $d, $ban = self::CT)
    {
        return (new KiemDieuKien())->lyDoLoai($d, $ban, $this->dm);
    }

    public function test_ho_so_hop_le_thi_null_ca_hai_ban()
    {
        $this->assertNull($this->ly($this->du()));
        $this->assertNull($this->ly($this->du(), self::TT));
    }

    public function test_khong_co_ho_so()
    {
        $this->assertSame('Không xác định được hồ sơ điều trị', $this->ly(new DuLieuHoSo(null)));
    }

    public function test_thieu_dia_chi()
    {
        $this->assertSame('Hồ sơ thiếu thông tin địa chỉ', $this->ly($this->du(['tdl_patient_commune_code' => ''])));
        $this->assertNull($this->ly($this->du(['tdl_patient_commune_code' => '', 'ht_commune_code' => 'x', 'ht_province_code' => 'y'])));
    }

    public function test_thieu_quoc_tich()
    {
        $this->assertSame('Hồ sơ thiếu thông tin quốc tịch', $this->ly($this->du(['tdl_patient_mps_national_code' => null])));
    }

    public function test_chua_khoa_vien_phi()
    {
        $this->assertSame('Hồ sơ chưa có thời gian khóa viện phí', $this->ly($this->du(['fee_lock_time' => null])));
    }

    public function test_thong_tuyen_khong_can_fee_lock()
    {
        $this->assertNull($this->ly($this->du(['fee_lock_time' => null]), self::TT));
    }

    public function test_chua_co_out_time()
    {
        $m = 'Hồ sơ chưa có thời gian kết thúc điều trị';
        $this->assertSame($m, $this->ly($this->du(['out_time' => null])));
        $this->assertSame($m, $this->ly($this->du(['out_time' => null]), self::TT));
    }

    public function test_fee_lock_nho_hon_out_time_thi_loai()
    {
        $d = $this->du(['fee_lock_time' => 20261004000000]);
        $this->assertSame('Thời gian ra viện lớn hơn thời gian thanh toán', $this->ly($d));
        $this->assertNull($this->ly($d, self::TT));
    }

    public function test_khoa_loai_ra_vien_ket_qua_benh()
    {
        $this->assertSame('Hồ sơ điều trị không có khoa kết thúc điều trị', $this->ly($this->du(['end_department_id' => null])));
        $this->assertSame('Hồ sơ điều trị không có loại ra viện', $this->ly($this->du(['treatment_end_type_id' => null])));
        $this->assertSame('Hồ sơ điều trị không có kết quả', $this->ly($this->du(['treatment_result_id' => null])));
        $this->assertSame('Hồ sơ không có thông tin bệnh', $this->ly($this->du(['icd_code' => '  '])));
    }

    public function test_khong_co_dien_dieu_tri_thi_loai_khong_nem()
    {
        $d = $this->du();
        $d->dienDieuTri = [];
        $this->assertSame('Không xác định được thông tin diện điều trị', $this->ly($d));
        $this->assertSame('Không xác định được thông tin diện điều trị', $this->ly($d, self::TT));
    }

    public function test_noi_tru_thieu_clinical_in_time()
    {
        $d = $this->du([], [], ['hein_treatment_type_code' => 'DT']);
        $this->assertSame('Hồ sơ điều trị nội trú nhưng không có thời gian vào điều trị', $this->ly($d));
        $d->hoSo->clinical_in_time = 20261001090000;
        $this->assertNull($this->ly($d));
    }

    public function test_dien_dieu_tri_lay_dong_dau()
    {
        $d = $this->du();
        $d->dienDieuTri = [$this->dien(['hein_treatment_type_code' => 'KB']), $this->dien(['id' => 0, 'hein_treatment_type_code' => 'DT'])];
        $this->assertNull($this->ly($d));
    }

    public function test_khong_doi_hein_lock_time()
    {
        $d = $this->du(['hein_lock_time' => null, 'fee_lock_time' => 20261010120000]);
        $this->assertNull($this->ly($d));
        $d->hoSo->hein_lock_time = 20261009000000; // nho hon fee_lock
        $this->assertNull($this->ly($d));
    }

    public function test_ma_benh_khong_duoc_bhyt_thanh_toan()
    {
        $d = $this->du(['icd_code' => 'Z00'], ['icd_code' => 'Z00']);
        $m = 'Mã bệnh Z00 không được bảo hiểm y tế thanh toán';
        $this->assertSame($m, $this->ly($d));
        $this->assertSame($m, $this->ly($d, self::TT));
        // doi tuong khong phai BHYT thi bo qua
        $d->dienDieuTri = [$this->dien(['patient_type_id' => 2])];
        $this->assertNull($this->ly($d));
    }

    public function test_lao_thieu_giay_xac_nhan()
    {
        $d = $this->du(['icd_code' => 'A15'], ['icd_code' => 'A15']);
        $m = 'Thiếu thông tin cấp giấy xác nhận điều trị Lao';
        $this->assertSame($m, $this->ly($d));
        $d->hoSo->tuberculosis_issued_org_name = 'BV';
        $this->assertSame($m, $this->ly($d));
        $d->hoSo->tuberculosis_issued_date = 20261001000000;
        $this->assertNull($this->ly($d));
    }

    public function test_thieu_danh_muc_doi_tuong_bhyt_thi_loai_khong_nem()
    {
        DB::connection('HISPro')->table('his_config')->delete();
        $dm = HisDanhMuc::napChoLuot('HISPro');
        $ly = (new KiemDieuKien())->lyDoLoai($this->du(), self::CT, $dm);
        $this->assertSame('Không xác định được đối tượng BHYT trong danh mục', $ly);
    }

    public function test_khong_co_dich_vu()
    {
        $d = $this->du();
        $d->dichVu = [];
        $this->assertSame('Hồ sơ không có dịch vụ thanh toán', $this->ly($d));
        $this->assertSame('Hồ sơ không có dịch vụ thanh toán', $this->ly($d, self::TT));
    }

    public function test_dich_vu_bi_loc_het_chinh_thuc()
    {
        foreach ([['amount' => 0], ['is_expend' => 1], ['tdl_hein_service_type_id' => null]] as $ghi) {
            $this->assertSame('Hồ sơ không có dịch vụ thanh toán', $this->ly($this->du([], $ghi)));
        }
        // kham chinh khong co loai BHYT van tinh
        $this->assertNull($this->ly($this->du([], ['tdl_hein_service_type_id' => null, 'tdl_service_type_id' => 1, 'tdl_is_main_exam' => 1])));
    }

    public function test_khong_sua_dich_vu()
    {
        $d = $this->du();
        $d->dichVu[] = $this->dichVu(['id' => 2, 'is_expend' => 1]);
        $this->assertNull($this->ly($d));
        $this->assertCount(2, $d->dichVu);
    }

    public function test_thong_tuyen_loc_dich_vu_theo_gia()
    {
        $kq = 'Hồ sơ không có dịch vụ thanh toán';
        $this->assertSame($kq, $this->ly($this->du([], ['tdl_hein_service_bhyt_name' => '']), self::TT));
        $this->assertSame($kq, $this->ly($this->du([], ['vir_price' => 0]), self::TT));
        $this->assertSame($kq, $this->ly($this->du([], ['amount' => 0]), self::TT));
        // dong BHYT: tien BHYT phai > 0, hoac = 0 va other_source_price > 0
        $this->assertSame($kq, $this->ly($this->du([], ['vir_total_hein_price' => null]), self::TT));
        $this->assertSame($kq, $this->ly($this->du([], ['vir_total_hein_price' => 0]), self::TT));
        $this->assertNull($this->ly($this->du([], ['vir_total_hein_price' => 0, 'other_source_price' => 5]), self::TT));
        // doi tuong khac BHYT: chi can gia > 0
        $this->assertNull($this->ly($this->du([], ['patient_type_id' => 2, 'vir_total_hein_price' => null]), self::TT));
        // is_expend khong anh huong ban thong tuyen
        $this->assertNull($this->ly($this->du([], ['is_expend' => 1]), self::TT));
    }

    public function test_so_sinh_thieu_the_tam_thi_loai()
    {
        $d = $this->du();
        $d->soSinh = [(object) ['id' => 1, 'hein_card_number_tmp' => 'TE1'], (object) ['id' => 2, 'hein_card_number_tmp' => '']];
        $this->assertSame('Thiếu thông tin thẻ tạm', $this->ly($d));
        $this->assertSame('Thiếu thông tin thẻ tạm của trẻ sơ sinh', $this->ly($d, self::TT));
        $d->soSinh[1]->hein_card_number_tmp = 'TE2';
        $this->assertNull($this->ly($d));
    }

    public function test_dich_vu_thieu_ma_benh()
    {
        $d = $this->du([], ['icd_code' => '']);
        $this->assertSame('thiếu thông tin mã bệnh(Mã y lệnh: YL1)', $this->ly($d));
        // khong tinh dong khong thuc hien loai 5
        $d = $this->du([], ['icd_code' => '', 'is_no_execute' => 1, 'tdl_hein_service_type_id' => 5]);
        $this->assertNull($this->ly($d));
        // ban thong tuyen khong kiem chi tiet
        $this->assertNull($this->ly($this->du([], ['icd_code' => '']), self::TT));
    }

    public function test_chi_dinh_truoc_vao_vien_va_sau_thanh_toan()
    {
        $this->assertSame('thời gian chỉ định trước thời gian vào viện(Mã y lệnh: YL1)',
            $this->ly($this->du([], ['tdl_intruction_time' => 20260901000000])));
        $this->assertSame('thời gian chỉ định sau thời gian thanh toán(Mã y lệnh: YL1)',
            $this->ly($this->du([], ['tdl_intruction_time' => 20261101000000])));
        $this->assertNull($this->ly($this->du([], ['tdl_intruction_time' => 20261101000000]), self::TT));
    }

    public function test_thieu_chung_chi_hanh_nghe()
    {
        $m = 'thiếu thông tin chứng chỉ hành nghề (Mã y lệnh: YL1)';
        $this->assertSame($m, $this->ly($this->du([], ['execute_loginname' => null, 'request_loginname' => ''])));
        $this->assertSame($m, $this->ly($this->du([], ['execute_loginname' => 'bs2'])));
        $this->assertSame($m, $this->ly($this->du([], ['execute_loginname' => 'khong_co'])));
        $this->assertNull($this->ly($this->du([], ['execute_loginname' => 'bs2', 'request_loginname' => 'bs1'])));
    }

    public function test_thieu_lieu_dung()
    {
        $thuoc = ['tdl_hein_service_type_id' => 9];
        $m = 'thiếu thông tin liều dùng(Mã y lệnh: YL1)';
        $this->assertSame($m, $this->ly($this->du([], $thuoc)));
        $this->assertNull($this->ly($this->du([], $thuoc + ['tutorial' => '1 vien'])));
        $this->assertNull($this->ly($this->du([], ['tdl_hein_service_type_id' => 6])));
        DB::connection('HISPro')->table('his_config')->insert(
            ['id' => 9, 'key' => 'HIS.Desktop.Plugins.ExportXml.HeinServiceTypeCodeNoTutorial', 'value' => 'AB|CD', 'is_active' => 1]);
        $this->dm = HisDanhMuc::napChoLuot('HISPro');
        $this->assertNull($this->ly($this->du([], $thuoc + ['active_ingr_bhyt_code' => 'CD'])));
        $this->assertSame($m, $this->ly($this->du([], $thuoc + ['active_ingr_bhyt_code' => 'XX'])));
    }
}

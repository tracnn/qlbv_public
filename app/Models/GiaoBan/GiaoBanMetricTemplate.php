<?php

namespace App\Models\GiaoBan;

use Illuminate\Database\Eloquent\Model;

class GiaoBanMetricTemplate extends Model
{
    protected $table = 'giaoban_metric_templates';
    protected $fillable = ['name', 'block_type', 'metrics', 'sort_order', 'is_active'];
    protected $casts = ['is_active' => 'boolean'];

    /** 5 mau chuyen tu giaoban-config.blade.php (script tpl-*), giu nguyen noi dung. */
    const SEED = [
        [
            'name' => 'Điều trị (mặc định)', 'block_type' => 'dieu_tri', 'sort_order' => 1,
            'metrics' => [
                ['code' => 'bn_cu', 'name' => 'BN cũ', 'type' => 'census_from'],
                ['code' => 'bn_vao', 'name' => 'BN vào', 'type' => 'movement_in'],
                ['code' => 'bn_chuyen_den', 'name' => 'BN chuyển đến', 'type' => 'movement_transfer_in'],
                ['code' => 'bn_ra_vien', 'name' => 'BN ra viện', 'type' => 'end_type', 'end_codes' => ['RV', 'HK', 'CC', 'XV', 'KH', 'TR']],
                ['code' => 'bn_chuyen_vien', 'name' => 'BN chuyển viện', 'type' => 'end_type', 'end_codes' => ['CV']],
                ['code' => 'bn_tu_vong', 'name' => 'BN tử vong', 'type' => 'end_type', 'end_codes' => ['TV']],
                ['code' => 'bn_chuyen_khoa', 'name' => 'BN chuyển khoa', 'type' => 'movement_transfer_out'],
                ['code' => 'hien_co', 'name' => 'Hiện có', 'type' => 'census_to'],
            ],
        ],
        [
            'name' => 'Khám (mặc định)', 'block_type' => 'kham', 'sort_order' => 2,
            'metrics' => [
                ['code' => 'luot_kham', 'name' => 'Lượt khám', 'type' => 'exam_visit'],
                ['code' => 'vao_vien', 'name' => 'Vào viện', 'type' => 'exam_visit', 'filter' => ['treatment_type_ids' => [3]]],
                ['code' => 'cap_toa_ve', 'name' => 'Cấp toa cho về', 'type' => 'exam_visit', 'filter' => ['end_type_codes' => ['CC']]],
                ['code' => 'chuyen_vien', 'name' => 'Chuyển viện', 'type' => 'exam_visit', 'filter' => ['end_type_codes' => ['CV']]],
                ['code' => 'hen_kham_lai', 'name' => 'Hẹn khám lại', 'type' => 'exam_visit', 'filter' => ['end_type_codes' => ['HK']]],
                ['code' => 'kham_yeu_cau', 'name' => 'Khám yêu cầu', 'type' => 'exam_visit', 'filter' => ['patient_type_ids' => [82]]],
                ['code' => 'kham_bhyt', 'name' => 'Khám BHYT', 'type' => 'exam_visit', 'filter' => ['patient_type_ids' => [1]]],
                ['code' => 'chuyen_gia', 'name' => 'Khám chuyên gia', 'type' => 'manual'],
            ],
        ],
        [
            'name' => 'Tổng dịch vụ', 'block_type' => 'can_lam_sang', 'sort_order' => 3,
            'metrics' => [
                ['code' => 'tong_dv', 'name' => 'Tổng dịch vụ', 'type' => 'service_count', 'filter' => ['execute_department_id_self' => true]],
            ],
        ],
        [
            'name' => 'CĐHA (XQ/CT/MRI/SA)', 'block_type' => 'can_lam_sang', 'sort_order' => 4,
            'metrics' => [
                ['code' => 'cdha_xq', 'name' => 'X-Quang', 'type' => 'service_count', 'filter' => ['execute_department_id_self' => true, 'service_type_ids' => [3], 'diim_type_ids' => [1]]],
                ['code' => 'cdha_ct', 'name' => 'CT', 'type' => 'service_count', 'filter' => ['execute_department_id_self' => true, 'service_type_ids' => [3], 'diim_type_ids' => [2]]],
                ['code' => 'cdha_mri', 'name' => 'MRI', 'type' => 'service_count', 'filter' => ['execute_department_id_self' => true, 'service_type_ids' => [3], 'diim_type_ids' => [3]]],
                ['code' => 'cdha_khac', 'name' => 'CĐHA khác', 'type' => 'service_count', 'filter' => ['execute_department_id_self' => true, 'service_type_ids' => [3], 'diim_type_other_of' => [1, 2, 3]]],
                ['code' => 'sieu_am', 'name' => 'Siêu âm', 'type' => 'service_count', 'filter' => ['execute_department_id_self' => true, 'service_type_ids' => [10]]],
            ],
        ],
        [
            'name' => 'Xét nghiệm (HH/SH/VS...)', 'block_type' => 'can_lam_sang', 'sort_order' => 5,
            'metrics' => [
                ['code' => 'xn_hh', 'name' => 'Huyết học', 'type' => 'service_count', 'filter' => ['execute_department_id_self' => true, 'service_type_ids' => [2], 'test_type_ids' => [1]]],
                ['code' => 'xn_sh', 'name' => 'Sinh hóa', 'type' => 'service_count', 'filter' => ['execute_department_id_self' => true, 'service_type_ids' => [2], 'test_type_ids' => [3]]],
                ['code' => 'xn_vs', 'name' => 'Vi sinh', 'type' => 'service_count', 'filter' => ['execute_department_id_self' => true, 'service_type_ids' => [2], 'test_type_ids' => [2]]],
                ['code' => 'xn_md', 'name' => 'Miễn dịch', 'type' => 'service_count', 'filter' => ['execute_department_id_self' => true, 'service_type_ids' => [2], 'test_type_ids' => [4]]],
                ['code' => 'xn_nt', 'name' => 'Nước tiểu', 'type' => 'service_count', 'filter' => ['execute_department_id_self' => true, 'service_type_ids' => [2], 'test_type_ids' => [7]]],
                ['code' => 'xn_khac', 'name' => 'XN khác', 'type' => 'service_count', 'filter' => ['execute_department_id_self' => true, 'service_type_ids' => [2], 'test_type_other_of' => [1, 2, 3, 4, 7]]],
            ],
        ],
    ];

    /** @return array chi tieu da decode */
    public function metricList()
    {
        $m = json_decode($this->metrics, true);
        return is_array($m) ? $m : [];
    }
}

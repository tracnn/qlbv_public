<?php

namespace App\Services\GiaoBan;

class GiaoBanPermission
{
    /**
     * @param bool  $isAdmin          user->can('giaoban-admin')
     * @param array $assignedDeptIds  dept_config_id được gán trong giaoban_user_departments
     * @param int   $deptConfigId     khoa đang sửa
     */
    public static function canEditDept($isAdmin, array $assignedDeptIds, $deptConfigId)
    {
        if ($isAdmin) return true;
        return in_array((int) $deptConfigId, array_map('intval', $assignedDeptIds), true);
    }

    /** Báo cáo final thì không ai sửa (admin phải mở khóa trước). */
    public static function canEditReport($status, $isAdmin)
    {
        return $status !== 'final';
    }

    /**
     * Khoa mà user được NHÌN THẤY số liệu.
     *
     * Cố ý tách khỏi canEditDept: quyền xem và quyền sửa là hai chuyện khác nhau.
     * Gộp lại thì sau này muốn cho ai đó xem-mà-không-sửa sẽ phải gỡ ra.
     *
     * @param bool  $isAdmin          user->can('giaoban-admin')
     * @param array $assignedDeptIds  dept_config_id được gán trong giaoban_user_departments
     * @param array $allActiveIds     id các khoa đang hoạt động, ĐÃ sắp theo sort_order
     * @return array id khoa được xem, giữ nguyên thứ tự của $allActiveIds
     */
    public static function visibleDeptConfigIds($isAdmin, array $assignedDeptIds, array $allActiveIds)
    {
        $tatCa = array_values(array_map('intval', $allActiveIds));
        if ($isAdmin) return $tatCa;

        $duocGan = array_map('intval', $assignedDeptIds);
        $out = [];
        // Duyet theo $allActiveIds chu khong theo $assignedDeptIds: thu tu hien thi phai bam
        // sort_order, va khoa da tat is_active thi khong hien du van con ban ghi gan.
        foreach ($tatCa as $id) {
            if (in_array($id, $duocGan, true)) $out[] = $id;
        }
        return $out;
    }

    /**
     * User thường không nhìn thấy khoa nào -> màn giao ban trống, phải báo cho họ biết vì sao.
     *
     * Nhận danh sách khoa NHÌN THẤY (kết quả visibleDeptConfigIds), không phải danh sách được gán:
     * người được gán một khoa đã tắt is_active cũng ra màn trống y hệt, và cũng cần thông báo.
     *
     * @param array $visibleDeptConfigIds kết quả của visibleDeptConfigIds()
     */
    public static function chuaPhanCongKhoa($isAdmin, array $visibleDeptConfigIds)
    {
        if ($isAdmin) return false;
        return empty($visibleDeptConfigIds);
    }
}

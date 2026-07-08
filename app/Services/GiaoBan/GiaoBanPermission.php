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
}

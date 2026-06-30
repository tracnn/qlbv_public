<?php

namespace App\Services\OrderCheck;

use App\Models\OrderCheck\OrderCheckViolation;
use Illuminate\Http\Request;

class ViolationQueryService
{
    /** Trạng thái người dùng được phép set qua workflow. */
    const UPDATABLE_STATUSES = ['seen', 'processed', 'false_positive'];

    public function isValidUpdateStatus($status)
    {
        return in_array($status, self::UPDATABLE_STATUSES, true);
    }

    /**
     * Query đã áp bộ lọc từ request. Dùng chung cho fetch/summary/export.
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function filtered(Request $request)
    {
        $q = OrderCheckViolation::query();

        if ($request->filled('date_from')) {
            $q->where('detected_at', '>=', $request->input('date_from') . ' 00:00:00');
        }
        if ($request->filled('date_to')) {
            $q->where('detected_at', '<=', $request->input('date_to') . ' 23:59:59');
        }
        if ($request->filled('severity')) {
            $q->where('severity', $request->input('severity'));
        }
        if ($request->filled('status')) {
            $q->where('status', $request->input('status'));
        }
        if ($request->filled('rule_code')) {
            $q->where('rule_code', $request->input('rule_code'));
        }
        if ($request->filled('department_id')) {
            $q->where('department_id', $request->input('department_id'));
        }
        if ($request->filled('service_req_type_id')) {
            $q->where('service_req_type_id', $request->input('service_req_type_id'));
        }
        if ($request->filled('department_keyword')) {
            $dk = trim($request->input('department_keyword'));
            $q->where(function ($w) use ($dk) {
                $w->where('department_code', 'like', "%{$dk}%")
                  ->orWhere('department_name', 'like', "%{$dk}%");
            });
        }
        if ($request->filled('keyword')) {
            $kw = trim($request->input('keyword'));
            $q->where(function ($w) use ($kw) {
                $w->where('patient_code', 'like', "%{$kw}%")
                  ->orWhere('patient_name', 'like', "%{$kw}%")
                  ->orWhere('treatment_code', 'like', "%{$kw}%")
                  ->orWhere('service_req_code', 'like', "%{$kw}%")
                  ->orWhere('service_code', 'like', "%{$kw}%")
                  ->orWhere('service_name', 'like', "%{$kw}%")
                  ->orWhere('doctor_loginname', 'like', "%{$kw}%")
                  ->orWhere('doctor_username', 'like', "%{$kw}%");
            });
        }

        return $q;
    }
}

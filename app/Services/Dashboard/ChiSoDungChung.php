<?php

namespace App\Services\Dashboard;

use DB;
use Illuminate\Database\Query\Builder;

/**
 * Cong thuc dung chung giua trang chu (HomeController) va Dashboard lanh dao.
 *
 * Moi ham tra Builder tren ket noi HISPro, CHUA select/groupBy: noi goi tu them
 * cot tong hop. Thoi gian HIS la so YYYYMMDDHH24MISS. branchIds rong = khong loc co so.
 */
class ChiSoDungChung
{
    /**
     * Bieu thuc so ngay dieu tri (khong alias; noi goi them "AS day_count").
     * < 4 gio = 0; 4-24 gio = 1; con lai = so ngay tron, cong 1 neu phan du >= 4 gio.
     */
    const SQL_SO_NGAY_DIEU_TRI = "
                CASE
                    WHEN ((TO_DATE(out_time, 'YYYYMMDDHH24MISS') - TO_DATE(clinical_in_time, 'YYYYMMDDHH24MISS')) * 24) < 4 THEN 0
                    WHEN ((TO_DATE(out_time, 'YYYYMMDDHH24MISS') - TO_DATE(clinical_in_time, 'YYYYMMDDHH24MISS')) * 24) >= 4 
                         AND ((TO_DATE(out_time, 'YYYYMMDDHH24MISS') - TO_DATE(clinical_in_time, 'YYYYMMDDHH24MISS')) * 24) < 24 THEN 1
                    ELSE 
                        CASE
                            WHEN MOD((TO_DATE(out_time, 'YYYYMMDDHH24MISS') - TO_DATE(clinical_in_time, 'YYYYMMDDHH24MISS')) * 24, 24) >= 4 
                                THEN FLOOR(TO_DATE(out_time, 'YYYYMMDDHH24MISS') - TO_DATE(clinical_in_time, 'YYYYMMDDHH24MISS')) + 1
                            ELSE FLOOR(TO_DATE(out_time, 'YYYYMMDDHH24MISS') - TO_DATE(clinical_in_time, 'YYYYMMDDHH24MISS'))
                        END
                END
            ";

    /** Ho so noi tru (loai 3) ra vien trong khoang. */
    public static function noiTruRaVien($from, $to, array $loaiTruIds, array $branchIds = [])
    {
        $q = DB::connection('HISPro')
            ->table('his_treatment')
            ->whereBetween('out_time', [$from, $to])
            ->where('tdl_treatment_type_id', 3);

        return self::apDungLoc($q, $loaiTruIds, $branchIds);
    }

    /** Luot kham cua benh nhan tao moi trong khoang. */
    public static function benhNhanMoi($from, $to, array $loaiTruIds, array $branchIds = [])
    {
        $q = DB::connection('HISPro')
            ->table('his_treatment')
            ->join('his_patient', 'his_patient.id', '=', 'his_treatment.patient_id')
            ->join('his_branch', 'his_branch.id', '=', 'his_treatment.branch_id')
            ->whereBetween('in_time', [$from, $to])
            ->whereBetween('his_patient.create_time', [$from, $to])
            ->where('his_patient.is_delete', 0);

        return self::apDungLoc($q, $loaiTruIds, $branchIds);
    }

    /** Luot kham cua benh nhan da co truoc khoang (tai kham). */
    public static function taiKham($from, $to, array $loaiTruIds, array $branchIds = [])
    {
        $q = DB::connection('HISPro')
            ->table('his_treatment')
            ->join('his_patient', 'his_patient.id', '=', 'his_treatment.patient_id')
            ->join('his_branch', 'his_branch.id', '=', 'his_treatment.branch_id')
            ->whereBetween('in_time', [$from, $to])
            ->where('his_patient.create_time', '<', $from)
            ->where('his_treatment.is_delete', 0);

        return self::apDungLoc($q, $loaiTruIds, $branchIds);
    }

    private static function apDungLoc(Builder $q, array $loaiTruIds, array $branchIds)
    {
        if ($loaiTruIds) {
            $q->whereNotIn('his_treatment.tdl_patient_type_id', $loaiTruIds);
        }
        if ($branchIds) {
            $q->whereIn('his_treatment.branch_id', $branchIds);
        }

        return $q;
    }
}

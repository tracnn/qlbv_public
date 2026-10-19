<?php

namespace App\Services\BHYT;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Truy van bao cao "Bac si y lenh": dem y lenh thuoc (xml2) + DVKT (xml3) cua ho so XML3176
 * theo bac si, gom theo khoa. Dung chung cho man danh sach va file Excel.
 */
class BacSiYLenhQuery
{
    /** Loai ngay chi co tren xml1 - loc qua tap ma_lk cua xml1. */
    const NGAY_TREN_XML1 = [
        'date_in' => 'ngay_vao',
        'date_out' => 'ngay_ra',
        'date_payment' => 'ngay_ttoan',
    ];

    /**
     * @param string $dateType date_yl (mac dinh) | date_in | date_out | date_payment | date_create | date_update
     * @param string $dateFrom 'Y-m-d' hoac 'Y-m-d H:i:s'
     * @param string $dateTo   'Y-m-d' hoac 'Y-m-d H:i:s'
     * @param string $cotMaKhoa Cot lay ma khoa: 'ms.ma_khoa' (khoa cua NVYT) hoac 'q.ma_khoa' (khoa tren y lenh)
     * @return \Illuminate\Database\Query\Builder
     */
    public static function build($dateType, $dateFrom, $dateTo, $cotMaKhoa = 'ms.ma_khoa')
    {
        if (strlen($dateFrom) == 10) {
            $dateFrom = Carbon::createFromFormat('Y-m-d', $dateFrom)->startOfDay()->format('Y-m-d H:i:s');
        }
        if (strlen($dateTo) == 10) {
            $dateTo = Carbon::createFromFormat('Y-m-d', $dateTo)->endOfDay()->format('Y-m-d H:i:s');
        }

        $laTimestamp = in_array($dateType, ['date_create', 'date_update'], true);
        $tu = $laTimestamp ? $dateFrom : Carbon::createFromFormat('Y-m-d H:i:s', $dateFrom)->format('YmdHi');
        $den = $laTimestamp ? $dateTo : Carbon::createFromFormat('Y-m-d H:i:s', $dateTo)->format('YmdHi');

        $union = self::nguon('xml3176_xml3s', $dateType, $tu, $den, $cotMaKhoa)
            ->unionAll(self::nguon('xml3176_xml2s', $dateType, $tu, $den, $cotMaKhoa));

        return DB::table(DB::raw("({$union->toSql()}) as t"))
            ->mergeBindings($union)
            ->select(['t.ma_khoa', 't.ten_khoa', 't.ma_bac_si', 't.ho_ten', DB::raw('COUNT(*) as tong_so')])
            ->groupBy('t.ma_khoa', 't.ten_khoa', 't.ma_bac_si', 't.ho_ten');
    }

    private static function nguon($bang, $dateType, $tu, $den, $cotMaKhoa)
    {
        $q = DB::table($bang . ' as q')
            ->leftJoin('medical_staffs as ms', 'ms.macchn', '=', 'q.ma_bac_si')
            ->select([
                DB::raw("COALESCE({$cotMaKhoa}, '') as ma_khoa"),
                DB::raw("COALESCE(ms.ten_khoa, '') as ten_khoa"),
                'q.ma_bac_si',
                DB::raw("COALESCE(ms.ho_ten, '') as ho_ten"),
            ]);

        if (isset(self::NGAY_TREN_XML1[$dateType])) {
            // xml1 unique (ma_lk, stt): JOIN se nhan doi y lenh - loc qua tap ma_lk.
            $cot = self::NGAY_TREN_XML1[$dateType];
            return $q->whereIn('q.ma_lk', function ($s) use ($cot, $tu, $den) {
                $s->from('xml3176_xml1s')->select('ma_lk')->whereBetween($cot, [$tu, $den]);
            });
        }

        $cot = ['date_create' => 'created_at', 'date_update' => 'updated_at'][$dateType] ?? 'ngay_yl';

        return $q->whereBetween("q.$cot", [$tu, $den]);
    }
}

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
     * @param string $cotMaKhoa 'ms.ma_khoa' (khoa cua NVYT, mot dong moi bac si) hoac 'q.ma_khoa' (khoa tren y lenh, mot dong moi bac si x khoa)
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

        $khoaYLenh = $cotMaKhoa === 'q.ma_khoa';

        $union = self::nguon('xml3176_xml3s', $dateType, $tu, $den, $khoaYLenh)
            ->unionAll(self::nguon('xml3176_xml2s', $dateType, $tu, $den, $khoaYLenh));

        // Dem theo bac si TRUOC roi moi noi NVYT: medical_staffs khong co index macchn
        // (JOIN tung dong y lenh quet cheo hang trieu x hang nghin dong) va co nhieu dong
        // cung macchn (unique theo dinh danh + khoa + co so + tu ngay) - JOIN thang nhan
        // so y lenh len theo so dong do.
        //
        // Binding cua bang dan xuat dat vao nhom 'join' (5.5 khong co nhom 'from'): no lam
        // phang TRUOC 'where'. mergeBindings giu nhom 'union' cua ngay xml2, ma nhom do
        // dung SAU 'where' - them where tim kiem tren t.* la lech thu tu dau '?'.
        $dem = DB::table(DB::raw("({$union->toSql()}) as u"))
            ->addBinding($union->getBindings(), 'join')
            ->select(array_merge(['u.ma_bac_si'], $khoaYLenh ? ['u.ma_khoa'] : [], [DB::raw('COUNT(*) as tong_so')]))
            ->groupBy(array_merge(['u.ma_bac_si'], $khoaYLenh ? ['u.ma_khoa'] : []));

        $nvyt = DB::table('medical_staffs')
            ->select(['macchn', DB::raw('MAX(ma_khoa) as ma_khoa'), DB::raw('MAX(ten_khoa) as ten_khoa'), DB::raw('MAX(ho_ten) as ho_ten')])
            ->groupBy('macchn');

        $ketQua = DB::table(DB::raw("({$dem->toSql()}) as d"))
            ->addBinding($dem->getBindings(), 'join')
            ->leftJoin(DB::raw("({$nvyt->toSql()}) as ms"), 'ms.macchn', '=', 'd.ma_bac_si')
            ->select([
                DB::raw('COALESCE(' . ($khoaYLenh ? 'd' : 'ms') . ".ma_khoa, '') as ma_khoa"),
                DB::raw("COALESCE(ms.ten_khoa, '') as ten_khoa"),
                'd.ma_bac_si',
                DB::raw("COALESCE(ms.ho_ten, '') as ho_ten"),
                'd.tong_so',
            ]);

        // Boc lai thanh bang t: man danh sach loc o tim kiem tren t.ma_khoa, t.ho_ten...
        return DB::table(DB::raw("({$ketQua->toSql()}) as t"))
            ->addBinding($ketQua->getBindings(), 'join')
            ->select(['t.ma_khoa', 't.ten_khoa', 't.ma_bac_si', 't.ho_ten', 't.tong_so']);
    }

    private static function nguon($bang, $dateType, $tu, $den, $khoaYLenh)
    {
        $q = DB::table($bang . ' as q')
            ->select($khoaYLenh ? ['q.ma_bac_si', 'q.ma_khoa'] : ['q.ma_bac_si']);

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

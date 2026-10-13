<?php

namespace App\Services\Xml3176;

use App\Models\BHYT\Xml3176VatPhamKemDvkt;
use App\Services\Xml3176\Support\VatPhamKemDvktKey;
use Illuminate\Support\Facades\DB;

/**
 * Hoc bang tra vat pham -> DVKT me tu dong XML3/XML2 da duoc HIS gan ma_dich_vu.
 *
 * Chi giu vat pham co ty le dong BHYT tra duoc gan >= nguong va so dong >= toi thieu: bom
 * kim, day truyen, NaCl... cung tung duoc gan vao DVKT (dung trong ca thu thuat) nen hoc tho
 * se bao nham hang chuc nghin ho so. Gom bang SQL GROUP BY, khong keo dong chi tiet ve PHP.
 */
class VatPhamKemDvktLearner
{
    const NGUON = [
        'VTYT' => ['bang' => 'xml3176_xml3s', 'ma' => 'ma_vat_tu', 'ten' => 'ten_vat_tu'],
        'THUOC' => ['bang' => 'xml3176_xml2s', 'ma' => 'ma_thuoc', 'ten' => 'ten_thuoc'],
    ];

    public function hoc(float $nguong, int $toiThieu, $tuNgay = null): array
    {
        $dong = [];
        $soVp = [];
        $cacCap = [];
        $maGoc = [];

        foreach (self::NGUON as $loai => $n) {
            $chon = $this->chonVatPham($loai, $n, $nguong, $toiThieu, $tuNgay);
            $soVp[$loai] = count($chon);
            if (empty($chon)) {
                continue;
            }
            $cap = $this->capDaGan($loai, $n, $chon, $tuNgay);
            foreach ($cap as $dsDv) {
                foreach ($dsDv as $maDv => $c) {
                    // Ca ma goc (vd 12.0448.1187_GT) lan ma chuan hoa: dong DVKT co the mang dang nao cung duoc.
                    $maGoc[] = $maDv;
                    $maGoc = array_merge($maGoc, array_keys($c['goc']));
                }
            }
            $cacCap[$loai] = [$chon, $cap];
        }

        $tenDv = $this->tenDvkt($maGoc);

        foreach ($cacCap as $loai => list($chon, $cap)) {
            foreach ($cap as $khoa => $dsDv) {
                $vp = $chon[$khoa];
                foreach ($dsDv as $maDv => $c) {
                    $dong[] = [
                        'loai' => $loai,
                        'ma_vat_pham' => $vp['ma'],
                        'ten_vat_pham' => $vp['ten'],
                        'ten_khop' => $vp['ten_khop'],
                        'ma_dvkt' => $maDv,
                        'ten_dvkt' => $tenDv[$maDv] ?? null,
                        'so_lan_gan' => $c['n'],
                        'ty_le_gan' => round($vp['lk'] / $vp['n'], 4),
                    ];
                }
            }
        }

        return ['dong' => $dong, 'so_vat_pham' => $soVp];
    }

    public function ghi(array $dong): array
    {
        $kq = ['them' => 0, 'cap_nhat' => 0, 'tat' => 0, 'bat_lai' => 0];

        DB::transaction(function () use ($dong, &$kq) {
            $hienCo = [];
            foreach (Xml3176VatPhamKemDvkt::all() as $m) {
                $hienCo[self::khoaDong($m->loai, $m->ma_vat_pham, $m->ten_khop, $m->ma_dvkt)] = $m;
            }

            $daHoc = [];
            foreach ($dong as $d) {
                $k = self::khoaDong($d['loai'], $d['ma_vat_pham'], $d['ten_khop'], $d['ma_dvkt']);
                $daHoc[$k] = true;
                $m = $hienCo[$k] ?? null;

                if ($m === null) {
                    Xml3176VatPhamKemDvkt::create($d + ['nguon' => Xml3176VatPhamKemDvkt::NGUON_HOC, 'hieu_luc' => true]);
                    $kq['them']++;
                    continue;
                }
                if ($m->nguon !== Xml3176VatPhamKemDvkt::NGUON_HOC) {
                    continue;
                }

                $m->fill([
                    'ten_vat_pham' => $d['ten_vat_pham'],
                    'ten_dvkt' => $d['ten_dvkt'] ?? $m->ten_dvkt, // khong ghi de ten cu bang null
                    'so_lan_gan' => $d['so_lan_gan'],
                    'ty_le_gan' => $d['ty_le_gan'],
                ]);
                if (!$m->hieu_luc && $m->ghi_chu === Xml3176VatPhamKemDvkt::GHI_CHU_TU_TAT) {
                    $m->hieu_luc = true;
                    $m->ghi_chu = null;
                    $kq['bat_lai']++;
                }
                $m->save();
                $kq['cap_nhat']++;
            }

            foreach ($hienCo as $k => $m) {
                if (isset($daHoc[$k]) || $m->nguon !== Xml3176VatPhamKemDvkt::NGUON_HOC || !$m->hieu_luc) {
                    continue;
                }
                $m->hieu_luc = false;
                if ($m->ghi_chu === null || $m->ghi_chu === '') {
                    $m->ghi_chu = Xml3176VatPhamKemDvkt::GHI_CHU_TU_TAT;
                }
                $m->save();
                $kq['tat']++;
            }
        });

        return $kq;
    }

    private static function khoaDong($loai, $ma, $tenKhop, $maDvkt): string
    {
        return implode("\x1F", [$loai, trim((string) $ma), (string) $tenKhop, VatPhamKemDvktKey::chuanHoaMaDvkt($maDvkt)]);
    }

    private function chonVatPham(string $loai, array $n, float $nguong, int $toiThieu, $tuNgay): array
    {
        $q = DB::table($n['bang'])
            ->selectRaw("{$n['ma']} as ma, {$n['ten']} as ten, COUNT(*) as so_dong, "
                . "SUM(CASE WHEN ma_dich_vu IS NOT NULL AND ma_dich_vu <> '' THEN 1 ELSE 0 END) as so_lk")
            ->where($n['ma'], '<>', '')
            ->where('t_bhtt', '>', 0)
            ->groupBy($n['ma'], $n['ten']);
        if ($tuNgay) {
            $q->where('ngay_yl', '>=', $tuNgay);
        }

        $gom = [];
        foreach ($q->get() as $r) {
            $khoa = VatPhamKemDvktKey::khoa($loai, $r->ma, $r->ten);
            if (!isset($gom[$khoa])) {
                $gom[$khoa] = [
                    'ma' => trim((string) $r->ma),
                    'ten' => (string) $r->ten,
                    'ten_khop' => VatPhamKemDvktKey::tenKhop($loai, $r->ma, $r->ten),
                    'n' => 0,
                    'lk' => 0,
                ];
            }
            $gom[$khoa]['n'] += (int) $r->so_dong;
            $gom[$khoa]['lk'] += (int) $r->so_lk;
        }

        return array_filter($gom, function ($g) use ($nguong, $toiThieu) {
            return $g['n'] >= $toiThieu && $g['lk'] / $g['n'] >= $nguong;
        });
    }

    private function capDaGan(string $loai, array $n, array $chon, $tuNgay): array
    {
        $q = DB::table($n['bang'])
            ->selectRaw("{$n['ma']} as ma, {$n['ten']} as ten, ma_dich_vu as dv, COUNT(*) as so_dong")
            ->where($n['ma'], '<>', '')
            ->where('ma_dich_vu', '<>', '')
            ->groupBy($n['ma'], $n['ten'], 'ma_dich_vu');
        if ($tuNgay) {
            $q->where('ngay_yl', '>=', $tuNgay);
        }

        $cap = [];
        foreach ($q->get() as $r) {
            $khoa = VatPhamKemDvktKey::khoa($loai, $r->ma, $r->ten);
            $dv = VatPhamKemDvktKey::chuanHoaMaDvkt($r->dv);
            if (!isset($chon[$khoa]) || $dv === '') {
                continue;
            }
            if (!isset($cap[$khoa][$dv])) {
                $cap[$khoa][$dv] = ['n' => 0, 'goc' => []];
            }
            $cap[$khoa][$dv]['n'] += (int) $r->so_dong;
            $cap[$khoa][$dv]['goc'][$r->dv] = true;
        }

        return $cap;
    }

    /** Ten DVKT lay tu chinh dong DVKT trong XML3 (ma_vat_tu rong), khoa theo ma chuan hoa. */
    private function tenDvkt(array $maGoc): array
    {
        $ten = [];
        foreach (array_chunk(array_values(array_unique($maGoc)), 500) as $lo) {
            $rows = DB::table('xml3176_xml3s')
                ->selectRaw('ma_dich_vu as ma, MAX(ten_dich_vu) as ten')
                ->where(function ($q) {
                    $q->whereNull('ma_vat_tu')->orWhere('ma_vat_tu', '');
                })
                ->whereIn('ma_dich_vu', $lo)
                ->groupBy('ma_dich_vu')
                ->get();
            foreach ($rows as $r) {
                $k = VatPhamKemDvktKey::chuanHoaMaDvkt($r->ma);
                if (!isset($ten[$k]) && $r->ten !== null && $r->ten !== '') {
                    $ten[$k] = $r->ten;
                }
            }
        }

        return $ten;
    }

    /**
     * Nap cac dong Excel da duyet. Dong co san -> cap nhat hieu_luc, ghi_chu; dong moi -> them
     * voi nguon TAY. Khong xoa dong nao. Dong loi duoc bao lai, cac dong khac van nap.
     */
    public function nap(array $hang): array
    {
        $kq = ['them' => 0, 'cap_nhat' => 0, 'loi' => []];

        foreach (array_values($hang) as $i => $h) {
            $dongExcel = $i + 2;
            if (count(array_filter(array_map(function ($v) { return trim((string) $v); }, (array) $h), 'strlen')) === 0) {
                continue;
            }

            $loai = strtoupper(trim((string) ($h['loai'] ?? '')));
            // Excel o dang so (vd 40.54 thay vi '40.540') da mat so 0 cuoi, khong the doan lai ma
            if ((isset($h['ma_vat_pham']) && !is_string($h['ma_vat_pham'])) || (isset($h['ma_dvkt']) && !is_string($h['ma_dvkt']))) {
                $kq['loi'][] = ['dong' => $dongExcel, 'ly_do' => 'Mã vật phẩm/mã DVKT phải ở định dạng Text trong Excel'];
                continue;
            }
            $ma = trim((string) ($h['ma_vat_pham'] ?? ''));
            $maDv = VatPhamKemDvktKey::chuanHoaMaDvkt($h['ma_dvkt'] ?? '');

            if (!in_array($loai, [Xml3176VatPhamKemDvkt::LOAI_VTYT, Xml3176VatPhamKemDvkt::LOAI_THUOC], true)) {
                $kq['loi'][] = ['dong' => $dongExcel, 'ly_do' => "Loại '{$loai}' không hợp lệ (VTYT hoặc THUOC)"];
                continue;
            }
            if ($ma === '' || $maDv === '') {
                $kq['loi'][] = ['dong' => $dongExcel, 'ly_do' => 'Thiếu mã vật phẩm hoặc mã DVKT'];
                continue;
            }

            $ten = trim((string) ($h['ten_vat_pham'] ?? ''));
            if ($loai === Xml3176VatPhamKemDvkt::LOAI_VTYT && VatPhamKemDvktKey::laMaNhomChung($ma) && $ten === '') {
                $kq['loi'][] = ['dong' => $dongExcel, 'ly_do' => 'VTYT mã nhóm chung cần tên vật phẩm để khớp'];
                continue;
            }
            $tenKhop = VatPhamKemDvktKey::tenKhop($loai, $ma, $ten);
            $ghiChu = trim((string) ($h['ghi_chu'] ?? ''));
            $ghiChu = $ghiChu === '' ? null : $ghiChu;
            $hieuLuc = self::docHieuLuc($h['hieu_luc'] ?? null);

            $m = Xml3176VatPhamKemDvkt::where([
                'loai' => $loai, 'ma_vat_pham' => $ma, 'ten_khop' => $tenKhop, 'ma_dvkt' => $maDv,
            ])->first();

            if ($m) {
                $m->hieu_luc = $hieuLuc;
                $m->ghi_chu = $ghiChu;
                $m->save();
                $kq['cap_nhat']++;
                continue;
            }

            $tenDvkt = trim((string) ($h['ten_dvkt'] ?? ''));
            Xml3176VatPhamKemDvkt::create([
                'loai' => $loai, 'ma_vat_pham' => $ma, 'ten_vat_pham' => $ten === '' ? null : $ten,
                'ten_khop' => $tenKhop, 'ma_dvkt' => $maDv, 'ten_dvkt' => $tenDvkt === '' ? null : $tenDvkt,
                'nguon' => Xml3176VatPhamKemDvkt::NGUON_TAY, 'so_lan_gan' => 0, 'ty_le_gan' => null,
                'hieu_luc' => $hieuLuc, 'ghi_chu' => $ghiChu,
            ]);
            $kq['them']++;
        }

        return $kq;
    }

    /** O trong -> bat. Tat khi: 0, false, khong/không, n, no, tat/tắt. */
    public static function docHieuLuc($v): bool
    {
        if ($v === null) {
            return true;
        }
        if (is_bool($v)) {
            return $v;
        }
        $s = mb_strtolower(trim((string) $v), 'UTF-8');

        return !in_array($s, ['0', 'false', 'khong', 'không', 'n', 'no', 'tat', 'tắt'], true);
    }
}

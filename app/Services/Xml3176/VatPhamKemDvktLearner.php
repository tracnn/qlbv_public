<?php

namespace App\Services\Xml3176;

use App\Models\BHYT\Xml3176VatPhamKemDvkt;
use App\Models\BHYT\Xml3176VpkdDvktBatBuoc;
use App\Models\BHYT\Xml3176VpkdUngVien;
use App\Services\Xml3176\Support\TextNormalizer;
use App\Services\Xml3176\Support\VatPhamKemDvktKey;
use Carbon\Carbon;
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

    public function hoc(float $nguong, int $toiThieu, $tuNgay = null, float $nguongUngVien = 0.5): array
    {
        $dong = [];
        $ungVienRa = [];
        $soVp = [];
        $cacCap = [];
        $maGoc = [];

        foreach (self::NGUON as $loai => $n) {
            $chon = [];
            $ungVien = [];
            foreach ($this->thongKeVatPham($loai, $n, $tuNgay) as $khoa => $g) {
                $tyLe = $g['lk'] / $g['n'];
                if ($g['n'] >= $toiThieu && $tyLe >= $nguong) {
                    $chon[$khoa] = $g;
                } elseif ($g['lk'] >= 1 && $tyLe >= $nguongUngVien) {
                    // Chua vao bang tra: hoac ty le trong [nguongUngVien, nguong), hoac du ty le
                    // nhung thieu so dong toi thieu.
                    $ungVien[$khoa] = $g;
                }
            }
            $soVp[$loai] = count($chon);
            if (empty($chon) && empty($ungVien)) {
                continue;
            }
            $cap = $this->capDaGan($loai, $n, $chon + $ungVien, $tuNgay);
            foreach ($cap as $dsDv) {
                foreach ($dsDv as $maDv => $c) {
                    // Ca ma goc (vd 12.0448.1187_GT) lan ma chuan hoa: dong DVKT co the mang dang nao cung duoc.
                    $maGoc[] = $maDv;
                    $maGoc = array_merge($maGoc, array_keys($c['goc']));
                }
            }
            $cacCap[$loai] = [$chon, $ungVien, $cap];
        }

        $tenDvDs = $this->tenDvkt($maGoc);
        $tenDv = array_map(function ($ds) { return max($ds); }, $tenDvDs);

        // Bo cap co DVKT me khong hop le TRUOC khi dung bang tra va ung vien.
        foreach ($cacCap as $loai => list($chon, $ungVien, $cap)) {
            foreach ($cap as $khoa => $dsDv) {
                $maVp = ($chon[$khoa] ?? $ungVien[$khoa])['ma'];
                foreach (array_keys($dsDv) as $maDv) {
                    if (!self::laDvktMeHopLe((string) $maDv, $maVp, $tenDvDs[$maDv] ?? [])) {
                        unset($cacCap[$loai][2][$khoa][$maDv]);
                    }
                }
            }
        }

        foreach ($cacCap as $loai => list($chon, $ungVien, $cap)) {
            foreach ($chon as $khoa => $vp) {
                foreach ($cap[$khoa] ?? [] as $maDv => $c) {
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
            foreach ($ungVien as $khoa => $vp) {
                $dsDv = [];
                foreach ($cap[$khoa] ?? [] as $maDv => $c) {
                    $dsDv[] = ['ma' => (string) $maDv, 'ten' => $tenDv[$maDv] ?? null, 'so_lan' => $c['n']];
                }
                usort($dsDv, function ($a, $b) {
                    return ($b['so_lan'] <=> $a['so_lan']) ?: strcmp($a['ma'], $b['ma']);
                });
                $ungVienRa[] = [
                    'loai' => $loai,
                    'ma_vat_pham' => $vp['ma'],
                    'ten_vat_pham' => $vp['ten'],
                    'ten_khop' => $vp['ten_khop'],
                    'so_dong' => $vp['n'],
                    'so_lien_ket' => $vp['lk'],
                    'ty_le_gan' => round($vp['lk'] / $vp['n'], 4),
                    'dvkt' => $dsDv,
                ];
            }
        }

        return ['dong' => $dong, 'so_vat_pham' => $soVp, 'ung_vien' => $ungVienRa];
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

    /** Thay toan bo bang ung vien bang danh sach moi (tu hoc()). Tra so dong ghi. */
    public function ghiUngVien(array $ungVien, $lanHocId = null): int
    {
        return DB::transaction(function () use ($ungVien, $lanHocId) {
            Xml3176VpkdUngVien::query()->delete();
            foreach ($ungVien as $u) {
                Xml3176VpkdUngVien::create($u + ['lan_hoc_id' => $lanHocId]);
            }

            return count($ungVien);
        });
    }

    /**
     * R3: DVKT ma >= $nguong ho so BHYT tra deu kem >= 1 vat pham cung nhom (vat pham co DVKT nay
     * trong danh sach me HIEU LUC cua bang tra) duoc BHYT tra, va co >= $toiThieu ho so. Doc bang tra
     * hien tai - goi SAU ghi(). Chi dem dong t_bhtt > 0 ca hai phia.
     */
    public function hocDvktBatBuoc(float $nguong, int $toiThieu, $tuNgay = null): array
    {
        $nhom = [];
        $tenDv = [];
        $maVp = ['VTYT' => [], 'THUOC' => []];
        foreach (Xml3176VatPhamKemDvkt::where('hieu_luc', true)->get(['loai', 'ma_vat_pham', 'ten_khop', 'ma_dvkt', 'ten_dvkt']) as $m) {
            $dv = VatPhamKemDvktKey::chuanHoaMaDvkt($m->ma_dvkt);
            $nhom[$dv][$m->loai . '|' . trim($m->ma_vat_pham) . '|' . $m->ten_khop] = true;
            $maVp[$m->loai][trim($m->ma_vat_pham)] = true;
            if (!isset($tenDv[$dv]) && $m->ten_dvkt !== null && $m->ten_dvkt !== '') {
                $tenDv[$dv] = $m->ten_dvkt;
            }
        }
        if (empty($nhom)) {
            return [];
        }

        // Ho so co DVKT duoc BHYT tra (ma goc va bien the _GT).
        $hs = [];
        foreach (array_chunk(array_keys($nhom), 250) as $lo) {
            $ma = array_merge($lo, array_map(function ($x) { return $x . '_GT'; }, $lo));
            $q = DB::table('xml3176_xml3s')->select('ma_lk', 'ma_dich_vu')
                ->where(function ($w) { $w->whereNull('ma_vat_tu')->orWhere('ma_vat_tu', ''); })
                ->whereIn('ma_dich_vu', $ma)->where('t_bhtt', '>', 0)
                ->groupBy('ma_lk', 'ma_dich_vu');
            if ($tuNgay) {
                $q->where('ngay_yl', '>=', $tuNgay);
            }
            foreach ($q->get() as $r) {
                $hs[VatPhamKemDvktKey::chuanHoaMaDvkt($r->ma_dich_vu)][$r->ma_lk] = true;
            }
        }
        $canLk = [];
        foreach ($hs as $lks) {
            $canLk += $lks;
        }

        // Vat pham duoc BHYT tra trong cac ho so do: ma_lk => {khoa vat pham}.
        $coVp = [];
        foreach (self::NGUON as $loai => $n) {
            foreach (array_chunk(array_keys($maVp[$loai]), 250) as $lo) {
                $q = DB::table($n['bang'])->select('ma_lk', "{$n['ma']} as ma", "{$n['ten']} as ten")
                    ->whereIn($n['ma'], $lo)->where('t_bhtt', '>', 0)
                    ->groupBy('ma_lk', $n['ma'], $n['ten']);
                if ($tuNgay) {
                    $q->where('ngay_yl', '>=', $tuNgay);
                }
                foreach ($q->cursor() as $r) {
                    if (isset($canLk[$r->ma_lk])) {
                        $coVp[$r->ma_lk][$loai . '|' . VatPhamKemDvktKey::khoa($loai, $r->ma, $r->ten)] = true;
                    }
                }
            }
        }

        $ra = [];
        foreach ($hs as $dv => $lks) {
            $n = count($lks);
            $co = 0;
            foreach (array_keys($lks) as $lk) {
                if (!empty(array_intersect_key($coVp[$lk] ?? [], $nhom[$dv]))) {
                    $co++;
                }
            }
            if ($n >= $toiThieu && $co / $n >= $nguong) {
                $ra[] = ['ma_dvkt' => (string) $dv, 'ten_dvkt' => $tenDv[$dv] ?? null, 'so_ho_so' => $n,
                    'so_ho_so_co_kem' => $co, 'ty_le' => round($co / $n, 4)];
            }
        }
        usort($ra, function ($a, $b) { return strcmp($a['ma_dvkt'], $b['ma_dvkt']); });

        return $ra;
    }

    /** Cung ngu nghia ghi(): chi dong HOC; giu hieu_luc/ghi_chu nguoi dung; tu tat / tu bat lai. */
    public function ghiDvktBatBuoc(array $dong): array
    {
        $kq = ['them' => 0, 'cap_nhat' => 0, 'tat' => 0, 'bat_lai' => 0];

        DB::transaction(function () use ($dong, &$kq) {
            $hienCo = Xml3176VpkdDvktBatBuoc::all()->keyBy('ma_dvkt');
            $daHoc = [];
            foreach ($dong as $d) {
                $daHoc[$d['ma_dvkt']] = true;
                $m = $hienCo->get($d['ma_dvkt']);
                if ($m === null) {
                    Xml3176VpkdDvktBatBuoc::create($d + ['nguon' => Xml3176VatPhamKemDvkt::NGUON_HOC, 'hieu_luc' => true]);
                    $kq['them']++;
                    continue;
                }
                if ($m->nguon !== Xml3176VatPhamKemDvkt::NGUON_HOC) {
                    continue;
                }
                $m->fill([
                    'ten_dvkt' => $d['ten_dvkt'] ?? $m->ten_dvkt,
                    'so_ho_so' => $d['so_ho_so'], 'so_ho_so_co_kem' => $d['so_ho_so_co_kem'], 'ty_le' => $d['ty_le'],
                ]);
                if (!$m->hieu_luc && $m->ghi_chu === Xml3176VatPhamKemDvkt::GHI_CHU_TU_TAT) {
                    $m->hieu_luc = true;
                    $m->ghi_chu = null;
                    $kq['bat_lai']++;
                }
                $m->save();
                $kq['cap_nhat']++;
            }
            foreach ($hienCo as $ma => $m) {
                if (isset($daHoc[$ma]) || $m->nguon !== Xml3176VatPhamKemDvkt::NGUON_HOC || !$m->hieu_luc) {
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

    /**
     * DVKT me hop le khi:
     *  - co dang ma ky thuat NN.NNNN.NNNN (prod 29/08/2026: HIS ghi MA_DICH_VU = 40.642, chinh ma
     *    thuoc Xenetix, tren 20 dong XML2 - lenh hoc tung coi do la mot DVKT);
     *  - khac ma vat pham;
     *  - MOI ten cua ma (string hoac mang) khong mang nghia "khong dung thuoc can quang/chat tuong phan" (prod: Iopamiro gan vao
     *    "CLVT long nguc khong tiem thuoc can quang" 42 lan - hoc vao thi R1 bo lot dung ca can bat).
     */
    public static function laDvktMeHopLe(string $maDv, string $maVatPham, $tenDv): bool
    {
        if ($maDv === trim($maVatPham) || !preg_match('/^\d{2}\.\d{4}\.\d{4}/', $maDv)) {
            return false;
        }
        $mauLoai = array_filter(array_map([TextNormalizer::class, 'chuan'],
            (array) config('xml3176.vat_pham_kem_dvkt.dvkt_me_loai_tru_ten', [])), 'strlen');
        foreach ((array) $tenDv as $t) {
            $ten = TextNormalizer::chuan($t);
            foreach ($mauLoai as $mau) {
                if ($ten !== '' && mb_strpos($ten, $mau, 0, 'UTF-8') !== false) {
                    return false;
                }
            }
        }

        return true;
    }

    private static function khoaDong($loai, $ma, $tenKhop, $maDvkt): string
    {
        return implode("\x1F", [$loai, trim((string) $ma), (string) $tenKhop, VatPhamKemDvktKey::chuanHoaMaDvkt($maDvkt)]);
    }

    private function thongKeVatPham(string $loai, array $n, $tuNgay): array
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

        return $gom;
    }

    private function capDaGan(string $loai, array $n, array $chon, $tuNgay): array
    {
        $q = DB::table($n['bang'])
            ->selectRaw("{$n['ma']} as ma, {$n['ten']} as ten, ma_dich_vu as dv, COUNT(*) as so_dong")
            ->where($n['ma'], '<>', '')
            ->where('ma_dich_vu', '<>', '')
            // Chi hoc tu dong quy BHYT tra: prod 20 dong Xenetix cua ho so dich vu (doi tuong 9, BN tu
            // tra) ghi MA_DICH_VU sai - du lieu tu tra khong duoc day quy tac thanh toan BHYT.
            ->where('t_bhtt', '>', 0)
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
    /**
     * MOI ten cua tung ma DVKT (dong DVKT trong XML3), khoa theo ma chuan hoa. Cung mot ma co the
     * mang nhieu ten (vd 18.0219.0040 "(PACS)" va "[khong co thuoc can quang]"): loai tru phai xet
     * het, con ten hien thi lay ten lon nhat nhu truoc.
     *
     * @return array ma => string[]
     */
    private function tenDvkt(array $maGoc): array
    {
        $ten = [];
        foreach (array_chunk(array_values(array_unique($maGoc)), 500) as $lo) {
            $rows = DB::table('xml3176_xml3s')
                ->select('ma_dich_vu as ma', 'ten_dich_vu as ten')
                ->where(function ($q) {
                    $q->whereNull('ma_vat_tu')->orWhere('ma_vat_tu', '');
                })
                ->whereIn('ma_dich_vu', $lo)
                ->whereNotNull('ten_dich_vu')->where('ten_dich_vu', '<>', '')
                ->distinct()
                ->get();
            foreach ($rows as $r) {
                $ten[VatPhamKemDvktKey::chuanHoaMaDvkt($r->ma)][$r->ten] = true;
            }
        }

        return array_map('array_keys', $ten);
    }

    /**
     * Nap cac dong Excel da duyet. Dong co san -> cap nhat hieu_luc, ghi_chu; dong moi -> them
     * voi nguon TAY. Khong xoa dong nao. Dong loi duoc bao lai, cac dong khac van nap.
     */
    public function nap(array $hang, $nguoiSuaId = null): array
    {
        $kq = ['them' => 0, 'cap_nhat' => 0, 'loi' => []];
        // Nap tu man quan tri thi ghi vet nguoi sua; lenh dong lenh truyen null.
        $vet = $nguoiSuaId === null ? [] : ['nguoi_sua_id' => (int) $nguoiSuaId, 'sua_luc' => Carbon::now()];

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
                $m->fill($vet);
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
            ] + $vet);
            $kq['them']++;
        }

        Xml3176PhienBanDanhMuc::tang('nap bang tra vat pham kem DVKT');

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

<?php

namespace App\Services\Xml3176;

use App\Services\Xml3176\Support\VatPhamKemDvktKey;

/**
 * VTYT/thuoc thanh toan ngoai gia DVKT ("Chua bao gom ..." trong QD gia) chi duoc quy tra
 * khi DVKT me duoc quy tra. Lop thuan: nhan dong XML3/XML2 cua MOT ho so, khong cham CSDL.
 *
 * R1 - dong KHONG lien ket (ma_dich_vu rong): vat pham co trong bang tra ma ho so khong co
 *      DVKT me nao duoc BHYT tra. Do tren CSDL that: 97% dong VTYT khong co ma_dich_vu,
 *      loi that (quen ke DVKT, BN tu tra DVKT) nam o day.
 * R2 - dong CO lien ket: DVKT me phai co, duoc BHYT tra, va so goi_vtyt khac nhau khong vuot
 *      so lan DVKT me duoc tra. Ngay 13/10/2026 do duoc 0 ca - day la chot chan khi HIS doi.
 */
class VatPhamKemDvktRule
{
    const TOI_DA_MA_ME = 5;
    const TOI_DA_TEN = 3;

    const TEN = [
        'VTYT_KHONG_CO_DVKT_ME' => 'VTYT thanh toán ngoài giá không có DVKT mẹ được BHYT thanh toán',
        'THUOC_KHONG_CO_DVKT_ME' => 'Thuốc thanh toán ngoài giá không có DVKT mẹ được BHYT thanh toán',
        'GOI_VTYT_KHONG_KHOP' => 'Gói VTYT không khớp DVKT mẹ được BHYT thanh toán',
        'THUOC_DVKT_ME_KHONG_KHOP' => 'Thuốc gắn DVKT mẹ không được BHYT thanh toán',
    ];

    /** @var array ['VTYT' => [khoa => [ma_dvkt => ten_dvkt]], 'THUOC' => [...]] */
    private $bang;

    public function __construct(array $bang)
    {
        $this->bang = $bang;
    }

    public function kiem($xml3, $xml2): array
    {
        $dvkt = $this->gomDvkt($xml3);

        $vtyt = [];
        foreach ($xml3 as $r) {
            if (self::rong($r->ma_vat_tu ?? null)) {
                continue;
            }
            $vtyt[] = self::vatPham($r->ma_vat_tu, $r->ten_vat_tu ?? '', $r, $r->goi_vtyt ?? '');
        }

        $thuoc = [];
        foreach ($xml2 as $r) {
            if (self::rong($r->ma_thuoc ?? null)) {
                continue;
            }
            $thuoc[] = self::vatPham($r->ma_thuoc, $r->ten_thuoc ?? '', $r, '');
        }

        return array_merge(
            $this->r1($vtyt, 'VTYT', $dvkt),
            $this->r1($thuoc, 'THUOC', $dvkt),
            $this->r2($vtyt, 'VTYT', $dvkt),
            $this->r2($thuoc, 'THUOC', $dvkt)
        );
    }

    private static function vatPham($ma, $ten, $r, $goi)
    {
        return (object) [
            'ma' => trim((string) $ma),
            'ten' => (string) $ten,
            'ma_dich_vu' => $r->ma_dich_vu ?? null,
            'goi' => trim((string) $goi),
            't_bhtt' => (float) ($r->t_bhtt ?? 0),
            'ngay_yl' => (string) ($r->ngay_yl ?? ''),
        ];
    }

    /** DVKT cua ho so (dong XML3 khong co ma_vat_tu) gom theo ma chuan hoa. */
    private function gomDvkt($xml3): array
    {
        $dv = [];
        foreach ($xml3 as $r) {
            if (!self::rong($r->ma_vat_tu ?? null)) {
                continue;
            }
            $k = VatPhamKemDvktKey::chuanHoaMaDvkt($r->ma_dich_vu ?? null);
            if ($k === '') {
                continue;
            }
            if (!isset($dv[$k])) {
                $dv[$k] = ['bhtt' => 0.0, 'sl_bhyt' => 0.0, 'ten' => (string) ($r->ten_dich_vu ?? '')];
            }
            $bhtt = (float) ($r->t_bhtt ?? 0);
            $dv[$k]['bhtt'] += $bhtt;
            if ($bhtt > 0) {
                $dv[$k]['sl_bhyt'] += (float) ($r->so_luong ?? 0);
            }
        }

        return $dv;
    }

    private function r1(array $dong, string $loai, array $dvkt): array
    {
        $bangLoai = $this->bang[$loai] ?? [];
        if (empty($bangLoai)) {
            return [];
        }

        $nhom = [];
        foreach ($dong as $d) {
            if ($d->t_bhtt <= 0 || !self::rong($d->ma_dich_vu)) {
                continue;
            }
            $khoa = VatPhamKemDvktKey::khoa($loai, $d->ma, $d->ten);
            if (!isset($bangLoai[$khoa])) {
                continue;
            }
            if (!isset($nhom[$khoa])) {
                $nhom[$khoa] = ['ma' => $d->ma, 'ten' => $d->ten, 'bhtt' => 0.0, 'ngay' => $d->ngay_yl];
            }
            $nhom[$khoa]['bhtt'] += $d->t_bhtt;
            if ($d->ngay_yl !== '' && ($nhom[$khoa]['ngay'] === '' || strcmp($d->ngay_yl, $nhom[$khoa]['ngay']) < 0)) {
                $nhom[$khoa]['ngay'] = $d->ngay_yl;
            }
        }

        $key = $loai === 'VTYT' ? 'VTYT_KHONG_CO_DVKT_ME' : 'THUOC_KHONG_CO_DVKT_ME';
        $ra = [];
        foreach ($nhom as $khoa => $g) {
            $me = $bangLoai[$khoa];
            $coTrongHs = [];
            $duocTra = false;
            foreach ($me as $maMe => $_) {
                if (!isset($dvkt[$maMe])) {
                    continue;
                }
                if ($dvkt[$maMe]['bhtt'] > 0) {
                    $duocTra = true;
                    break;
                }
                $coTrongHs[] = $maMe;
            }
            if ($duocTra) {
                continue;
            }

            $dau = sprintf('%s (%s) BHYT trả %s, YL %s: ', $g['ten'], $g['ma'], self::tien($g['bhtt']), self::ngay($g['ngay']));
            if (!empty($coTrongHs)) {
                $maMe = $coTrongHs[0];
                $moTa = $dau . sprintf('DVKT mẹ %s %s có trong hồ sơ nhưng quỹ BHYT không thanh toán.', $maMe, $dvkt[$maMe]['ten']);
            } else {
                $moTa = $dau . 'không có DVKT mẹ trong hồ sơ. Cần một trong: ' . self::dsMe($me) . '.';
            }

            $ra[] = ['key' => $key, 'ten' => self::TEN[$key], 'mo_ta' => $moTa];
        }

        return $ra;
    }

    private function r2(array $dong, string $loai, array $dvkt): array
    {
        $nhom = [];
        foreach ($dong as $d) {
            if ($d->t_bhtt <= 0 || self::rong($d->ma_dich_vu)) {
                continue;
            }
            $k = VatPhamKemDvktKey::chuanHoaMaDvkt($d->ma_dich_vu);
            if (!isset($nhom[$k])) {
                $nhom[$k] = ['ten' => [], 'goi' => []];
            }
            $nhom[$k]['ten'][$d->ten] = true;
            if ($d->goi !== '') {
                $nhom[$k]['goi'][$d->goi] = true;
            }
        }

        $key = $loai === 'VTYT' ? 'GOI_VTYT_KHONG_KHOP' : 'THUOC_DVKT_ME_KHONG_KHOP';
        $ra = [];
        foreach ($nhom as $k => $g) {
            if (!isset($dvkt[$k])) {
                $lyDo = sprintf('DVKT mẹ %s không có trong hồ sơ.', $k);
            } elseif ($dvkt[$k]['bhtt'] <= 0) {
                $lyDo = sprintf('DVKT mẹ %s %s có trong hồ sơ nhưng quỹ BHYT không thanh toán.', $k, $dvkt[$k]['ten']);
            } elseif ($loai === 'VTYT' && count($g['goi']) > $dvkt[$k]['sl_bhyt']) {
                $lyDo = sprintf(
                    '%d gói VTYT (%s) nhưng DVKT mẹ %s chỉ được BHYT thanh toán %s lần.',
                    count($g['goi']), implode(', ', array_keys($g['goi'])), $k, self::so($dvkt[$k]['sl_bhyt'])
                );
            } else {
                continue;
            }

            $ra[] = ['key' => $key, 'ten' => self::TEN[$key], 'mo_ta' => self::dsTen(array_keys($g['ten'])) . ' gắn DVKT ' . $k . ': ' . $lyDo];
        }

        return $ra;
    }

    private static function rong($s): bool
    {
        return $s === null || trim((string) $s) === '';
    }

    private static function tien(float $x): string
    {
        return number_format(round($x), 0, ',', '.') . 'đ';
    }

    private static function so(float $x): string
    {
        return rtrim(rtrim(number_format($x, 2, ',', ''), '0'), ',');
    }

    private static function ngay(string $s): string
    {
        $d = substr($s, 0, 8);
        if (strlen($d) === 8 && ctype_digit($d)) {
            return substr($d, 6, 2) . '/' . substr($d, 4, 2) . '/' . substr($d, 0, 4);
        }

        return $s;
    }

    private static function dsMe(array $me): string
    {
        $phan = [];
        foreach (array_slice($me, 0, self::TOI_DA_MA_ME, true) as $ma => $ten) {
            $phan[] = trim($ma . ' ' . $ten);
        }
        $con = count($me) - self::TOI_DA_MA_ME;

        return implode('; ', $phan) . ($con > 0 ? ' và ' . $con . ' mã khác' : '');
    }

    private static function dsTen(array $ten): string
    {
        $con = count($ten) - self::TOI_DA_TEN;

        return implode(', ', array_slice($ten, 0, self::TOI_DA_TEN)) . ($con > 0 ? ' và ' . $con . ' vật phẩm khác' : '');
    }
}

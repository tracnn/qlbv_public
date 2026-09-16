<?php

namespace App\Services\Xml3176\Support;

/**
 * So khop MA_BENH_CHINH voi danh muc Phu luc I Thong tu 01/2025/TT-BYT (benh duoc tu den
 * KCB tai co so cap chuyen sau).
 *
 * Danh muc KHONG phai danh sach ma phang: moi STT cua Phu luc I la mot nhom dong mau ma,
 * co dong 'bao_gom' va dong 'tru'. Ma tru chi co hieu luc trong CHINH STT cua no - dong 23
 * tru C83.5 nhung dong 22 (C00-C97, nguoi duoi 18 tuoi) van bao gom C83.5. Gop thanh mot
 * danh sach tru chung se bao oan tre em ung thu.
 *
 * Hai ghi chu cua van ban quyet dinh cach khop mau:
 *   1. Ma 3 ky tu bao gom moi ma chi tiet 4 ky tu (C25 gom C25.0 ... C25.9).
 *   2. Co ma chi tiet 4 ky tu thi phai ghi ro 4 ky tu - ho so ghi C79 khong khop dong C79.3.
 *
 * Helper thuan - khong cham DB/model/config.
 */
class BenhPl1Matcher
{
    const BAO_GOM = 'bao_gom';
    const TRU = 'tru';

    /** Trim, bo ky hieu phan loai kep (dao †, sao *) va dau cach, viet hoa. */
    public static function chuanHoaMa($ma): string
    {
        return strtoupper(str_replace(['†', '*', ' '], '', trim((string) $ma)));
    }

    /** Mot mau trong danh muc co khop ma benh khong. */
    public static function mauKhop($mau, $ma): bool
    {
        $mau = self::chuanHoaMa($mau);
        $ma  = self::chuanHoaMa($ma);

        if ($mau === '' || $ma === '') {
            return false;
        }

        if (strlen($mau) === 3) {
            return $ma === $mau || strpos($ma, $mau . '.') === 0;
        }

        return $ma === $mau;
    }

    /**
     * @param string $maBenh MA_BENH_CHINH cua ho so
     * @param array $dongDanhMuc cac dong ['stt', 'ma_icd', 'loai', 'tuoi_duoi']
     * @param int|null $tuoi tuoi du nam tai NGAY_VAO; null neu khong xac dinh duoc
     * @return array ['khop' => bool, 'stt_sai_tuoi' => int[]]
     *   stt_sai_tuoi: STT khop ma, khong bi tru, nhung nguoi benh khong duoi tuoi dong yeu cau
     */
    public static function kiemTra($maBenh, array $dongDanhMuc, $tuoi): array
    {
        $theoStt = [];

        foreach ($dongDanhMuc as $d) {
            if (!self::mauKhop($d['ma_icd'], $maBenh)) {
                continue;
            }

            $stt = (int) $d['stt'];

            if ($d['loai'] === self::TRU) {
                $theoStt[$stt]['tru'] = true;
            } elseif ($d['loai'] === self::BAO_GOM) {
                $tuoiDuoi = ($d['tuoi_duoi'] === null || $d['tuoi_duoi'] === '') ? null : (int) $d['tuoi_duoi'];
                $theoStt[$stt]['bao_gom'][] = $tuoiDuoi;
            }
        }

        $sttSaiTuoi = [];

        foreach ($theoStt as $stt => $x) {
            if (empty($x['bao_gom']) || !empty($x['tru'])) {
                continue;
            }

            foreach ($x['bao_gom'] as $tuoiDuoi) {
                // Khong xac dinh duoc tuoi thi coi nhu thoa: thieu can cu thi khong bao loi.
                if ($tuoiDuoi === null || $tuoi === null || $tuoi < $tuoiDuoi) {
                    return ['khop' => true, 'stt_sai_tuoi' => []];
                }
            }

            $sttSaiTuoi[] = $stt;
        }

        sort($sttSaiTuoi);

        return ['khop' => false, 'stt_sai_tuoi' => $sttSaiTuoi];
    }
}

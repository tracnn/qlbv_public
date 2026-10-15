<?php

namespace App\Services\Xml3176;

/**
 * Phan loai mot dinh danh nhan vien y te theo vai tro (hop le / khong co trong DM / sai dinh dang).
 */
class NvytRule
{
    const TTDV = 'TTDV';
    const BS_CHI_DINH = 'BS_CHI_DINH';
    const NGUOI_THUC_HIEN = 'NGUOI_THUC_HIEN';
    const BS_DOC_KQ = 'BS_DOC_KQ';

    const TEN = [
        'NVYT_THIEU' => 'Thiếu mã nhân viên y tế',
        'NVYT_KHONG_CO_TRONG_DM' => 'Mã nhân viên y tế không có trong danh mục NVYT',
        'NVYT_SAI_DINH_DANG' => 'Mã nhân viên y tế sai định danh',
    ];

    const MA_CU = [
        'XML1_INFO_ERROR_MA_TTDV', 'XML1_INVALID_MEDICAL_STAFF_MA_TTDV',
        'XML2_MISSING_MEDICAL_STAFF_CODE', 'XML2_INVALID_MEDICAL_STAFF_CODE',
        'XML3_INFO_ERROR_GROUP_CODE_NGUOI_THUC_HIEN', 'XML3_INFO_ERROR_NGUOI_THUC_HIEN_NOT_FOUND',
        'XML3_INFO_ERROR_MA_BAC_SI', 'XML3_INFO_ERROR_MA_BAC_SI_NOT_FOUND',
        'XML4_INFO_ERROR_MA_BS_DOC_KQ', 'XML4_INFO_ERROR_MA_BS_DOC_KQ_NOT_FOUND',
        'XML5_INFO_ERROR_NGUOI_THUC_HIEN', 'XML5_INFO_ERROR_NGUOI_THUC_HIEN_NOT_FOUND',
        'XML7_INFO_ERROR_MA_TTDV', 'XML7_INFO_ERROR_MA_TTDV_NOT_FOUND',
        'XML7_INFO_ERROR_MA_BS', 'XML7_INFO_ERROR_MA_BS_NOT_FOUND',
        'XML8_INFO_ERROR_MA_TTDV', 'XML8_INFO_ERROR_MA_TTDV_NOT_FOUND',
        'XML9_INFO_ERROR_MA_TTDV', 'XML9_INVALID_MEDICAL_STAFF_MA_TTDV',
        'XML10_INFO_ERROR_MA_TTDV', 'XML10_INFO_ERROR_MA_TTDV_NOT_FOUND',
        'XML11_INFO_ERROR_MA_BS', 'XML11_INVALID_MEDICAL_STAFF_MA_BS',
        'XML11_INFO_ERROR_MA_TTDV', 'XML11_INVALID_MEDICAL_STAFF_MA_TTDV',
        'XML13_INFO_ERROR_MA_BAC_SI', 'XML13_INVALID_MEDICAL_STAFF_MA_BAC_SI',
        'XML13_INFO_ERROR_MA_TTDV', 'XML13_INVALID_MEDICAL_STAFF_MA_TTDV',
        'XML14_INFO_ERROR_MA_BAC_SI', 'XML14_INVALID_MEDICAL_STAFF_MA_BAC_SI',
        'XML14_INFO_ERROR_MA_TTDV', 'XML14_INVALID_MEDICAL_STAFF_MA_TTDV',
    ];

    const DANG_CCHN = '/^\d{4,7}\/[A-ZĐÐ]{2,4}-(CCHN|GPHN)$/iu';
    const DANG_SO_DINH_DANH = '/^\d{10,12}$/';

    private $bang;

    public function __construct(array $bang)
    {
        $this->bang = $bang;
    }

    /**
     * @return array|null null neu hop le; nguoc lai ['key' => ..., 'ly_do' => ...]
     */
    public function phanLoai(string $vaiTro, string $giaTri): ?array
    {
        if (empty($this->bang['co_du_lieu'])) {
            return null;
        }
        $giaTri = trim($giaTri);
        if ($giaTri === '') {
            return null;
        }
        $k = NvytLookup::khoa($giaTri);
        $laTtdv = $vaiTro === self::TTDV;

        // 1. Khop cot tra cua vai tro
        if (isset($this->bang['macchn'][$k])) {
            return null;
        }
        if ($laTtdv && (isset($this->bang['so_dinh_danh'][$k]) || isset($this->bang['ma_bhxh'][$k]))) {
            return null;
        }

        // 2. Vai tro chuyen mon gui so dinh danh / ma BHXH cua mot nguoi
        if (!$laTtdv) {
            foreach (['so_dinh_danh' => 'số định danh', 'ma_bhxh' => 'mã BHXH'] as $cot => $nhan) {
                if (isset($this->bang[$cot][$k])) {
                    $nguoi = $this->bang[$cot][$k];
                    $duoi = $nguoi['macchn'] !== ''
                        ? 'vai trò chuyên môn phải dùng mã CCHN ' . $nguoi['macchn']
                        : 'người này chưa có CCHN trong danh mục NVYT';

                    return $this->loi('NVYT_SAI_DINH_DANG', "là {$nhan} của {$nguoi['ho_ten']} — {$duoi}");
                }
            }
        }

        // 3. Dung dang cua vai tro nhung khong co trong danh muc
        $dangCchn = (bool) preg_match(self::DANG_CCHN, $giaTri);
        $muoiMuoiHai = (bool) preg_match(self::DANG_SO_DINH_DANH, $giaTri);
        if ($laTtdv && ($dangCchn || $muoiMuoiHai)) {
            return $this->loi('NVYT_KHONG_CO_TRONG_DM', 'không có trong danh mục NVYT (đã tra theo CCHN, số định danh, mã BHXH)');
        }
        if (!$laTtdv && $dangCchn) {
            return $this->loi('NVYT_KHONG_CO_TRONG_DM', 'không có trong danh mục NVYT');
        }

        // 4. Con lai: sai dinh dang, kem loai
        if (!$laTtdv && $muoiMuoiHai) {
            return $this->loi('NVYT_SAI_DINH_DANG', 'là mã BHXH/số định danh, không phải mã CCHN — vai trò chuyên môn phải dùng CCHN');
        }
        if (preg_match('/\pL/u', $giaTri)) {
            return $this->loi('NVYT_SAI_DINH_DANG', 'là tài khoản/tên đăng nhập, không phải mã CCHN');
        }
        if (ctype_digit($giaTri) && strlen($giaTri) < 10) {
            $ly = 'số CCHN thiếu phần đuôi';
            $ung = $this->bang['so_cchn'][ltrim($giaTri, '0')] ?? [];
            if (count($ung) === 1) {
                $ly .= ', có thể là ' . $ung[0];
            }

            return $this->loi('NVYT_SAI_DINH_DANG', $ly);
        }

        return $this->loi('NVYT_SAI_DINH_DANG', 'định dạng không hợp lệ');
    }

    const THU_TU_VAI_TRO = [self::TTDV, self::BS_CHI_DINH, self::NGUOI_THUC_HIEN, self::BS_DOC_KQ];

    const NHAN_VAI_TRO = [
        self::TTDV => 'thủ trưởng đơn vị',
        self::BS_CHI_DINH => 'BS chỉ định',
        self::NGUOI_THUC_HIEN => 'người thực hiện',
        self::BS_DOC_KQ => 'BS đọc KQ',
    ];

    const TOI_DA_NHAN = 3;

    /**
     * Gom loi dinh danh NVYT cua mot ho so.
     * Thu tu tra ve: cac loi THIEU truoc (theo thu tu vai tro), sau do loi theo gia tri
     * (theo thu tu xuat hien dau tien cua gia tri trong danh sach o).
     *
     * @param array $o list ['vai_tro','xml','gia_tri','bat_buoc','nhan']
     * @return array list ['key','ten','mo_ta']
     */
    public function kiem(array $o): array
    {
        $thieu = [];   // [vaiTro][xml] => ['k' => int, 'nhan' => []]
        $nhom = [];    // [khoa] => ['gia_tri', 'kq' => [vaiTro => ketqua], 'cho' => [vaiTro][xml] => ...]

        foreach ($o as $c) {
            $vt = $c['vai_tro'];
            $xml = (int) $c['xml'];
            $gt = trim((string) ($c['gia_tri'] ?? ''));
            $nhan = trim((string) ($c['nhan'] ?? ''));

            if ($gt === '') {
                if (!empty($c['bat_buoc'])) {
                    $this->ghiNhan($thieu[$vt][$xml], $nhan);
                }
                continue;
            }
            $kq = $this->phanLoai($vt, $gt);
            if ($kq === null) {
                continue;
            }
            $khoa = NvytLookup::khoa($gt);
            if (!isset($nhom[$khoa])) {
                $nhom[$khoa] = ['gia_tri' => $gt, 'kq' => [], 'cho' => []];
            }
            $nhom[$khoa]['kq'][$vt] = $nhom[$khoa]['kq'][$vt] ?? $kq;
            $this->ghiNhan($nhom[$khoa]['cho'][$vt][$xml], $nhan);
        }

        $loi = [];
        foreach (self::THU_TU_VAI_TRO as $vt) {
            if (empty($thieu[$vt])) {
                continue;
            }
            $loi[] = $this->moTaThieu($vt, $thieu[$vt]);
        }
        foreach ($nhom as $g) {
            $kq = null;
            $doan = [];
            foreach (self::THU_TU_VAI_TRO as $vt) {
                if (!isset($g['kq'][$vt])) {
                    continue;
                }
                $kq = $kq ?? $g['kq'][$vt];
                ksort($g['cho'][$vt]);
                foreach ($g['cho'][$vt] as $xml => $info) {
                    $doan[] = self::NHAN_VAI_TRO[$vt] . " XML{$xml} (" . $this->dongVaNhan($vt, $info) . ')';
                }
            }
            $loi[] = [
                'key' => $kq['key'],
                'ten' => self::TEN[$kq['key']],
                'mo_ta' => $g['gia_tri'] . ' ' . $kq['ly_do'] . ' — ' . implode(', ', $doan),
            ];
        }

        return $loi;
    }

    private function ghiNhan(&$ref, string $nhan): void
    {
        if ($ref === null) {
            $ref = ['k' => 0, 'nhan' => []];
        }
        $ref['k']++;
        if ($nhan !== '' && !in_array($nhan, $ref['nhan'], true)) {
            $ref['nhan'][] = $nhan;
        }
    }

    /** "{k} dong[: nhan1, nhan2, nhan3 va N muc khac]" - chi liet ke nhan o vai tro thuc hien / doc KQ */
    private function dongVaNhan(string $vt, array $info): string
    {
        $s = $info['k'] . ' dòng';
        if (!in_array($vt, [self::NGUOI_THUC_HIEN, self::BS_DOC_KQ], true) || !$info['nhan']) {
            return $s;
        }
        $hien = array_slice($info['nhan'], 0, self::TOI_DA_NHAN);
        $du = count($info['nhan']) - count($hien);

        return $s . ': ' . implode(', ', $hien) . ($du > 0 ? " và {$du} mục khác" : '');
    }

    private function moTaThieu(string $vt, array $theoXml): array
    {
        ksort($theoXml);
        if ($vt === self::TTDV) {
            $mo = 'Thiếu mã thủ trưởng đơn vị ở XML' . implode(', XML', array_keys($theoXml)) . '.';
        } else {
            $tong = array_sum(array_column($theoXml, 'k'));
            $doan = [];
            foreach ($theoXml as $xml => $info) {
                $doan[] = "XML{$xml} (" . $this->dongVaNhan($vt, $info) . ')';
            }
            $mo = 'Thiếu mã ' . self::NHAN_VAI_TRO[$vt] . " ở {$tong} dòng: " . implode(', ', $doan) . '.';
        }

        return ['key' => 'NVYT_THIEU', 'ten' => self::TEN['NVYT_THIEU'], 'mo_ta' => $mo];
    }

    private function loi(string $key, string $lyDo): array
    {
        return ['key' => $key, 'ly_do' => $lyDo];
    }
}

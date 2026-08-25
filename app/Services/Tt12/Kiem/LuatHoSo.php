<?php

namespace App\Services\Tt12\Kiem;

/**
 * Luat nhin TOAN BO ho so: STT trung, va cap dong cu/moi cua mot lan thay doi.
 *
 * Cap dong cu/moi la luat de sai nhat khi nguoi dung tu sua Excel. TT12 quy dinh: co
 * thay doi thi gui 02 dong - dong thu nhat ghi thong tin cu voi DEN_NGAY la ngay ngung,
 * dong thu hai ghi thong tin moi voi TU_NGAY la ngay bat dau va DEN_NGAY de trong.
 *
 * Ham THUAN.
 */
class LuatHoSo
{
    /**
     * @param string $lop
     * @param array  $cacDong [['stt' => int, 'du_lieu' => array], ...]
     * @return array cac Tt12Loi
     */
    public static function kiem($lop, array $cacDong)
    {
        return array_merge(
            self::kiemSttTrung($cacDong),
            self::kiemCapCuMoi($lop, $cacDong)
        );
    }

    private static function kiemSttTrung(array $cacDong)
    {
        $loi = array();
        $dem = array();

        foreach ($cacDong as $dong) {
            $stt = (string) $dong['stt'];
            $dem[$stt] = isset($dem[$stt]) ? $dem[$stt] + 1 : 1;
        }

        foreach ($dem as $stt => $soLan) {
            if ($soLan > 1) {
                $loi[] = Tt12Loi::loi(
                    'STT_TRUNG',
                    'Số thứ tự ' . $stt . ' xuất hiện ' . $soLan . ' lần; STT không được trùng',
                    (int) $stt, 'STT'
                );
            }
        }

        return $loi;
    }

    /**
     * Voi moi ma xuat hien nhieu lan trong tep: dung mot dong duoc de ngo DEN_NGAY, va
     * cac khoang hieu luc khong duoc chong lan.
     */
    private static function kiemCapCuMoi($lop, array $cacDong)
    {
        $theMa = $lop::theMa();
        $nhom = array();

        foreach ($cacDong as $dong) {
            $duLieu = $dong['du_lieu'];
            $ma = isset($duLieu[$theMa]) ? trim((string) $duLieu[$theMa]) : '';

            if ($ma === '') {
                continue;
            }

            $nhom[$ma][] = array(
                'stt' => $dong['stt'],
                'tu'  => isset($duLieu['TU_NGAY'])  ? trim((string) $duLieu['TU_NGAY'])  : '',
                'den' => isset($duLieu['DEN_NGAY']) ? trim((string) $duLieu['DEN_NGAY']) : '',
            );
        }

        $loi = array();

        foreach ($nhom as $ma => $cac) {
            if (count($cac) < 2) {
                continue;
            }

            $soMo = 0;

            foreach ($cac as $mot) {
                if ($mot['den'] === '') {
                    $soMo++;
                }
            }

            if ($soMo > 1) {
                $loi[] = Tt12Loi::loi(
                    'HAI_DONG_CUNG_MO',
                    'Mã ' . $ma . ' có ' . $soMo . ' dòng cùng để trống DEN_NGAY; '
                    . 'chỉ một dòng được đang có hiệu lực',
                    $cac[0]['stt'], $theMa
                );
            }

            // Sap theo TU_NGAY roi doi chieu tung cap ke nhau. Khoang truoc phai DONG
            // (co DEN_NGAY) va dong TRUOC khi khoang sau bat dau.
            usort($cac, function ($a, $b) {
                return strcmp($a['tu'], $b['tu']);
            });

            for ($i = 0; $i < count($cac) - 1; $i++) {
                $truoc = $cac[$i];
                $sau   = $cac[$i + 1];

                if (!preg_match('/^\d{8}$/', $sau['tu'])) {
                    continue;
                }

                if ($truoc['den'] === '') {
                    $loi[] = Tt12Loi::loi(
                        'HIEU_LUC_CHONG_LAN',
                        'Mã ' . $ma . ': dòng STT ' . $truoc['stt'] . ' không có DEN_NGAY '
                        . 'nhưng có dòng sau bắt đầu từ ' . $sau['tu'],
                        $truoc['stt'], 'DEN_NGAY'
                    );
                    continue;
                }

                if (preg_match('/^\d{8}$/', $truoc['den']) && $truoc['den'] >= $sau['tu']) {
                    $loi[] = Tt12Loi::loi(
                        'HIEU_LUC_CHONG_LAN',
                        'Mã ' . $ma . ': dòng STT ' . $truoc['stt'] . ' hiệu lực đến '
                        . $truoc['den'] . ' nhưng dòng STT ' . $sau['stt']
                        . ' đã bắt đầu từ ' . $sau['tu'],
                        $sau['stt'], 'TU_NGAY'
                    );
                }
            }
        }

        return $loi;
    }
}

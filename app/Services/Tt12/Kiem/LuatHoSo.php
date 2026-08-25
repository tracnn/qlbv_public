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

    // QUAN TRONG: dem trung tren du_lieu['STT'] (gia tri NGUOI DUNG go trong Excel, cung
    // la gia tri se ghi vao the <STT> cua XML gui cong BHYT), KHONG PHAI tren $dong['stt'].
    //
    // $dong['stt'] la so thu tu do bo nap TU GAN tuan tu (Tt12LuuHoSo::ghiLo) va co rang
    // buoc unique(ho_so_id, stt) trong CSDL - no khong bao gio trung, dem tren no la ma
    // chet. Vai tro cua $dong['stt'] chi la CON TRO chi dong cho nguoi dung, nen van dung
    // no de bao vi tri loi, khong dung de dem trung.
    private static function kiemSttTrung(array $cacDong)
    {
        $loi = array();
        // nhom[gia_tri_chuan_hoa] = array('hien_thi' => ..., 'vi_tri' => array(stt_dong,...))
        $nhom = array();

        foreach ($cacDong as $dong) {
            $sttGoc = isset($dong['du_lieu']['STT']) ? $dong['du_lieu']['STT'] : '';
            $sttGoc = trim((string) $sttGoc);

            // STT rong da duoc LuatO bat qua THIEU_BAT_BUOC, khong gop nhom o day de
            // tranh sinh them mot loi thu hai chong len loi da co.
            if ($sttGoc === '') {
                continue;
            }

            // So sanh theo gia tri so khi la chuoi so nguyen, de "5" va "05" duoc coi la
            // trung - nguoi dung hay chep tu he thong khac vao Excel keo theo so 0 dau,
            // va cong doc chung la cung mot so.
            $khoa = ctype_digit($sttGoc) ? (string) (int) $sttGoc : $sttGoc;

            if (!isset($nhom[$khoa])) {
                $nhom[$khoa] = array('hien_thi' => $sttGoc, 'vi_tri' => array());
            }

            $nhom[$khoa]['vi_tri'][] = $dong['stt'];
        }

        foreach ($nhom as $thongTin) {
            $viTri = $thongTin['vi_tri'];

            if (count($viTri) < 2) {
                continue;
            }

            $loi[] = Tt12Loi::loi(
                'STT_TRUNG',
                'STT = ' . $thongTin['hien_thi'] . ' xuất hiện ở ' . count($viTri) . ' dòng (dòng thứ '
                    . implode(', ', $viTri) . ')',
                (int) $viTri[0], 'STT'
            );
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
            //
            // TIE-BREAK THEO STT LA BAT BUOC: usort() cua PHP KHONG on dinh. Hai dong cung
            // ma cung TU_NGAY thi thu tu sau sap xep khong xac dinh, va HIEU_LUC_CHONG_LAN
            // bao luc co luc khong - CUNG MOT TEP cho hai ket qua khac nhau. Day khong phai
            // bien hiem: cap dong cu/moi cung ngay chinh la tep TT12 khuyen khich gui khi
            // co so sua nham ngay. Ma day la luat CHAN KY, nen hanh vi bap benh la khong
            // chap nhan duoc.
            usort($cac, function ($a, $b) {
                $theo = strcmp($a['tu'], $b['tu']);

                if ($theo !== 0) {
                    return $theo;
                }

                return $a['stt'] < $b['stt'] ? -1 : ($a['stt'] > $b['stt'] ? 1 : 0);
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

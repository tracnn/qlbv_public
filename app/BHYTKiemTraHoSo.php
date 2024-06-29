<?php

namespace App;

use App\Models\BHYT\XML1;
use App\Models\BHYT\XML2;
use App\Models\BHYT\XML3;
use App\Models\CheckBHYT\check_by_date;
use App\Models\CheckBHYT\check_hein_card;

class BHYTKiemTraHoSo
{
    public static function KiemTraTuDong($from_date, $to_date) {
        $hoso_xml = XML1::with('XML2', 'XML3', 'XML5', 'check_hein_card')
            ->whereBetween('updated_at', [date_create($from_date), date_create($to_date)])
            ->get();

        $ma_thuoc_ko_sodk = getParam('ma_thuoc_ko_sodk')->param_value;

        $errorsXML1 = [
            'sl_cannang' => 0, 'mota_cannang' => '',
            'sl_ldvv' => 0, 'mota_ldvv' => '',
            'sl_khamdaingay' => 0, 'mota_khamdaingay' => ''
        ];

        $errorsXML2 = [
            'sl_komathuoc' => 0, 'mota_komathuoc' => '',
            'sl_kottthauthuoc' => 0, 'mota_kottthauthuoc' => '',
            'sl_kosodkthuoc' => 0, 'mota_kosodkthuoc' => '',
            'sl_saidinhdangthauthuoc' => 0, 'mota_saidinhdangthauthuoc' => '',
            'sl_tgianyl' => 0, 'mota_tgianyl' => ''
        ];

        $errorsXML3 = [
            'sl_komadvktvtyt' => 0, 'mota_komadvktvtyt' => '',
            'sl_kottthauvtyt' => 0, 'mota_kottthauvtyt' => '',
            'sl_tgianyl' => 0, 'mota_tgianyl' => '',
            'sl_pttthon1lan_ngay' => 0, 'mota_pttthon1lan_ngay' => '',
            'sl_ngaygiuongkb' => 0, 'mota_ngaygiuongkb' => '',
            'sl_giuongsaiqd' => 0, 'mota_giuongsaiqd' => '',
            'sl_tranvtyt' => 0, 'mota_tranvtyt' => '',
            'sl_ngaykq' => 0, 'mota_ngaykq' => '',
            'sl_gihon1' => 0, 'mota_gihon1' => ''
        ];

        $errorsXML5 = [
            'sl_dbientrong' => 0, 'mota_dbientrong' => ''
        ];

        $errorsHeinCard = [
            'sl_loi_the' => 0, 'mota_loi_the' => ''
        ];

        foreach ($hoso_xml as $hoso) {
            self::checkXML1($hoso, $errorsXML1);
            self::checkXML2($hoso, $errorsXML2, $ma_thuoc_ko_sodk);
            self::checkXML3($hoso, $errorsXML3);
            self::checkXML5($hoso, $errorsXML5);
            self::checkHeinCard($hoso, $errorsHeinCard);
        }

        self::saveLogs($errorsXML1, $to_date, 'XML1');
        self::saveLogs($errorsXML2, $to_date, 'XML2');
        self::saveLogs($errorsXML3, $to_date, 'XML3');
        self::saveLogs($errorsXML5, $to_date, 'XML5');
        self::saveLogs($errorsHeinCard, $to_date, 'CARD');

        return 'Error:' . $errorsXML1['mota_khamdaingay'];
    }

    private static function checkXML1($hoso, &$errors) {
        if ($hoso->CAN_NANG > 200) {
            $errors['sl_cannang']++;
            $errors['mota_cannang'] .= $hoso->MA_LK . '; ';
        }
        
        if (in_array($hoso->MA_DKBD, ['01013', '01061']) && in_array($hoso->MA_LYDO_VVIEN, [3, 4]) ||
            (!in_array($hoso->MA_DKBD, ['01013', '01061']) && in_array($hoso->MA_LYDO_VVIEN, [4]))) {
            $errors['sl_ldvv']++;
            $errors['mota_ldvv'] .= $hoso->MA_LK . ', Mã ĐKBĐ: ' . $hoso->MA_DKBD . '; ';
        }

        if (\DateTime::createFromFormat("YmdHi", $hoso->NGAY_VAO)->diff(\DateTime::createFromFormat("YmdHi", $hoso->NGAY_RA))->days > 1 && $hoso->MA_LOAI_KCB == 1) {
            $errors['sl_khamdaingay']++;
            $errors['mota_khamdaingay'] .= $hoso->MA_LK . '; ';
        }
    }

    private static function checkXML2($hoso, &$errors, $ma_thuoc_ko_sodk) {
        foreach ($hoso->XML2 as $xml2) {
            if (empty($xml2->MA_THUOC) || empty($xml2->TEN_THUOC)) {
                $errors['sl_komathuoc']++;
                $errors['mota_komathuoc'] .= $xml2->MA_LK . ', Thuốc: ' . ($xml2->TEN_THUOC ?: $xml2->MA_THUOC) . '; ';
            }

            if (!in_array($xml2->MA_THUOC, explode(',', getParam('ma_thuoc_oxy')->param_value))) {
                if (!empty($xml2->MA_THUOC) && empty($xml2->TT_THAU) && $xml2->MA_KHOA != 'K16' && $xml2->MA_NHOM != 7) {
                    $errors['sl_kottthauthuoc']++;
                    $errors['mota_kottthauthuoc'] .= $xml2->MA_LK . ', Thuốc: ' . $xml2->TEN_THUOC . '; ';
                }

                if (empty($xml2->SO_DANG_KY) && strtoupper(substr($xml2->MA_THUOC, 0, 3)) != $ma_thuoc_ko_sodk && $xml2->MA_NHOM != 7 && strtoupper(substr($xml2->MA_THUOC, 0, 2)) != 'VM' && strtoupper(substr($xml2->MA_THUOC, 0, 2)) != 'BB') {
                    $errors['sl_kosodkthuoc']++;
                    $errors['mota_kosodkthuoc'] .= $xml2->MA_LK . ', Thuốc: ' . $xml2->TEN_THUOC . '; ';
                }

                if (!empty($xml2->MA_THUOC) && (empty($xml2->TT_THAU) && $xml2->MA_KHOA != 'K16' && $xml2->MA_NHOM != 7 || substr_count($xml2->TT_THAU, ";") < 2)) {
                    $errors['sl_saidinhdangthauthuoc']++;
                    $errors['mota_saidinhdangthauthuoc'] .= $xml2->MA_LK . ', Tên: ' . $xml2->TEN_THUOC . '; ';
                }
            }

            if ($xml2->NGAY_YL > $hoso->NGAY_RA) {
                $errors['sl_tgianyl']++;
                $errors['mota_tgianyl'] .= $xml2->MA_LK . ', Thuốc: ' . $xml2->TEN_THUOC . ', Thời gian: ' . $xml2->NGAY_YL . '; ';
            }
        }
    }

    private static function checkXML3($hoso, &$errors) {
        $sl_gi = 0;
        foreach ($hoso->XML3 as $xml3) {
            if (empty($xml3->MA_DICH_VU) && empty($xml3->MA_VAT_TU)) {
                $errors['sl_komadvktvtyt']++;
                $errors['mota_komadvktvtyt'] .= $xml3->MA_LK . ', Tên: ' . ($xml3->TEN_VAT_TU ?: $xml3->TEN_DICH_VU) . ', Ngày YL: ' . $xml3->NGAY_YL . '; ';
            }

            if (!empty($xml3->MA_VAT_TU) && (empty($xml3->TT_THAU) || count(explode('.', $xml3->TT_THAU)) < 3 || in_array('', explode('.', $xml3->TT_THAU)))) {
                $errors['sl_kottthauvtyt']++;
                $errors['mota_kottthauvtyt'] .= $xml3->MA_LK . ', Tên: ' . $xml3->TEN_VAT_TU . '; ';
            }

            if ($xml3->NGAY_YL > $hoso->NGAY_RA) {
                $errors['sl_tgianyl']++;
                $errors['mota_tgianyl'] .= $xml3->MA_LK . ', DVKT/VT: ' . ($xml3->TEN_VAT_TU ?: $xml3->TEN_DICH_VU) . ', Thời gian: ' . $xml3->NGAY_YL . '; ';
            }

            if ($xml3->MA_NHOM == 8 && $xml3->SO_LUONG > 1) {
                $errors['sl_pttthon1lan_ngay']++;
                $errors['mota_pttthon1lan_ngay'] .= $xml3->MA_LK . ', DVKT: ' . $xml3->TEN_DICH_VU . ', Ngày YL: ' . $xml3->NGAY_YL . '; ';
            }

            if ($hoso->MA_LOAI_KCB == 1 && ($xml3->MA_NHOM == 14 || $xml3->MA_NHOM == 15)) {
                $errors['sl_ngaygiuongkb']++;
                $errors['mota_ngaygiuongkb'] .= $xml3->MA_LK . '; ';
            }

            if (in_array($xml3->MA_NHOM, [14, 15, 16])) {
                $sl_gi += $xml3->SO_LUONG;
            }

            if ($xml3->T_TRANTT && $xml3->T_TRANTT < $xml3->T_BHTT) {
                $errors['sl_tranvtyt']++;
                $errors['mota_tranvtyt'] .= $xml3->MA_LK . ', Mã VTYT: ' . ($xml3->MA_DICH_VU ?: $xml3->MA_VAT_TU) . '; ';
            }

            if (!in_array($xml3->MA_NHOM, [10]) && ($xml3->NGAY_YL > $xml3->NGAY_KQ || $xml3->NGAY_KQ > $hoso->NGAY_RA)) {
                $errors['sl_ngaykq']++;
                $errors['mota_ngaykq'] .= $xml3->MA_LK . ', Tên VT/DV: ' . ($xml3->TEN_DICH_VU ?: $xml3->TEN_VAT_TU) . ', Ngày YL/Ngày KQ: ' . $xml3->NGAY_YL . '/' . $xml3->NGAY_KQ . '; ';
            }

            if (in_array($xml3->MA_NHOM, [14, 15, 16]) && $xml3->SO_LUONG > 1) {
                $errors['sl_gihon1']++;
                $errors['mota_gihon1'] .= $xml3->MA_LK . ' Ngày YL: ' . $xml3->NGAY_YL . '; ';
            }
        }

        if ($sl_gi > $hoso->SO_NGAY_DTRI && $hoso->MA_LOAI_KCB == 3 && $hoso->SO_NGAY_DTRI >= 2) {
            if (!(in_array($hoso->KET_QUA_DTRI, [3, 4, 5]) && in_array($hoso->TINH_TRANG_RV, [2, 3, 4]))) {
                $errors['sl_giuongsaiqd']++;
                $errors['mota_giuongsaiqd'] .= $hoso->MA_LK . '; ';
            }
        }
    }

    private static function checkXML5($hoso, &$errors) {
        foreach ($hoso->XML5 as $xml5) {
            if (empty($xml5->DIEN_BIEN)) {
                $errors['sl_dbientrong']++;
                $errors['mota_dbientrong'] .= $xml5->MA_LK . '; ';
            }
        }
    }

    private static function checkHeinCard($hoso, &$errors) {
        foreach ($hoso->check_hein_card as $check_hein_card) {
            if (substr($hoso->MA_THE, 5, 2) != 'KT' && $hoso->MA_DKBD != '01000') {
                if (($check_hein_card->MA_TRACUU != '000' && $check_hein_card->MA_TRACUU != '001' && $check_hein_card->MA_TRACUU != '002' && $check_hein_card->MA_TRACUU != '004') || $check_hein_card->MA_KIEMTRA != '00') {
                    $errors['sl_loi_the']++;
                    $errors['mota_loi_the'] .= $check_hein_card->MA_LK . ', Lỗi: ';

                    if ($check_hein_card->MA_TRACUU != '000' && $check_hein_card->MA_TRACUU != '001' && $check_hein_card->MA_TRACUU != '002' && $check_hein_card->MA_TRACUU != '004') {
                        $errors['mota_loi_the'] .= (isset(config('__tech.insurance_error_code')[$check_hein_card->MA_TRACUU]) ? config('__tech.insurance_error_code')[$check_hein_card->MA_TRACUU] : 'Chưa xác định');
                    } else {
                        $errors['mota_loi_the'] .= (isset(config('__tech.check_insurance_code')[$check_hein_card->MA_KIEMTRA]) ? config('__tech.check_insurance_code')[$check_hein_card->MA_KIEMTRA] : 'Chưa xác định');
                    }
                    $errors['mota_loi_the'] .= '; ';
                }
            }
        }
    }

    private static function saveLogs($errors, $to_date, $loai_loi) {
        foreach ($errors as $key => $value) {
            if (strpos($key, 'sl_') === 0 && $value > 0) {
                $ma_loi = $key;
                self::saveLog($ma_loi, $to_date, $value, $errors['mota_' . substr($key, 3)], $loai_loi);
            }
        }
    }

    public static function saveLog($MA_LOI, $NGAY_DL, $SO_LUONG, $MO_TA, $LOAI_LOI)
    {
        check_by_date::updateOrCreate(
            [
                'NGAY_DL' => $NGAY_DL,
                'MA_LOI' => $MA_LOI
            ],
            [
                'SO_LUONG' => $SO_LUONG,
                'MO_TA' => $MO_TA,
                'LOAI_LOI' => $LOAI_LOI
            ]
        );
    }
}
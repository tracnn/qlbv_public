<?php

namespace App\Services\Tt12\Kiem;

/**
 * Luat lien quan giua CAC O trong cung mot dong. Ham THUAN.
 */
class LuatDong
{
    /**
     * @param string $lop
     * @param array  $duLieu
     * @param int    $sttDong
     * @param string $maCskcbHoSo ma co so cua ho so, de doi chieu voi cot MA_CSKCB
     * @return array cac Tt12Loi
     */
    public static function kiem($lop, array $duLieu, $sttDong, $maCskcbHoSo)
    {
        $loi = array();

        $tu  = isset($duLieu['TU_NGAY'])  ? trim((string) $duLieu['TU_NGAY'])  : '';
        $den = isset($duLieu['DEN_NGAY']) ? trim((string) $duLieu['DEN_NGAY']) : '';

        // So sanh chuoi yyyymmdd la du: dinh dang nay sap xep dung theo thu tu tu dien.
        // Chi so sanh khi CA HAI deu dung 8 chu so - LuatO da bao loi dinh dang roi, bao
        // them mot loi "den truoc tu" cho cung mot o la lam nguoi dung roi.
        if (preg_match('/^\d{8}$/', $tu) && preg_match('/^\d{8}$/', $den) && $den < $tu) {
            $loi[] = Tt12Loi::loi(
                'DEN_TRUOC_TU',
                'DEN_NGAY (' . $den . ') trước TU_NGAY (' . $tu . ')',
                $sttDong, 'DEN_NGAY'
            );
        }

        $tuHd  = isset($duLieu['TU_NGAY_HD'])  ? trim((string) $duLieu['TU_NGAY_HD'])  : '';
        $denHd = isset($duLieu['DEN_NGAY_HD']) ? trim((string) $duLieu['DEN_NGAY_HD']) : '';

        // CANH BAO chu khong phai LOI: thoi han hop dong nguoc khong lam XML sai cau truc
        // va cong khong tu choi vi no - day la loi du lieu dang nghi ngo, khong dang chan
        // ky. (Cap TU_NGAY/DEN_NGAY o tren thi khac: no la khoang hieu luc cua chinh ban
        // ghi danh muc, sai la sai ban chat.)
        if (preg_match('/^\d{8}$/', $tuHd) && preg_match('/^\d{8}$/', $denHd) && $denHd < $tuHd) {
            $loi[] = Tt12Loi::canhBao(
                'DEN_HD_TRUOC_TU_HD',
                'DEN_NGAY_HD (' . $denHd . ') trước TU_NGAY_HD (' . $tuHd . ')',
                $sttDong, 'DEN_NGAY_HD'
            );
        }

        $maDong = isset($duLieu['MA_CSKCB']) ? trim((string) $duLieu['MA_CSKCB']) : '';

        if ($maDong !== '' && $maCskcbHoSo !== null && $maDong !== (string) $maCskcbHoSo) {
            // LOP CHAN THU HAI. Tt12Importer da tu choi ca tep neu co dong lech, nen luat
            // nay khong the kich hoat voi ho so nap qua man hinh. Giu lai vi no re va vi
            // du lieu co the den tu duong khac ve sau (nap lai tu ban sao, sua tay trong
            // CSDL). Mot bat bien duoc kiem o hai tang khong phai la lap - tang nap chan
            // som de bao loi som, tang kiem chan de khong bao gio gui sai.
            $loi[] = Tt12Loi::loi(
                'MA_CSKCB_LECH',
                'MA_CSKCB của dòng (' . $maDong . ') khác mã cơ sở của hồ sơ ('
                . $maCskcbHoSo . ')',
                $sttDong, 'MA_CSKCB'
            );
        }

        return $loi;
    }
}

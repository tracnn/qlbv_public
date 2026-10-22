<?php

namespace App\Services\SinhXml3176;

/**
 * Ghi tep an toan: ghi <ten>.tmp cung thu muc roi rename sang <ten>.xml.
 * Lenh quet cua tien giam dinh chi nhan .xml nen khong doc phai tep ghi do.
 * Ten co moc thoi gian giay; neu trung ten thi ghi de (rename de len tep cu,
 * tren Windows PHP dung MoveFileEx REPLACE_EXISTING; neu that bai thi unlink roi rename).
 * Loi thu muc/ghi/doi ten nem LoiGhiTep (loi moi truong, LuotQuet dung luot).
 */
class GhiTep
{
    public static function ghi($thuMuc, $treatmentCode, $loaiBan, $noiDung, \DateTimeInterface $luc)
    {
        if ($loaiBan !== CauHinhSinhXml3176::CHINH_THUC && $loaiBan !== CauHinhSinhXml3176::THONG_TUYEN) {
            throw new \InvalidArgumentException('Loai ban khong hop le: ' . $loaiBan);
        }

        if (!is_dir($thuMuc) && !@mkdir($thuMuc, 0775, true) && !is_dir($thuMuc)) {
            throw new LoiGhiTep('Khong tao duoc thu muc ' . $thuMuc);
        }

        $ten = 'QLBV_' . $luc->format('YmdHis') . '_' . $treatmentCode . ($loaiBan === CauHinhSinhXml3176::THONG_TUYEN ? '_TT' : '') . '.xml';
        $dich = rtrim($thuMuc, '/\\') . DIRECTORY_SEPARATOR . $ten;
        $tam = substr($dich, 0, -4) . '.tmp';

        if (@file_put_contents($tam, $noiDung, LOCK_EX) === false) {
            @unlink($tam);
            throw new LoiGhiTep('Khong ghi duoc tep ' . $tam);
        }

        if (!@rename($tam, $dich)) {
            @unlink($dich);
            if (!@rename($tam, $dich)) {
                @unlink($tam);
                throw new LoiGhiTep('Khong doi ten duoc sang ' . $dich);
            }
        }

        return $ten;
    }
}

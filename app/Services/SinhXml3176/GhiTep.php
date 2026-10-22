<?php

namespace App\Services\SinhXml3176;

/**
 * Ghi tep an toan: ghi <ten>.tmp vao thu muc tam NGOAI cay thu muc dich roi rename sang <dich>\<ten>.xml.
 * Tien giam dinh (XML3176Import) quet de quy allFiles() va loc theo mime doan tu NOI DUNG, nen mot
 * tep .tmp nam trong thu muc quet cung bi coi la text/xml; vi vay tep tam khong bao gio nam trong thu muc dich.
 * Thu muc tam mac dinh la thu muc anh em <cha cua dich>\.sinh_xml3176_tmp (cung o dia => rename nguyen tu),
 * hoac thu_muc_tam khai trong cau hinh (CauHinhSinhXml3176::loiCauHinh kiem nam ngoai cay quet, cung o dia).
 * Ten co moc thoi gian giay; neu trung ten thi ghi de (rename de len tep cu,
 * tren Windows PHP dung MoveFileEx REPLACE_EXISTING; neu that bai thi unlink roi rename).
 * Loi thu muc/ghi/doi ten nem LoiGhiTep (loi moi truong, LuotQuet dung luot).
 */
class GhiTep
{
    const TEN_THU_MUC_TAM = '.sinh_xml3176_tmp';

    /** <cha cua thu muc dich>\.sinh_xml3176_tmp */
    public static function thuMucTamMacDinh($thuMuc)
    {
        $dich = rtrim((string) $thuMuc, '/\\');

        return dirname($dich) . DIRECTORY_SEPARATOR . self::TEN_THU_MUC_TAM;
    }

    public static function ghi($thuMuc, $treatmentCode, $loaiBan, $noiDung, \DateTimeInterface $luc, $thuMucTam = null)
    {
        if ($loaiBan !== CauHinhSinhXml3176::CHINH_THUC && $loaiBan !== CauHinhSinhXml3176::THONG_TUYEN) {
            throw new \InvalidArgumentException('Loai ban khong hop le: ' . $loaiBan);
        }

        $thuMucTam = $thuMucTam === null || trim((string) $thuMucTam) === ''
            ? self::thuMucTamMacDinh($thuMuc)
            : (string) $thuMucTam;
        if (CauHinhSinhXml3176::namTrong($thuMucTam, $thuMuc)) {
            throw new LoiGhiTep('Thu muc tam ' . $thuMucTam . ' nam trong thu muc dich ' . $thuMuc);
        }

        if (!is_dir($thuMuc) && !@mkdir($thuMuc, 0775, true) && !is_dir($thuMuc)) {
            throw new LoiGhiTep('Khong tao duoc thu muc ' . $thuMuc);
        }
        if (!is_dir($thuMucTam) && !@mkdir($thuMucTam, 0775, true) && !is_dir($thuMucTam)) {
            throw new LoiGhiTep('Khong tao duoc thu muc tam ' . $thuMucTam);
        }

        $ten = 'QLBV_' . $luc->format('YmdHis') . '_' . $treatmentCode . ($loaiBan === CauHinhSinhXml3176::THONG_TUYEN ? '_TT' : '') . '.xml';
        $dich = rtrim($thuMuc, '/\\') . DIRECTORY_SEPARATOR . $ten;
        $tam = rtrim($thuMucTam, '/\\') . DIRECTORY_SEPARATOR . substr($ten, 0, -4) . '.tmp';

        if (@file_put_contents($tam, $noiDung, LOCK_EX) === false) {
            if (is_file($tam)) {
                @unlink($tam);
            }
            throw new LoiGhiTep('Khong ghi duoc tep ' . $tam);
        }

        if (!@rename($tam, $dich)) {
            if (is_file($dich)) {
                @unlink($dich);
            }
            if (!@rename($tam, $dich)) {
                @unlink($tam);
                throw new LoiGhiTep('Khong doi ten duoc sang ' . $dich);
            }
        }

        return $ten;
    }
}

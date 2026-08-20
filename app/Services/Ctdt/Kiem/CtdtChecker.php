<?php

namespace App\Services\Ctdt\Kiem;

/**
 * Kiem noi dung MOT chung tu. HAM THUAN: nhan mang du lieu, tra mang loi.
 *
 * Khong doc CSDL, khong biet Eloquent, khong ghi gi. Phan doc/ghi la viec cua
 * CheckCtdtJob. Nho vay kiem duoc het cac nhanh ma khong can dung mot bang nao.
 *
 * MUC DO khong go tay o tung cho sinh loi ma LAY TU config('ctdt.ma_loi'): hai nguon su
 * that cho cung mot muc do la cach chac chan de mot ngay nao do man hinh hien "canh bao"
 * cho mot loi dang duoc tinh vao so_loi.
 */
class CtdtChecker
{
    /**
     * @param string $loaiHoSo    Gia tri LOAIHOSO
     * @param array  $duLieu      TEN THE => gia tri chuoi
     * @param string $macskcbHoSo Ma co so cua ho so, de doi chieu voi the MACSKCB
     * @return array Mang ['ma_loi' =>, 'ten_truong' =>, 'mo_ta' =>, 'muc_do' =>]
     */
    public static function kiem($loaiHoSo, array $duLieu, $macskcbHoSo)
    {
        $loi = [];

        self::kiemBatBuoc($loaiHoSo, $duLieu, $loi);
        self::kiemKhuyenNghi($loaiHoSo, $duLieu, $loi);
        self::kiemKieuTruong($duLieu, $loi);
        self::kiemCapNgay($duLieu, $loi);
        self::kiemMacskcb($duLieu, $macskcbHoSo, $loi);

        return $loi;
    }

    private static function kiemBatBuoc($loaiHoSo, array $duLieu, array &$loi)
    {
        foreach (CtdtTruongBatBuoc::cua($loaiHoSo) as $the) {
            if (self::trong($duLieu, $the)) {
                $loi[] = self::loi('CTDT001', $the, 'Thiếu trường bắt buộc ' . $the);
            }
        }
    }

    private static function kiemKhuyenNghi($loaiHoSo, array $duLieu, array &$loi)
    {
        foreach (CtdtTruongBatBuoc::khuyenNghi($loaiHoSo) as $the) {
            if (self::trong($duLieu, $the)) {
                $loi[] = self::loi('CTDT008', $the, 'Thiếu ' . $the
                    . ' (chấp nhận được nếu là trẻ em không thẻ)');
            }
        }
    }

    /**
     * O TRONG KHONG BI KIEM DINH DANG. Truong khong bat buoc de trong la hop le; bat dinh
     * dang o o trong se sinh mot bien loi gia cho moi ho so, va nguoi van hanh se hoc cach
     * bo qua ca cot so loi.
     */
    private static function kiemKieuTruong(array $duLieu, array &$loi)
    {
        foreach ($duLieu as $the => $giaTri) {
            $giaTri = trim((string) $giaTri);

            if ($giaTri === '') {
                continue;
            }

            $kieu = CtdtQuyTac::kieuCua($the);

            if ($kieu === 'ngay' && !self::ngayHopLe($giaTri, CtdtQuyTac::choPhepChiNam($the))) {
                $loi[] = self::loi('CTDT002', $the,
                    $the . ' = "' . $giaTri . '" không phải ngày hợp lệ (cần '
                    . (CtdtQuyTac::choPhepChiNam($the) ? '4, ' : '')
                    . '8, 12 hoặc 14 chữ số và là ngày có thật)');
            } elseif ($kieu === 'gioi_tinh' && !in_array($giaTri, ['1', '2', '3'], true)) {
                $loi[] = self::loi('CTDT003', $the,
                    $the . ' = "' . $giaTri . '" ngoài giá trị cho phép (1 Nam, 2 Nữ, 3 chưa xác định)');
            } elseif ($kieu === 'loai_giayto' && !in_array($giaTri, ['0', '1', '2', '3', '4'], true)) {
                $loi[] = self::loi('CTDT004', $the,
                    $the . ' = "' . $giaTri . '" ngoài giá trị cho phép (0 đến 4)');
            } elseif ($kieu === 'co_khong' && !in_array($giaTri, ['0', '1'], true)) {
                $loi[] = self::loi('CTDT005', $the,
                    $the . ' = "' . $giaTri . '" ngoài giá trị 0/1');
            }
        }
    }

    /**
     * So sanh tren TAM ky tu dau (phan ngay).
     *
     * Hai the trong cung mot cap co the khac do dai: CT06 dung NGAY_VAO/NGAY_RA dang 8 ky
     * tu con CT03 dang 12. So sanh ca chuoi se cho ket qua sai khi do dai lech.
     */
    private static function kiemCapNgay(array $duLieu, array &$loi)
    {
        foreach (CtdtQuyTac::CAP_NGAY as $theDau => $theCuoi) {
            if (self::trong($duLieu, $theDau) || self::trong($duLieu, $theCuoi)) {
                continue;
            }

            $dau   = trim((string) $duLieu[$theDau]);
            $cuoi  = trim((string) $duLieu[$theCuoi]);

            if (!self::ngayHopLe($dau) || !self::ngayHopLe($cuoi)) {
                // Da co CTDT002 cho cai sai dinh dang; so sanh tiep chi sinh them nhieu.
                continue;
            }

            if (substr($cuoi, 0, 8) < substr($dau, 0, 8)) {
                $loi[] = self::loi('CTDT006', $theCuoi,
                    $theCuoi . ' (' . $cuoi . ') sớm hơn ' . $theDau . ' (' . $dau . ')');
            }
        }
    }

    private static function kiemMacskcb(array $duLieu, $macskcbHoSo, array &$loi)
    {
        if (self::trong($duLieu, 'MACSKCB')) {
            return;
        }

        $trongChungTu = trim((string) $duLieu['MACSKCB']);
        $cuaHoSo = trim((string) $macskcbHoSo);

        if ($cuaHoSo !== '' && $trongChungTu !== $cuaHoSo) {
            $loi[] = self::loi('CTDT007', 'MACSKCB',
                'MACSKCB trong chứng từ (' . $trongChungTu . ') khác mã cơ sở của hồ sơ ('
                . $cuaHoSo . ')');
        }
    }

    /**
     * Ngay hop le: 8, 12 hoac 14 CHU SO, va phan ngay phai co that tren lich.
     *
     * KHONG ghim do dai theo tung the: cung mot ten the co do dai khac nhau tuy loai -
     * NGAY_VAO cua CT03 la 12 ky tu con cua CT06 la 8.
     */
    private static function ngayHopLe($giaTri, $choPhepChiNam = false)
    {
        // Dang chi co nam: hop le NGAY tai day, khong di tiep xuong checkdate() vi khong co
        // thang/ngay de kiem. Chi mo cho nhung the trong CtdtQuyTac::CHI_NAM - xem chu thich
        // o do de biet vi sao danh sach phai hep.
        if ($choPhepChiNam && preg_match('/^\d{4}$/', $giaTri)) {
            return true;
        }

        if (!preg_match('/^\d{8}$|^\d{12}$|^\d{14}$/', $giaTri)) {
            return false;
        }

        $nam   = (int) substr($giaTri, 0, 4);
        $thang = (int) substr($giaTri, 4, 2);
        $ngay  = (int) substr($giaTri, 6, 2);

        // checkdate lo luon nam nhuan: 29/02/1995 sai, 29/02/1996 dung.
        return checkdate($thang, $ngay, $nam);
    }

    private static function trong(array $duLieu, $the)
    {
        return !array_key_exists($the, $duLieu) || trim((string) $duLieu[$the]) === '';
    }

    /** Muc do LAY TU danh muc, khong go tay - tranh hai nguon su that. */
    private static function loi($maLoi, $tenTruong, $moTa)
    {
        return [
            'ma_loi'     => $maLoi,
            'ten_truong' => $tenTruong,
            'mo_ta'      => $moTa,
            'muc_do'     => (string) config('ctdt.ma_loi.' . $maLoi . '.muc_do', 'chan'),
        ];
    }
}

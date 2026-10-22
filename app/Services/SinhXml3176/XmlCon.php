<?php

namespace App\Services\SinhXml3176;

/**
 * Mang dong -> chuoi XML con (UTF-8, khong BOM). Cung cau truc voi bo xuat cu
 * (Xml3176Service::generateContent) de tien giam dinh doc duoc.
 */
class XmlCon
{
    /** Truong van ban tu do: boc CDATA. */
    private static $cdata = [
        'CHAN_DOAN_VAO', 'CHAN_DOAN_RV', 'DIA_CHI', 'LIEU_DUNG', 'KET_LUAN', 'GHI_CHU', 'PP_DIEU_TRI',
    ];

    /** Truong CDATA rieng tung loai (cac loai khong co o day dung danh sach chung). */
    private static $cdataTheoLoai = [
        'XML4' => ['TEN_CHI_SO', 'GIA_TRI', 'DON_VI_DO', 'MO_TA', 'KET_LUAN'],
        'XML5' => ['DIEN_BIEN_LS', 'GIAI_DOAN_BENH', 'HOI_CHAN', 'PHAU_THUAT'],
        // XML6-15 theo cac truong XmlCDataSection cua lop XML<n>Data / XML<n>DetailData trong DLL
        'XML6' => ['DIA_CHI', 'NOI_LAY_MAU_XN', 'NOI_BDDT_ARV', 'LOAI_DTRI_LAO', 'PHACDO_DTRI_LAO', 'KQ_XNTL_VR'],
        'XML7' => ['PP_DIEUTRI'],
        'XML8' => ['TOMTAT_KQ', 'PP_DIEUTRI', 'KET_QUA_DTRI'],
        'XML9' => ['HO_TEN_NND', 'NOICAP_CCCD_NND', 'NOI_CU_TRU_NND', 'HO_TEN_CHA', 'HO_TEN_CON', 'NOI_SINH_CON',
            'TINH_TRANG_CON', 'GHI_CHU', 'NGUOI_DO_DE', 'NGUOI_GHI_PHIEU', 'QUYEN_SO'],
        'XML10' => ['DON_VI', 'CHAN_DOAN_RV', 'TEN_BS'],
        'XML11' => ['DON_VI', 'CHAN_DOAN_RV', 'PP_DIEUTRI', 'NGUYENNHAN_DINHCHI', 'HO_TEN_CHA', 'HO_TEN_ME'],
        'XML13' => ['HO_TEN', 'DIA_CHI', 'DAU_HIEU_LS', 'CHAN_DOAN_RV', 'QT_BENHLY', 'TOMTAT_KQ', 'PP_DIEUTRI', 'TEN_DICH_VU',
            'TEN_THUOC', 'PP_DIEU_TRI', 'HUONG_DIEU_TRI', 'PHUONGTIEN_VC', 'HOTEN_NGUOI_HT'],
        'XML14' => ['HO_TEN', 'DIA_CHI', 'CHAN_DOAN_RV'],
        'XML15' => ['HO_TEN'],
    ];

    /** The goc cua cac bang phang (mot ban ghi). */
    private static $goc = [
        'XML7' => 'CHI_TIEU_DU_LIEU_GIAY_RA_VIEN',
        'XML8' => 'CHI_TIEU_DU_LIEU_TOM_TAT_HO_SO_BENH_AN',
        'XML10' => 'CHI_TIEU_DU_LIEU_GIAY_NGHI_DUONG_THAI',
        'XML11' => 'CHI_TIEU_DU_LIEU_GIAY_CHUNG_NHAN_NGHI_VIEC_HUONG_BAO_HIEM_XA_HOI',
        'XML13' => 'CHI_TIEU_GIAYCHUYENTUYEN',
        'XML14' => 'CHI_TIEU_GIAYHEN_KHAMLAI',
    ];

    private static $cdataHienTai = null;

    public static function chuoi($loai, array $dong)
    {
        self::$cdataHienTai = isset(self::$cdataTheoLoai[$loai]) ? self::$cdataTheoLoai[$loai] : self::$cdata;
        $w = new \XMLWriter();
        $w->openMemory();
        $w->setIndent(true);
        $w->startDocument('1.0', 'utf-8');

        switch ($loai) {
            case 'XML1':
                $w->startElement('TONG_HOP');
                foreach ($dong as $d) {
                    self::ghiDong($w, $d);
                }
                $w->endElement();
                break;
            case 'XML2':
                self::ghiDanhSach($w, $dong, 'CHITIEU_CHITIET_THUOC', 'DSACH_CHI_TIET_THUOC', 'CHI_TIET_THUOC');
                break;
            case 'XML3':
                self::ghiDanhSach($w, $dong, 'CHITIEU_CHITIET_DVKT_VTYT', 'DSACH_CHI_TIET_DVKT', 'CHI_TIET_DVKT');
                break;
            case 'XML4':
                self::ghiDanhSach($w, $dong, 'CHITIEU_CHITIET_DICHVUCANLAMSANG', 'DSACH_CHI_TIET_CLS', 'CHI_TIET_CLS');
                break;
            case 'XML5':
                self::ghiDanhSach($w, $dong, 'CHITIEU_CHITIET_DIENBIENLAMSANG', 'DSACH_CHI_TIET_DIEN_BIEN_BENH', 'CHI_TIET_DIEN_BIEN_BENH');
                break;
            case 'XML6':
                self::ghiDanhSach($w, $dong, 'CHI_TIEU_HO_SO_BENH_AN_CHAM_SOC_VA_DIEU_TRI_HIV_AIDS',
                    'DSACH_HO_SO_BENH_AN_CHAM_SOC_VA_DIEU_TRI_HIV_AIDS', 'HO_SO_BENH_AN_CHAM_SOC_VA_DIEU_TRI_HIV_AIDS');
                break;
            case 'XML9':
                self::ghiDanhSach($w, $dong, 'CHI_TIEU_DU_LIEU_GIAY_CHUNG_SINH', 'DSACH_GIAYCHUNGSINH', 'DU_LIEU_GIAY_CHUNG_SINH');
                break;
            case 'XML15':
                self::ghiDanhSach($w, $dong, 'CHI_TIEU_DIEUTRI_BENHLAO', 'DSACH_CHITIET_DIEUTRI_BENHLAO', 'CHITIET_DIEUTRI_BENHLAO');
                break;
            case 'XML7':
            case 'XML8':
            case 'XML10':
            case 'XML11':
            case 'XML13':
            case 'XML14':
                // Bang phang: mot ban ghi, the goc chinh la ban ghi
                $w->startElement(self::$goc[$loai]);
                if (isset($dong[0])) {
                    self::ghiDong($w, $dong[0]);
                }
                $w->endElement();
                break;
            default:
                throw new \InvalidArgumentException('Chua ho tro loai bang ' . $loai);
        }

        $w->endDocument();

        // XMLWriter in encoding="UTF-8"; dung theo khai bao chuan cua bo xuat cu.
        return preg_replace('/^<\?xml[^>]*\?>/', '<?xml version="1.0" encoding="utf-8"?>', $w->outputMemory(), 1);
    }

    private static function ghiDanhSach(\XMLWriter $w, array $dong, $goc, $ds, $ct)
    {
        $w->startElement($goc);
        $w->startElement($ds);
        foreach ($dong as $d) {
            $w->startElement($ct);
            self::ghiDong($w, $d);
            $w->endElement();
        }
        $w->endElement();
        $w->endElement();
    }

    private static function ghiDong(\XMLWriter $w, array $d)
    {
        foreach ($d as $ten => $giaTri) {
            $giaTri = self::lamSach($giaTri);
            $w->startElement($ten);
            if ($giaTri !== '') {
                if (in_array($ten, self::$cdataHienTai, true)) {
                    self::ghiCdata($w, $giaTri);
                } else {
                    $w->text($giaTri);
                }
            }
            $w->endElement();
        }
    }

    /** Bo ky tu khong hop le trong XML 1.0 (dieu khien tru tab, LF, CR). */
    private static function lamSach($giaTri)
    {
        if ($giaTri === null) {
            return '';
        }
        $s = (string) $giaTri;
        $mau = '/[^\x{9}\x{A}\x{D}\x{20}-\x{D7FF}\x{E000}-\x{FFFD}\x{10000}-\x{10FFFF}]/u';
        $r = @preg_replace($mau, '', $s);

        // Chuoi khong phai UTF-8 hop le: bo byte hong roi thu lai.
        if ($r === null) {
            $s = (string) @iconv('UTF-8', 'UTF-8//IGNORE', $s);
            $r = preg_replace($mau, '', $s);
        }

        return (string) $r;
    }

    /** CDATA; chuoi "]]>" duoc tach sang hai doan CDATA lien nhau. */
    private static function ghiCdata(\XMLWriter $w, $s)
    {
        $parts = explode(']]>', $s);
        $n = count($parts);
        foreach ($parts as $i => $p) {
            $w->writeCdata(($i > 0 ? '>' : '') . $p . ($i < $n - 1 ? ']]' : ''));
        }
    }
}

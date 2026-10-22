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

    public static function chuoi($loai, array $dong)
    {
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
                if (in_array($ten, self::$cdata, true)) {
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

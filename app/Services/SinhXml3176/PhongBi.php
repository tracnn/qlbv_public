<?php

namespace App\Services\SinhXml3176;

/** Goi cac XML con vao phong bi GIAMDINHHS. */
class PhongBi
{
    /**
     * @param string $maCskcb
     * @param array  $xmlCon ['XML1' => '<...>', ...]; duoc sap theo so bang. Khong dua vao bang
     *                       ma lop dung tra ve [] (ho so khong co bang do).
     * @param \DateTimeInterface $luc moc lap (NGAYLAP)
     */
    public static function chuoi($maCskcb, array $xmlCon, \DateTimeInterface $luc)
    {
        uksort($xmlCon, function ($a, $b) {
            return (int) substr($a, 3) - (int) substr($b, 3);
        });

        $w = new \XMLWriter();
        $w->openMemory();
        $w->setIndent(true);
        $w->startDocument('1.0', 'utf-8');
        $w->startElement('GIAMDINHHS');

        $w->startElement('THONGTINDONVI');
        $w->writeElement('MACSKCB', (string) $maCskcb);
        $w->endElement();

        $w->startElement('THONGTINHOSO');
        $w->writeElement('NGAYLAP', $luc->format('Ymd'));
        $w->writeElement('SOLUONGHOSO', '1');
        $w->startElement('DANHSACHHOSO');
        $w->startElement('HOSO');
        foreach ($xmlCon as $loai => $noiDung) {
            $w->startElement('FILEHOSO');
            $w->writeElement('LOAIHOSO', $loai);
            // base64 cua UTF-8 khong BOM (XmlCon khong sinh BOM)
            $w->writeElement('NOIDUNGFILE', base64_encode($noiDung));
            $w->endElement();
        }
        $w->endElement();
        $w->endElement();
        $w->endElement();

        $w->writeElement('CHUKYDONVI', '');

        $w->endElement();
        $w->endDocument();

        return $w->outputMemory();
    }
}

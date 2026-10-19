<?php

namespace App\Services\Tt12\Bh01;

/**
 * Dung XML HSTH01BH chua ky tu cac dong da dong bang cua mot ban 01/BH (spec muc 6).
 * Ham thuan: khong cham CSDL. Dung XMLWriter de khong giu ca cay DOM khi ban co hang chuc nghin dong.
 */
class Bh01PhongBi
{
    /**
     * @param string   $idDsChiTiet gia tri thuoc tinh Id cua the DS_CHITIET
     * @param iterable $cacDong     cac mang/stdClass khoa ten thuong (stt + 19 the); duyet MOT lan
     * @return string XML day du, co khai bao va the rong CHUKYDONVI
     * @throws \InvalidArgumentException
     */
    public static function dung($idDsChiTiet, $cacDong)
    {
        if ((string) $idDsChiTiet === '') {
            throw new \InvalidArgumentException('Thieu Id cua DS_CHITIET.');
        }

        $the = Bh01DacTa::the();

        $w = new \XMLWriter();
        $w->openMemory();
        $w->setIndent(false);
        $w->startDocument('1.0', 'UTF-8');
        $w->startElement('HSTH01BH');
        $w->startElement('DS_CHITIET');
        $w->writeAttribute('Id', (string) $idDsChiTiet);

        $soDong = 0;
        foreach ($cacDong as $dong) {
            $dong = (array) $dong;
            $w->startElement('CHITIET_HS01BH');
            foreach ($the as $t) {
                $khoa = strtolower($t['the']);
                $gt = isset($dong[$khoa]) ? (string) $dong[$khoa] : '';
                $w->startElement($t['the']);
                if ($gt !== '') {
                    $w->text($gt);
                }
                $w->endElement();
            }
            $w->endElement();
            $soDong++;
        }

        if ($soDong === 0) {
            throw new \InvalidArgumentException('Ban 01/BH khong co dong nao de dung XML.');
        }

        $w->endElement(); // DS_CHITIET

        // The RONG, dat CUOI: XMLSignService ghi chu ky vao the DA TON TAI (xem Tt12PhongBi).
        $w->startElement('CHUKYDONVI');
        $w->endElement();

        $w->endElement(); // HSTH01BH (khong goi endDocument: no them ky tu xuong dong cuoi tep)

        return $w->outputMemory();
    }
}

<?php

namespace App\Services\Xml3176;

/**
 * Nguon cua cot "Ma Khoa" tren tung sheet loi khi xuat file loi XML3176.
 *
 * Chi bon bang XML co cot khoa that: XML1, XML2, XML3 (ma_khoa) va XML7 (ma_khoa_rv).
 * Quy tac nguoi dung da chot: lay khoa cua CHINH DONG khi bang co cot khoa, khong co
 * hoac rong thi lay khoa ho so (xml3176_xml1s.ma_khoa).
 *
 * Do tren du lieu that 15/09/2026: dong loi noi ve dong goc bang (ma_lk, stt) khop 100%
 * o XML2/XML3/XML4, va cap (ma_lk, stt) khong trung - tuc phep noi khong nhan doi dong
 * loi. Day la so do tren DU LIEU, luoc do khong bao dam; scripts/kiem-xuat-loi-xml3176.php
 * kiem lai bat bien nay.
 *
 * Lop thuan: khong cham DB, khong doc config.
 */
class Xml3176KhoaNguon
{
    /**
     * 15 sheet XML, thu tu co dinh.
     *
     * KHONG lay tu Xml3176CheckTypes::LOAI: hang so do chi co 12 loai co checker, dung no
     * se mat sheet XML6/XML12/XML15 ma nguoi dung yeu cau luon co.
     */
    const LOAI_XML = [
        'XML1', 'XML2', 'XML3', 'XML4', 'XML5', 'XML6', 'XML7', 'XML8',
        'XML9', 'XML10', 'XML11', 'XML12', 'XML13', 'XML14', 'XML15',
    ];

    /**
     * Loai XML co khoa rieng tren tung dong.
     *
     * XML1 KHONG nam day du co cot ma_khoa: XML1 chinh la bang khoa ho so, truy van sheet
     * da noi san bang nay nen khong can noi them lan nua.
     *
     * XML4 CO Y khong nam day: bang khong co cot khoa, va suy tu XML3 theo ma dich vu thi
     * nhap nhang vi mot ma dich vu co the xuat hien o nhieu khoa trong cung ho so.
     */
    const NGUON = [
        'XML2' => ['bang' => 'xml3176_xml2s', 'cot' => 'ma_khoa',    'noiStt' => true],
        'XML3' => ['bang' => 'xml3176_xml3s', 'cot' => 'ma_khoa',    'noiStt' => true],
        'XML7' => ['bang' => 'xml3176_xml7s', 'cot' => 'ma_khoa_rv', 'noiStt' => false],
    ];

    /**
     * @param string $loai 'XML1'...'XML15' hoac 'XMLComplete'
     * @return array|null ['bang', 'cot', 'noiStt'], hoac null nghia la dung khoa ho so
     */
    public static function nguon($loai)
    {
        return array_key_exists((string) $loai, self::NGUON) ? self::NGUON[$loai] : null;
    }
}

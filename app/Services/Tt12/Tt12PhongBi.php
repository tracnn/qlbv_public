<?php

namespace App\Services\Tt12;

/**
 * Dung goi XML HSDANHMUC cho MOT ho so, san sang de ky va gui.
 *
 * DUNG DOMDocument CHU KHONG NOI CHUOI: ten thuoc, ten khoa, ten nha san xuat deu den tu
 * tep Excel cua nguoi dung. Mot dau '&' trong ten nha san xuat la du pha vo ca tai lieu,
 * va ta chi biet dieu do khi cong tra 205 sau khi da ky xong.
 *
 * Ham THUAN: khong doc CSDL, khong ghi tep. Nho vay kiem duoc ky - va day la lop duy
 * nhat quyet dinh noi dung that su gui len cong.
 */
class Tt12PhongBi
{
    const NS_XSD = 'http://www.w3.org/2001/XMLSchema';
    const NS_XSI = 'http://www.w3.org/2001/XMLSchema-instance';

    /**
     * @param string $lop         ten lop dac ta mau
     * @param string $idDanhSach  gia tri thuoc tinh Id cua the DANHSACH_*
     * @param array  $cacDong     [['du_lieu' => [THE => gia tri], 'con' => [[cot => gia tri]]], ...]
     * @return string XML day du, co khai bao va the rong CHUKYDONVI
     * @throws \InvalidArgumentException
     */
    public static function dung($lop, $idDanhSach, array $cacDong)
    {
        if ($cacDong === array()) {
            // Mot phong bi rong van duoc cong nhan va tra ve MaGD, va ta se tuong da gui
            // thanh cong mot danh muc von khong co gi ben trong.
            throw new \InvalidArgumentException('Hồ sơ không có dòng nào để gửi');
        }

        if (trim((string) $idDanhSach) === '') {
            // Chu ky XMLDSig tham chieu #Id nay. Thieu Id thi chu ky khong tro vao dau.
            throw new \InvalidArgumentException('Thiếu id_danh_sach của hồ sơ');
        }

        $doc = new \DOMDocument('1.0', 'UTF-8');
        $doc->formatOutput = false;

        $goc = $doc->createElement('HSDANHMUC');
        $goc->setAttribute('xmlns:xsd', self::NS_XSD);
        $goc->setAttribute('xmlns:xsi', self::NS_XSI);
        $doc->appendChild($goc);

        $danhSach = $doc->createElement($lop::theDanhSach());
        $danhSach->setAttribute('Id', (string) $idDanhSach);
        $goc->appendChild($danhSach);

        foreach ($cacDong as $dong) {
            $danhSach->appendChild(self::dungMotDong($doc, $lop, $dong));
        }

        // The RONG, dat CUOI. XMLSignService GHI chu ky vao mot the DA TON TAI
        // (tag_store_signature_value = 'CHUKYDONVI'), khong tu tao - giong het cach
        // Xml3176Service, Qd130XmlService va CtdtPhongBi dang lam truoc khi goi ky.
        $goc->appendChild($doc->createElement('CHUKYDONVI'));

        return $doc->saveXML();
    }

    private static function dungMotDong(\DOMDocument $doc, $lop, array $dong)
    {
        $duLieu = isset($dong['du_lieu']) && is_array($dong['du_lieu']) ? $dong['du_lieu'] : array();
        $con    = isset($dong['con']) && is_array($dong['con']) ? $dong['con'] : array();

        $the = $doc->createElement($lop::theDong());

        foreach ($lop::tenThe() as $tenThe) {
            $giaTri = isset($duLieu[$tenThe]) ? (string) $duLieu[$tenThe] : '';

            // The RONG van in ra: tai lieu viet <DEN_NGAY/> chu khong bo the. Bo the la
            // doi cau truc XML gui len cong.
            $the->appendChild(self::theVanBan($doc, $tenThe, $giaTri));
        }

        if ($lop::cotCon() !== array() && $con !== array()) {
            $the->appendChild(self::dungBangCon($doc, $lop, $con));
        }

        return $the;
    }

    private static function dungBangCon(\DOMDocument $doc, $lop, array $cacCon)
    {
        $danhSachCon = $doc->createElement($lop::theDanhSachCon());

        foreach ($cacCon as $con) {
            $theCon = $doc->createElement($lop::theDongCon());

            foreach ($lop::tenTheCon() as $tenThe) {
                // Ban ghi bang con luu duoi ten cot CHU THUONG (xem Tt12LuuHoSo::ghiBangCon).
                $cot = strtolower($tenThe);
                $giaTri = isset($con[$cot]) ? (string) $con[$cot] : '';

                $theCon->appendChild(self::theVanBan($doc, $tenThe, $giaTri));
            }

            $danhSachCon->appendChild($theCon);
        }

        return $danhSachCon;
    }

    /**
     * Tao mot the co noi dung van ban.
     *
     * Dung createTextNode de PHP lo phan thoat ky tu dac biet. The rong thi KHONG them
     * text node: DOMDocument se in <X/> thay vi <X></X>, dung nhu tai lieu.
     */
    private static function theVanBan(\DOMDocument $doc, $ten, $giaTri)
    {
        $the = $doc->createElement($ten);

        if ($giaTri !== '') {
            $the->appendChild($doc->createTextNode($giaTri));
        }

        return $the;
    }
}

<?php

namespace App\Services\Ctdt;

/**
 * Dung goi XML chua DUNG MOT ho so, san sang de ky va gui.
 *
 * VI SAO PHAI DUNG LAI thay vi gui tep goc: tep XML nguoi dung tai len KHONG duoc luu lai -
 * cot duong_dan_goc chi ghi TEN tep nguoi dung thay, khong phai duong dan tren dia. Chu ky
 * CHUKYDONVI cua ben gui da bi loai bo ngay luc nap. Khong co duong "gui nguyen tep goc".
 *
 * VI SAO MOI HO SO MOT GOI: goi goc co the chua nhieu HOSO, nhung trang thai gui, MaGD va ma
 * ket qua deu theo TUNG ho so. Gui ca goi thi mot MaGD ung voi nhieu ho so, va khong the noi
 * ho so nao bi tu choi.
 *
 * Dung DOMDocument chu khong noi chuoi: macskcb, id_goi_xml va noi dung chung tu deu den tu
 * XML ben ngoai. Noi chuoi la mo duong cho mot gia tri chua '<' pha vo ca tai lieu.
 *
 * Ham THUAN: khong doc CSDL, khong ghi tep.
 */
class CtdtPhongBi
{
    /**
     * @param array $hoSo    ['dich_vu', 'macskcb', 'id_goi_xml', 'ngay_lap']
     * @param array $chungTu Cac ['loai_ho_so', 'noi_dung_goc']
     * @return string XML day du, co khai bao va the rong CHUKYDONVI
     * @throws \InvalidArgumentException
     */
    public static function dung(array $hoSo, array $chungTu)
    {
        $dichVu = isset($hoSo['dich_vu']) ? $hoSo['dich_vu'] : null;
        $cauHinh = config('ctdt.dich_vu.' . $dichVu);

        if (empty($cauHinh['the_goc'])) {
            throw new \InvalidArgumentException('Dich vu khong biet: ' . (string) $dichVu);
        }

        if (empty($chungTu)) {
            // Mot phong bi rong van duoc cong nhan va tra ve MaGD, va ta se tuong da gui
            // thanh cong mot ho so von khong co gi ben trong.
            throw new \InvalidArgumentException('Ho so khong co chung tu nao de gui');
        }

        $doc = new \DOMDocument('1.0', 'UTF-8');
        $doc->formatOutput = false;

        $goc = $doc->createElement($cauHinh['the_goc']);
        $doc->appendChild($goc);

        if ($cauHinh['the_goc'] === 'HSCHUNGTU') {
            self::dungLongNhau($doc, $goc, $hoSo, $chungTu);
        } else {
            self::dungPhang($doc, $goc, $chungTu);
        }

        // The RONG, dat CUOI. Dich vu ky GHI chu ky vao mot the da ton tai
        // (tag_store_signature_value = 'CHUKYDONVI'), khong tu tao - giong het cach
        // Xml3176Service va Qd130XmlService dang lam truoc khi goi ky.
        $goc->appendChild($doc->createElement('CHUKYDONVI'));

        return $doc->saveXML();
    }

    /** Goi HSCHUNGTU: THONGTINDONVI + THONGTINHOSO > DANHSACHHOSO > HOSO > FILEHOSO* */
    private static function dungLongNhau(\DOMDocument $doc, \DOMElement $goc, array $hoSo, array $chungTu)
    {
        $donVi = $doc->createElement('THONGTINDONVI');
        $donVi->appendChild(self::the($doc, 'MACSKCB', (string) $hoSo['macskcb']));
        $goc->appendChild($donVi);

        $thongTin = $doc->createElement('THONGTINHOSO');

        if (!empty($hoSo['id_goi_xml'])) {
            $thongTin->setAttribute('Id', (string) $hoSo['id_goi_xml']);
        }

        if (!empty($hoSo['ngay_lap'])) {
            // Thieu thi BO HAN the. Mot the NGAYLAP rong la mot ngay khong hop le gui len
            // cong, con thieu the thi cong tu quyet dinh.
            $thongTin->appendChild(self::the($doc, 'NGAYLAP', (string) $hoSo['ngay_lap']));
        }

        // LUON bang 1: goi nay chua dung mot ho so. Ghi lai so cua goi goc la khai bao sai.
        $thongTin->appendChild(self::the($doc, 'SOLUONGHOSO', '1'));

        $danhSach = $doc->createElement('DANHSACHHOSO');
        $motHoSo = $doc->createElement('HOSO');

        foreach ($chungTu as $ct) {
            $file = $doc->createElement('FILEHOSO');
            $file->appendChild(self::the($doc, 'LOAIHOSO', (string) $ct['loai_ho_so']));
            $file->appendChild(self::the($doc, 'NOIDUNGFILE', base64_encode((string) $ct['noi_dung_goc'])));
            $motHoSo->appendChild($file);
        }

        $danhSach->appendChild($motHoSo);
        $thongTin->appendChild($danhSach);
        $goc->appendChild($thongTin);
    }

    /** Goi HSDLGBT / HSDLGCS: the goc chua TRUC TIEP mot chung tu, khong base64 */
    private static function dungPhang(\DOMDocument $doc, \DOMElement $goc, array $chungTu)
    {
        foreach ($chungTu as $ct) {
            $manh = new \DOMDocument('1.0', 'UTF-8');

            // Cat bo khai bao XML neu co: CtdtLuuHoSo luu noi_dung_goc bang asXML() tren mot
            // tai lieu da parse, nen chuoi co the mang san khai bao. Ghep thang vao giua mot
            // tai lieu khac la XML hong.
            $nguon = preg_replace('/^\s*<\?xml[^>]*\?>\s*/i', '', (string) $ct['noi_dung_goc']);

            $truoc = libxml_use_internal_errors(true);
            $ok = $manh->loadXML($nguon);
            libxml_clear_errors();
            libxml_use_internal_errors($truoc);

            if (!$ok || $manh->documentElement === null) {
                // NEM chu khong bo qua: bo qua roi van gui thi cong nhan mot ho so THIEU
                // chung tu va tra MaGD - hong im lang, khong lo ra cho toi luc doi soat.
                throw new \InvalidArgumentException(
                    'Noi dung chung tu ' . (string) $ct['loai_ho_so'] . ' khong phai XML hop le'
                );
            }

            // importNode voi deep = true giu nguyen ca cay con VA cac thuoc tinh - trong do
            // co Id, thu ma chu ky XMLDSig tro toi. Mat Id la chu ky khong tham chieu duoc
            // vao dau va cong tra 205.
            $goc->appendChild($doc->importNode($manh->documentElement, true));
        }
    }

    /** Tao mot the co noi dung van ban, de createTextNode lo phan thoat ky tu dac biet */
    private static function the(\DOMDocument $doc, $ten, $giaTri)
    {
        $the = $doc->createElement($ten);
        $the->appendChild($doc->createTextNode($giaTri));

        return $the;
    }
}

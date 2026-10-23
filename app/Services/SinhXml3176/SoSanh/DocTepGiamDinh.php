<?php

namespace App\Services\SinhXml3176\SoSanh;

/**
 * Doc noi dung tep GIAMDINHHS thanh cac bang dong.
 *
 * Mot FILEHOSO hong (khong giai ma duoc base64 hoac XML con khong doc duoc) chi ghi vao `loi[loai]`;
 * cac bang con lai van doc binh thuong. Khong nem ngoai le.
 */
class DocTepGiamDinh
{
    /**
     * @param string $noiDung noi dung tep GIAMDINHHS
     *
     * @return array ['bang' => [loai => dong[]], 'loi' => [loai => thong diep], 'tho' => [loai => XML con nguyen van]]; loi['TEP'] khi chinh tep ngoai khong doc duoc
     */
    public function doc($noiDung)
    {
        $kq = ['bang' => [], 'loi' => [], 'tho' => []];
        $doc = new \DOMDocument();
        if (!is_string($noiDung) || $noiDung === '' || !@$doc->loadXML($noiDung, LIBXML_NONET)) {
            $kq['loi']['TEP'] = 'Tep GIAMDINHHS rong hoac khong doc duoc.';

            return $kq;
        }
        foreach ($doc->getElementsByTagName('FILEHOSO') as $f) {
            $loai = strtoupper(trim($this->noiDungThe($f, 'LOAIHOSO')));
            if ($loai === '') {
                continue;
            }
            $xml = base64_decode(preg_replace('/\s+/', '', $this->noiDungThe($f, 'NOIDUNGFILE')), true);
            if ($xml === false) {
                $kq['loi'][$loai] = 'Khong giai ma duoc NOIDUNGFILE (base64 hong).';
                continue;
            }
            if (substr($xml, 0, 3) === "\xEF\xBB\xBF") {
                $xml = substr($xml, 3);
            }
            if (!isset(SoSanhBang::CAC_BANG[$loai])) {
                continue;
            }
            $dong = $this->dong($loai, $xml);
            if ($dong === null) {
                $kq['loi'][$loai] = 'XML con khong doc duoc.';
                continue;
            }
            $kq['bang'][$loai] = $dong;
            $kq['tho'][$loai] = $xml;
        }

        return $kq;
    }

    private function noiDungThe(\DOMElement $cha, $ten)
    {
        foreach ($cha->childNodes as $n) {
            if ($n instanceof \DOMElement && $n->nodeName === $ten) {
                return $n->textContent;
            }
        }

        return '';
    }

    /** @return array[]|null null khi XML con khong doc duoc */
    private function dong($loai, $xml)
    {
        $doc = new \DOMDocument();
        if (!@$doc->loadXML($xml, LIBXML_NONET)) {
            return null;
        }
        $kq = [];
        $goc = SoSanhBang::CAC_BANG[$loai][0];
        if ($goc === null) {
            if ($doc->documentElement !== null) {
                $kq[] = $this->truong($doc->documentElement);
            }

            return $kq;
        }
        foreach ($doc->getElementsByTagName($goc[1]) as $e) {
            $kq[] = $this->truong($e);
        }

        return $kq;
    }

    private function truong(\DOMElement $e)
    {
        $d = [];
        foreach ($e->childNodes as $n) {
            if ($n instanceof \DOMElement) {
                $d[$n->nodeName] = $n->textContent;
            }
        }

        return $d;
    }
}

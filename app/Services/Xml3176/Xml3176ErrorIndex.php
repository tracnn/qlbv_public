<?php

namespace App\Services\Xml3176;

/**
 * Chi muc loi XML3176 trong bo nho.
 *
 * Truoc day blade chi tiet hoi CO SO DU LIEU mot lan cho MOI dong, va chay hai luot:
 * mot lan cho huy hieu tren tab, mot lan khi dung than bang. Trong khi do toan bo tap
 * loi da nam san trong $xml1->Xml3176ErrorResult (duoc nap cho tab "Loi XML"). Lop nay
 * dung chi muc mot lan roi tra cuu trong bo nho -> khong ton them truy van nao.
 *
 * Lop khong cham co so du lieu: nhan vao mot collection, tra ra gia tri.
 */
class Xml3176ErrorIndex
{
    /** @var array [xml][stt] => [mo ta, ...] */
    private $theoStt = [];

    /** @var array [xml] => so ban ghi loi */
    private $demTheoNhom = [];

    /**
     * @param iterable $errors Cac ban ghi co ->xml, ->stt, ->description
     * @return self
     */
    public static function tu($errors)
    {
        $ix = new self();

        foreach ($errors as $loi) {
            $xml = (string) $loi->xml;
            $stt = (string) $loi->stt;

            $ix->theoStt[$xml][$stt][] = (string) $loi->description;

            $ix->demTheoNhom[$xml] = isset($ix->demTheoNhom[$xml])
                ? $ix->demTheoNhom[$xml] + 1
                : 1;
        }

        return $ix;
    }

    /**
     * Khong truyen $stt: hoi o muc xml ("bang nay co loi nao khong").
     */
    public function coLoi($xml, $stt = null)
    {
        $xml = (string) $xml;

        if ($stt === null) {
            return isset($this->theoStt[$xml]);
        }

        return isset($this->theoStt[$xml][(string) $stt]);
    }

    /**
     * Chuoi mo ta noi bang '; ', tra '' khi khong co loi.
     */
    public function moTa($xml, $stt = null)
    {
        $xml = (string) $xml;

        if ($stt === null) {
            if (!isset($this->theoStt[$xml])) {
                return '';
            }

            $gop = [];
            foreach ($this->theoStt[$xml] as $moTa) {
                $gop = array_merge($gop, $moTa);
            }

            return implode('; ', $gop);
        }

        $stt = (string) $stt;

        return isset($this->theoStt[$xml][$stt])
            ? implode('; ', $this->theoStt[$xml][$stt])
            : '';
    }

    /**
     * So BAN GHI loi thuoc $xml. Dung cho huy hieu tab XML1.
     */
    public function demLoi($xml)
    {
        $xml = (string) $xml;

        return isset($this->demTheoNhom[$xml]) ? $this->demTheoNhom[$xml] : 0;
    }

    /**
     * So DONG co loi khop stt cua chinh no. Dung cho XML2, XML3, XML4, XML5.
     *
     * Nhan thang danh sach so stt (tu pluck) chu khong phai danh sach model: vo modal
     * khong con nap collection nua, chi lay dung cot stt.
     */
    public function demTheoStt($dsStt, $xml)
    {
        $dem = 0;

        foreach ($dsStt as $stt) {
            if ($this->coLoi($xml, $stt)) {
                $dem++;
            }
        }

        return $dem;
    }

    /**
     * Co loi thuoc $xml thi MOI dong duoc tinh, khong thi 0.
     *
     * Day la ngu nghia hien tai cua bay tab khong co cot stt (XML7..XML14) va cua
     * XML15 (co cot stt nhung huy hieu khong dung toi). Giu nguyen co chu dich:
     * doi cach dem la doi con so nguoi dung nhin thay.
     */
    public function demTheoXml($items, $xml)
    {
        if (!$this->coLoi($xml)) {
            return 0;
        }

        // Nhan ca so nguyen (tu withCount) lan mang/Collection.
        return is_int($items) ? $items : count($items);
    }
}

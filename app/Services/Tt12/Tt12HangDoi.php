<?php

namespace App\Services\Tt12;

/**
 * Ba ten hang doi cua module danh muc TT12, mot noi duy nhat.
 *
 * VI SAO CAN LOP NAY: ten hang doi bi go doc lap o nhieu noi - Tt12Importer,
 * BHYTTt12Controller, update.bat, install_service.bat, docs/organization.php, tai lieu va
 * test. Lech mot ky tu la worker nghe MOT hang doi con job vao hang doi KHAC: khong nem,
 * khong log, khong dau hieu gi. Ho so nam mai trong hang doi, con cot "So loi" tren man
 * danh sach vinh vien bang 0 - trong y het nhu moi ho so deu sach.
 *
 * Cac tep .bat khong doc duoc PHP nen van phai go tay; Tt12CauHinhTest khoa hai ben lai voi
 * nhau.
 *
 * VI SAO `config($khoa) ?: $macDinh` chu khong `config($khoa, $macDinh)`: tham so thu hai
 * cua config() CHI duoc dung khi khoa KHONG TON TAI. Khoa ton tai nhung gia tri la null -
 * dung canh mot nguoi sao chep khoi cau hinh roi xoa gia tri - thi config() tra ve null, va
 * ->onQueue(null) day job vao hang doi mac dinh 'default' ma khong ai nghe.
 *
 * BA HANG DOI RIENG chu khong mot: ky so hong vi ly do CUC BO (USB token bi rut, HSM khong
 * phan hoi) con gui hong vi MANG. Gop chung thi mot lan mang chap keo theo ky lai - thao tac
 * ton thoi gian nhat trong chuoi, va voi TT12 thi mot ho so co the la hang nghin dong.
 *
 * GIU nguyen khoa 'hang_doi' cho bo kiem: cac may dang chay da khai khoa do va da co worker
 * nghe ten do. Doi ten khoa la bat moi noi trien khai phai sua cung luc, doi lay mot su
 * nhat quan ten goi ma khong ai duoc loi.
 */
class Tt12HangDoi
{
    /** Bo kiem loi. Cac tep .bat phai cai worker nghe dung ten nay. */
    const KIEM = 'tt12';

    /** Ky so. */
    const KY = 'tt12-ky';

    /** Gui len cong BHXH. */
    const GUI = 'tt12-gui';

    /** @return string ten hang doi chay bo kiem loi */
    public static function kiem()
    {
        return self::doc('hang_doi', self::KIEM);
    }

    /** @return string ten hang doi ky so */
    public static function ky()
    {
        return self::doc('hang_doi_ky', self::KY);
    }

    /** @return string ten hang doi gui len cong */
    public static function gui()
    {
        return self::doc('hang_doi_gui', self::GUI);
    }

    /**
     * Lui ve mac dinh khi khoa vang MAT, khi gia tri null, VA khi gia tri la chuoi rong hay
     * chuoi toan khoang trang. Ca ba deu la "khong ai dat ten hang doi", va ca ba deu dan
     * toi cung mot ket cuc im lang neu de lot xuong ->onQueue().
     *
     * @param  string $ten     ten khoa trong organization.tt12
     * @param  string $macDinh
     * @return string
     */
    private static function doc($ten, $macDinh)
    {
        $giaTri = config('organization.tt12.' . $ten);

        // trim() truoc khi hoi rong: mot khoang trang lot vao khi sao chep cau hinh se tao
        // ra hang doi ten ' ' - ton tai that, khong worker nao nghe.
        $giaTri = is_string($giaTri) ? trim($giaTri) : $giaTri;

        return $giaTri ?: $macDinh;
    }
}

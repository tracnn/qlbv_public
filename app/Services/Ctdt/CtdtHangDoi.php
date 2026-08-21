<?php

namespace App\Services\Ctdt;

/**
 * Ba ten hang doi cua module chung tu dien tu, mot noi duy nhat.
 *
 * VI SAO CAN LOP NAY: truoc do ten hang doi duoc go doc lap o nam noi - CtdtImporter,
 * BHYTCtdtController (hai lan), install_service.bat, docs/organization.php, tai lieu va test.
 * Lech mot ky tu la worker nghe MOT hang doi con job vao hang doi KHAC: khong nem, khong log,
 * khong dau hieu gi. Ho so nam mai trong hang doi, con cot "So loi" tren man danh sach vinh
 * vien bang 0 - trong y het nhu moi ho so deu sach.
 *
 * install_service.bat khong doc duoc PHP nen van phai go tay; CtdtCauHinhTest khoa hai ben
 * lai voi nhau.
 *
 * VI SAO `config($khoa) ?: $macDinh` chu khong `config($khoa, $macDinh)`: tham so thu hai cua
 * config() CHI duoc dung khi khoa KHONG TON TAI. Khoa ton tai nhung gia tri la null - dung
 * canh mot nguoi sao chep khoi cau hinh roi xoa gia tri - thi config() tra ve null, va
 * ->onQueue(null) day job vao hang doi mac dinh 'default' ma khong ai nghe.
 *
 * Ba hang doi RIENG chu khong mot: ky so hong vi ly do CUC BO (USB token bi rut, HSM khong
 * phan hoi) con gui hong vi MANG. Gop chung thi mot lan mang chap keo theo ba lan ky lai -
 * thao tac ton thoi gian nhat trong chuoi.
 */
class CtdtHangDoi
{
    /** Bo kiem loi. install_service.bat phai cai worker nghe dung ten nay. */
    const KIEM = 'JobCtdt';

    /** Ky so. */
    const KY = 'JobSignCtdt';

    /** Gui len cong BHXH. */
    const GUI = 'JobSubmitCtdt';

    /** @return string ten hang doi chay bo kiem loi */
    public static function kiem()
    {
        return self::doc('queue_name', self::KIEM);
    }

    /** @return string ten hang doi ky so */
    public static function ky()
    {
        return self::doc('sign_queue_name', self::KY);
    }

    /** @return string ten hang doi gui len cong */
    public static function gui()
    {
        return self::doc('submit_queue_name', self::GUI);
    }

    /**
     * Lui ve mac dinh khi khoa vang MAT, khi gia tri null, VA khi gia tri la chuoi rong hay
     * chuoi toan khoang trang. Ca ba deu la "khong ai dat ten hang doi", va ca ba deu dan
     * toi cung mot ket cuc im lang neu de lot xuong ->onQueue().
     *
     * @param  string $ten     ten khoa trong organization.chung_tu_dien_tu
     * @param  string $macDinh
     * @return string
     */
    private static function doc($ten, $macDinh)
    {
        $giaTri = config('organization.chung_tu_dien_tu.' . $ten);

        // trim() truoc khi hoi rong: mot khoang trang lot vao khi sao chep cau hinh se tao ra
        // hang doi ten ' ' - ton tai that, khong worker nao nghe.
        $giaTri = is_string($giaTri) ? trim($giaTri) : $giaTri;

        return $giaTri ?: $macDinh;
    }
}

<?php

namespace App\Services\Ctdt;

/**
 * Quyet dinh co gui mot ho so chung tu dien tu len cong BHXH hay khong.
 *
 * VI SAO LOP RIENG chu khong mo rong App\Services\Xml3176\QuyetDinhGui: lop do dang duoc
 * Xml3176Service va Qd130XmlService goi voi dung hai tham so, va tests/Unit/QuyetDinhGuiTest
 * khoa du moi to hop. Them tham so bat buoc la lam vo hai duong san pham khong lien quan;
 * them tham so tuy chon la nhet khai niem so_loi/checked_at - thu XML3176 khong co - vao mot
 * lop dung chung.
 *
 * THU TU UU TIEN quan trong, va KHAC thu tu cua CtdtTrangThaiGui mot cach co chu dich:
 *
 *   lop nay tra loi "CO GUI KHONG"         -> co bat/tat hoi TRUOC
 *   CtdtTrangThaiGui tra loi "HIEN CHU GI" -> tinh trang ho so hoi truoc
 *
 * Khi chuc nang gui dang tat thi khong co lan gui nao dien ra, nen ghi submit_error la BIA:
 * nguoi doc se tuong da thu gui va that bai. Nguoc lai, nguoi doc man danh sach van can biet
 * ho so con loi ngay ca khi chuc nang gui dang tat.
 *
 * Ham THUAN de kiem duoc: khong doc config, khong doc CSDL.
 */
class CtdtQuyetDinhGui
{
    /** Du dieu kien: dung phong bi, ky, gui */
    const GUI = 'gui';

    /** Bo kiem chua chay xong - khong biet ho so co sach hay khong */
    const CHUA_KIEM = 'chua_kiem';

    /** Da kiem va con loi muc chan: ghi submit_error, khong goi mang */
    const CON_LOI = 'con_loi';

    /** Sach nhung chua ky so: ghi submit_error, khong goi mang */
    const CHUA_KY = 'chua_ky';

    /** Chuc nang gui dang tat: khong lam gi ca, ke ca ghi loi */
    const KHONG_GUI = 'khong_gui';

    /** Da kiem, sach: du dieu kien KY. Chi nenKy() tra ve gia tri nay. */
    const KY = 'ky';

    /**
     * Luat "da kiem va sach chua" - MOT noi duy nhat.
     *
     * VI SAO TACH RA: luat nay truoc do duoc chep tay o ba noi - nen() ngay duoi day,
     * CtdtTrangThaiGui::cua(), va SignCtdtJob::handle(). Ba ban chep dang dong bo, nhung
     * khong gi buoc chung dong bo. Ngay ai do noi long mot ban (vi du coi so_loi = null la
     * sach) thi hai ban kia van chan - va trieu chung la mot ho so hien "Chua ky so" vinh
     * vien tren man danh sach trong khi job ky im lang bo qua no.
     *
     * Chu y THU TU: so_loi = 0 cua mot ho so CHUA KIEM khong co nghia la sach, no co nghia
     * la chua ai nhin. Phai hoi da_kiem TRUOC, khong thi may chu chua chay worker JobCtdt se
     * coi moi ho so la sach.
     *
     * @param  mixed $daKiem checked_at - rong nghia la bo kiem chua chay xong
     * @param  mixed $soLoi  so_loi (chi dem loi muc chan)
     * @return string CHUA_KIEM / CON_LOI / KY
     */
    public static function nenKy($daKiem, $soLoi)
    {
        if (empty($daKiem)) {
            return self::CHUA_KIEM;
        }

        return (int) $soLoi > 0 ? self::CON_LOI : self::KY;
    }

    /**
     * @param mixed $guiBat  config submit_enabled
     * @param mixed $daKiem  checked_at - rong nghia la bo kiem chua chay xong
     * @param mixed $soLoi   so_loi (chi dem loi muc chan)
     * @param mixed $daKy    is_signed
     * @return string mot trong GUI / CHUA_KIEM / CON_LOI / CHUA_KY / KHONG_GUI
     */
    public static function nen($guiBat, $daKiem, $soLoi, $daKy)
    {
        // Ep ve bool o moi nhanh: cac gia tri nay den tu MySQL tinyint(1) (dang 0/1), tu
        // config (co the la chuoi), va tu cot timestamp (chuoi hoac null). So sanh nghiem
        // ngat se phan nhanh sai mot cach im lang.
        if (!(bool) $guiBat) {
            return self::KHONG_GUI;
        }

        // Luat "da kiem va sach chua" nam trong nenKy() - dung chep lai o day.
        $ky = self::nenKy($daKiem, $soLoi);

        if ($ky !== self::KY) {
            return $ky;
        }

        return (bool) $daKy ? self::GUI : self::CHUA_KY;
    }
}

<?php

namespace App\Services\Xml3176;

/**
 * Quyet dinh co gui ho so len cong BHXH hay khong, sau khi da xuat tep.
 *
 * Vi sao chan ho so chua ky: gui len cong thi cong cung tu choi. Chan tai cho vua khong ton
 * mot vong goi mang, vua cho thong bao ro hon thong bao cua cong.
 *
 * THU TU UU TIEN quan trong: kiem co gui TRUOC. Khi chuc nang gui dang tat thi khong co lan
 * gui nao dien ra, nen ghi submit_error la BIA - nguoi doc se tuong da thu gui va that bai.
 *
 * Ham THUAN de kiem duoc.
 */
class QuyetDinhGui
{
    /** Gui binh thuong */
    const GUI = 'gui';

    /** Gui dang bat nhung ho so chua ky: khong dispatch, ghi nhan loi */
    const CHUA_KY = 'chua_ky';

    /** Chuc nang gui dang tat: khong lam gi ca, ke ca ghi loi */
    const KHONG_GUI = 'khong_gui';

    /**
     * @param mixed $guiBat co gui len cong dang bat khong
     * @param mixed $daKy ho so da ky so chua
     * @return string mot trong GUI / CHUA_KY / KHONG_GUI
     */
    public static function nen($guiBat, $daKy)
    {
        // Ep ve bool: is_signed doc tu MySQL tinyint(1) ve dang 0/1, va cau hinh co the la
        // chuoi. So sanh nghiem ngat o day se phan nhanh sai mot cach im lang.
        if (!(bool) $guiBat) {
            return self::KHONG_GUI;
        }

        return (bool) $daKy ? self::GUI : self::CHUA_KY;
    }
}

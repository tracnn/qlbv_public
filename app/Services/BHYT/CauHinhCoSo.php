<?php

namespace App\Services\BHYT;

use InvalidArgumentException;

/**
 * Phan giai cau hinh cong BHXH theo tung co so KCB.
 *
 * Vi sao can: he thong phuc vu nhieu co so nhung moi loi goi cong BHXH truoc day dung MOT
 * tai khoan duy nhat chot cung. Ho so cua co so nao phai duoc tra bang tai khoan cua co so
 * do moi hop le.
 *
 * KHONG bao gio roi ve tai khoan mac dinh khi co so chua khai: tra bang tai khoan cua co so
 * khac chinh la thu lam ket qua khong hop le. Nem ngoai le de loi lo ra ngay, thay vi tra
 * cuu thanh cong nhung sai danh nghia.
 *
 * Ham THUAN de kiem duoc.
 */
class CauHinhCoSo
{
    /**
     * Cau hinh cua mot co so.
     *
     * @param string|null $maCskcb
     * @param array $dsCoSo config('organization.BHYT_CO_SO')
     * @return array bon khoa: username, password, ho_ten_cb, cccd_cb
     * @throws InvalidArgumentException khi ma rong, co so chua khai, hoac thieu tai khoan
     */
    public static function cua($maCskcb, array $dsCoSo)
    {
        $ma = trim((string) $maCskcb);

        if ($ma === '') {
            throw new InvalidArgumentException('Thieu ma co so KCB khi tra cau hinh cong BHXH');
        }

        if (!isset($dsCoSo[$ma]) || !is_array($dsCoSo[$ma])) {
            throw new InvalidArgumentException(
                'Chua khai tai khoan cong BHXH cho co so ' . $ma . ' trong organization.BHYT_CO_SO'
            );
        }

        $c = $dsCoSo[$ma];

        foreach (['username', 'password'] as $bat_buoc) {
            if (trim((string) (isset($c[$bat_buoc]) ? $c[$bat_buoc] : '')) === '') {
                throw new InvalidArgumentException(
                    'Co so ' . $ma . ' thieu ' . $bat_buoc . ' cong BHXH'
                );
            }
        }

        // ho_ten_cb / cccd_cb chi la thong tin can bo tra cuu, khong phai dieu kien dang
        // nhap - thieu thi tra chuoi rong chu khong chan. Ai can chung (khi check_by_user
        // tat) thi tu kiem lay va bao loi cho ro.
        return [
            'username' => (string) $c['username'],
            'password' => (string) $c['password'],
            'ho_ten_cb' => self::doc($c, ['ho_ten_cb', 'hoTenCb']),
            'cccd_cb' => self::doc($c, ['cccd_cb', 'cccdCb']),
        ];
    }

    /**
     * Doc gia tri dau tien co that trong nhieu cach dat ten khoa.
     *
     * Chap nhan CA HAI cach dat ten: khoi BHYT cu trong cung tep config dung hoTenCb/cccdCb,
     * nen nguoi khai rat de viet theo kieu do. Neu chi nhan ho_ten_cb thi khoa sai se tra
     * chuoi rong trong IM LANG, va loi chi lo ra o thong bao kho hieu cua cong BHXH
     * ("Null hoTenCb") - da xay ra that.
     *
     * @param array $c khoi cau hinh cua mot co so
     * @param array $ten cac cach dat ten, uu tien tu trai sang
     */
    private static function doc(array $c, array $ten)
    {
        foreach ($ten as $t) {
            if (isset($c[$t]) && trim((string) $c[$t]) !== '') {
                return (string) $c[$t];
            }
        }

        return '';
    }

    /**
     * Ma tinh = hai ky tu dau cua ma co so.
     *
     * Suy ra thay vi khai rieng: cau hinh cu chot cung '01' trong khi co so 37470 o Ninh
     * Binh phai la '37'. Bot mot truong la bot mot cho co the khai sai.
     *
     * @throws InvalidArgumentException khi ma ngan hon 2 ky tu
     */
    public static function maTinh($maCskcb)
    {
        $ma = trim((string) $maCskcb);

        if (mb_strlen($ma) < 2) {
            throw new InvalidArgumentException('Ma co so khong hop le de suy ma tinh: ' . $ma);
        }

        return mb_substr($ma, 0, 2);
    }
}

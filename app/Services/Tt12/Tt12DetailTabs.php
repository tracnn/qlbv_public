<?php

namespace App\Services\Tt12;

/**
 * Dung dau bang DONG theo dac ta mau.
 *
 * Sau mau co 11-37 cot khac nhau nen khong the go cung dau bang trong blade. Hoi lop
 * dac ta - cung co che ma Xml3176DetailTabs va CtdtDetailTabs dang dung.
 */
class Tt12DetailTabs
{
    /** @return array ['dong' => nhan, 'loi' => nhan, 'xml' => nhan, 'lich_su' => nhan] */
    public static function cacTab()
    {
        return array(
            'dong'    => 'Dòng dữ liệu',
            'loi'     => 'Lỗi',
            'xml'     => 'XML đã ký',
            'lich_su' => 'Nhật ký gửi',
        );
    }

    public static function coTab($tab)
    {
        return array_key_exists($tab, self::cacTab());
    }

    /**
     * @param string $mau
     * @return array [['the' => TEN_THE, 'nhan' => nhan hien thi], ...]
     */
    public static function cotBang($mau)
    {
        $lop = Tt12MauRegistry::cho($mau);

        $cot = array();

        foreach ($lop::cot() as $mot) {
            $cot[] = array(
                'the'  => $mot['the'],
                // Nhan hien thi = ten the. Dich sang tieng Viet co dau se lam nguoi dung
                // khong doi chieu duoc voi tep Excel ho vua tai len - cot trong tep mang
                // dung ten the nay.
                'nhan' => $mot['the'],
            );
        }

        return $cot;
    }
}

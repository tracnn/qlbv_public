<?php

namespace App\Services\Xml3176\Support;

/**
 * Nhận diện thẻ tạm cấp cho trẻ sơ sinh: nơi ĐKBĐ dạng XX000 (mã tỉnh + 000) VÀ mã thẻ bắt
 * đầu bằng tiền tố trẻ em (mặc định TE1). Thẻ này chưa có trên cổng BHXH và nơi ĐKBĐ không
 * có trong danh mục CSKCB, nên tra cổng / đối chiếu danh mục đều ra lỗi giả.
 *
 * Trên HIS từ 07/2026: 1.817 lượt ĐKBĐ XX000, 1.815 thẻ TE1 (1.816 trẻ dưới 1 tuổi). Hai
 * thẻ TR1, HT3 có ĐKBĐ XX000 không được coi là thẻ tạm - vẫn tra cổng.
 */
class TheTamSoSinh
{
    /**
     * @param string|null $maThe  một mã thẻ (không phải chuỗi nhiều thẻ nối ';')
     * @param string|null $maDkbd nơi ĐKBĐ đi cùng thẻ đó
     */
    public static function la($maThe, $maDkbd): bool
    {
        $maThe = trim((string) $maThe);
        $maDkbd = trim((string) $maDkbd);
        if ($maThe === '' || $maDkbd === '') {
            return false;
        }

        $cauHinh = config('xml3176.the_tam_so_sinh', []);
        $mau = isset($cauHinh['dkbd_pattern']) ? $cauHinh['dkbd_pattern'] : '/^\d{2}000$/';
        if (!preg_match($mau, $maDkbd)) {
            return false;
        }

        foreach ((array) (isset($cauHinh['tien_to_the']) ? $cauHinh['tien_to_the'] : ['TE1']) as $tienTo) {
            if ($tienTo !== '' && strpos($maThe, $tienTo) === 0) {
                return true;
            }
        }

        return false;
    }
}

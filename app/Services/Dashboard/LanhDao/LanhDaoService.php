<?php

namespace App\Services\Dashboard\LanhDao;

/**
 * Điều phối các phần của Dashboard lãnh đạo. Không cache thêm: từng lớp tự cache
 * (bọc thêm sẽ làm đứng ảnh chụp "giường hiện tại" của tab điều trị).
 */
class LanhDaoService
{
    const PHAN = ['the-so', 'thanh-toan', 'dieu-tri-giuong', 'kham-thuoc', 'he-ngoai'];

    const LOP = [
        'the-so' => TheSo::class,
        'thanh-toan' => TabThanhToan::class,
        'dieu-tri-giuong' => TabDieuTriGiuong::class,
        'kham-thuoc' => TabKhamThuoc::class,
        'he-ngoai' => TabHeNgoai::class,
    ];

    public function lay(string $phan, KyLoc $ky): array
    {
        if (!isset(self::LOP[$phan])) {
            throw new \InvalidArgumentException("Phần không hợp lệ: {$phan}");
        }

        $kq = app(self::LOP[$phan])->lay($ky);
        // Số giao dịch bị bỏ chỉ ghi log, không hiển thị (spec)
        unset($kq['bo_qua']);

        return $kq;
    }
}

<?php

namespace App\Services\OrderCheck\RuleHandlers\ServiceReq;

use App\Services\OrderCheck\RuleHandlers\ServiceReq\Types;

/**
 * Điều phối luật cấp phiếu:
 * - common(): áp cho MỌI loại (xem CommonRules).
 * - forType($id): CHỈ áp cho loại phiếu id (xem Types/<Loại>Rules.php).
 */
class ServiceReqRuleRegistry
{
    /** @var array<int,\App\Services\OrderCheck\Contracts\TypeRules>|null */
    protected static $typeMap;

    /** @return \App\Services\OrderCheck\Contracts\RuleHandler[] */
    public static function common()
    {
        return CommonRules::handlers();
    }

    /** @return \App\Services\OrderCheck\Contracts\RuleHandler[] */
    public static function forType($typeId)
    {
        if ($typeId === null) {
            return [];
        }
        if (self::$typeMap === null) {
            self::$typeMap = [];
            foreach (self::typeRules() as $tr) {
                self::$typeMap[$tr->typeId()] = $tr;
            }
        }
        $id = (int) $typeId;
        return isset(self::$typeMap[$id]) ? self::$typeMap[$id]->handlers() : [];
    }

    /**
     * Danh sách 18 nhóm luật theo loại. Thêm loại mới = thêm 1 dòng ở đây.
     * @return \App\Services\OrderCheck\Contracts\TypeRules[]
     */
    protected static function typeRules()
    {
        return [
            new Types\KhamRules(),
            new Types\XetNghiemRules(),
            new Types\ChanDoanHinhAnhRules(),
            new Types\ThuThuatRules(),
            new Types\ThamDoChucNangRules(),
            new Types\DonPhongKhamRules(),
            new Types\GiuongRules(),
            new Types\NoiSoiRules(),
            new Types\SieuAmRules(),
            new Types\PhauThuatRules(),
            new Types\KhacRules(),
            new Types\PhucHoiChucNangRules(),
            new Types\GiaiPhauBenhRules(),
            new Types\DonTuTrucRules(),
            new Types\DonDieuTriRules(),
            new Types\DonMauRules(),
            new Types\SuatAnRules(),
            new Types\NgoaiKcbRules(),
        ];
    }
}

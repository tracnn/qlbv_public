<?php

namespace App\Services\SinhXml3176;

/**
 * Loi moi truong khi ghi tep (khong tao duoc thu muc, khong ghi duoc, khong doi ten duoc: day o dia, mat quyen).
 * LuotQuet KHONG ghi trang thai 'loi' cho ho so khi gap loi nay ma dung ca luot va khong tien moc:
 * canSinh khong thu lai dong cung moc, nen ghi 'loi' se lam mat lang lang ca cua so ho so.
 */
class LoiGhiTep extends \RuntimeException
{
}

<?php

namespace App\Services\SinhXml3176;

/**
 * Bang nhan tieng Viet dung chung cho cac man theo doi sinh XML3176 (danh sach, lich su, xem, so sanh):
 * mot nguon duy nhat de khong man nao hien ma tho (da_ghi, thong_tuyen...).
 */
class NhanHienThi
{
    const TRANG_THAI = ['da_ghi' => 'Đã ghi', 'bi_loai' => 'Bị loại', 'loi' => 'Lỗi'];
    const LOAI_BAN = ['chinh_thuc' => 'Chính thức', 'thong_tuyen' => 'Thông tuyến'];
    const THU_MUC = ['that' => 'Thật', 'thu' => 'Thử', 'khac' => 'Khác'];

    public static function trangThai($ma)
    {
        return isset(self::TRANG_THAI[$ma]) ? self::TRANG_THAI[$ma] : (string) $ma;
    }

    public static function loaiBan($ma)
    {
        return isset(self::LOAI_BAN[$ma]) ? self::LOAI_BAN[$ma] : (string) $ma;
    }

    public static function thuMuc($ma)
    {
        return isset(self::THU_MUC[$ma]) ? self::THU_MUC[$ma] : (string) $ma;
    }
}

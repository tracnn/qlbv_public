<?php

namespace App\Services\Mcct;

/**
 * Nguong mien cung chi tra: 6 thang luong co so tai thoi diem tra cuu.
 *
 * Ham THUAN - nhan bang luong lam THAM SO chu khong tu doc config, giong cach CoSoTraCuu
 * nhan $dsCoSo. Nho vay kiem duoc moi moc luong ma khong phai sua cau hinh that.
 */
class NguongMienCungChiTra
{
    /**
     * Muc luong co so co hieu luc tai mot ngay.
     *
     * Tra 0 khi ngay nam TRUOC moi moc. KHONG roi ve moc dau tien: roi ve nghia la bia ra
     * mot nguong cho khoang thoi gian chua khai, va cai sai do khong co dau hieu gi.
     *
     * @param string $ngay dang Y-m-d
     * @param array $bangLuong ['Y-m-d' => muc luong], config('mcct.luong_co_so')
     * @return int
     */
    public static function luongCoSoTaiNgay($ngay, array $bangLuong)
    {
        $ngay = trim((string) $ngay);
        $muc = 0;

        // Duyet theo thu tu moc tang dan, giu lai moc cuoi cung con <= ngay can tra.
        // Sap lai tai day chu khong tin thu tu nguoi khai go trong config.
        ksort($bangLuong);

        foreach ($bangLuong as $moc => $gia) {
            if (strcmp((string) $moc, $ngay) <= 0) {
                $muc = (int) $gia;
            }
        }

        return $muc;
    }

    /**
     * @param string $ngay dang Y-m-d
     * @param array $bangLuong ['Y-m-d' => muc luong]
     * @param int $soThang config('mcct.so_thang_luong_co_so')
     * @return float
     */
    public static function nguong($ngay, array $bangLuong, $soThang)
    {
        return (float) (self::luongCoSoTaiNgay($ngay, $bangLuong) * (int) $soThang);
    }

    /**
     * NĐ 188/2025/NĐ-CP dung cau chu "LON HON 6 thang luong co so" - dau > chu khong phai
     * >=. Bang dung nguong la CHUA du dieu kien.
     *
     * Nguong 0 (bang luong chua khai) luon tra false: khong co nguong thi khong ket luan
     * duoc, va ket luan "ai cung du" la sai theo huong te nhat.
     *
     * @param float $luyKe
     * @param float $nguong
     * @return bool
     */
    public static function duDieuKien($luyKe, $nguong)
    {
        if ((float) $nguong <= 0) {
            return false;
        }

        return (float) $luyKe > (float) $nguong;
    }

    /**
     * Tinh muc mien cung chi tra THEO DUNG diem c khoan 2 Dieu 18 ND 188/2025/ND-CP.
     *
     * VI SAO KHONG LAY THANG 6 x LUONG HIEN HANH: khi luong co so doi giua nam, quy dinh
     * KHONG cho lay nguong moi ap cho ca nam. Phan da dong o giai doan luong CU duoc quy doi
     * ra SO THANG theo luong cu, phan con thieu moi tinh theo luong moi:
     *
     *     R = ( 6 - A / L_cu ) x L_moi
     *
     * voi A la tong da cung chi tra tu 01/01 den TRUOC ngay doi luong. Lay 6 x luong hien
     * hanh se doi nguoi benh dong nhieu hon quy dinh - sai theo huong bat loi cho ho.
     *
     * Ngoai le cua chinh quy dinh: A da DU HOAC VUOT 6 thang luong cu thi huong quyen loi
     * ngay, khong ap dung cong thuc. Vong lap duoi day tu xu ly duoc: khi do so thang con
     * lai <= 0.
     *
     * Cach viet nay tong quat cho 0, 1 hay nhieu lan doi luong trong nam. Nam khong doi luong
     * thi vong lap khong chay va ket qua trung khop hanh vi cu - khong can nhanh rieng.
     *
     * @param array $dong cac dong DataCCT da chuan hoa (ngay_ra, ngay_nhan, t_bn_cct_luy_ke)
     * @param string $ngayTra dang Y-m-d
     * @param array $bangLuong ['Y-m-d' => muc luong]
     * @param int $soThang so thang luong co so lam moc, thuong la 6
     * @return array
     */
    public static function tinhTheoQuyDinh(array $dong, $ngayTra, array $bangLuong, $soThang)
    {
        ksort($bangLuong);

        $ngayTra = trim((string) $ngayTra);
        $nam = substr($ngayTra, 0, 4);
        $luongHienTai = self::luongCoSoTaiNgay($ngayTra, $bangLuong);

        $soThangConLai = (float) (int) $soThang;
        $daDongTruoc = 0.0;
        $mocCuoi = null;
        $luongTruocMoc = 0;

        foreach ($bangLuong as $moc => $muc) {
            $moc = (string) $moc;

            // Chi xet cac moc doi luong NAM TRONG nam tra cuu va khong sau ngay tra.
            if (substr($moc, 0, 4) !== $nam || strcmp($moc, $ngayTra) > 0) {
                continue;
            }

            // Luong ngay TRUOC moc: lui mot ngay de tra bang chinh ham co san.
            $luongCu = self::luongCoSoTaiNgay(date('Y-m-d', strtotime($moc . ' -1 day')), $bangLuong);

            // Chua khai luong cua giai doan truoc thi khong quy doi ra so thang duoc. Bo qua
            // moc nay con hon chia cho 0 hoac bia ra mot con so.
            if ($luongCu <= 0) {
                continue;
            }

            $aMoc = self::luyKeTruocNgay($dong, $moc);

            $soThangConLai -= ($aMoc - $daDongTruoc) / $luongCu;
            $daDongTruoc = $aMoc;
            $mocCuoi = $moc;
            $luongTruocMoc = $luongCu;
        }

        $luyKeTong = self::luyKeLonNhat($dong);
        $daDongDoanHienTai = $luyKeTong - $daDongTruoc;

        // So thang con lai <= 0 chinh la ngoai le cua quy dinh ("da du HOAC vuot qua"), nen
        // ve nay mang dau >=. Ve con lai mang dau > theo cau chu "lon hon" cua truong hop
        // thuong. Hai dau khac nhau o hai ve khac nhau - dung van ban, khong phai mau thuan.
        if ($soThangConLai <= 0) {
            return [
                'luong_hien_tai' => $luongHienTai,
                'co_doi_luong' => $mocCuoi !== null,
                'moc_doi_luong' => $mocCuoi,
                'luong_truoc_moc' => $luongTruocMoc,
                'da_dong_truoc_moc' => $daDongTruoc,
                'so_thang_con_lai' => $soThangConLai,
                'so_tien_con_phai_dong' => 0.0,
                // Nguong DA vuot qua la 6 thang luong CU. Khong lay A + R o day: R am nen
                // tong se be hon chinh so tien da dong - mot con so vo nghia tren man hinh.
                'tong_nguong_ca_nam' => (float) ((int) $soThang * $luongTruocMoc),
                'da_dong_doan_hien_tai' => $daDongDoanHienTai,
                'luy_ke_tong' => $luyKeTong,
                'con_thieu' => 0.0,
                'du_dieu_kien' => true,
            ];
        }

        $conPhaiDong = $soThangConLai * $luongHienTai;

        // Chua khai luong co so thi khong co nguong de so - khong ket luan duoc. Ket luan
        // "du dieu kien" khi khong biet nguong la sai theo huong te nhat co the.
        $duDieuKien = $luongHienTai > 0 && $daDongDoanHienTai > $conPhaiDong;

        return [
            'luong_hien_tai' => $luongHienTai,
            'co_doi_luong' => $mocCuoi !== null,
            'moc_doi_luong' => $mocCuoi,
            'luong_truoc_moc' => $luongTruocMoc,
            'da_dong_truoc_moc' => $daDongTruoc,
            'so_thang_con_lai' => $soThangConLai,
            'so_tien_con_phai_dong' => $conPhaiDong,
            // "Sau do cong them phan da co so tien cung chi tra" - buoc cuoi trong vi du cua
            // Thong bao Benh vien Bach Mai ngay 02/7/2026. Day moi la con so SO SANH DUOC voi
            // luy ke: ca hai cung tinh tu 01/01, con $conPhaiDong thi tinh tu moc doi luong.
            'tong_nguong_ca_nam' => $daDongTruoc + $conPhaiDong,
            'da_dong_doan_hien_tai' => $daDongDoanHienTai,
            'luy_ke_tong' => $luyKeTong,
            'con_thieu' => $duDieuKien ? 0.0 : max(0.0, $conPhaiDong - $daDongDoanHienTai),
            'du_dieu_kien' => $duDieuKien,
        ];
    }

    /**
     * Tong da cung chi tra tu 01/01 den TRUOC mot ngay.
     *
     * tBNCCTLuyKe la so CONG DON den het dot do, nen "tong den truoc ngay X" chinh la luy ke
     * LON NHAT trong cac dot ket thuc truoc X - khong phai tong cac dot.
     *
     * Moc ngay cua mot dot lay theo NGAY RA VIEN: dot duoc quyet toan khi ra vien, va chinh
     * cong cung sap DataCCT giam dan theo ngay ra vien. Thieu ngay ra thi lui ve ngay nhan
     * ho so; thieu ca hai thi khong xep duoc vao giai doan nao nen bo qua.
     *
     * @param array $dong
     * @param string $ngay dang Y-m-d
     * @return float
     */
    private static function luyKeTruocNgay(array $dong, $ngay)
    {
        $max = 0.0;

        foreach ($dong as $d) {
            $moc = self::mocCuaDong($d);

            if ($moc === null || strcmp($moc, $ngay) >= 0) {
                continue;
            }

            $v = isset($d['t_bn_cct_luy_ke']) ? (float) $d['t_bn_cct_luy_ke'] : 0.0;

            if ($v > $max) {
                $max = $v;
            }
        }

        return $max;
    }

    /** @return float luy ke lon nhat tren toan bo cac dot */
    private static function luyKeLonNhat(array $dong)
    {
        $max = 0.0;

        foreach ($dong as $d) {
            $v = isset($d['t_bn_cct_luy_ke']) ? (float) $d['t_bn_cct_luy_ke'] : 0.0;

            if ($v > $max) {
                $max = $v;
            }
        }

        return $max;
    }

    /** @return string|null ngay xep giai doan cua mot dot, dang Y-m-d */
    private static function mocCuaDong(array $d)
    {
        foreach (['ngay_ra', 'ngay_nhan'] as $khoa) {
            if (isset($d[$khoa]) && trim((string) $d[$khoa]) !== '') {
                return (string) $d[$khoa];
            }
        }

        return null;
    }
}

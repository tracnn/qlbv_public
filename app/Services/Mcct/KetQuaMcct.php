<?php

namespace App\Services\Mcct;

/**
 * Ket qua mot lan tra cuu MCCT, da phan tich tu JSON cong tra ve.
 *
 * VI SAO CO LOP NAY thay vi dung thang mang JSON: cong tra ngay dang dd/MM/yyyy, tien co
 * the la chuoi, va DataCCT/ThongTinSoThe co the la NULL. De mang tho di xuyen qua controller
 * xuong CSDL nghia la moi noi dung deu phai tu doan lai nhung dieu do - va chi mot noi quen
 * la du lieu hong lang le.
 *
 * Lop nay KHONG biet gi ve CSDL va KHONG cham mang.
 */
class KetQuaMcct
{
    /** @var string Ma ket qua cong tra ve: 200/204/400/500; chuoi rong neu khong doc duoc */
    public $maKetQua = '';

    /** @var string GhiChu NGUYEN VAN - chua moc "du lieu tinh den ...", khong duoc rut gon */
    public $ghiChu = '';

    /** @var array bon khoa ho_ten/ngay_sinh/ngay_ket_thuc/ma_bhxh; rong khi cong tra null */
    public $thongTinThe = [];

    /** @var array cac dong DataCCT da chuan hoa */
    public $dong = [];

    /**
     * @param array $json mang da json_decode tu than phan hoi
     * @return self
     */
    public static function tuMang(array $json)
    {
        $kq = new self();

        $kq->maKetQua = isset($json['MaKetQua']) ? trim((string) $json['MaKetQua']) : '';
        $kq->ghiChu = isset($json['GhiChu']) ? (string) $json['GhiChu'] : '';

        $the = isset($json['ThongTinSoThe']) && is_array($json['ThongTinSoThe'])
            ? $json['ThongTinSoThe'] : [];

        if ($the !== []) {
            $kq->thongTinThe = [
                'ho_ten' => self::chuoi($the, 'hoTen'),
                // Giu nguyen dinh dang dd/MM/yyyy hoac MM/yyyy hoac yyyy: cot CSDL the_ngay_sinh
                // la string(10), khong phai date; cong tra co the chi co thang/nam hoac chi nam
                // nen khong the doi sang Y-m-d. Ngay_ket_thuc duoi day la date nen phai doi.
                'ngay_sinh' => self::chuoi($the, 'ngaySinh'),
                'ngay_ket_thuc' => self::ngay($the, 'ngayKetThuc'),
                'ma_bhxh' => self::chuoi($the, 'maBhxh'),
            ];
        }

        $ds = isset($json['DataCCT']) && is_array($json['DataCCT']) ? $json['DataCCT'] : [];

        foreach ($ds as $d) {
            if (!is_array($d)) {
                continue;
            }

            $kq->dong[] = [
                'id_cong' => isset($d['Id']) ? (int) $d['Id'] : null,
                'ngay_tra_cuu' => self::ngay($d, 'ngayTraCuu'),
                'ma_the' => self::chuoi($d, 'maThe'),
                'ma_cskcb' => self::chuoi($d, 'maCskcb'),
                'ngay_vao' => self::ngay($d, 'ngayVao'),
                'ngay_ra' => self::ngay($d, 'ngayRa'),
                'ma_doi_tuong_kcb' => self::chuoi($d, 'maDoiTuongKCB'),
                't_bn_cct_mcct' => self::tien($d, 'tBNCCTMCCT'),
                't_bn_cct_luy_ke' => self::tien($d, 'tBNCCTLuyKe'),
                'ngay_nhan_cong' => self::ngay($d, 'ngayNhanCong'),
                'ngay_nhan' => self::ngay($d, 'ngayNhan'),
            ];
        }

        return $kq;
    }

    /** @return bool */
    public function thanhCong()
    {
        return $this->maKetQua === '200';
    }

    /**
     * Lay MAX chu khong lay dong dau: cong sap giam dan theo NGAY RA VIEN, khong phai theo
     * so luy ke. Ho so nhan muon co the nam cuoi danh sach ma mang so luy ke lon nhat.
     *
     * @return float
     */
    public function luyKeLonNhat()
    {
        $max = 0.0;

        foreach ($this->dong as $d) {
            if ($d['t_bn_cct_luy_ke'] > $max) {
                $max = $d['t_bn_cct_luy_ke'];
            }
        }

        return $max;
    }

    private static function chuoi(array $m, $khoa)
    {
        return isset($m[$khoa]) ? trim((string) $m[$khoa]) : '';
    }

    /**
     * dd/MM/yyyy -> Y-m-d. Chuoi rong -> null.
     *
     * Tra null chu khong tra chuoi rong: cot CSDL kieu date nhan chuoi rong se thanh
     * '0000-00-00' tren mot so cau hinh MySQL - mot ngay khong ton tai, sap xep sai, va
     * khong the phan biet voi "chua co du lieu".
     */
    private static function ngay(array $m, $khoa)
    {
        $v = self::chuoi($m, $khoa);

        if ($v === '' || strlen($v) !== 10) {
            return null;
        }

        $p = explode('/', $v);

        if (count($p) !== 3) {
            return null;
        }

        return $p[2] . '-' . $p[1] . '-' . $p[0];
    }

    /**
     * Doi truong tien cua cong thanh so.
     *
     * CONG TRA TIEN DUOI DANG CHUOI CO DAU PHAY NGAN NGHIN: "893,973", "3,862,166" - da xac
     * nhan bang du lieu that ngay 2026-09-03, du dac ta ghi kieu "So thuc".
     *
     * Ep thang (float) la SAI VA IM LANG: PHP dung lai o dau phay, nen (float) '3,862,166'
     * bang 3.0. Mot nguoi benh co luy ke 3.862.166 d se bi doc thanh 3 d, va ket luan "du
     * dieu kien mien cung chi tra" luon luon sai - sai theo huong khong ai nhin ra tu man hinh.
     *
     * Chi bo dau PHAY va khoang trang, GIU dau cham lam dau thap phan. Khong doan dau cham la
     * ngan nghin: '1.120' khi do vua co the la 1,12 vua co the la 1120, va doan sai o day cung
     * im lang y het loi cu.
     */
    private static function tien(array $m, $khoa)
    {
        if (!isset($m[$khoa])) {
            return 0.0;
        }

        $v = $m[$khoa];

        if (is_int($v) || is_float($v)) {
            return (float) $v;
        }

        $v = str_replace([',', ' ', "\xc2\xa0"], '', trim((string) $v));

        return is_numeric($v) ? (float) $v : 0.0;
    }
}

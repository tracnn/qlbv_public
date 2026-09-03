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

    private static function tien(array $m, $khoa)
    {
        return isset($m[$khoa]) ? (float) $m[$khoa] : 0.0;
    }
}

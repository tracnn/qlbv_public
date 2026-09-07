<?php

namespace App\Services\Mcct;

/**
 * Dung khung JSON tra ve cho modal tra cuu MCCT.
 *
 * VI SAO TACH RA MOT LOP THAY VI VIET THANG TRONG CONTROLLER: controller goi cong BHXH that,
 * nen mot test cham vao no la mot test co the gui request that len cong san xuat - da xay ra
 * mot lan trong du an nay. Lop nay THUAN: nhan KetQuaMcct roi tra mang, khong cham mang va
 * khong cham CSDL, nen kiem duoc day du ma khong co rui ro do.
 *
 * Man HTML dung lai chinh cac thong bao o day, de mot cau chu chi ton tai o MOT cho: sua o
 * day la sua ca hai duong.
 */
class McctPhanHoiJson
{
    /**
     * @param KetQuaMcct $kq
     * @param float $nguong nguong mien cung chi tra tai thoi diem tra
     * @param bool|null $duDieuKien null khi tra cuu khong thanh cong
     * @param string|null $maCskcb co so da dung tai khoan de tra
     * @param array|null $muc ket qua NguongMienCungChiTra::tinhTheoQuyDinh(); null khi khong tinh duoc
     * @return array
     */
    public static function tuKetQua(KetQuaMcct $kq, $nguong, $duDieuKien, $maCskcb = null, array $muc = null)
    {
        $tb = self::thongBao($kq, $maCskcb);

        return [
            'ok' => $kq->thanhCong(),
            'ma_ket_qua' => $kq->maKetQua,
            // NGUYEN VAN: GhiChu chua moc "du lieu tinh den ...". So lieu cong co do tre nen
            // man hinh bat buoc hien moc do truoc khi nguoi dung ket luan voi nguoi benh.
            'ghi_chu' => $kq->ghiChu,
            'thong_tin_the' => $kq->thongTinThe,
            'dong' => $kq->dong,
            'luy_ke' => $kq->luyKeLonNhat(),
            'nguong' => (float) $nguong,
            'du_dieu_kien' => $duDieuKien,
            // Cach tinh theo diem c khoan 2 Dieu 18 ND 188/2025 - long nguyen khoi thay vi
            // trai phang tung khoa: them mot khoa moi o day la them mot khoa phai nho bo sung
            // vao ca nhanh loi() ben duoi, va quen mot khoa se thanh 'undefined' tren man hinh.
            'muc' => $muc,
            'thong_bao' => $tb['thong_bao'],
            'muc_do' => $tb['muc_do'],
        ];
    }

    /**
     * Khung cho nhanh HONG (loi mang, loi cau hinh, token hong...).
     *
     * Tra DU moi khoa giong het nhanh thanh cong, chu khong chi tra {ok:false, thong_bao}.
     * Javascript khi do doc mot kieu du lieu duy nhat thay vi phai doan xem lan nay may chu
     * tra ve hinh dang nao - va mot khoa thieu se thanh 'undefined' hien thang ra man hinh.
     *
     * @param string $thongBao
     * @return array
     */
    public static function loi($thongBao)
    {
        return [
            'ok' => false,
            'ma_ket_qua' => '',
            'ghi_chu' => '',
            'thong_tin_the' => [],
            'dong' => [],
            'luy_ke' => 0.0,
            'nguong' => 0.0,
            'du_dieu_kien' => null,
            'muc' => null,
            'thong_bao' => (string) $thongBao,
            'muc_do' => 'danger',
        ];
    }

    /**
     * Doi ma ket qua cua cong thanh thong bao cho nguoi dung.
     *
     * Ma 500 TACH LAM HAI theo noi dung GhiChu: "loi trong qua trinh tra cuu" nghia la tai
     * khoan bi han che tra cuu - van de TAI KHOAN, khong phai loi he thong. Gop chung se day
     * nguoi doc di do nham huong hang gio.
     *
     * @return array ['thong_bao' => string|null, 'muc_do' => string|null]
     */
    private static function thongBao(KetQuaMcct $kq, $maCskcb)
    {
        if ($kq->maKetQua === '200') {
            return ['thong_bao' => null, 'muc_do' => null];
        }

        if ($kq->maKetQua === '204') {
            // Noi ca HAI kha nang: cong dung chung mot ma cho "khong thay the" va "the chua
            // phat sinh chi phi". Noi gop thanh "the sai" la day nguoi dung di sua cai khong sai.
            return [
                'thong_bao' => 'Không tìm thấy dữ liệu. Có thể do sai thông tin thẻ, hoặc thẻ '
                    . 'chưa phát sinh chi phí cùng chi trả.',
                'muc_do' => 'warning',
            ];
        }

        if ($kq->maKetQua === '500' && mb_strpos($kq->ghiChu, 'quá trình tra cứu') !== false) {
            return [
                'thong_bao' => 'Tài khoản của cơ sở ' . $maCskcb . ' đang bị cổng hạn chế tra '
                    . 'cứu. Liên hệ BHXH tỉnh để được mở.',
                'muc_do' => 'danger',
            ];
        }

        return [
            'thong_bao' => 'Cổng BHXH báo lỗi (' . $kq->maKetQua . '): ' . $kq->ghiChu,
            'muc_do' => 'danger',
        ];
    }
}

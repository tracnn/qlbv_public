<?php

namespace App\Services\Mcct;

/**
 * Dung lai ket qua tra cuu MCCT tu du lieu DA LUU, khong goi cong.
 *
 * VI SAO CAN: cong tra ve cham (5-60 giay). Hien ngay ket qua lan truoc roi de nguoi dung tu
 * quyet dinh co tra lai hay khong nhanh hon han, va tiet kiem luot goi - cong co danh sach
 * tai khoan bi han che tra cuu.
 *
 * Tra ve DUNG khung JSON cua duong goi cong that (McctPhanHoiJson), cong them hai khoa
 * `tu_cache` va `tra_luc`. Cung khung nghia la javascript dung lai NGUYEN bo dung ket qua,
 * khong phai viet bo thu hai - va hai duong khong bao gio hien khac nhau duoc.
 *
 * Lop THUAN: nhan mang du lieu, khong tu truy van CSDL va khong cham mang.
 */
class McctDungLaiKetQua
{
    /**
     * @param array $phien mot dong mcct_tra_cuu dang mang
     * @param array $dong cac dong mcct_chi_phi cua phien do
     * @param array $bangLuong config('mcct.luong_co_so')
     * @param int $soThang config('mcct.so_thang_luong_co_so')
     * @return array
     */
    public static function tuBanGhi(array $phien, array $dong, array $bangLuong, $soThang)
    {
        $cacDong = [];

        foreach ($dong as $d) {
            $d = (array) $d;

            $cacDong[] = [
                'id_cong' => isset($d['id_cong']) ? (int) $d['id_cong'] : null,
                'ngay_tra_cuu' => self::chuoi($d, 'ngay_tra_cuu'),
                'ma_the' => self::chuoi($d, 'ma_the'),
                'ma_cskcb' => self::chuoi($d, 'ma_cskcb'),
                'ngay_vao' => self::chuoi($d, 'ngay_vao'),
                'ngay_ra' => self::chuoi($d, 'ngay_ra'),
                'ma_doi_tuong_kcb' => self::chuoi($d, 'ma_doi_tuong_kcb'),
                // CSDL tra ve decimal duoi dang CHUOI ('893973.00'). De lot xuong javascript
                // thanh chuoi thi phep cong o do se noi chuoi thay vi cong so.
                't_bn_cct_mcct' => self::so($d, 't_bn_cct_mcct'),
                't_bn_cct_luy_ke' => self::so($d, 't_bn_cct_luy_ke'),
                'ngay_nhan_cong' => self::chuoi($d, 'ngay_nhan_cong'),
                'ngay_nhan' => self::chuoi($d, 'ngay_nhan'),
            ];
        }

        $traLuc = self::chuoi($phien, 'tra_luc');

        // NGAY TRA CUA LAN DO, khong phai hom nay. Tinh theo hom nay thi mot ban ghi cu se doi
        // ket luan moi khi luong co so thay doi - dung cai ma nguyen tac "ghi nguong vao bang
        // chu khong tinh lai" da chan tu dau giai doan 1.
        $ngayTra = $traLuc !== '' ? substr($traLuc, 0, 10) : date('Y-m-d');

        $muc = NguongMienCungChiTra::tinhTheoQuyDinh($cacDong, $ngayTra, $bangLuong, $soThang);

        $the = [];

        if (self::chuoi($phien, 'the_ho_ten') !== '') {
            $the = [
                'ho_ten' => self::chuoi($phien, 'the_ho_ten'),
                'ngay_sinh' => self::chuoi($phien, 'the_ngay_sinh'),
                'ngay_ket_thuc' => self::chuoi($phien, 'the_ngay_ket_thuc'),
                'ma_bhxh' => self::chuoi($phien, 'the_ma_bhxh'),
            ];
        }

        return [
            'ok' => self::chuoi($phien, 'ma_ket_qua') === '200',
            'ma_ket_qua' => self::chuoi($phien, 'ma_ket_qua'),
            // GhiChu nguyen van cua cong: no ghi du lieu "tinh den" thoi diem nao. Moc do KHAC
            // voi tra_luc ben duoi - mot cai la luc cong chot du lieu, mot cai la luc minh hoi.
            'ghi_chu' => self::chuoi($phien, 'ghi_chu'),
            'thong_tin_the' => $the,
            'dong' => $cacDong,
            'luy_ke' => $muc['luy_ke_tong'],
            'nguong' => self::so($phien, 'nguong_ap_dung'),
            // Lay ket luan VUA TINH LAI chu khong lay cot du_dieu_kien_mien da luu: cac ban ghi
            // truoc ban va sua cong thuc (07/9/2026) duoc luu bang cach tinh cu, hien lai chung
            // la lam song lai chinh cai loi da sua.
            'du_dieu_kien' => $muc['du_dieu_kien'],
            'muc' => $muc,
            'thong_bao' => null,
            'muc_do' => null,
            'tu_cache' => true,
            'co_du_lieu' => true,
            'tra_luc' => $traLuc !== '' ? $traLuc : null,
        ];
    }

    /**
     * The chua tung duoc tra lan nao.
     *
     * Tra DU khung nhu tuBanGhi() de javascript doc mot kieu du lieu duy nhat; `co_du_lieu`
     * la khoa duy nhat no can xem de biet co phai tu goi cong hay khong.
     *
     * @return array
     */
    public static function khongCo()
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
            'thong_bao' => null,
            'muc_do' => null,
            'tu_cache' => true,
            'co_du_lieu' => false,
            'tra_luc' => null,
        ];
    }

    private static function chuoi(array $m, $khoa)
    {
        return isset($m[$khoa]) ? trim((string) $m[$khoa]) : '';
    }

    private static function so(array $m, $khoa)
    {
        return isset($m[$khoa]) ? (float) $m[$khoa] : 0.0;
    }
}

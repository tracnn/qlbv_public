<?php

namespace App\Services\Mcct;

use App\Models\Mcct\McctChiPhi;
use App\Models\Mcct\McctTraCuu;

/**
 * Ghi mot phien tra cuu MCCT xuong CSDL.
 *
 * Tach khoi McctTraCuuService de kiem duoc ma khong cham mang, va de giai doan 2 (tra hang
 * loat) dung lai nguyen ma khong keo theo tang HTTP.
 */
class McctLuuTraCuu
{
    /**
     * @param KetQuaMcct $kq
     * @param array $thamSo ma_cskcb, ma_the, ho_ten, ngay_sinh, nguon, tra_boi, nguong,
     *                      du_dieu_kien
     * @return McctTraCuu
     */
    public static function luu(KetQuaMcct $kq, array $thamSo)
    {
        $the = $kq->thongTinThe;

        $ban = McctTraCuu::create([
            'ma_cskcb' => isset($thamSo['ma_cskcb']) ? $thamSo['ma_cskcb'] : '',
            'ma_the' => isset($thamSo['ma_the']) ? $thamSo['ma_the'] : '',
            'ho_ten' => isset($thamSo['ho_ten']) ? $thamSo['ho_ten'] : null,
            'ngay_sinh' => isset($thamSo['ngay_sinh']) ? $thamSo['ngay_sinh'] : null,

            'ma_ket_qua' => $kq->maKetQua,
            'ghi_chu' => $kq->ghiChu,

            'the_ho_ten' => isset($the['ho_ten']) ? $the['ho_ten'] : null,
            'the_ngay_sinh' => isset($the['ngay_sinh']) ? $the['ngay_sinh'] : null,
            'the_ngay_ket_thuc' => isset($the['ngay_ket_thuc']) ? $the['ngay_ket_thuc'] : null,
            'the_ma_bhxh' => isset($the['ma_bhxh']) ? $the['ma_bhxh'] : null,

            'luy_ke_lon_nhat' => $kq->luyKeLonNhat(),

            // Nguong TINH SAN o tren truyen xuong, khong tinh lai o day va cung khong tinh
            // lai luc doc: luong co so se tang, va ban ghi cu phai giu nguyen ket luan cu.
            'nguong_ap_dung' => isset($thamSo['nguong']) ? $thamSo['nguong'] : null,
            'du_dieu_kien_mien' => isset($thamSo['du_dieu_kien']) ? $thamSo['du_dieu_kien'] : null,

            // Cach tinh theo diem c khoan 2 Dieu 18 ND 188/2025: khac nguong_ap_dung khi
            // luong co so doi giua nam. Xem NguongMienCungChiTra::tinhTheoQuyDinh().
            'so_tien_con_phai_dong' => isset($thamSo['so_tien_con_phai_dong'])
                ? $thamSo['so_tien_con_phai_dong'] : null,
            'da_dong_truoc_moc' => isset($thamSo['da_dong_truoc_moc'])
                ? $thamSo['da_dong_truoc_moc'] : null,

            'nguon' => isset($thamSo['nguon']) ? $thamSo['nguon'] : 'thu_cong',
            'tra_boi' => isset($thamSo['tra_boi']) ? $thamSo['tra_boi'] : null,
            'tra_luc' => date('Y-m-d H:i:s'),
        ]);

        foreach ($kq->dong as $d) {
            $d['tra_cuu_id'] = $ban->id;
            McctChiPhi::create($d);
        }

        return $ban;
    }
}

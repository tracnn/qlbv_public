<?php

namespace App\Services\Ctdt\Loai;

/**
 * Mot loai chung tu TU MO TA: bang nao, the nao, quy tac kiem nao, tab nao.
 *
 * Vi sao tinh (static) chu khong phai doi tuong: khong lop nao co trang thai rieng, va
 * registry chi giu TEN LOP. Khoi tao chi de goi mot ham thuan la them buoc thua.
 *
 * Vi sao KHONG dat quy tac kiem o day trong Giai doan 1: bo kiem thuoc Giai doan 3.
 * Them phuong thuc quyTacKiem() vao interface se buoc 9 lop cai mot ham rong ngay bay gio.
 */
interface LoaiChungTu
{
    /** Gia tri the LOAIHOSO trong FILEHOSO, vi du 'GIAYDIEUTRINOITRU' */
    public static function maLoaiHoSo();

    /** Ten the goc BEN TRONG base64, vi du 'CTGiayDieuTriNoiTru'. KHONG luon trung maLoaiHoSo(). */
    public static function theGoc();

    /** Dich vu chua loai nay: 'CT2025' | 'GBT' | 'GCS' */
    public static function dichVu();

    /** Ten bang chi tiet */
    public static function bang();

    /** Ten lop model */
    public static function model();

    /** Nhan hien thi tren tab chi tiet */
    public static function tenTab();

    /** @return array Anh xa TEN THE => ten cot */
    public static function truong();

    /** @return string|null Khoa nghiep vu cua chung tu (MA_YTE / MA_GBT / MA_GCS) */
    public static function maChungTu(\SimpleXMLElement $xml);

    /** @return array Nam cot rut gon: ma_the, ho_ten, ngay_sinh, ngay_vao, ngay_ra */
    public static function rutGon(\SimpleXMLElement $xml);
}

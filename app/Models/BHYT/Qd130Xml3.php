<?php

namespace App\Models\BHYT;

use Illuminate\Database\Eloquent\Model;

class Qd130Xml3 extends Model
{
    protected $fillable = [
        'ma_lk',
        'stt',
        'ma_dich_vu',
        'ma_pttt_qt',
        'ma_vat_tu',
        'ma_nhom',
        'goi_vtyt',
        'ten_vat_tu',
        'ten_dich_vu',
        'ma_xang_dau',
        'don_vi_tinh',
        'pham_vi',
        'so_luong',
        'don_gia_bv',
        'don_gia_bh',
        'tt_thau',
        'tyle_tt_dv',
        'tyle_tt_bh',
        'thanh_tien_bv',
        'thanh_tien_bh',
        't_trantt',
        'muc_huong',
        't_nguonkhac_nsnn',
        't_nguonkhac_vtnn',
        't_nguonkhac_vttn',
        't_nguonkhac_cl',
        't_nguonkhac',
        't_bhtt',
        't_bntt',
        't_bncct',
        'ma_khoa',
        'ma_giuong',
        'ma_bac_si',
        'nguoi_thuc_hien',
        'ma_benh',
        'ma_benh_yhct',
        'ngay_yl',
        'ngay_th_yl',
        'ngay_kq',
        'ma_pttt',
        'vet_thuong_tp',
        'pp_vo_cam',
        'vi_tri_th_dvkt',
        'ma_may',
        'ma_hieu_sp',
        'tai_su_dung',
        'du_phong'
    ];

    public function Qd130Xml1()
    {
        return $this->belongsTo('App\Models\BHYT\Qd130Xml1', 'ma_lk', 'ma_lk');
    }

    public function errorResult()
    {
        return $this->hasMany('App\Models\BHYT\Qd130XmlErrorResult', 'ma_lk', 'ma_lk')
                    ->where('xml', 'XML3')
                    ->whereColumn('stt', 'stt');
    }
}

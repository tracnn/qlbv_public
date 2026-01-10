<?php

namespace App\Models\BHYT;

use Illuminate\Database\Eloquent\Model;

class Xml3176Xml13 extends Model
{
    protected $table = 'xml3176_xml13s';

    protected $fillable = [
        'ma_lk',
        'so_hoso',
        'so_chuyentuyen',
        'giay_chuyen_tuyen',
        'ma_cskcb',
        'ma_cskcb_di',
        'ma_cskcb_den',
        'ho_ten',
        'ngay_sinh',
        'gioi_tinh',
        'ma_quoctich',
        'ma_dantoc',
        'ma_nghe_nghiep',
        'dia_chi',
        'ma_the_bhyt',
        'gt_the_den',
        'ngay_vao',
        'ngay_vao_noi_tru',
        'ngay_ra',
        'dau_hieu_ls',
        'chan_doan_rv',
        'qt_benhly',
        'tomtat_kq',
        'pp_dieutri',
        'ma_benh_chinh',
        'ma_benh_kt',
        'ma_benh_yhct',
        'tinh_trang_ct',
        'ma_loai_rv',
        'ma_lydo_ct',
        'huong_dieu_tri',
        'phuongtien_vc',
        'hoten_nguoi_ht',
        'chucdanh_nguoi_ht',
        'ma_bac_si',
        'ma_ttdv',
        'du_phong',
    ];

    public function errorResult()
    {
        return $this->hasMany('App\Models\BHYT\Xml3176ErrorResult', 'ma_lk', 'ma_lk')
                    ->where('xml', 'XML13');
    }
}

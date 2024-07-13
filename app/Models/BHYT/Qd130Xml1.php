<?php

namespace App\Models\BHYT;

use Illuminate\Database\Eloquent\Model;

class Qd130Xml1 extends Model
{
    protected $fillable = [
        'ma_lk',
        'stt',
        'ma_bn',
        'ho_ten',
        'so_cccd',
        'ngay_sinh',
        'gioi_tinh',
        'nhom_mau',
        'ma_quoctich',
        'ma_dantoc',
        'ma_nghe_nghiep',
        'dia_chi',
        'matinh_cu_tru',
        'mahuyen_cu_tru',
        'maxa_cu_tru',
        'dien_thoai',
        'ma_the_bhyt',
        'ma_dkbd',
        'gt_the_tu',
        'gt_the_den',
        'ngay_mien_cct',
        'ly_do_vv',
        'ly_do_vnt',
        'ma_ly_do_vnt',
        'chan_doan_vao',
        'chan_doan_rv',
        'ma_benh_chinh',
        'ma_benh_kt',
        'ma_benh_yhct',
        'ma_pttt_qt',
        'ma_doituong_kcb',
        'ma_noi_di',
        'ma_noi_den',
        'ma_tai_nan',
        'ngay_vao',
        'ngay_vao_noi_tru',
        'ngay_ra',
        'giay_chuyen_tuyen',
        'so_ngay_dtri',
        'pp_dieu_tri',
        'ket_qua_dtri',
        'ma_loai_rv',
        'ghi_chu',
        'ngay_ttoan',
        't_thuoc',
        't_vtyt',
        't_tongchi_bv',
        't_tongchi_bh',
        't_bntt',
        't_bncct',
        't_bhtt',
        't_nguonkhac',
        't_bhtt_gdv',
        'nam_qt',
        'thang_qt',
        'ma_loai_kcb',
        'ma_khoa',
        'ma_cskcb',
        'ma_khuvuc',
        'can_nang',
        'can_nang_con',
        'nam_nam_lien_tuc',
        'ngay_tai_kham',
        'ma_hsba',
        'ma_ttdv',
        'du_phong'
    ];

    public function Qd130Xml2()
    {
        return $this->hasMany('App\Models\BHYT\Qd130Xml2', 'ma_lk', 'ma_lk');
    }

    public function Qd130Xml3()
    {
        return $this->hasMany('App\Models\BHYT\Qd130Xml3', 'ma_lk', 'ma_lk');
    }

    public function Qd130Xml4()
    {
        return $this->hasMany('App\Models\BHYT\Qd130Xml4', 'ma_lk', 'ma_lk');
    }

    public function Qd130Xml5()
    {
        return $this->hasMany('App\Models\BHYT\Qd130Xml5', 'ma_lk', 'ma_lk');
    }

    public function Qd130Xml6()
    {
        return $this->hasMany('App\Models\BHYT\Qd130Xml6', 'ma_lk', 'ma_lk');
    }

    public function Qd130Xml7()
    {
        return $this->hasMany('App\Models\BHYT\Qd130Xml7', 'ma_lk', 'ma_lk');
    }

    public function Qd130Xml8()
    {
        return $this->hasMany('App\Models\BHYT\Qd130Xml8', 'ma_lk', 'ma_lk');
    }

    public function Qd130Xml9()
    {
        return $this->hasMany('App\Models\BHYT\Qd130Xml9', 'ma_lk', 'ma_lk');
    }

    public function Qd130Xml10()
    {
        return $this->hasMany('App\Models\BHYT\Qd130Xml10', 'ma_lk', 'ma_lk');
    }

    public function Qd130Xml11()
    {
        return $this->hasMany('App\Models\BHYT\Qd130Xml11', 'ma_lk', 'ma_lk');
    }

    public function Qd130Xml13()
    {
        return $this->hasMany('App\Models\BHYT\Qd130Xml13', 'ma_lk', 'ma_lk');
    }

    public function Qd130Xml14()
    {
        return $this->hasMany('App\Models\BHYT\Qd130Xml14', 'ma_lk', 'ma_lk');
    }

    public function Qd130Xml15()
    {
        return $this->hasMany('App\Models\BHYT\Qd130Xml15', 'ma_lk', 'ma_lk');
    }


    public function check_hein_card()
    {
        return $this->hasOne('App\Models\CheckBHYT\check_hein_card', 'ma_lk', 'ma_lk');
    }

    public function Qd130XmlErrorResult()
    {
        return $this->hasMany('App\Models\BHYT\Qd130XmlErrorResult', 'ma_lk', 'ma_lk');
    }

    public function Qd130XmlInformation()
    {
        return $this->hasOne('App\Models\BHYT\Qd130XmlInformation', 'ma_lk', 'ma_lk');
    }
}

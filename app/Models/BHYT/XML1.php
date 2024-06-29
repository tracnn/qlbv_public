<?php

namespace App\Models\BHYT;

use Illuminate\Database\Eloquent\Model;

class XML1 extends Model
{
    protected $table = 'xml1s';

    // Thêm các thuộc tính vào fillable
    protected $fillable = [
        'ma_lk', 'stt', 'ma_bn', 'ho_ten', 'ngay_sinh', 'gioi_tinh', 'dia_chi', 'ma_the', 'ma_dkbd', 'gt_the_tu',
        'gt_the_den', 'mien_cung_ct', 'ten_benh', 'ma_benh', 'ma_benhkhac', 'ma_lydo_vvien', 'ma_noi_chuyen',
        'ma_tai_nan', 'ngay_vao', 'ngay_ra', 'so_ngay_dtri', 'ket_qua_dtri', 'tinh_trang_rv', 'ngay_ttoan',
        't_thuoc', 't_vtyt', 't_tongchi', 't_bntt', 't_bncct', 't_bhtt', 't_nguonkhac', 't_ngoaids', 'nam_qt',
        'thang_qt', 'ma_loai_kcb', 'ma_khoa', 'ma_cskcb', 'ma_khuvuc', 'ma_pttt_qt', 'can_nang'
    ];

    public function department()
    {
        return $this->hasOne('App\Models\BHYT\department', 'ma_khoa', 'ma_khoa');
    }

    public function xml5()
    {
        return $this->hasMany('App\Models\BHYT\xml5', 'ma_lk', 'ma_lk');
    }

    public function xml4()
    {
        return $this->hasMany('App\Models\BHYT\xml4', 'ma_lk', 'ma_lk');
    }

    public function xml3()
    {
        return $this->hasMany('App\Models\BHYT\xml3', 'ma_lk', 'ma_lk');
    }

    public function xml2()
    {
        return $this->hasMany('App\Models\BHYT\xml2', 'ma_lk', 'ma_lk');
    }

    public function cat_cond_service()
    {
        return $this->hasManyThrough('App\Models\BHYT\cat_cond_service', 'App\Models\BHYT\xml3', 'ma_dich_vu', 'service_code', 'ma_lk');
    }

    public function cat_cond_pharma()
    {
        return $this->hasManyThrough('App\Models\BHYT\cat_cond_pharma', 'App\Models\BHYT\xml2', 'ma_thuoc', 'pharma_code', 'ma_lk');
    }

    public function check_hein_card()
    {
        return $this->hasOne('App\Models\CheckBHYT\check_hein_card', 'ma_lk', 'ma_lk');
    }

    public function xmlErrorChecks()
    {
        return $this->hasMany('App\Models\XmlErrorCheck', 'ma_lk', 'ma_lk');
    }
}
<?php

namespace App\Models\BHYT\Ctdt;

use Illuminate\Database\Eloquent\Model;

class CtdtCt03 extends Model
{
    protected $table = 'ctdt_ct03';

    protected $fillable = [
        'chung_tu_id',
        'so_luu_tru', 'ma_yte', 'ma_khoa', 'ma_bhxh', 'ma_the', 'ho_ten',
        'ngay_sinh', 'gioi_tinh', 'ma_dantoc', 'nghe_nghiep', 'dia_chi',
        'ngay_vao', 'ngay_ra', 'dinh_chi_thai_nghen', 'tuoi_thai',
        'chan_doan', 'pp_dieutri', 'ghi_chu',
        'thu_truong_dvi', 'ma_cchn_truongkhoa', 'ten_truongkhoa',
        'ngay_chung_tu', 'tekt', 'ho_ten_cha', 'ho_ten_me',
        'ngoaitru_tungay', 'ngoaitru_denngay',
        'loai_giayto', 'so_cccd', 'ngaycap_cccd', 'noicap_cccd',
        'benhicd10_id', 'tenbenhicd10',
    ];

    public function chungTu()
    {
        return $this->belongsTo(CtdtChungTu::class, 'chung_tu_id', 'id');
    }
}

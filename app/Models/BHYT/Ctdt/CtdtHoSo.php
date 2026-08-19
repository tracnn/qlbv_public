<?php

namespace App\Models\BHYT\Ctdt;

use Illuminate\Database\Eloquent\Model;

class CtdtHoSo extends Model
{
    protected $table = 'ctdt_ho_so';

    protected $fillable = [
        'ma_ho_so', 'id_goi_xml', 'dich_vu', 'loai_hs', 'macskcb', 'ngay_lap',
        'so_luong_ho_so', 'so_chung_tu', 'duong_dan_goc', 'duong_dan_da_ky',
        'imported_at', 'imported_by', 'import_error',
        'checked_at', 'so_loi',
        'is_signed', 'sign_method', 'signed_at', 'signed_error',
        'submitted_at', 'submitted_by', 'submit_error', 'submitted_message',
        'ma_gd', 'ma_ket_qua', 'thoi_gian_tiep_nhan', 'lich_su_gui',
    ];

    public function chungTu()
    {
        return $this->hasMany(CtdtChungTu::class, 'ho_so_id', 'id');
    }

    public function loi()
    {
        return $this->hasMany(CtdtLoi::class, 'ho_so_id', 'id');
    }
}

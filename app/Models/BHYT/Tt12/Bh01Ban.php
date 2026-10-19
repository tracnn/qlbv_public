<?php

namespace App\Models\BHYT\Tt12;

use Illuminate\Database\Eloquent\Model;

class Bh01Ban extends Model
{
    const KY_CHO  = 'cho_ky';
    const KY_XONG = 'da_ky';
    const KY_LOI  = 'ky_loi';

    const NGUON_XML3176 = 'xml3176';
    const NGUON_EXCEL   = 'excel';

    protected $table = 'bh01_ban';

    protected $fillable = [
        'ma_ban', 'ma_cskcb', 'nam_qt', 'thang_qt', 'so_ban', 'so_dong',
        't_tongchi_bv', 't_tongchi_bh', 't_bhtt', 't_bncct', 't_bntt', 't_nguonkhac',
        'so_canh_bao', 'id_ds_chitiet', 'tao_boi', 'tao_luc',
        'trang_thai_ky', 'sign_method', 'signed_at', 'signed_error', 'duong_dan_da_ky', 'sha256_tep',
        'submitted_at', 'submitted_by', 'ma_ket_qua', 'ma_gd', 'thoi_gian_tiep_nhan', 'thong_diep',
        'dang_xu_ly_tu',
        'nguon', 'ten_tep_nguon', 'duong_dan_tep_nguon',
    ];

    protected $casts = [
        'so_ban'      => 'integer',
        'so_dong'     => 'integer',
        'so_canh_bao' => 'integer',
    ];

    protected $dates = ['tao_luc', 'signed_at', 'submitted_at', 'dang_xu_ly_tu'];

    public function dong()
    {
        return $this->hasMany(Bh01Dong::class, 'ban_id')->orderBy('stt');
    }

    public function lichSuGui()
    {
        return $this->hasMany(Bh01LichSuGui::class, 'ban_id')->orderBy('id', 'desc');
    }
}

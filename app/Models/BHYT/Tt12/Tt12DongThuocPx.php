<?php

namespace App\Models\BHYT\Tt12;

use Illuminate\Database\Eloquent\Model;

class Tt12DongThuocPx extends Model
{
    protected $table = 'tt12_dong_thuoc_px';

    protected $fillable = [
        'dong_id', 'stt', 'ma_thuoc', 'ten_thuoc', 'so_dang_ky', 'don_vi_tinh', 'tt_thau',
        'don_gia_thuoc', 'dm_nsx_cdd', 'dm_thucte_cdd', 'lieu_bq_px', 'tl_thucte_bq_px',
        'thanh_tien_thuoc',
    ];

    public function dong()
    {
        return $this->belongsTo(Tt12Dong::class, 'dong_id');
    }
}

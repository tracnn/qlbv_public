<?php

namespace App\Models\BHYT\Tt12;

use Illuminate\Database\Eloquent\Model;

class Bh01Dong extends Model
{
    protected $table = 'bh01_dong';

    // Bang khong co created_at/updated_at: dong la anh chup dong bang.
    public $timestamps = false;

    protected $fillable = [
        'ban_id', 'stt', 'ma_lk',
        'ho_ten', 'ngay_sinh', 'gioi_tinh', 'ma_the_bhyt', 'ma_benh_chinh',
        'ngay_vao', 'ngay_vao_noi_tru', 'ngay_ra', 'so_ngay_dtri', 'ma_loai_kcb',
        't_tongchi_bv', 't_tongchi_bh', 't_bhtt', 't_bncct', 't_bntt', 't_nguonkhac',
        'ma_cskcb', 'nam_qt', 'thang_qt',
        'canh_bao',
    ];

    public function ban()
    {
        return $this->belongsTo(Bh01Ban::class, 'ban_id');
    }
}

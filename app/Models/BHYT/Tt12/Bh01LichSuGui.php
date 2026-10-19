<?php

namespace App\Models\BHYT\Tt12;

use Illuminate\Database\Eloquent\Model;

class Bh01LichSuGui extends Model
{
    protected $table = 'bh01_lich_su_gui';

    protected $fillable = [
        'ban_id', 'gui_luc', 'gui_boi', 'ma_ket_qua', 'ma_gd', 'thoi_gian_tiep_nhan',
        'thong_diep', 'loi', 'nguyen_van',
    ];

    protected $dates = ['gui_luc'];

    public function ban()
    {
        return $this->belongsTo(Bh01Ban::class, 'ban_id');
    }
}

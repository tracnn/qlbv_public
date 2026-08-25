<?php

namespace App\Models\BHYT\Tt12;

use Illuminate\Database\Eloquent\Model;

class Tt12LichSuGui extends Model
{
    protected $table = 'tt12_lich_su_gui';

    protected $fillable = [
        'ho_so_id', 'ma_ho_so', 'gui_luc', 'gui_boi', 'ma_ket_qua', 'ma_gd',
        'thoi_gian_tiep_nhan', 'thong_diep', 'loi',
    ];

    protected $dates = ['gui_luc'];

    public function hoSo()
    {
        return $this->belongsTo(Tt12HoSo::class, 'ho_so_id');
    }
}

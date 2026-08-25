<?php

namespace App\Models\BHYT\Tt12;

use Illuminate\Database\Eloquent\Model;

class Tt12Loi extends Model
{
    protected $table = 'tt12_loi';

    protected $fillable = ['ho_so_id', 'stt_dong', 'cot', 'ma_loi', 'muc_do', 'mo_ta'];

    const MUC_DO_LOI      = 'loi';
    const MUC_DO_CANH_BAO = 'canh_bao';

    public function hoSo()
    {
        return $this->belongsTo(Tt12HoSo::class, 'ho_so_id');
    }
}

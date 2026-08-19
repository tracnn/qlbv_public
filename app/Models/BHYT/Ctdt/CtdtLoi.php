<?php

namespace App\Models\BHYT\Ctdt;

use Illuminate\Database\Eloquent\Model;

class CtdtLoi extends Model
{
    protected $table = 'ctdt_loi';

    protected $fillable = [
        'ho_so_id', 'chung_tu_id', 'ma_loi', 'ten_truong', 'mo_ta', 'muc_do',
    ];

    public function hoSo()
    {
        return $this->belongsTo(CtdtHoSo::class, 'ho_so_id', 'id');
    }

    public function chungTu()
    {
        return $this->belongsTo(CtdtChungTu::class, 'chung_tu_id', 'id');
    }
}

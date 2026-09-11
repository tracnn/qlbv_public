<?php

namespace App\Models\BHYT\Ctdt;

use Illuminate\Database\Eloquent\Model;

/**
 * Mot dong = mot lan sua XML goc cua mot chung tu.
 *
 * Xem chu thich migration create_ctdt_lich_su_sua_table ve vi sao luu ca ban TRUOC va vi
 * sao khong dat khoa ngoai toi ctdt_chung_tu.
 */
class CtdtLichSuSua extends Model
{
    protected $table = 'ctdt_lich_su_sua';

    protected $fillable = [
        'ma_ho_so', 'ma_chung_tu', 'loai_ho_so', 'chung_tu_id',
        'noi_dung_truoc', 'noi_dung_sau', 'nguoi_sua', 'ma_gd_luc_sua',
    ];

    public function chungTu()
    {
        return $this->belongsTo(CtdtChungTu::class, 'chung_tu_id');
    }
}

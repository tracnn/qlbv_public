<?php

namespace App\Models\BHYT\Ctdt;

use Illuminate\Database\Eloquent\Model;

/**
 * Mot dong = mot lan goi cong BHXH. Xem chu thich migration ve vi sao khong dung cot
 * ctdt_ho_so.lich_su_gui.
 */
class CtdtLichSuGui extends Model
{
    /** Nguoi bam nut tren man chi tiet */
    const NGUON_MAN_HINH = 'man_hinh';

    /** Lenh ctdt:import chay nen */
    const NGUON_CONSOLE = 'console';

    protected $table = 'ctdt_lich_su_gui';

    protected $fillable = [
        'ho_so_id', 'ma_ho_so', 'nguoi_gui', 'nguon',
        'ma_gd', 'ma_ket_qua', 'thoi_gian_tiep_nhan',
        'thanh_cong', 'thong_diep',
    ];

    protected $casts = ['thanh_cong' => 'boolean'];

    public function hoSo()
    {
        return $this->belongsTo(CtdtHoSo::class, 'ho_so_id');
    }
}

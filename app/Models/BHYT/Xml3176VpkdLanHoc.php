<?php

namespace App\Models\BHYT;

use Illuminate\Database\Eloquent\Model;

class Xml3176VpkdLanHoc extends Model
{
    const CHO = 'CHO';
    const DANG_CHAY = 'DANG_CHAY';
    const XONG = 'XONG';
    const LOI = 'LOI';

    protected $table = 'xml3176_vpkd_lan_hoc';

    protected $fillable = ['user_id', 'trang_thai', 'ket_qua', 'loi', 'bat_dau_luc', 'xong_luc'];

    protected $casts = ['user_id' => 'integer', 'ket_qua' => 'array'];

    protected $dates = ['bat_dau_luc', 'xong_luc'];
}

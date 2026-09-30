<?php

namespace App\Models\BHYT;

use Illuminate\Database\Eloquent\Model;

/**
 * Mot yeu cau xuat tep chay nen cua man XML3176. Chi nguoi tao (user_id) thay va tai duoc.
 */
class Xml3176TepXuat extends Model
{
    const CHO = 'cho';
    const DANG_TAO = 'dang_tao';
    const XONG = 'xong';
    const LOI = 'loi';

    /** Loai tep: dot nay chi co xuat danh sach loi; de cho cho hai nut xuat khac sau nay. */
    const LOAI_LOI = 'loi';

    protected $table = 'xml3176_tep_xuat';

    protected $fillable = [
        'user_id', 'loai', 'bo_loc', 'trang_thai', 'duong_dan', 'kich_thuoc', 'loi',
        'bat_dau_luc', 'xong_luc',
    ];

    protected $casts = [
        'bo_loc' => 'array',
        'user_id' => 'integer',
        'kich_thuoc' => 'integer',
    ];

    protected $dates = ['bat_dau_luc', 'xong_luc'];
}

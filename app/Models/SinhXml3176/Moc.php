<?php

namespace App\Models\SinhXml3176;

use Illuminate\Database\Eloquent\Model;

/**
 * Moc quet. Khoa chinh la chuoi loai_ban; chi co updated_at (khong co created_at).
 */
class Moc extends Model
{
    protected $table = 'sinh_xml3176_moc';

    protected $primaryKey = 'loai_ban';

    // Laravel 5.5 khong ho tro CREATED_AT = null: tat timestamps, TheoDoiSinh tu dat updated_at.
    public $timestamps = false;

    public $incrementing = false;

    protected $keyType = 'string';

    protected $guarded = [];
}

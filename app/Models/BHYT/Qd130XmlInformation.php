<?php

namespace App\Models\BHYT;

use Illuminate\Database\Eloquent\Model;

class Qd130XmlInformation extends Model
{
    protected $fillable = [
        'ma_lk',
        'macskcb',
        'soluonghoso',
        'imported_at',
        'exported_at',
        'import_error',
        'export_error',
        'imported_by',
        'exported_by',
    ];
}

<?php

namespace App\Models\BHYT;

use Illuminate\Database\Eloquent\Model;

class Xml3176Information extends Model
{
    protected $table = 'xml3176_informations';

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
        'submitted_at',
        'submitted_by',
        'submit_error',
        'is_signed',
        'signed_error',
        'submitted_message',
    ];
}

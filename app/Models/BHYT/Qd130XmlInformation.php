<?php

namespace App\Models\BHYT;

use Illuminate\Database\Eloquent\Model;

class Qd130XmlInformation extends Model
{
    protected $fillable = [
        'ma_lk',
        'macskcb', 
        'imported_at', 
        'exported_at',
    ];
}

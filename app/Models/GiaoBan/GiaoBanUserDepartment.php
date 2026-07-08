<?php

namespace App\Models\GiaoBan;

use Illuminate\Database\Eloquent\Model;

class GiaoBanUserDepartment extends Model
{
    protected $table = 'giaoban_user_departments';
    protected $fillable = ['user_id', 'dept_config_id'];
    protected $casts = ['user_id' => 'integer', 'dept_config_id' => 'integer'];
}

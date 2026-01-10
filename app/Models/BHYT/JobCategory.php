<?php

namespace App\Models\BHYT;

use Illuminate\Database\Eloquent\Model;

class JobCategory extends Model
{
    protected $table = 'job_categories';
    protected $fillable = ['job_code', 'job_name'];
}

<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class RoleUser extends Model
{
    protected $connection = 'mysql';
    protected $table = 'role_user';
    public $timestamps = false;
    protected $fillable = ['role_id', 'user_id', 'user_type'];
}

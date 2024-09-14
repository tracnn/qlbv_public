<?php

namespace App;

use Laratrust\Models\LaratrustPermission;

class Permission extends LaratrustPermission
{
    //
    protected $connection = 'mysql';
    protected $fillable = ['name', 'display_name', 'description'];
}

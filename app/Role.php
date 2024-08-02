<?php

namespace App;

use Laratrust\Models\LaratrustRole;

class Role extends LaratrustRole
{
    //
    protected $connection = 'mysql';
    protected $fillable = ['name', 'display_name', 'description'];
}

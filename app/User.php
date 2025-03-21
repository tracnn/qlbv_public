<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laratrust\Traits\LaratrustUserTrait;

class User extends Authenticatable
{
    use LaratrustUserTrait;
    use Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    public function authorizePermissions($permissions)
    {
      return $this->can($permissions) || 
             abort(401, 'This action is unauthorized.');
    }

    public function authorizeRoles($roles)
    {
      return $this->hasRole($roles) || 
             abort(401, 'This action is unauthorized.');
    }
}

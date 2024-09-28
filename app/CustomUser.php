<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laratrust\Traits\LaratrustUserTrait;

use App\UserConfiguration;

class CustomUser extends Authenticatable
{
    use LaratrustUserTrait;
    use Notifiable;

    protected $table = 'acs_user'; // Tên bảng tùy chỉnh
    protected $connection = 'ACS_RS'; // Đảm bảo sử dụng kết nối Oracle

    // Chỉ định tên cột cho 'username' và 'password' nếu chúng khác mặc định
    protected $fillable = ['loginname', 'password'];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * Override để vô hiệu hóa cột remember_token.
     */
    public function setRememberToken($value)
    {
        // Không cần lưu giá trị token
    }

    public function getRememberToken()
    {
        return null; // Không trả về giá trị remember_token
    }

    public function getRememberTokenName()
    {
        return null; // Trả về null vì không có cột remember_token
    }

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

    public function his_employee()
    {
        return $this->hasOne('App\Models\HISPro\HIS_EMPLOYEE', 'loginname', 'loginname');
    }
}

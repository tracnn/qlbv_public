<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use App\Role;
use App\RoleUser;

class CheckFirstLogin
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        $user = Auth::user();

        $userType = Config::get('auth.providers.users.model');
        
        $role = Role::where('name', 'superadministrator')->first();

        // Kiểm tra nếu không có user nào trong bảng role_user được gán role superadministrator
        $existingSuperAdmin = RoleUser::where('role_id', $role->id)
        ->where('user_type', 'App\CustomUser')
        ->exists();

        if (!$existingSuperAdmin && $user) {
            // Phân vai trò superadministrator cho user đầu tiên đăng nhập
            RoleUser::updateOrCreate([
                'user_id' => $user->id, // ID của user
                'role_id' => $role->id, // ID của role
                'user_type' => $userType, // Lấy giá trị từ config
            ]);
        }

        return $next($request);
    }
}
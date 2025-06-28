<?php

namespace App\Menu\Filters;

use Illuminate\Support\Facades\Auth;

class CheckRoleFilter
{
    public function transform($item)
    {
        // Nếu menu có khai báo checkrole
        if (isset($item['checkrole'])) {
            // Nếu người dùng có quyền (hasRole hoặc can)
            if (Auth::check() && (Auth::user()->hasRole($item['checkrole']) || Auth::user()->can($item['checkrole']))) {
                return $item; // Giữ menu này
            }
            // Nếu không có quyền, ẩn menu
            return false;
        }

        // Nếu không có checkrole, giữ nguyên
        return $item;
    }
}
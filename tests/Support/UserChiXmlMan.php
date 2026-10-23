<?php

namespace Tests\Support;

/**
 * User chi qua duoc CheckRole cua quyen xml-man (hasRole/can tra true duy nhat voi 'xml-man'),
 * khong truy van bang roles. Dung de kiem cac route chi danh cho superadministrator tra 403.
 */
class UserChiXmlMan extends \App\User
{
    public function hasRole($role, $team = null, $requireAll = false)
    {
        return $role === 'xml-man';
    }

    public function can($permission, $team = null, $requireAll = false)
    {
        return $permission === 'xml-man';
    }

    public static function tao($id = 2)
    {
        $user = new static();
        $user->id = $id;

        return $user;
    }
}

<?php

namespace Tests\Support;

/**
 * User da dang nhap va qua duoc middleware CheckRole ma KHONG truy van bang roles.
 *
 * VI SAO CAN: CheckRole goi $user->hasRole($role) || $user->can($role). Mot User that lay tu
 * factory()->make() chua he duoc luu, nen Entrust truy van bang roles va khong thay dong nao
 * -> abort(403). Test viet de kiem VALIDATION (mong doi 422) se nhan 403 va do, nhung do vi
 * ly do khong lien quan gi den thu no dinh kiem.
 *
 * Dat o Tests\Support chu khong khai trong long tep test: lop khai trong
 * Xml3176DashboardControllerTest.php ten la Tests\Feature\Dashboard\FakeAdminUser, ma PSR-4
 * anh xa Tests\ -> tests/ nen ten lop khong khop ten tep. Lop do chi ton tai khi PHPUnit tinh
 * co nap dung tep ay; goi tu tep khac la fatal error. Lop nay nam dung cho autoload tim duoc.
 */
class UserGiaCoQuyen extends \App\User
{
    public function hasRole($role, $team = null, $requireAll = false)
    {
        return true;
    }

    public function can($permission, $team = null, $requireAll = false)
    {
        return true;
    }

    /** Tien ich: tao san mot user co id, dung duoc ngay trong actingAs() */
    public static function tao($id = 1)
    {
        $user = new static();
        $user->id = $id;

        return $user;
    }
}

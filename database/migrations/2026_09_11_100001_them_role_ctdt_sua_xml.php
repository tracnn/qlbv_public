<?php

use App\Role;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Migrations\Migration;

/**
 * Role rieng cho viec SUA XML GOC cua chung tu dien tu.
 *
 * VI SAO TACH KHOI xml-man: xml-man la quyen xem danh sach, xem chi tiet va bam gui. Sua
 * noi dung XML la viec khac han - noi dung do duoc base64 THANG vao phong bi roi ky so va
 * POST len cong BHXH, nen nguoi sua dang sua chinh van ban phap ly se nam tren cong. Gop
 * chung hai quyen nghia la moi nguoi xem duoc danh sach deu sua duoc.
 *
 * VI SAO KHONG TU CAP CHO AI: cac migration role truoc (vd them_role_tra_cuu_loi_ho_so) tu
 * gan role moi cho nhung nguoi da co role nguon, vi do la NOI RONG mot quyen san co. O day
 * nguoc lai - day la quyen HEP hon xml-man, cap cho vai nguoi. Tu gan cho ca nhom xml-man
 * se pha dung dieu role nay sinh ra de lam.
 *
 * Quan tri cap tay sau khi trien khai.
 *
 * VI SAO LA ROLE CHU KHONG PHAI PERMISSION: menu di qua AppServiceProvider::filterMenu, ham
 * do CHI kiem hasRole(), khong co nhanh can().
 */
class ThemRoleCtdtSuaXml extends Migration
{
    const TEN = 'ctdt-sua-xml';

    public function up()
    {
        if (Role::where('name', self::TEN)->exists()) {
            return;
        }

        Role::create([
            'name'         => self::TEN,
            'display_name' => 'Sửa XML gốc chứng từ điện tử',
            'description'  => 'Sửa nội dung XML gốc của chứng từ điện tử trước khi ký số và gửi cổng BHXH',
        ]);
    }

    public function down()
    {
        $role = Role::where('name', self::TEN)->first();

        if (!$role) {
            return;
        }

        DB::table('role_user')->where('role_id', $role->id)->delete();

        // KHONG dung $role->delete(): su kien "deleting" cua Laratrust goi quan he users()
        // toi App\CustomUser von nam tren ket noi Oracle ACS_RS, lam Laravel tim bang
        // role_user trong Oracle -> ORA-00942.
        DB::table('roles')->where('id', $role->id)->delete();
    }
}

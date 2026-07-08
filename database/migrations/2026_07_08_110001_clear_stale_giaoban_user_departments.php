<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Trước bản nâng cấp, giaoban_user_departments.user_id được ghi theo App\User (MySQL) id
 * trong khi runtime so với auth()->id() = acs_user.id (HIS) — hai không gian id khác nhau,
 * gán không bao giờ khớp. Ngữ nghĩa đổi sang acs_user.id nên mọi dòng cũ đều vô nghĩa và
 * có thể trùng ngẫu nhiên với acs_user.id của người khác -> dọn sạch để gán lại chuẩn.
 */
class ClearStaleGiaobanUserDepartments extends Migration
{
    public function up()
    {
        DB::table('giaoban_user_departments')->delete();
    }

    public function down()
    {
        // Không khôi phục dữ liệu cũ (đã sai ngữ nghĩa).
    }
}

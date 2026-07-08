<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use App\Services\GiaoBan\NoteSanitizer;

/**
 * Trước khi chuyển ghi chú sang rich text, note/general_note được lưu thô (không sanitize)
 * và chỉ escape khi render. Nay render HTML thô -> làm sạch dữ liệu cũ 1 lần để tránh XSS
 * từ bản ghi lịch sử. Bản ghi mới đã được NoteSanitizer làm sạch ngay khi lưu.
 */
class SanitizeExistingGiaobanNotes extends Migration
{
    public function up()
    {
        foreach (DB::table('giaoban_report_cells')->where('metric_code', 'note')->whereNotNull('note')->get() as $r) {
            DB::table('giaoban_report_cells')->where('id', $r->id)
                ->update(['note' => NoteSanitizer::clean($r->note)]);
        }
        foreach (DB::table('giaoban_reports')->whereNotNull('general_note')->get() as $r) {
            DB::table('giaoban_reports')->where('id', $r->id)
                ->update(['general_note' => NoteSanitizer::clean($r->general_note)]);
        }
    }

    public function down()
    {
        // Không khôi phục (dữ liệu thô cũ không an toàn).
    }
}

<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

/**
 * Phien ban danh muc cho kiem XML3176 (mot dong duy nhat id = 1).
 *
 * Worker hang doi giu danh muc trong bo dem tinh (Xml3176BoDem, NvytLookup,
 * VatPhamKemDvktLookup). Moi lan sua danh muc qua ung dung, phien_ban tang 1; job kiem doc
 * phien_ban luc bat dau va xoa bo dem neu doi - sua danh muc co hieu luc ngay o job ke tiep,
 * khong can khoi dong lai hang doi. ly_do ghi ly do lan tang gan nhat (de tra soat).
 *
 * Chay lai khong loi: chi tao bang/dong khi chua co.
 */
class CreateXml3176PhienBanDanhMucTable extends Migration
{
    const BANG = 'xml3176_phien_ban_danh_muc';

    public function up()
    {
        if (!Schema::hasTable(self::BANG)) {
            Schema::create(self::BANG, function (Blueprint $t) {
                $t->unsignedInteger('id')->primary();
                $t->unsignedBigInteger('phien_ban')->default(0);
                $t->string('ly_do', 255)->nullable();
                $t->timestamp('updated_at')->nullable();
            });
        }

        if (!DB::table(self::BANG)->where('id', 1)->exists()) {
            DB::table(self::BANG)->insert(['id' => 1, 'phien_ban' => 0, 'ly_do' => null, 'updated_at' => null]);
        }
    }

    public function down()
    {
        Schema::dropIfExists(self::BANG);
    }
}

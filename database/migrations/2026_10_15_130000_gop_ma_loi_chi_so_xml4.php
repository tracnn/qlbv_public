<?php

use App\Models\BHYT\Xml3176ErrorCatalog;
use Illuminate\Database\Migrations\Migration;

/**
 * Gop XML4_MA_CHI_SO_EMPTY + XML4_TEN_CHI_SO_EMPTY thanh XML4_CHI_SO_EMPTY (ra soat 15/10/2026:
 * 350.508 dong bao ca hai cho cung mot sai sot).
 *
 * Ma moi lay muc chan cua XML4_MA_CHI_SO_EMPTY nguoi van hanh dang dat (khong co thi canh bao) va
 * EP muc do (updateOrCreate): code chay truoc migrate se tu tao ma moi o muc nghiem trong.
 * Hai ma cu tat (is_check = false), khong xoa de giu lich su loi da ghi.
 */
class GopMaLoiChiSoXml4 extends Migration
{
    const GHI_CHU = 'Đã gộp vào XML4_CHI_SO_EMPTY (15/10/2026)';

    const MA_CU = ['XML4_MA_CHI_SO_EMPTY', 'XML4_TEN_CHI_SO_EMPTY'];

    public function up()
    {
        $cu = Xml3176ErrorCatalog::where('error_code', 'XML4_MA_CHI_SO_EMPTY')->orderBy('id')->first();

        Xml3176ErrorCatalog::updateOrCreate(
            ['xml' => 'XML4', 'error_code' => 'XML4_CHI_SO_EMPTY'],
            [
                'error_name'     => 'Mã/tên chỉ số để trống',
                'description'    => 'Dòng XML4 có giá trị hoặc đơn vị đo nhưng thiếu mã chỉ số và/hoặc tên chỉ số (#1274, #1276).',
                'critical_error' => $cu ? (bool) $cu->critical_error : false,
                'is_check'       => true,
            ]
        );

        $ghiChu = self::GHI_CHU;
        Xml3176ErrorCatalog::whereIn('error_code', self::MA_CU)->get()->each(function ($r) use ($ghiChu) {
            $mo = (string) $r->description;
            $r->is_check = false;
            if (strpos($mo, $ghiChu) === false) {
                $r->description = $mo === '' ? $ghiChu : $mo . ' — ' . $ghiChu;
            }
            $r->save();
        });
    }

    public function down()
    {
        // Chi bat lai ma cu; giu ma moi de khong mat lich su loi da ghi.
        Xml3176ErrorCatalog::whereIn('error_code', self::MA_CU)->update(['is_check' => true]);
    }
}

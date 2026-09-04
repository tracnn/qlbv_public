<?php

use Illuminate\Database\Seeder;
use App\Models\BHYT\Xml3176ErrorCatalog;

/**
 * Nạp sẵn 3 error_code Nhóm B1 (thuộc tính thuốc XML2 đối chiếu danh mục) để
 * cấu hình trước lần scan đầu. Idempotent (updateOrCreate) — chạy lại an toàn.
 */
class Xml3176ErrorCatalogNhomB1Seeder extends Seeder
{
    public function run()
    {
        $rules = [
            ['XML2', 'XML2_INVALID_DUONG_DUNG', 'Đường dùng không khớp danh mục'],
            ['XML2', 'XML2_INVALID_DANG_BAO_CHE', 'Dạng bào chế không khớp danh mục'],
            ['XML2', 'XML2_INVALID_DON_VI_TINH', 'Đơn vị tính không khớp danh mục'],
        ];

        foreach ($rules as $r) {
            list($xml, $code, $name) = $r;
            Xml3176ErrorCatalog::updateOrCreate(
                ['xml' => $xml, 'error_code' => $code],
                ['error_name' => $name, 'description' => $name, 'critical_error' => true, 'is_check' => true]
            );
        }
    }
}

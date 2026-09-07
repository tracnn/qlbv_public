<?php

use Illuminate\Database\Seeder;
use App\Models\BHYT\Xml3176ErrorCatalog;

/**
 * Nap ma loi "ma xa khong thuoc tinh cu tru".
 *
 * is_check = false CO Y: quy tac chi dung khi danh muc da la 2 cap thuan. Bat luc danh
 * muc con cu hoac con lan lon se bao oan hang loat. Nguoi van hanh bat bang o tich
 * "Co kiem tra" o man Danh muc ma loi SAU khi da nap danh muc moi va ra thu mot lo.
 *
 * critical_error = false: co quan bao hiem xep loi ma tinh/xa vao loai BAO LOI, khong
 * phai khoan tru tien - khong chan xuat ho so.
 *
 * Idempotent (updateOrCreate) - chay lai an toan.
 */
class Xml3176ErrorCatalogHanhChinhSeeder extends Seeder
{
    public function run()
    {
        Xml3176ErrorCatalog::updateOrCreate(
            ['xml' => 'XML1', 'error_code' => 'XML1_ADMIN_INFO_ERROR_MAXA_NOT_IN_MATINH'],
            [
                'error_name'     => 'Mã xã không thuộc tỉnh cư trú',
                'description'    => 'Mã xã cư trú không thuộc tỉnh cư trú đã khai trong danh mục đơn vị hành chính',
                'critical_error' => false,
                'is_check'       => false,
            ]
        );
    }
}

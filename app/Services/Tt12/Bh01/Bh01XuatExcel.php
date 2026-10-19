<?php

namespace App\Services\Tt12\Bh01;

use App\Exports\Bh01DongExport;
use App\Models\BHYT\Tt12\Bh01Ban;
use App\Services\ExcelLuong\GhiExcelLuong;

/**
 * Xuat cac dong cua mot ban 01/BH ra .xlsx theo dung dinh dang bo nhap (Bh01NhapExcel) chap nhan,
 * va tep mau chi co tieu de. Tra duong dan tuyet doi tep tam; nguoi goi tai ve roi xoa tep
 * (response()->download(...)->deleteFileAfterSend(true)).
 */
class Bh01XuatExcel
{
    protected $thuMucTam;

    /** @param string|null $thuMucTam goc tep tam; null = storage/app/xuat-tam */
    public function __construct($thuMucTam = null)
    {
        $this->thuMucTam = $thuMucTam;
    }

    public function xuatBan(Bh01Ban $ban)
    {
        return $this->ghi(new Bh01DongExport($ban->id));
    }

    public function tepMau()
    {
        return $this->ghi(new Bh01DongExport(null));
    }

    protected function ghi(Bh01DongExport $export)
    {
        $goc = $this->thuMucTam ?: storage_path('app/xuat-tam');
        if (!is_dir($goc) && !mkdir($goc, 0777, true)) {
            throw new \RuntimeException("Không tạo được thư mục $goc");
        }
        // Tep dich ngay trong thu muc goc (GhiExcelLuong chi xoa thu muc tam rieng cua no, khong dung toi tep dich).
        $dich = $goc . DIRECTORY_SEPARATOR . uniqid('bh01_', true) . '.xlsx';

        (new GhiExcelLuong($goc))->ghi(array($export), $dich);

        return $dich;
    }
}

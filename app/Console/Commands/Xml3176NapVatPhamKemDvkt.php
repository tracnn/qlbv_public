<?php

namespace App\Console\Commands;

use App\Imports\VatPhamKemDvktImport;
use App\Services\Xml3176\VatPhamKemDvktLearner;
use Illuminate\Console\Command;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Nap tep Excel bang tra vat pham -> DVKT me sau khi nguoi dung duyet (dinh dang = tep xuat
 * cua xml3176:hoc-vat-pham-kem-dvkt). Dong moi thanh nguon TAY; cot hieu_luc bat/tat dong.
 * Ma phai o dinh dang Text trong Excel ('40.540' go thanh so se mat so 0 cuoi).
 */
class Xml3176NapVatPhamKemDvkt extends Command
{
    protected $signature = 'xml3176:nap-vat-pham-kem-dvkt {tep : Duong dan tep .xlsx}';

    protected $description = 'Nạp bảng tra VTYT/thuốc -> DVKT mẹ đã duyệt từ Excel';

    public function handle(VatPhamKemDvktLearner $learner)
    {
        $tep = $this->argument('tep');
        if (!is_file($tep)) {
            $this->error("Không tìm thấy tệp: {$tep}");

            return 1;
        }

        $imp = new VatPhamKemDvktImport();
        Excel::import($imp, $tep);
        $kq = $learner->nap($imp->hang);

        $this->info("Thêm {$kq['them']}, cập nhật {$kq['cap_nhat']}, lỗi " . count($kq['loi']));
        foreach ($kq['loi'] as $l) {
            $this->warn("Dòng {$l['dong']}: {$l['ly_do']}");
        }

        // Van xu ly het cac dong, nhung bao that bai de script/CI nhan ra
        return empty($kq['loi']) ? 0 : 1;
    }
}

<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Services\CatalogImportService;

/**
 * Chuyen danh muc don vi hanh chinh sang 2 cap Tinh/Xa.
 *
 * KHONG tu nghi huu va tu kich hoat lai: CatalogImportService da lo viec do san.
 * 'administrative_unit' nam trong CatalogImportService::LAM_MOI_TRON_BO, nen
 * nhanDienTuLoDau() tat is_active cua TOAN BO ban ghi (sau khi da chac tep dung dinh
 * dang), roi ganDangDung() bat lai dung nhung dong co trong tep.
 *
 * Lam tay them mot lan nua khong chi thua ma con SAI: kich hoat lai theo danh sach ma doc
 * tu tep se bat ca nhung dong bi bo qua vi thieu truong bat buoc - do la dong CU truoc sap
 * nhap, mang ma tinh cu, va se song day thanh mot dong 2 cap sai tinh khong phan biet duoc.
 *
 * Viec duy nhat con lai phai lam tay: xoa ma/ten huyen. Tep 2 cap khong mang hai cot do
 * nen import khong ghi de, dong trung ma xa se giu lai gia tri huyen cu.
 */
class HanhChinhChuyen2Cap extends Command
{
    protected $signature = 'hanh-chinh:chuyen-2-cap {tep : Duong dan tep Excel danh muc 2 cap}
                            {--force : Khong hoi xac nhan}';

    protected $description = 'Chuyen danh muc don vi hanh chinh sang 2 cap Tinh/Xa';

    public function handle(CatalogImportService $importService)
    {
        $tep = $this->argument('tep');

        if (!is_file($tep)) {
            $this->error('Khong tim thay tep: ' . $tep);
            return 1;
        }

        $truoc = DB::table('administrative_units')->where('is_active', 1)->count();
        $this->info('Dang hoat dong truoc : ' . $truoc . ' dong');

        if (!$this->option('force')
            && !$this->confirm('Thay toan bo danh muc don vi hanh chinh dang dung bang tep nay?')) {
            $this->warn('Da huy, khong thay doi gi.');
            return 0;
        }

        try {
            DB::transaction(function () use ($importService, $tep) {
                $ketQua = $importService->import($tep);
                $so = $ketQua->toArray();

                // GhiTheoLo NUOT loi muc dong vao ket qua thay vi nem ra. Khong tu kiem thi
                // mot lan nhap hong hoan toan van COMMIT - va vi import da tat is_active cua
                // toan bo ban ghi truoc do, ket qua la bang rong sach ma lenh van bao thanh cong.
                if ($so['so_loi'] > 0 || $so['so_bo_qua'] > 0) {
                    throw new \RuntimeException(
                        'Tep co dong hong nen KHONG doi danh muc. ' . $ketQua->tomTat()
                    );
                }

                if (!$ketQua->coGhi()) {
                    throw new \RuntimeException(
                        'Khong ghi duoc dong nao nen KHONG doi danh muc. ' . $ketQua->tomTat()
                    );
                }

                // Chi con viec nay phai lam tay - xem chu thich dau lop.
                DB::table('administrative_units')
                    ->where('is_active', 1)
                    ->update(['district_code' => null, 'district_name' => null]);

                $this->info($ketQua->tomTat());
            });
        } catch (\Exception $e) {
            $this->error('Da huy va hoan tac toan bo. ' . $e->getMessage());
            return 1;
        }

        $tong   = DB::table('administrative_units')->count();
        $active = DB::table('administrative_units')->where('is_active', 1)->count();
        $soTinh = DB::table('administrative_units')->where('is_active', 1)->distinct()->count('province_code');

        $this->info('---');
        $this->info('Tong dong trong bang : ' . $tong);
        $this->info('Dang hoat dong       : ' . $active);
        $this->info('So tinh hoat dong    : ' . $soTinh);
        $this->info('Da nghi huu          : ' . ($tong - $active));

        return 0;
    }
}

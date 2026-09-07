<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use App\Imports\CatalogChunkImport;
use App\Services\CatalogImportService;

/**
 * Chuyen danh muc don vi hanh chinh sang 2 cap Tinh/Xa.
 *
 * THU TU la van de dung/sai chu khong phai phong cach: dong trung ma xa giua danh muc cu
 * va moi se bi CAP NHAT chu khong chen moi, ma is_active KHONG nam trong mapping nen no
 * giu nguyen gia tri 0 vua dat. Khong co buoc kich hoat lai thi dung nhung xa trung ma se
 * nam im o trang thai nghi huu va bi bao "khong ton tai".
 *
 * Doc ma xa THEO LO (CatalogChunkImport) chu khong dung Excel::toCollection: tep 10.000
 * dong tung lam dinh bo nho 208 MB tren may chu 128 MB.
 */
class HanhChinhChuyen2Cap extends Command
{
    protected $signature = 'hanh-chinh:chuyen-2-cap {tep : Duong dan tep Excel danh muc 2 cap}
                            {--force : Khong hoi xac nhan}';

    protected $description = 'Chuyen danh muc don vi hanh chinh sang 2 cap Tinh/Xa (nghi huu dong cu, nap tep moi)';

    public function handle(CatalogImportService $importService)
    {
        $tep = $this->argument('tep');

        if (!is_file($tep)) {
            $this->error('Khong tim thay tep: ' . $tep);
            return 1;
        }

        $maXa = $this->docMaXa($tep);
        if (empty($maXa)) {
            $this->error('Khong doc duoc ma xa nao tu tep. Kiem tra tep co cot "Ma PX" khong.');
            return 1;
        }

        $truoc = DB::table('administrative_units')->count();
        $this->info('Dang co trong bang : ' . $truoc . ' dong');
        $this->info('Ma xa trong tep    : ' . count($maXa));

        if (!$this->option('force') && !$this->confirm('Nghi huu toan bo dong hien co roi nap tep moi?')) {
            $this->warn('Da huy, khong thay doi gi.');
            return 0;
        }

        DB::transaction(function () use ($importService, $tep, $maXa) {
            // 1. Nghi huu toan bo
            DB::table('administrative_units')->update(['is_active' => 0]);

            // 2. Nap tep moi (upsert theo commune_code)
            $importService->import($tep);

            // 3. Kich hoat lai dung cac ma xa co trong tep, va xoa du lieu huyen con sot
            //    o cac dong bi cap nhat (tep 2 cap khong mang cot huyen nen import khong ghi de).
            foreach (array_chunk($maXa, 1000) as $lo) {
                DB::table('administrative_units')
                    ->whereIn('commune_code', $lo)
                    ->update(['is_active' => 1, 'district_code' => null, 'district_name' => null]);
            }
        });

        $sauTong   = DB::table('administrative_units')->count();
        $sauActive = DB::table('administrative_units')->where('is_active', 1)->count();
        $soTinh    = DB::table('administrative_units')->where('is_active', 1)->distinct()->count('province_code');

        $this->info('---');
        $this->info('Tong dong sau     : ' . $sauTong);
        $this->info('Dang hoat dong    : ' . $sauActive);
        $this->info('So tinh hoat dong : ' . $soTinh);
        $this->info('Da nghi huu       : ' . ($sauTong - $sauActive));

        return 0;
    }

    /**
     * Doc tap hop ma xa tu tep, theo lo. Tim cot bang chinh danh sach bi danh trong
     * mapping - mot nguon su that, khong khai lai o day.
     *
     * @return string[]
     */
    private function docMaXa($tep): array
    {
        $biDanh = (array) config('catalog_import_mapping.administrative_unit.mapping.commune_code', []);
        $viTri = null;
        $ma = [];

        $doc = new CatalogChunkImport(function ($rows, $dongDau, $laLoDau) use (&$viTri, &$ma, $biDanh) {
            if ($laLoDau) {
                foreach ($rows->first() as $i => $ten) {
                    if (in_array(trim((string) $ten), $biDanh, true)) {
                        $viTri = $i;
                        break;
                    }
                }
                $rows = $rows->slice(1);
            }

            if ($viTri === null) {
                return;
            }

            foreach ($rows as $dong) {
                $gt = trim((string) ($dong[$viTri] ?? ''));
                if ($gt !== '') {
                    $ma[$gt] = true;
                }
            }
        });

        Excel::import($doc, $tep);

        return array_keys($ma);
    }
}

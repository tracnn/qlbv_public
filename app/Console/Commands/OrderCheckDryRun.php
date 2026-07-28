<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\OrderCheck\HisOrderSource;
use App\Services\OrderCheck\Support\BhytScope;
use App\Services\OrderCheck\Support\CatalogLookup;
use App\Services\OrderCheck\RuleHandlers\Bhyt\BhytCodeMissingRule;
use App\Services\OrderCheck\RuleHandlers\Bhyt\BhytServiceCatalogRule;
use App\Services\OrderCheck\RuleHandlers\Bhyt\BhytDrugCatalogRule;
use App\Services\OrderCheck\RuleHandlers\Bhyt\BhytSupplyCatalogRule;
use App\Services\OrderCheck\RuleHandlers\Bhyt\BhytServiceNameRule;
use App\Services\OrderCheck\RuleHandlers\Bhyt\BhytDrugNameRule;
use App\Services\OrderCheck\RuleHandlers\Bhyt\BhytSupplyNameRule;
use App\Services\OrderCheck\Support\NgayHieuLuc;
use DB;

/**
 * Dem truoc so vi pham cua bay quy tac danh muc BHYT, KHONG ghi gi.
 *
 * Ly do ton tai: khong do duoc ti le khop that truoc khi trien khai. Bat thang cac quy
 * tac co the do ra hang nghin vi pham ngay dau. Lenh nay cho xem con so truoc.
 *
 * CHAY BAY QUY TAC BAT KE is_active - muc dich la xem truoc.
 */
class OrderCheckDryRun extends Command
{
    protected $signature = 'kiemtraylenh:thu {--ngay=7 : So ngay gan nhat} {--lo=2000 : So phieu toi da}';

    protected $description = 'Chay thu cac quy tac danh muc BHYT, chi dem, khong ghi vi pham';

    public function handle()
    {
        $ngay = max(1, (int) $this->option('ngay'));
        $lo = max(1, (int) $this->option('lo'));

        $this->canhBaoDanhMucRong();
        $this->canhBaoDinhDangNgay();

        $source = app(HisOrderSource::class);
        $handlers = [
            new BhytCodeMissingRule(),
            new BhytServiceCatalogRule(),
            new BhytDrugCatalogRule(),
            new BhytSupplyCatalogRule(),
            new BhytServiceNameRule(),
            new BhytDrugNameRule(),
            new BhytSupplyNameRule(),
        ];

        $tuThoiDiem = (int) (date('Ymd', strtotime('-' . $ngay . ' days')) . '000000');

        $this->info('Quet phieu tu ' . $tuThoiDiem . ', toi da ' . number_format($lo) . ' phieu.');
        $this->info('Doi tuong coi la BHYT: ' . (BhytScope::dsDoiTuong()
            ? implode(',', BhytScope::dsDoiTuong())
            : '(khong loc - moi dong deu duoc xet)'));
        $this->line('');

        $rows = $source->fetchServiceRequests($tuThoiDiem, 0, $lo);
        $reqIds = $rows->pluck('id')->map(function ($v) { return (int) $v; })->all();
        $servicesMap = empty($reqIds) ? [] : $source->fetchServicesByReqIds($reqIds);

        $theoMa = [];
        $theoKhoa = [];
        $soPhieuXet = 0;
        $soPhieuBoQua = 0;

        foreach ($rows as $row) {
            $ctx = $source->buildContext($row, isset($servicesMap[(int) $row->id]) ? $servicesMap[(int) $row->id] : []);

            if (!empty($ctx->services) && !BhytScope::coDongBhyt($ctx->services)) {
                $soPhieuBoQua++;
                continue;
            }

            $soPhieuXet++;

            foreach ($handlers as $h) {
                foreach ($h->check($ctx) as $vi) {
                    $ma = $vi->ruleCode;
                    $theoMa[$ma] = isset($theoMa[$ma]) ? $theoMa[$ma] + 1 : 1;

                    $khoa = $ctx->departmentId === null ? '(khong ro)' : $ctx->departmentId;
                    $theoKhoa[$khoa] = isset($theoKhoa[$khoa]) ? $theoKhoa[$khoa] + 1 : 1;
                }
            }
        }

        $this->line('Phieu doc duoc  : ' . number_format($rows->count()));
        $this->line('  duoc xet      : ' . number_format($soPhieuXet));
        $this->line('  bo qua (khong co dong BHYT): ' . number_format($soPhieuBoQua));
        $this->line('');

        if (empty($theoMa)) {
            $this->info('Khong co vi pham nao.');
        } else {
            arsort($theoMa);
            $this->table(['Ma quy tac', 'So vi pham'], array_map(function ($k, $v) {
                return [$k, number_format($v)];
            }, array_keys($theoMa), $theoMa));

            arsort($theoKhoa);
            $top = array_slice($theoKhoa, 0, 10, true);
            $this->line('');
            $this->table(['Khoa (id)', 'So vi pham'], array_map(function ($k, $v) {
                return [$k, number_format($v)];
            }, array_keys($top), $top));
        }

        $this->line('');
        $this->comment('KHONG ghi dong nao vao order_check_violations.');
    }

    private function canhBaoDanhMucRong()
    {
        $bang = [
            'service_catalogs' => 'ma_dich_vu',
            'medicine_catalogs' => 'ma_thuoc',
            'medical_supply_catalogs' => 'ma_vat_tu',
        ];

        foreach ($bang as $b => $c) {
            if (!(new CatalogLookup($b, $c))->sanSang()) {
                $this->warn('Danh muc ' . $b . ' dang RONG -> quy tac tuong ung se im lang.');
            }
        }
    }

    /**
     * Danh muc ghi tu_ngay tho tu o Excel, khong chuan hoa.
     *
     * Neu ti le doc duoc thap thi loc theo hieu luc tu vo hieu hoa mot cach IM LANG va
     * hanh vi tro ve nhu chua co tinh nang. Phai bao cho nguoi chay biet, khong de troi
     * qua ma khong ai hay.
     */
    private function canhBaoDinhDangNgay()
    {
        $this->line('');
        $this->info('Kiem dinh dang ngay hieu luc cua danh muc (mau 500 dong):');

        foreach (['service_catalogs', 'medicine_catalogs', 'medical_supply_catalogs'] as $b) {
            $mau = DB::table($b)->limit(500)->pluck('tu_ngay');

            if ($mau->isEmpty()) {
                $this->line(sprintf('  %-24s bang RONG', $b));
                continue;
            }

            $doc = 0;

            foreach ($mau as $gt) {
                if (NgayHieuLuc::phanTich($gt) !== null) {
                    $doc++;
                }
            }

            $ti = $doc / $mau->count() * 100;
            $dong = sprintf('  %-24s doc duoc %d/%d (%.1f%%)', $b, $doc, $mau->count(), $ti);

            if ($ti < 50) {
                $this->error($dong . ' - LOC HIEU LUC GAN NHU VO HIEU');
            } else {
                $this->line($dong);
            }
        }

        $this->line('');
    }
}

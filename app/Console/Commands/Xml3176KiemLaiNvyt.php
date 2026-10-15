<?php

namespace App\Console\Commands;

use App\Models\BHYT\Xml3176ErrorResult;
use App\Services\Xml3176\NvytLookup;
use App\Services\Xml3176\NvytRule;
use App\Services\Xml3176CompleteChecker;
use App\Services\Xml3176ErrorService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Kiem lai NVYT hang loat cho ho so da nap: thay 34 ma loi cu bang 3 ma XMLComplete_NVYT_*.
 *
 * Moi ho so xu ly trong mot transaction (xoa ma cu + ma moi, ghi lai tu kiemNvyt) nen dung
 * giua chung roi chay lai van an toan. Duyet theo ma_lk tang dan, khong dung toi export_error
 * hay luong xuat. Loi khac (khong thuoc nhom NVYT) giu nguyen.
 */
class Xml3176KiemLaiNvyt extends Command
{
    const MA_MOI = [
        'XMLComplete_NVYT_THIEU',
        'XMLComplete_NVYT_KHONG_CO_TRONG_DM',
        'XMLComplete_NVYT_SAI_DINH_DANG',
    ];

    protected $signature = 'xml3176:kiem-lai-nvyt
        {--tu= : Y-m-d, theo xml3176_xml1s.created_at}
        {--den= : Y-m-d, theo xml3176_xml1s.created_at}
        {--thu : Chi dem, khong ghi}
        {--lo=500 : So ho so moi lo}';

    protected $description = 'Kiểm lại NVYT hàng loạt: thay 34 mã lỗi cũ bằng 3 mã XMLComplete_NVYT_*';

    public function handle()
    {
        $thu = (bool) $this->option('thu');
        $lo = max(1, (int) $this->option('lo'));
        $tu = $this->option('tu');
        $den = $this->option('den');

        NvytLookup::xoaBoNho();

        $checker = app(Xml3176CompleteChecker::class);
        $service = app(Xml3176ErrorService::class);
        $maXoa = array_merge(NvytRule::MA_CU, self::MA_MOI);

        $soHs = 0;
        $soXoa = 0;
        $moiTheoMa = array_fill_keys(self::MA_MOI, 0);
        $hetChan = [];
        $cuoi = '';

        while (true) {
            $q = DB::table('xml3176_xml1s')
                ->select('ma_lk', DB::raw('MIN(stt) as stt'))
                ->where('ma_lk', '>', $cuoi);
            if ($tu) {
                $q->where('created_at', '>=', $tu . ' 00:00:00');
            }
            if ($den) {
                $q->where('created_at', '<=', $den . ' 23:59:59');
            }
            $dsLo = $q->groupBy('ma_lk')->orderBy('ma_lk')->limit($lo)->get();
            if ($dsLo->isEmpty()) {
                break;
            }

            foreach ($dsLo as $hs) {
                $maLk = (string) $hs->ma_lk;
                $cuoi = $maLk;
                $soHs++;

                if ($thu) {
                    $soXoa += Xml3176ErrorResult::where('ma_lk', $maLk)->whereIn('error_code', $maXoa)->count();
                    foreach ($checker->kiemNvyt($maLk) as $loi) {
                        if (isset($moiTheoMa[$loi->error_code])) {
                            $moiTheoMa[$loi->error_code]++;
                        }
                    }
                    continue;
                }

                DB::transaction(function () use ($maLk, $hs, $maXoa, $checker, $service, &$soXoa, &$moiTheoMa, &$hetChan) {
                    $truoc = Xml3176ErrorResult::where('ma_lk', $maLk)->where('critical_error', true)->exists();

                    $soXoa += Xml3176ErrorResult::where('ma_lk', $maLk)->whereIn('error_code', $maXoa)->delete();

                    $loiMoi = $checker->kiemNvyt($maLk);
                    $service->saveErrors('XMLComplete', $maLk, (int) $hs->stt, $loiMoi);
                    foreach ($loiMoi as $loi) {
                        if (isset($moiTheoMa[$loi->error_code])) {
                            $moiTheoMa[$loi->error_code]++;
                        }
                    }

                    $sau = Xml3176ErrorResult::where('ma_lk', $maLk)->where('critical_error', true)->exists();
                    if ($truoc && !$sau) {
                        $hetChan[] = $maLk;
                    }
                });
            }

            $this->line("Đã xử lý {$soHs} hồ sơ (đến ma_lk {$cuoi})");
        }

        $this->info(($thu ? '[CHỈ ĐẾM] ' : '') . "Số hồ sơ: {$soHs}");
        $this->info('Số dòng mã cũ ' . ($thu ? 'sẽ xoá' : 'đã xoá') . ": {$soXoa}");
        foreach ($moiTheoMa as $ma => $n) {
            $this->info("Lỗi mới {$ma}: {$n}");
        }

        if ($thu) {
            return 0;
        }

        $this->info('Số hồ sơ hết lỗi chặn: ' . count($hetChan));
        if (!empty($hetChan)) {
            $tep = storage_path('logs/kiem-lai-nvyt-' . date('YmdHis') . '.txt');
            file_put_contents($tep, implode(PHP_EOL, $hetChan) . PHP_EOL);
            $this->info("Danh sách ma_lk hết chặn: {$tep}");
        }

        return 0;
    }
}

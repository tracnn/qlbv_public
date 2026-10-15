<?php

namespace App\Console\Commands;

use App\Models\BHYT\Xml3176ErrorCatalog;
use App\Models\BHYT\Xml3176ErrorResult;
use App\Services\Xml3176\NvytLookup;
use App\Services\Xml3176\NvytRule;
use App\Services\Xml3176CompleteChecker;
use App\Services\Xml3176ErrorService;
use Illuminate\Console\Command;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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

        foreach (['tu' => $tu, 'den' => $den] as $ten => $gt) {
            if ($gt && !$this->ngayHopLe($gt)) {
                $this->error("--{$ten} phải có dạng Y-m-d (ví dụ 2026-10-15), nhận được: {$gt}");
                return 1;
            }
        }

        // Ma moi chua co trong danh muc thi getCriticalErrorStatus mac dinh true -> loi se bi tao thanh chan
        if (Xml3176ErrorCatalog::whereIn('error_code', self::MA_MOI)->count() < count(self::MA_MOI)) {
            $this->error('Thiếu mã lỗi NVYT mới trong xml3176_error_catalogs. Hãy chạy migration trước khi kiểm lại.');
            return 1;
        }

        NvytLookup::xoaBoNho();

        $checker = app(Xml3176CompleteChecker::class);
        $service = app(Xml3176ErrorService::class);
        $maXoa = array_merge(NvytRule::MA_CU, self::MA_MOI);

        $soHs = 0;
        $soXoa = 0;
        $moiTheoMa = array_fill_keys(self::MA_MOI, 0);
        $hetChan = 0;
        $cuoi = '';
        $soLoi = 0;
        $soLoiGhiTep = 0;

        // Tep ma_lk het chan mo tu dau, ghi noi tiep ngay sau moi transaction: ngat giua chung khong mat dau vet
        $tep = null;
        if (!$thu) {
            $tep = storage_path('logs/kiem-lai-nvyt-' . date('YmdHis') . '.txt');
            if (@file_put_contents($tep, '') === false) {
                $this->error("Không ghi được tệp ma_lk hết chặn: {$tep}. Dừng, chưa xử lý hồ sơ nào.");
                return 1;
            }
            $this->info("Tệp ma_lk hết chặn: {$tep}");
        }

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

                $xoaTruoc = $soXoa;
                $moiTruoc = $moiTheoMa;
                $daHetChan = false;
                try {
                DB::transaction(function () use ($maLk, $hs, $maXoa, $checker, $service, &$soXoa, &$moiTheoMa, &$daHetChan) {
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
                        $daHetChan = true;
                    }
                });
                } catch (\Throwable $e) {
                    // Transaction da rollback: tra so dem ve truoc ho so nay
                    $soXoa = $xoaTruoc;
                    $moiTheoMa = $moiTruoc;
                    $soLoi++;
                    Log::error('xml3176:kiem-lai-nvyt loi ma_lk ' . $maLk . ': ' . $e->getMessage());
                    $this->error("Lỗi ma_lk {$maLk}: " . $e->getMessage());
                    continue;
                }
                if ($daHetChan) {
                    $hetChan++;
                    if (@file_put_contents($tep, $maLk . PHP_EOL, FILE_APPEND | LOCK_EX) === false) {
                        $soLoiGhiTep++;
                        $this->warn("Không ghi được ma_lk {$maLk} vào tệp {$tep}");
                    }
                }
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

        $this->info('Số hồ sơ hết lỗi chặn: ' . $hetChan);
        $this->info('Số hồ sơ lỗi (đã bỏ qua, chạy lại để xử lý): ' . $soLoi);

        if ($soLoiGhiTep > 0) {
            $this->warn("Số ma_lk hết chặn không ghi được vào tệp: {$soLoiGhiTep}");
        }

        return ($soLoi > 0 || $soLoiGhiTep > 0) ? 1 : 0;
    }

    private function ngayHopLe(string $v): bool
    {
        try {
            $d = Carbon::createFromFormat('Y-m-d', $v);
        } catch (\Exception $e) {
            return false;
        }
        $e = Carbon::getLastErrors();
        return $d && empty($e['warning_count']) && empty($e['error_count']);
    }
}

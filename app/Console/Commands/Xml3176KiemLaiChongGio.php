<?php

namespace App\Console\Commands;

use App\Models\BHYT\Xml3176ErrorCatalog;
use App\Models\BHYT\Xml3176ErrorResult;
use App\Models\BHYT\Xml3176Xml3;
use App\Services\Xml3176ErrorService;
use App\Services\Xml3176Xml3Checker;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Kiem lai hai quy tac thoi gian PTTT cho ho so da nap: thay loi chong gio theo CAP (truoc
 * 15/10/2026, ~1 trieu dong tren 1.950 ho so) bang loi theo DONG + XML3_EXECUTION_TIME_TOO_LONG.
 *
 * Moi ho so mot transaction (xoa hai ma -> kiem lai -> ghi qua bo dem gom co chan tran) nen dung
 * giua chung roi chay lai van an toan. Loi khac giu nguyen.
 */
class Xml3176KiemLaiChongGio extends Command
{
    const MA = ['XML3_OVERLAPPING_SERVICE_EXECUTION', 'XML3_EXECUTION_TIME_TOO_LONG'];

    protected $signature = 'xml3176:kiem-lai-chong-gio
        {--tu= : Y-m-d, theo xml3176_xml1s.created_at}
        {--den= : Y-m-d, theo xml3176_xml1s.created_at}
        {--thu : Chi dem, khong ghi}
        {--lo=500 : So ho so moi lo}';

    protected $description = 'Kiểm lại lỗi chồng giờ / thời gian dài bất thường của PTTT (XML3)';

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

        if (Xml3176ErrorCatalog::whereIn('error_code', self::MA)->distinct()->count('error_code') < count(self::MA)) {
            $this->error('Thiếu mã lỗi trong xml3176_error_catalogs (' . implode(', ', self::MA) . '). Hãy chạy migration trước khi kiểm lại.');
            return 1;
        }

        $checker = app(Xml3176Xml3Checker::class);
        $service = app(Xml3176ErrorService::class);
        $groups = (array) config('xml3176.xml3.overlap_execution_groups', []);

        $soHs = 0;
        $soXoa = 0;
        $moiTheoMa = array_fill_keys(self::MA, 0);
        $soLoi = 0;
        $cuoi = '';

        while (true) {
            $q = DB::table('xml3176_xml1s as x1')
                ->select('x1.ma_lk')
                ->where('x1.ma_lk', '>', $cuoi)
                ->where(function ($w) use ($groups) {
                    $w->whereExists(function ($e) use ($groups) {
                        $e->select(DB::raw(1))->from('xml3176_xml3s as x3')
                            ->whereColumn('x3.ma_lk', 'x1.ma_lk')->whereIn('x3.ma_nhom', $groups);
                    })->orWhereExists(function ($e) {
                        $e->select(DB::raw(1))->from('xml3176_error_results as er')
                            ->whereColumn('er.ma_lk', 'x1.ma_lk')->whereIn('er.error_code', self::MA);
                    });
                });
            if ($tu) {
                $q->where('x1.created_at', '>=', $tu . ' 00:00:00');
            }
            if ($den) {
                $q->where('x1.created_at', '<=', $den . ' 23:59:59');
            }
            $dsLo = $q->groupBy('x1.ma_lk')->orderBy('x1.ma_lk')->limit($lo)->pluck('ma_lk');
            if ($dsLo->isEmpty()) {
                break;
            }

            foreach ($dsLo as $maLk) {
                $maLk = (string) $maLk;
                $cuoi = $maLk;
                $soHs++;

                if ($thu) {
                    $soXoa += Xml3176ErrorResult::where('ma_lk', $maLk)->whereIn('error_code', self::MA)->count();
                    foreach ($this->dongPttt($maLk, $groups) as $dong) {
                        foreach ($checker->kiemThoiGianPttt($dong) as $loi) {
                            $moiTheoMa[$loi->error_code]++;
                        }
                    }
                    continue;
                }

                $xoaTruoc = $soXoa;
                $moiTruoc = $moiTheoMa;
                try {
                    DB::transaction(function () use ($maLk, $groups, $checker, $service, &$soXoa, &$moiTheoMa) {
                        $soXoa += Xml3176ErrorResult::where('ma_lk', $maLk)->whereIn('error_code', self::MA)->delete();

                        $service->batDauGom();
                        try {
                            foreach ($this->dongPttt($maLk, $groups) as $dong) {
                                $loi = $checker->kiemThoiGianPttt($dong);
                                $service->saveErrors('XML3', $maLk, (int) $dong->stt, $loi, $checker->thongTinThem($dong));
                            }
                            foreach ($service->boDemGom() as $d) {
                                $moiTheoMa[$d['error_code']]++;
                            }
                            $service->ketThucGom();
                        } finally {
                            if ($service->dangGom()) {
                                // Loi giua chung: bo bo dem, transaction rollback lo phan da xoa
                                $service->batDauGom();
                                $service->ketThucGom();
                            }
                        }
                    });
                } catch (\Throwable $e) {
                    $soXoa = $xoaTruoc;
                    $moiTheoMa = $moiTruoc;
                    $soLoi++;
                    Log::error('xml3176:kiem-lai-chong-gio loi ma_lk ' . $maLk . ': ' . $e->getMessage());
                    $this->error("Lỗi ma_lk {$maLk}: " . $e->getMessage());
                }
            }

            $this->line("Đã xử lý {$soHs} hồ sơ (đến ma_lk {$cuoi})");
        }

        $this->info(($thu ? '[CHỈ ĐẾM] ' : '') . "Số hồ sơ: {$soHs}");
        $this->info('Số dòng mã cũ ' . ($thu ? 'sẽ xoá' : 'đã xoá') . ": {$soXoa}");
        foreach ($moiTheoMa as $ma => $n) {
            $this->info("Lỗi mới {$ma}: {$n}");
        }

        if (!$thu) {
            $this->info('Số hồ sơ lỗi (đã bỏ qua, chạy lại để xử lý): ' . $soLoi);
        }

        return $soLoi > 0 ? 1 : 0;
    }

    /** Dong XML3 thuoc nhom cau hinh cua ho so, theo id. */
    private function dongPttt(string $maLk, array $groups)
    {
        return Xml3176Xml3::where('ma_lk', $maLk)->whereIn('ma_nhom', $groups)->orderBy('id')->get();
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

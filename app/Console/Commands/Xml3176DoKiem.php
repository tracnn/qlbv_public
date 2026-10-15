<?php

namespace App\Console\Commands;

use App\Models\BHYT\Xml3176ErrorCatalog;
use App\Services\Xml3176\Xml3176CheckTypes;
use App\Services\Xml3176CompleteChecker;
use App\Services\Xml3176ErrorService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Do thoi gian kiem XML3176 va xuat danh sach loi de so sanh truoc/sau khi toi uu.
 *
 * CHI DOC: cau dau tien dat phien MySQL o che do READ ONLY, va ErrorService o che do gom
 * (khong bao gio ketThucGom) nen khong co dong loi nao duoc ghi. Chay giong
 * CheckXml3176TypeJob::handle: moi loai XML nap dong theo lo 500, roi XMLComplete.
 */
class Xml3176DoKiem extends Command
{
    const CO_LO = 500;
    const SO_MAU_SQL = 40;

    protected $signature = 'xml3176:do-kiem
        {--ma-lk-tep= : Tep moi dong mot ma_lk}
        {--so=40 : So ho so lay ngau nhien neu khong co tep}
        {--tu=2026-10-01 : Lay ho so co created_at >= ngay nay}
        {--uu-tien-noi-tru : It nhat mot nua mau la ho so noi tru}
        {--ghi-ma-lk= : Luu danh sach ma_lk da dung}
        {--xuat-loi= : Tep jsonl danh sach loi}
        {--xuat-do= : Tep thong ke}';

    protected $description = 'Đo thời gian kiểm XML3176 và xuất danh sách lỗi (chỉ đọc) để so sánh trước/sau tối ưu';

    /** @var string|null Muc thong ke dang chay, null = ngoai pham vi do */
    private $hienTai;
    /** Co tat khi xong de callback DB::listen (khong go duoc) khong dem tiep */
    private $dangDo = false;
    private $thongKe = [];
    private $mauSql = [];

    public function handle()
    {
        // Phai la cau dau tien: tu day lenh khong the ghi.
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement('SET SESSION TRANSACTION READ ONLY');
        }

        $dsMaLk = $this->layDanhSach();
        if ($dsMaLk === null) {
            return 1;
        }

        if ($this->option('ghi-ma-lk')) {
            $this->ghiTep($this->option('ghi-ma-lk'), implode("\n", $dsMaLk) . "\n");
        }

        $loi = app(Xml3176ErrorService::class);
        $loi->batDauGom(); // khong bao gio ketThucGom -> khong ghi

        $this->thongKe = [];
        $this->mauSql = [];
        $this->theoDoiSql();
        $this->dangDo = true;
        $ngoaiLe = [];

        foreach ($dsMaLk as $maLk) {
            foreach (Xml3176CheckTypes::LOAI as $loai => $cauHinh) {
                $this->doMuc($loai, function () use ($cauHinh, $maLk, $loai) {
                    $checker = app($cauHinh['checker']);
                    $model = $cauHinh['model'];
                    $model::where('ma_lk', $maLk)->chunk(self::CO_LO, function ($dong) use ($checker, $loai) {
                        foreach ($dong as $d) {
                            $this->thongKe[$loai]['dong']++; // dem ca khi checker nem ngoai le
                            $checker->checkErrors($d);
                        }
                    });
                }, $maLk, $ngoaiLe);
            }

            $this->doMuc('XMLComplete', function () use ($maLk) {
                $this->thongKe['XMLComplete']['dong']++;
                app(Xml3176CompleteChecker::class)->checkErrors($maLk);
            }, $maLk, $ngoaiLe);
        }

        $this->hienTai = null;
        $this->dangDo = false;

        if ($this->option('xuat-loi')) {
            $this->ghiTep($this->option('xuat-loi'), $this->dongLoi($loi->boDemGom()));
        }

        if ($this->option('xuat-do')) {
            $this->ghiTep($this->option('xuat-do'), $this->baoCaoDo(count($dsMaLk), $ngoaiLe));
        }

        $this->info('Đã kiểm ' . count($dsMaLk) . ' hồ sơ, ' . $loi->soDongTrongBoDem() . ' lỗi, '
            . count($ngoaiLe) . ' ngoại lệ.');

        if ($ngoaiLe) {
            foreach ($ngoaiLe as $dong) {
                $this->error('ngoai le: ' . $dong);
            }
            return 2; // co ngoai le -> ket qua do/so sanh khong dung duoc
        }

        return 0;
    }

    /** @return array|null null khi tham so sai */
    private function layDanhSach()
    {
        $tep = $this->option('ma-lk-tep');
        if ($tep) {
            if (!is_file($tep)) {
                $this->error('Không thấy tệp: ' . $tep);
                return null;
            }
            $ds = array_map('trim', preg_split('/\R/', file_get_contents($tep)));
            return array_values(array_unique(array_filter($ds, 'strlen')));
        }

        $so = max(1, (int) $this->option('so'));
        $tu = $this->option('tu');
        $goc = function () use ($tu) {
            return DB::table('xml3176_xml1s')->where('created_at', '>=', $tu);
        };

        $ds = [];
        if ($this->option('uu-tien-noi-tru')) {
            $ds = $goc()->whereIn('ma_loai_kcb', config('xml3176.treatment_type_inpatient'))
                ->inRandomOrder()->limit((int) ceil($so / 2))->pluck('ma_lk')->all();
        }

        $con = $so - count($ds);
        if ($con > 0) {
            $truy = $goc();
            if ($ds) {
                $truy->whereNotIn('ma_lk', $ds);
            }
            $ds = array_merge($ds, $truy->inRandomOrder()->limit($con)->pluck('ma_lk')->all());
        }

        return array_values(array_unique($ds));
    }

    private function theoDoiSql()
    {
        DB::listen(function ($q) {
            if (!$this->dangDo || $this->hienTai === null) {
                return;
            }
            $this->thongKe[$this->hienTai]['truyVan']++;
            $this->thongKe[$this->hienTai]['msSql'] += $q->time;

            $mau = preg_replace(
                ['/\s+/', '/\b\d+\b/', "/'[^']*'/", '/\(\?(, ?\?)*\)/'],
                [' ', 'N', "'S'", '(?...)'],
                mb_substr($q->sql, 0, 160)
            );
            $khoa = $this->hienTai . ' | ' . $mau;
            if (!isset($this->mauSql[$khoa])) {
                $this->mauSql[$khoa] = ['lan' => 0, 'ms' => 0];
            }
            $this->mauSql[$khoa]['lan']++;
            $this->mauSql[$khoa]['ms'] += $q->time;
        });
    }

    private function doMuc($muc, callable $chay, $maLk, array &$ngoaiLe)
    {
        if (!isset($this->thongKe[$muc])) {
            $this->thongKe[$muc] = ['dong' => 0, 'truyVan' => 0, 'msSql' => 0, 'msTong' => 0];
        }

        $this->hienTai = $muc;
        $bat = microtime(true);
        try {
            $chay();
        } catch (\Throwable $e) {
            $ngoaiLe[] = "$muc $maLk: " . get_class($e) . ': ' . $e->getMessage();
        }
        $this->thongKe[$muc]['msTong'] += (microtime(true) - $bat) * 1000;
        $this->hienTai = null;
    }

    /** Moi loi mot dong JSON, sap theo (ma_lk, xml, stt, error_code, description). */
    private function dongLoi(array $boDem)
    {
        // Loc y het ketThucGom: bo ma loi bi tat (is_check = false), dung chuanBiGhi cua job.
        $ds = [];
        if ($boDem) {
            $ma = array_values(array_unique(array_column($boDem, 'error_code')));
            $maBiTat = Xml3176ErrorCatalog::whereIn('error_code', $ma)
                ->where('is_check', false)->pluck('error_code')->all();

            $sanSang = Xml3176ErrorService::chuanBiGhi($boDem, $maBiTat, '');
            foreach ($sanSang['nhom'] as $dsDong) {
                foreach ($dsDong as $d) {
                    $ds[] = [
                        'xml'            => $d['xml'],
                        'ma_lk'          => $d['ma_lk'],
                        'stt'            => $d['stt'],
                        'error_code'     => $d['error_code'],
                        'description'    => $d['description'],
                        'critical_error' => $d['critical_error'],
                    ];
                }
            }
        }

        $khoa = function ($d) {
            return [(string) $d['ma_lk'], (string) $d['xml'], (int) $d['stt'], (string) $d['error_code'],
                (string) $d['description'], (int) $d['critical_error']];
        };
        usort($ds, function ($a, $b) use ($khoa) {
            return $khoa($a) <=> $khoa($b);
        });

        $kq = '';
        foreach ($ds as $d) {
            $kq .= json_encode($d, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
        }

        return $kq;
    }

    private function baoCaoDo($soHoSo, array $ngoaiLe)
    {
        $kq = "mau: $soHoSo ho so\n";
        $kq .= sprintf("%-12s %8s %9s %10s %10s %8s\n", 'XML', 'dong', 'truy van', 'ms SQL', 'ms tong', 'q/dong');
        foreach ($this->thongKe as $muc => $s) {
            $kq .= sprintf("%-12s %8d %9d %10.0f %10.0f %8.1f\n", $muc, $s['dong'], $s['truyVan'], $s['msSql'], $s['msTong'],
                $s['dong'] ? $s['truyVan'] / $s['dong'] : 0);
        }

        uasort($this->mauSql, function ($a, $b) {
            return $b['ms'] <=> $a['ms'];
        });
        $kq .= '== top ' . self::SO_MAU_SQL . " mau SQL theo thoi gian\n";
        foreach (array_slice($this->mauSql, 0, self::SO_MAU_SQL, true) as $khoa => $v) {
            $kq .= sprintf("%6d lan %8.0f ms  %s\n", $v['lan'], $v['ms'], $khoa);
        }

        $kq .= '== ngoai le: ' . count($ngoaiLe) . "\n";
        foreach ($ngoaiLe as $dong) {
            $kq .= $dong . "\n";
        }

        return $kq;
    }

    private function ghiTep($duongDan, $noiDung)
    {
        file_put_contents($duongDan, $noiDung, LOCK_EX); // UTF-8, khong BOM
    }
}

<?php

namespace App\Console\Commands;

use App\Models\BHYT\Xml3176ErrorCatalog;
use App\Services\Xml3176\KiemHoSoXml3176;
use App\Services\Xml3176\Xml3176CheckTypes;
use App\Services\Xml3176ErrorService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Do thoi gian kiem XML3176 va xuat danh sach loi de so sanh truoc/sau khi toi uu.
 *
 * CHI DOC: cau dau tien dat phien MySQL o che do READ ONLY, va ErrorService o che do gom
 * (khong bao gio ketThucGom) nen khong co dong loi nao duoc ghi. Kiem bang chinh
 * KiemHoSoXml3176 (duong kiem cua job) o che do khong ghi: moi loai XML lo 500, roi XMLComplete.
 */
class Xml3176DoKiem extends Command
{
    const SO_MAU_SQL = 40;

    protected $signature = 'xml3176:do-kiem
        {--ma-lk-tep= : Tep moi dong mot ma_lk}
        {--so=40 : So ho so lay ngau nhien neu khong co tep}
        {--tu= : Lay ho so co created_at >= ngay nay (mac dinh: 14 ngay truoc hom nay)}
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
    /** @var resource|null Tep tam gom loi da loc, ghi noi sau moi ho so */
    private $tepTam;
    private $duongTepTam;
    private $tongLoi = 0;
    private $boDemToiDa = 0;
    private $nhoDinhKiem = 0;
    private $nhoDinhSapXep = 0;
    /** @var float[] Thoi gian tuong (ms) moi ho so */
    private $thoiGianHoSo = [];

    public function handle()
    {
        // Phai la cau dau tien: tu day lenh khong the ghi.
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement('SET SESSION TRANSACTION READ ONLY');
        }

        // Chi doc; bang chua co (CSDL chua migrate) thi bo qua, khong nem.
        \App\Services\Xml3176\Xml3176PhienBanDanhMuc::dongBoBoDem();

        $dsMaLk = $this->layDanhSach();
        if ($dsMaLk === null) {
            return 1;
        }

        if ($this->option('ghi-ma-lk')) {
            $this->ghiTep($this->option('ghi-ma-lk'), implode("\n", $dsMaLk) . "\n");
        }

        $loi = app(Xml3176ErrorService::class);
        $loi->batDauGom(); // khong bao gio ketThucGom -> khong ghi
        $kiem = app(KiemHoSoXml3176::class);

        $this->thongKe = [];
        $this->mauSql = [];
        $this->tongLoi = 0;
        $this->boDemToiDa = 0;
        $this->thoiGianHoSo = [];
        if ($this->option('xuat-loi')) {
            $this->moTepTam();
        }
        $this->theoDoiSql();
        $this->dangDo = true;
        $ngoaiLe = [];

        try {
        foreach ($dsMaLk as $maLk) {
            $batHoSo = microtime(true);
            // Cung duong kiem voi KiemHoSoXml3176Job, che do khong ghi: moi loai co checker
            // (loai khong co dong chi ton mot truy van rong, nhu lenh cu) roi tong the.
            $kiem->chay($maLk, array_keys(Xml3176CheckTypes::LOAI), function () {
                return true;
            }, false, function ($muc, callable $viec) use ($maLk, &$ngoaiLe) {
                $this->doMuc($muc, $viec, $maLk, $ngoaiLe);
            }, function ($muc) {
                $this->thongKe[$muc]['dong']++; // dem ca khi checker nem ngoai le
            });

            // Xa bo dem theo tung ho so de bo nho/cache giong worker that.
            $this->hienTai = null;
            $this->thoiGianHoSo[] = (microtime(true) - $batHoSo) * 1000;
            $this->boDemToiDa = max($this->boDemToiDa, $loi->soDongTrongBoDem());
            $this->xaBoDem($loi);
        }

        $this->hienTai = null;
        $this->dangDo = false;
        $this->nhoDinhKiem = memory_get_peak_usage(true); // truoc buoc sap xep cuoi

        if ($this->option('xuat-loi')) {
            $this->ghiTep($this->option('xuat-loi'), $this->docTepTam());
        }
        $this->nhoDinhSapXep = memory_get_peak_usage(true);
        } finally {
            $this->dangDo = false;
            $this->donTepTam();
        }

        if ($this->option('xuat-do')) {
            $this->ghiTep($this->option('xuat-do'), $this->baoCaoDo(count($dsMaLk), $ngoaiLe));
        }

        $tg = $this->tomTatThoiGian();
        $this->info('Đã kiểm ' . count($dsMaLk) . ' hồ sơ (mẫu thực tế), ' . $this->tongLoi . ' lỗi, '
            . count($ngoaiLe) . ' ngoại lệ, bộ nhớ đỉnh (kiểm) '
            . round($this->nhoDinhKiem / 1048576, 1) . ' MB, (sắp xếp) '
            . round($this->nhoDinhSapXep / 1048576, 1) . ' MB, thời gian/hồ sơ: tb '
            . round($tg['tb'], 1) . ' ms, p90 ' . round($tg['p90'], 1) . ' ms, max ' . round($tg['max'], 1) . ' ms.');

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
        $tu = $this->option('tu') ?: date('Y-m-d', strtotime('-14 days'));
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

    /** Loc bo dem cua mot ho so (y het ketThucGom), ghi noi moi loi mot dong JSON vao tep tam, xoa bo dem. */
    private function xaBoDem(Xml3176ErrorService $loi)
    {
        $boDem = $loi->boDemGom();
        $loi->xoaBoDemGom();
        if (!$boDem) {
            return;
        }

        // Loc y het ketThucGom: bo ma loi bi tat (is_check = false), dung chuanBiGhi cua job.
        $ma = array_values(array_unique(array_column($boDem, 'error_code')));
        $maBiTat = Xml3176ErrorCatalog::whereIn('error_code', $ma)
            ->where('is_check', false)->pluck('error_code')->all();

        $sanSang = Xml3176ErrorService::chuanBiGhi($boDem, $maBiTat, '');
        $kq = '';
        foreach ($sanSang['nhom'] as $dsDong) {
            foreach ($dsDong as $d) {
                $kq .= json_encode([
                    'xml'            => $d['xml'],
                    'ma_lk'          => $d['ma_lk'],
                    'stt'            => $d['stt'],
                    'error_code'     => $d['error_code'],
                    'description'    => $d['description'],
                    'critical_error' => $d['critical_error'],
                ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
                $this->tongLoi++;
            }
        }
        if ($kq !== '' && $this->tepTam) {
            fwrite($this->tepTam, $kq);
        }
    }

    private function moTepTam()
    {
        $xuat = $this->option('xuat-loi');
        $thuMuc = $xuat ? dirname($xuat) : sys_get_temp_dir();
        if (!is_dir($thuMuc) || !is_writable($thuMuc)) {
            $thuMuc = sys_get_temp_dir();
        }
        $this->duongTepTam = tempnam($thuMuc, 'dokiem_');
        $this->tepTam = fopen($this->duongTepTam, 'wb');
    }

    private function donTepTam()
    {
        if (is_resource($this->tepTam)) {
            fclose($this->tepTam);
        }
        $this->tepTam = null;
        if ($this->duongTepTam && is_file($this->duongTepTam)) {
            @unlink($this->duongTepTam);
        }
        $this->duongTepTam = null;
    }

    /** Doc tep tam, sap theo (ma_lk, xml, stt, error_code, description, critical_error), tra noi dung xuat. */
    private function docTepTam()
    {
        fflush($this->tepTam);
        $dong = [];
        $ds = [];
        $fh = fopen($this->duongTepTam, 'rb');
        while (($l = fgets($fh)) !== false) {
            $l = rtrim($l, "\n");
            if ($l === '') {
                continue;
            }
            $d = json_decode($l, true);
            $dong[] = $l;
            $ds[] = [(string) $d['ma_lk'], (string) $d['xml'], (int) $d['stt'], (string) $d['error_code'],
                (string) $d['description'], (int) $d['critical_error']];
        }
        fclose($fh);

        $thuTu = array_keys($ds);
        usort($thuTu, function ($a, $b) use ($ds) {
            return $ds[$a] <=> $ds[$b];
        });

        $kq = '';
        foreach ($thuTu as $i) {
            $kq .= $dong[$i] . "\n";
        }

        return $kq;
    }

    private function tomTatThoiGian()
    {
        $n = count($this->thoiGianHoSo);
        if (!$n) {
            return ['tb' => 0, 'p90' => 0, 'max' => 0];
        }
        $s = $this->thoiGianHoSo;
        sort($s);

        return [
            'tb'  => array_sum($s) / $n,
            'p90' => $s[(int) min($n - 1, ceil(0.9 * $n) - 1)],
            'max' => $s[$n - 1],
        ];
    }

    private function baoCaoDo($soHoSo, array $ngoaiLe)
    {
        $kq = "mau: $soHoSo ho so\n";
        $kq .= sprintf("bo nho dinh (kiem): %.1f MB\n", $this->nhoDinhKiem / 1048576);
        $kq .= sprintf("bo nho dinh (sap xep): %.1f MB\n", $this->nhoDinhSapXep / 1048576);
        $tg = $this->tomTatThoiGian();
        $kq .= sprintf("wall/ho so: tb %.1f ms, p90 %.1f ms, max %.1f ms\n", $tg['tb'], $tg['p90'], $tg['max']);
        $kq .= "bo dem toi da: {$this->boDemToiDa}\n";
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

<?php

namespace App\Console\Commands;

use App\Services\SinhXml3176\SoSanh\DocTepGiamDinh;
use App\Services\SinhXml3176\SoSanh\SoSanhBang;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Nghiem thu: so tung truong giua tep GIAMDINHHS do HIS sinh (hoac bang xml3176_* cua tien giam dinh, --csdl)
 * va tep do module sinh. Voi --csdl chi `select` bang query builder theo ten bang; khong dung model/service tien giam dinh.
 *
 * - Mot thu muc co the co nhieu tep cung MA_LK (sinh lai): lay tep co thoi gian sua (mtime) moi nhat.
 * - Khoa dong: XML1 = MA_LK; XML2 = MA_LK|MA_THUOC|NGAY_YL; XML3 = MA_LK|MA_DICH_VU|MA_VAT_TU|NGAY_YL.
 *   Neu mot khoa xuat hien nhieu lan o mot ben thi them hau to #1, #2... cho moi lan xuat hien (o ca hai ben)
 *   de cac dong van ghep duoc theo thu tu.
 * - XML4 = MA_LK|MA_DICH_VU|MA_CHI_SO|NGAY_KQ; XML5 = MA_LK|THOI_DIEM_DBLS.
 * - XML6, XML15 = MA_LK|STT; XML9 = MA_LK|SO (SO rong thi NGAY_SINH_CON); bang phang (XML7, 8, 10, 11, 13, 14) = MA_LK.
 * - Chuan hoa truoc khi so: cat khoang trang; so thi so theo gia tri ("15000" == "15000.00", chi cat so 0 o phan
 *   thap phan, "17000" != "17"); the rong == khong co the; rong == "0" chi o cot so (tien/so luong/ty le).
 */
class SinhXml3176SoSanh extends Command
{
    protected $signature = 'sinhxml3176:so-sanh
        {--his= : Thu muc tep HIS goc (loai tru voi --csdl)}
        {--csdl : Phia HIS lay tu bang xml3176_* cua CSDL tien giam dinh (chi doc)}
        {--qlbv= : Thu muc tep module sinh}
        {--ra= : Tep CSV ket qua (mac dinh storage/app/sinhxml3176_so_sanh_<thoi gian>.csv)}
        {--bang=XML1,XML2,XML3,XML4,XML5 : Cac bang can so}';

    protected $description = 'So sanh tung truong giua tep GIAMDINHHS cua HIS va cua module sinh (chi doc tep; --csdl doc them cac bang tien giam dinh xml3176_* o che do chi doc). '
        . 'Nhieu tep cung MA_LK trong mot thu muc: lay tep moi nhat theo mtime. '
        . 'Khoa dong trung trong mot ben: them hau to #1, #2.';

    /** Cot bo qua khi doc bang tien giam dinh. */
    private static $cotKyThuat = ['id', 'created_at', 'updated_at'];

    public function handle()
    {
        $his = (string) $this->option('his');
        $qlbv = (string) $this->option('qlbv');
        $csdl = (bool) $this->option('csdl');
        if ($csdl && $his !== '') {
            $this->error('--his va --csdl loai tru nhau.');

            return 1;
        }
        if (!$csdl && !is_dir($his)) {
            $this->error('Can mot trong hai: --his tro toi thu muc co that, hoac --csdl.');

            return 1;
        }
        if (!is_dir($qlbv)) {
            $this->error('Can --qlbv tro toi thu muc co that.');

            return 1;
        }

        $dsBang = array_values(array_filter(array_map('trim', explode(',', strtoupper((string) $this->option('bang'))))));
        foreach ($dsBang as $b) {
            if (!isset(SoSanhBang::CAC_BANG[$b])) {
                $this->error('Chua ho tro bang ' . $b . ' (chi XML1..XML11, XML13..XML15).');

                return 1;
            }
        }

        $this->coBang = [];
        if ($csdl) {
            if (!$this->coBang('xml3176_informations')) {
                $this->error('Khong co bang xml3176_informations trong CSDL (chua migrate?): khong co gi de so voi --csdl.');

                return 1;
            }
            foreach ($dsBang as $b) {
                $ten = 'xml3176_' . strtolower($b) . 's';
                if (!$this->coBang($ten)) {
                    $this->warn('Khong co bang ' . $ten . ': coi nhu 0 dong o phia HIS (csdl).');
                }
            }
        }

        $ra = (string) $this->option('ra');
        if ($ra === '') {
            $ra = storage_path('app/sinhxml3176_so_sanh_' . date('YmdHis') . '.csv');
        }
        $h = @fopen($ra, 'w');
        if (!$h) {
            $this->error('Khong ghi duoc ' . $ra);

            return 1;
        }
        fwrite($h, "\xEF\xBB\xBF");
        fputcsv($h, ['ma_lk', 'bang', 'khoa_dong', 'truong', 'gia_tri_his', 'gia_tri_qlbv']);

        $chiMucHis = $csdl ? [] : $this->chiMuc($his);
        $chiMucQl = $this->chiMuc($qlbv);
        $tatCa = $csdl
            ? array_keys($chiMucQl)
            : array_unique(array_merge(array_keys($chiMucHis), array_keys($chiMucQl)));
        sort($tatCa, SORT_STRING);

        $soLech = 0;
        foreach ($tatCa as $maLk) {
            $maLk = (string) $maLk;
            if (!isset($chiMucQl[$maLk])) {
                fputcsv($h, [$maLk, 'XML1', $maLk, 'chi_co_his', '', '']);
                $soLech++;
                continue;
            }
            $b = $this->docBang($chiMucQl[$maLk]);
            if ($csdl) {
                if (!$this->coHoSoCsdl($maLk)) {
                    fputcsv($h, [$maLk, 'XML1', $maLk, 'chi_co_qlbv', '', '']);
                    $soLech++;
                    continue;
                }
                $a = $this->docCsdl($maLk, $dsBang, $b);
            } else {
                if (!isset($chiMucHis[$maLk])) {
                    fputcsv($h, [$maLk, 'XML1', $maLk, 'chi_co_qlbv', '', '']);
                    $soLech++;
                    continue;
                }
                $a = $this->docBang($chiMucHis[$maLk]);
            }
            foreach ($dsBang as $loai) {
                $soLech += $this->soBang($h, $maLk, $loai, $a, $b);
            }
        }
        fclose($h);

        $this->info('Ho so HIS' . ($csdl ? ' (csdl)' : '') . ': ' . ($csdl ? 'theo --qlbv' : count($chiMucHis)) . ', ho so module: ' . count($chiMucQl) . ', cho lech: ' . $soLech);
        $this->info('Ket qua: ' . $ra);

        return 0;
    }

    /** MA_LK => duong dan tep moi nhat. */
    private function chiMuc($thuMuc)
    {
        $kq = [];
        $mtime = [];
        foreach ((array) scandir($thuMuc) as $ten) {
            $p = rtrim($thuMuc, '\\/') . DIRECTORY_SEPARATOR . $ten;
            if (!is_file($p) || strtolower(pathinfo($ten, PATHINFO_EXTENSION)) !== 'xml') {
                continue;
            }
            $maLk = $this->maLk($this->docBang($p));
            if ($maLk === null) {
                $this->warn('Bo qua tep khong doc duoc MA_LK: ' . $ten);
                continue;
            }
            $t = filemtime($p);
            if (!isset($kq[$maLk]) || $t > $mtime[$maLk] || ($t === $mtime[$maLk] && strcmp($p, $kq[$maLk]) > 0)) {
                $kq[$maLk] = $p;
                $mtime[$maLk] = $t;
            }
        }

        return $kq;
    }

    /** @return array loai => danh sach dong cua mot tep GIAMDINHHS (tep khong doc duoc thi rong) */
    private function docBang($duongDan)
    {
        $noiDung = @file_get_contents($duongDan);
        $kq = (new DocTepGiamDinh())->doc($noiDung === false ? '' : $noiDung);

        return $kq['bang'];
    }

    /** MA_LK cua XML1; neu khong co XML1 thi lay cua dong dau tien o bang bat ky. */
    private function maLk(array $bang)
    {
        $thuTu = array_merge(['XML1'], array_diff(array_keys($bang), ['XML1']));
        foreach ($thuTu as $loai) {
            if (!isset($bang[$loai]) || !isset(SoSanhBang::CAC_BANG[$loai])) {
                continue;
            }
            foreach ($bang[$loai] as $d) {
                if (isset($d['MA_LK']) && trim($d['MA_LK']) !== '') {
                    return trim($d['MA_LK']);
                }
            }
        }

        return null;
    }

    /** @var array ten bang => co/khong, nho trong mot lan chay */
    private $coBang = [];

    private function coBang($ten)
    {
        if (!isset($this->coBang[$ten])) {
            $this->coBang[$ten] = Schema::hasTable($ten);
        }

        return $this->coBang[$ten];
    }

    private function coHoSoCsdl($maLk)
    {
        return DB::table('xml3176_informations')->where('ma_lk', $maLk)->exists();
    }

    /**
     * Phia HIS lay tu bang tien giam dinh: chi `select`, theo id; cot -> TEN THE VIET HOA;
     * bo cot ky thuat va cot khong co trong dong cua module.
     *
     * @return array loai => danh sach dong
     */
    private function docCsdl($maLk, array $dsBang, array $b)
    {
        $kq = [];
        foreach ($dsBang as $loai) {
            $bang = 'xml3176_' . strtolower($loai) . 's';
            if (!$this->coBang($bang)) {
                $kq[$loai] = [];
                continue;
            }
            $truong = null;
            if (!empty($b[$loai])) {
                $truong = [];
                foreach ($b[$loai] as $d) {
                    foreach (array_keys($d) as $t) {
                        $truong[$t] = true;
                    }
                }
            }
            $dong = [];
            foreach (DB::table($bang)->where('ma_lk', $maLk)->orderBy('id')->get() as $r) {
                $d = [];
                foreach ((array) $r as $cot => $giaTri) {
                    if (in_array($cot, self::$cotKyThuat, true)) {
                        continue;
                    }
                    $t = strtoupper($cot);
                    if ($truong !== null && !isset($truong[$t])) {
                        continue;
                    }
                    $d[$t] = $giaTri === null ? '' : (string) $giaTri;
                }
                $dong[] = $d;
            }
            $kq[$loai] = $dong;
        }

        return $kq;
    }

    /** Ghi cac cho lech cua mot bang; tra ve so dong lech da ghi. */
    private function soBang($h, $maLk, $loai, array $a, array $b)
    {
        $dongA = isset($a[$loai]) ? $a[$loai] : [];
        $dongB = isset($b[$loai]) ? $b[$loai] : [];
        $n = 0;
        foreach (SoSanhBang::so($loai, $dongA, $dongB) as $m) {
            if ($m['kieu'] === 'chi_co_a') {
                fputcsv($h, [$maLk, $loai, $m['khoa'], 'chi_co_his', '', '']);
            } elseif ($m['kieu'] === 'chi_co_b') {
                fputcsv($h, [$maLk, $loai, $m['khoa'], 'chi_co_qlbv', '', '']);
            } else {
                fputcsv($h, [$maLk, $loai, $m['khoa'], $m['truong'], $m['a'], $m['b']]);
            }
            $n++;
        }

        return $n;
    }
}

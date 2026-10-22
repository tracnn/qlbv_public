<?php

namespace App\Console\Commands;

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

    protected $description = 'So sanh tung truong giua tep GIAMDINHHS cua HIS va cua module sinh (chi doc tep). '
        . 'Nhieu tep cung MA_LK trong mot thu muc: lay tep moi nhat theo mtime. '
        . 'Khoa dong trung trong mot ben: them hau to #1, #2.';

    /** loai => [[the goc, the dong], danh sach khoa] */
    private static $bang = [
        'XML1' => [null, ['MA_LK']],
        'XML2' => [['CHITIEU_CHITIET_THUOC', 'CHI_TIET_THUOC'], ['MA_LK', 'MA_THUOC', 'NGAY_YL']],
        'XML3' => [['CHITIEU_CHITIET_DVKT_VTYT', 'CHI_TIET_DVKT'], ['MA_LK', 'MA_DICH_VU', 'MA_VAT_TU', 'NGAY_YL']],
        'XML4' => [['CHITIEU_CHITIET_DICHVUCANLAMSANG', 'CHI_TIET_CLS'], ['MA_LK', 'MA_DICH_VU', 'MA_CHI_SO', 'NGAY_KQ']],
        'XML5' => [['CHITIEU_CHITIET_DIENBIENLAMSANG', 'CHI_TIET_DIEN_BIEN_BENH'], ['MA_LK', 'THOI_DIEM_DBLS']],
    ];

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
            if (!isset(self::$bang[$b])) {
                $this->error('Chua ho tro bang ' . $b . ' (chi XML1..XML5).');

                return 1;
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
            $b = $this->dongTatCa($this->docTep($chiMucQl[$maLk]));
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
                $a = $this->dongTatCa($this->docTep($chiMucHis[$maLk]));
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
            $maLk = $this->maLk($this->docTep($p));
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

    /** @return array loai => chuoi XML con (da bo BOM) */
    private function docTep($duongDan)
    {
        $kq = [];
        $doc = new \DOMDocument();
        $noiDung = @file_get_contents($duongDan);
        if ($noiDung === false || !@$doc->loadXML($noiDung, LIBXML_NONET)) {
            return $kq;
        }
        foreach ($doc->getElementsByTagName('FILEHOSO') as $f) {
            $loai = $this->noiDungThe($f, 'LOAIHOSO');
            $xml = base64_decode(preg_replace('/\s+/', '', $this->noiDungThe($f, 'NOIDUNGFILE')), true);
            if (trim($loai) === '' || $xml === false) {
                continue;
            }
            if (substr($xml, 0, 3) === "\xEF\xBB\xBF") {
                $xml = substr($xml, 3);
            }
            $kq[strtoupper(trim($loai))] = $xml;
        }

        return $kq;
    }

    private function noiDungThe(\DOMElement $cha, $ten)
    {
        foreach ($cha->childNodes as $n) {
            if ($n instanceof \DOMElement && $n->nodeName === $ten) {
                return $n->textContent;
            }
        }

        return '';
    }

    /** MA_LK cua XML1; neu khong co XML1 thi lay cua dong dau tien o bang bat ky. */
    private function maLk(array $bang)
    {
        $thuTu = array_merge(['XML1'], array_diff(array_keys($bang), ['XML1']));
        foreach ($thuTu as $loai) {
            if (!isset($bang[$loai]) || !isset(self::$bang[$loai])) {
                continue;
            }
            foreach ($this->dong($loai, $bang[$loai]) as $d) {
                if (isset($d['MA_LK']) && trim($d['MA_LK']) !== '') {
                    return trim($d['MA_LK']);
                }
            }
        }

        return null;
    }

    /** @return array[] danh sach dong (ten the => noi dung) */
    private function dong($loai, $xml)
    {
        $doc = new \DOMDocument();
        if (!@$doc->loadXML($xml, LIBXML_NONET)) {
            return [];
        }
        $kq = [];
        $goc = self::$bang[$loai][0];
        if ($goc === null) {
            if ($doc->documentElement !== null) {
                $kq[] = $this->truong($doc->documentElement);
            }

            return $kq;
        }
        foreach ($doc->getElementsByTagName($goc[1]) as $e) {
            $kq[] = $this->truong($e);
        }

        return $kq;
    }

    private function truong(\DOMElement $e)
    {
        $d = [];
        foreach ($e->childNodes as $n) {
            if ($n instanceof \DOMElement) {
                $d[$n->nodeName] = $n->textContent;
            }
        }

        return $d;
    }

    /** @return array loai => danh sach dong, tu map loai => chuoi XML con */
    private function dongTatCa(array $xml)
    {
        $kq = [];
        foreach ($xml as $loai => $chuoi) {
            if (isset(self::$bang[$loai])) {
                $kq[$loai] = $this->dong($loai, $chuoi);
            }
        }

        return $kq;
    }

    private function coHoSoCsdl($maLk)
    {
        if (!Schema::hasTable('xml3176_informations')) {
            return false;
        }

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
            if (!Schema::hasTable($bang)) {
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
        $khoaA = $this->danhKhoa($loai, $dongA);
        $khoaB = $this->danhKhoa($loai, $dongB);
        // khoa trung o mot ben thi danh so ca hai ben
        $trung = [];
        foreach ([$khoaA, $khoaB] as $ds) {
            foreach (array_count_values($ds) as $k => $n) {
                if ($n > 1) {
                    $trung[(string) $k] = true;
                }
            }
        }
        $mA = $this->gan($khoaA, $dongA, $trung);
        $mB = $this->gan($khoaB, $dongB, $trung);

        $n = 0;
        foreach (array_unique(array_merge(array_keys($mA), array_keys($mB))) as $khoa) {
            $khoa = (string) $khoa;
            if (!isset($mB[$khoa])) {
                fputcsv($h, [$maLk, $loai, $khoa, 'chi_co_his', '', '']);
                $n++;
                continue;
            }
            if (!isset($mA[$khoa])) {
                fputcsv($h, [$maLk, $loai, $khoa, 'chi_co_qlbv', '', '']);
                $n++;
                continue;
            }
            foreach (array_unique(array_merge(array_keys($mA[$khoa]), array_keys($mB[$khoa]))) as $t) {
                $x = isset($mA[$khoa][$t]) ? trim($mA[$khoa][$t]) : '';
                $y = isset($mB[$khoa][$t]) ? trim($mB[$khoa][$t]) : '';
                if (!$this->bang($x, $y, $this->laCotSo($t))) {
                    fputcsv($h, [$maLk, $loai, $khoa, $t, $x, $y]);
                    $n++;
                }
            }
        }

        return $n;
    }

    private function danhKhoa($loai, array $dong)
    {
        $kq = [];
        foreach ($dong as $d) {
            $p = [];
            foreach (self::$bang[$loai][1] as $t) {
                $p[] = isset($d[$t]) ? trim($d[$t]) : '';
            }
            $kq[] = implode('|', $p);
        }

        return $kq;
    }

    /** khoa (co hau to #n neu trung) => dong */
    private function gan(array $khoa, array $dong, array $trung)
    {
        $kq = [];
        $dem = [];
        foreach ($khoa as $i => $k) {
            if (isset($trung[$k])) {
                $dem[$k] = isset($dem[$k]) ? $dem[$k] + 1 : 1;
                $k .= '#' . $dem[$k];
            }
            $kq[$k] = $dong[$i];
        }

        return $kq;
    }

    /** Cot so: tien, so luong, ty le... Chi o cot nay rong == 0. */
    private function laCotSo($truong)
    {
        return (bool) preg_match('/^(T_|THANH_TIEN|DON_GIA|SO_LUONG|TYLE|MUC_HUONG|SO_NGAY|CAN_NANG)/', $truong);
    }

    private function bang($x, $y, $cotSo)
    {
        $x = $this->chuanHoa($x);
        $y = $this->chuanHoa($y);
        if ($x === $y) {
            return true;
        }

        return $cotSo && in_array($x, ['', '0'], true) && in_array($y, ['', '0'], true);
    }

    /** So thi dua ve dang thap phan chuan ("15000.00" -> "15000"); chi cat so 0 sau dau thap phan; khong dung float. */
    private function chuanHoa($v)
    {
        if (preg_match('/^(-?)(0|[1-9][0-9]*)(?:\.([0-9]+))?$/', $v, $m)) {
            $le = isset($m[3]) ? rtrim($m[3], '0') : '';
            $so = $m[2] . ($le !== '' ? '.' . $le : '');

            return $so === '0' ? '0' : $m[1] . $so;
        }

        return $v;
    }
}

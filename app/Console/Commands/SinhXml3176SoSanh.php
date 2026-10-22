<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

/**
 * Nghiem thu: so tung truong giua tep GIAMDINHHS do HIS sinh va tep do module sinh.
 * Chi doc tep, khong dung CSDL.
 *
 * - Mot thu muc co the co nhieu tep cung MA_LK (sinh lai): lay tep co thoi gian sua (mtime) moi nhat.
 * - Khoa dong: XML1 = MA_LK; XML2 = MA_LK|MA_THUOC|NGAY_YL; XML3 = MA_LK|MA_DICH_VU|MA_VAT_TU|NGAY_YL.
 *   Neu mot khoa xuat hien nhieu lan o mot ben thi them hau to #1, #2... cho moi lan xuat hien (o ca hai ben)
 *   de cac dong van ghep duoc theo thu tu.
 * - Chuan hoa truoc khi so: cat khoang trang; so thi so theo gia tri ("15000" == "15000.00");
 *   the rong == khong co the.
 */
class SinhXml3176SoSanh extends Command
{
    protected $signature = 'sinhxml3176:so-sanh
        {--his= : Thu muc tep HIS goc}
        {--qlbv= : Thu muc tep module sinh}
        {--ra= : Tep CSV ket qua (mac dinh storage/app/sinhxml3176_so_sanh_<thoi gian>.csv)}
        {--bang=XML1,XML2,XML3 : Cac bang can so}';

    protected $description = 'So sanh tung truong giua tep GIAMDINHHS cua HIS va cua module sinh (chi doc tep). '
        . 'Nhieu tep cung MA_LK trong mot thu muc: lay tep moi nhat theo mtime. '
        . 'Khoa dong trung trong mot ben: them hau to #1, #2.';

    /** loai => [[the goc, the dong], danh sach khoa] */
    private static $bang = [
        'XML1' => [null, ['MA_LK']],
        'XML2' => [['CHITIEU_CHITIET_THUOC', 'CHI_TIET_THUOC'], ['MA_LK', 'MA_THUOC', 'NGAY_YL']],
        'XML3' => [['CHITIEU_CHITIET_DVKT_VTYT', 'CHI_TIET_DVKT'], ['MA_LK', 'MA_DICH_VU', 'MA_VAT_TU', 'NGAY_YL']],
    ];

    public function handle()
    {
        $his = (string) $this->option('his');
        $qlbv = (string) $this->option('qlbv');
        if (!is_dir($his) || !is_dir($qlbv)) {
            $this->error('Can --his va --qlbv tro toi thu muc co that.');

            return 1;
        }

        $dsBang = array_values(array_filter(array_map('trim', explode(',', strtoupper((string) $this->option('bang'))))));
        foreach ($dsBang as $b) {
            if (!isset(self::$bang[$b])) {
                $this->error('Chua ho tro bang ' . $b . ' (chi XML1, XML2, XML3).');

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

        $chiMucHis = $this->chiMuc($his);
        $chiMucQl = $this->chiMuc($qlbv);
        $tatCa = array_unique(array_merge(array_keys($chiMucHis), array_keys($chiMucQl)));
        sort($tatCa, SORT_STRING);

        $soLech = 0;
        foreach ($tatCa as $maLk) {
            $maLk = (string) $maLk;
            if (!isset($chiMucQl[$maLk])) {
                fputcsv($h, [$maLk, 'XML1', $maLk, 'chi_co_his', '', '']);
                $soLech++;
                continue;
            }
            if (!isset($chiMucHis[$maLk])) {
                fputcsv($h, [$maLk, 'XML1', $maLk, 'chi_co_qlbv', '', '']);
                $soLech++;
                continue;
            }
            $a = $this->docTep($chiMucHis[$maLk]);
            $b = $this->docTep($chiMucQl[$maLk]);
            foreach ($dsBang as $loai) {
                $soLech += $this->soBang($h, $maLk, $loai, $a, $b);
            }
        }
        fclose($h);

        $this->info('Ho so HIS: ' . count($chiMucHis) . ', ho so module: ' . count($chiMucQl) . ', cho lech: ' . $soLech);
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

    /** Ghi cac cho lech cua mot bang; tra ve so dong lech da ghi. */
    private function soBang($h, $maLk, $loai, array $a, array $b)
    {
        $dongA = isset($a[$loai]) ? $this->dong($loai, $a[$loai]) : [];
        $dongB = isset($b[$loai]) ? $this->dong($loai, $b[$loai]) : [];
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
                if ($this->chuanHoa($x) !== $this->chuanHoa($y)) {
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

    /** So thi dua ve dang thap phan chuan ("15000.00" -> "15000"); khong dung float. Ma co so 0 dau giu nguyen. */
    private function chuanHoa($v)
    {
        if (preg_match('/^(-?)(0|[1-9][0-9]*)(?:\.([0-9]+))?$/', $v, $m)) {
            $le = isset($m[3]) ? rtrim($m[3], '0') : '';
            $so = $m[2] . ($le !== '' ? '.' . $le : '');

            return ($so === '0' ? '' : $m[1]) . $so;
        }

        return $v;
    }
}

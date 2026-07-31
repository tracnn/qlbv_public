<?php

namespace App\Services\GiaoBan;

/**
 * Dung cau truc bang tong hop khoi Dieu tri noi tru cho man trinh chieu.
 *
 * Lop THUAN: khong cham co so du lieu, nhan du lieu qua tham so. Cac quy tac ben duoi
 * (loc chi tieu so, gop cot theo nhan, quy null ve 0, bo tong cot phan tram) neu nam trong
 * JavaScript cua blade thi khong co cach nao kiem ngoai nhin bang mat.
 *
 * Bang la BAO CAO DONG: khong co bo cot co dinh. Moi khoa khai mot bo chi tieu khac nhau
 * (Noi TH khai bn_cu/bn_vao_thang/chuyen_vien, Phu san khai bn_cu/de_thuong/de_mo...), cot
 * la hop cua tat ca, khoa nao khong khai thi o bang 0.
 *
 * KHOA COT THEO NHAN, khong theo ma. Ly do lay tu chinh ghi chu cua man Tong quan trong
 * MetricSchema::COMMON_FIELDS: gom theo nhan thi man khong trong lai khi KHTH doi ma chi
 * tieu. Danh doi da luong: khoa doi NHAN thi cot tach doi.
 */
class BangDieuTri
{
    const KHOI = 'dieu_tri';

    /**
     * @param array $configs mang khai bao khoa; moi phan tu co block_type, display_name,
     *                       sort_order, is_active, metrics
     * @param array $cells   mang o; moi phan tu co dept_config_id, metric_code,
     *                       auto_value, manual_value
     * @return array ['cot' => [...], 'dong' => [...], 'tong' => [...]]
     */
    public static function dung(array $configs, array $cells)
    {
        $khoa = self::locKhoa($configs);

        if (empty($khoa)) {
            return ['cot' => [], 'dong' => [], 'tong' => []];
        }

        $cot = self::dungCot($khoa);

        if (empty($cot)) {
            return ['cot' => [], 'dong' => [], 'tong' => []];
        }

        $tra = self::traO($cells);
        $dong = [];

        foreach ($khoa as $k) {
            $o = [];

            foreach ($cot as $c) {
                $o[] = self::giaTri($k, $c['khoa'], $tra);
            }

            $dong[] = ['ten' => (string) $k['display_name'], 'o' => $o];
        }

        return ['cot' => $cot, 'dong' => $dong, 'tong' => self::tong($cot, $dong)];
    }

    /** Khoa thuoc khoi dieu tri, dang bat, sap theo sort_order */
    protected static function locKhoa(array $configs)
    {
        $ra = [];

        foreach ($configs as $c) {
            $block = isset($c['block_type']) ? $c['block_type'] : '';
            $active = !array_key_exists('is_active', $c) || $c['is_active'];

            if ($block === self::KHOI && $active) {
                $ra[] = $c;
            }
        }

        usort($ra, function ($a, $b) {
            $sa = isset($a['sort_order']) ? (int) $a['sort_order'] : 0;
            $sb = isset($b['sort_order']) ? (int) $b['sort_order'] : 0;

            return $sa === $sb ? 0 : ($sa < $sb ? -1 : 1);
        });

        return $ra;
    }

    /**
     * Cot theo thu tu xuat hien dau tien: duyet khoa theo sort_order, trong moi khoa duyet
     * chi tieu theo thu tu khai.
     *
     * Co `dieu_tri_slide` chon COT chu khong chon o: he mot chi tieu mang nhan do bat co thi
     * cot len slide, va moi khoa khai cung nhan deu do so vao (xem giaTri). Khong khoa nao bat
     * co thi hien tat ca — neu khong, trien khai xong la slide trang cho toi khi KHTH cau hinh.
     */
    protected static function dungCot(array $khoa)
    {
        $locTheoCo = self::coChiTieuBatCo($khoa);
        $cot = [];
        $viTri = [];

        // Vong 1: TAO cot.
        foreach ($khoa as $k) {
            foreach (self::chiTieuSo($k) as $m) {
                $kh = self::khoaCot($m);

                if ($kh === '' || isset($viTri[$kh])) {
                    continue;
                }

                if ($locTheoCo && !self::batCo($m)) {
                    continue;
                }

                $viTri[$kh] = count($cot);
                $cot[] = ['khoa' => $kh, 'nhan' => $kh, 'percent' => true];
            }
        }

        // Vong 2: ha co percent. Cot chi la percent khi MOI khai bao gop vao no deu la percent
        // — ke ca khai bao KHONG bat co, vi gia tri cua no van duoc cong vao cot. Phai tach
        // thanh vong rieng: khoa lam mat tinh percent co the duoc duyet TRUOC khoa tao ra cot.
        foreach ($khoa as $k) {
            foreach (self::chiTieuSo($k) as $m) {
                $kh = self::khoaCot($m);

                if ($kh === '' || !isset($viTri[$kh])) {
                    continue;
                }

                if (!self::laPercent($m)) {
                    $cot[$viTri[$kh]]['percent'] = false;
                }
            }
        }

        return $cot;
    }

    /** Co it nhat mot chi tieu SO bat co -> bat che do chon cot. */
    protected static function coChiTieuBatCo(array $khoa)
    {
        foreach ($khoa as $k) {
            foreach (self::chiTieuSo($k) as $m) {
                if (self::batCo($m)) {
                    return true;
                }
            }
        }

        return false;
    }

    /** Chi tieu dang SO cua mot khoa; loai chi tieu nhap tay kieu van ban */
    protected static function chiTieuSo(array $cfg)
    {
        $ra = [];
        $ds = isset($cfg['metrics']) && is_array($cfg['metrics']) ? $cfg['metrics'] : [];

        foreach ($ds as $m) {
            if (is_array($m) && !self::laChuoi($m)) {
                $ra[] = $m;
            }
        }

        return $ra;
    }

    /** value_type chi co int|decimal|percent|text; chi text la khong phai so */
    protected static function laChuoi(array $m)
    {
        $type = isset($m['type']) ? $m['type'] : '';
        $vt = isset($m['input']['value_type']) ? $m['input']['value_type'] : '';

        return $type === 'manual' && $vt === 'text';
    }

    protected static function laPercent(array $m)
    {
        $vt = isset($m['input']['value_type']) ? $m['input']['value_type'] : '';

        return $vt === 'percent';
    }

    /**
     * Chi tieu duoc danh dau hien tren slide Hoat dong dieu tri.
     *
     * Dung !empty chu khong ===true: MetricValidator ep kieu bool tu nay tro di, nhung ban ghi
     * cu di qua duong khac co the mang 1 hoac '1'.
     */
    protected static function batCo(array $m)
    {
        return !empty($m['dieu_tri_slide']);
    }

    protected static function khoaCot(array $m)
    {
        $nhan = isset($m['name']) ? trim((string) $m['name']) : '';

        return $nhan !== '' ? $nhan : trim((string) (isset($m['code']) ? $m['code'] : ''));
    }

    /** @return array "deptConfigId|metricCode" => float */
    protected static function traO(array $cells)
    {
        $ra = [];

        foreach ($cells as $o) {
            $id = isset($o['dept_config_id']) ? (int) $o['dept_config_id'] : 0;
            $code = isset($o['metric_code']) ? (string) $o['metric_code'] : '';

            $v = array_key_exists('manual_value', $o) && $o['manual_value'] !== null
                ? $o['manual_value']
                : (isset($o['auto_value']) ? $o['auto_value'] : null);

            $ra[$id . '|' . $code] = $v === null ? 0.0 : (float) $v;
        }

        return $ra;
    }

    /**
     * Gia tri cua mot khoa tai mot cot.
     *
     * Mot khoa co the khai NHIEU chi tieu cung nhan (khac ma) - khi do cong don, vi chung
     * da duoc gop thanh mot cot.
     */
    protected static function giaTri(array $cfg, $khoaCot, array $tra)
    {
        $id = isset($cfg['id']) ? (int) $cfg['id'] : 0;
        $tong = 0.0;

        foreach (self::chiTieuSo($cfg) as $m) {
            if (self::khoaCot($m) !== $khoaCot) {
                continue;
            }

            $key = $id . '|' . (isset($m['code']) ? $m['code'] : '');
            $tong += isset($tra[$key]) ? $tra[$key] : 0.0;
        }

        return $tong;
    }

    /** @return array tong tung cot; null o cot percent */
    protected static function tong(array $cot, array $dong)
    {
        $ra = [];

        foreach ($cot as $i => $c) {
            if (!empty($c['percent'])) {
                $ra[] = null;
                continue;
            }

            $t = 0.0;

            foreach ($dong as $d) {
                $t += isset($d['o'][$i]) ? $d['o'][$i] : 0.0;
            }

            $ra[] = $t;
        }

        return $ra;
    }
}

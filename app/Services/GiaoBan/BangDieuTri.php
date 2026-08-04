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
     * Cot sap theo `dieu_tri_order` KHTH khai; cot khong khai xep sau, giu thu tu xuat hien dau
     * tien (duyet khoa theo sort_order, trong moi khoa duyet chi tieu theo thu tu khai). Truoc khi
     * co `dieu_tri_order`, thu tu hoan toan la "xuat hien dau tien" nen nhin tren slide nhu ngau
     * nhien: cot nao len truoc phu thuoc khoa nao tinh co khai nhan do som nhat.
     *
     * Co `dieu_tri_slide` chon COT chu khong chon o: he mot chi tieu mang nhan do bat co thi
     * cot len slide, va moi khoa khai cung nhan deu do so vao (xem giaTri). Khong khoa nao bat
     * co thi hien tat ca — neu khong, trien khai xong la slide trang cho toi khi KHTH cau hinh.
     *
     * Nguong "khong co nao bat -> hien tat ca" (coChiTieuBatCo) chi quet tren $khoa TRUYEN VAO
     * ham nay, khong phai toan vien: neu sau nay co noi tieu thu truyen mot tap $configs da bi
     * loc quyen (nhu GiaoBanController::show() dang lam voi $visibleIds), nguong nay se phu
     * thuoc nguoi xem — hai nguoi xem hai tap khoa khac nhau co the thay bang o hai che do khac
     * nhau (nguoi thi thay tat ca cot, nguoi thi chi thay cot duoc bat co). Hom nay vo hai vi
     * bang_dieu_tri chi duoc man trinh chieu admin-only tieu thu (present() chan !isAdmin).
     */
    protected static function dungCot(array $khoa)
    {
        $locTheoCo = self::coChiTieuBatCo($khoa);
        $trangThaiPercent = [];
        $viTri = [];
        $thuTu = [];
        $order = [];

        foreach ($khoa as $k) {
            foreach (self::chiTieuSo($k) as $m) {
                $kh = self::khoaCot($m);

                if ($kh === '') {
                    continue;
                }

                // Trang thai percent bam theo NHAN, khong bam theo cot: khai bao khong bat co
                // van duoc cong vao cot (xem giaTri) nen van phai ha co, ke ca khi no duoc duyet
                // TRUOC khai bao tao ra cot (cot chua ton tai luc do).
                if (!isset($trangThaiPercent[$kh])) {
                    $trangThaiPercent[$kh] = true;
                }

                if (!self::laPercent($m)) {
                    $trangThaiPercent[$kh] = false;
                }

                // Thu tu cung bam theo NHAN va cung ly do: nhieu khoa khai cung nhan thi lay so
                // NHO NHAT, de KHTH chi phai khai mot cho thay vi nho khai du moi khoa.
                $so = self::thuTuCot($m);

                if ($so !== null && (!isset($order[$kh]) || $so < $order[$kh])) {
                    $order[$kh] = $so;
                }

                if (isset($viTri[$kh]) || ($locTheoCo && !self::batCo($m))) {
                    continue;
                }

                $viTri[$kh] = count($thuTu);
                $thuTu[] = $kh;
            }
        }

        $thuTu = self::sapTheoOrder($thuTu, $viTri, $order);

        $cot = [];

        foreach ($thuTu as $kh) {
            $cot[] = ['khoa' => $kh, 'nhan' => $kh, 'percent' => $trangThaiPercent[$kh]];
        }

        return $cot;
    }

    /**
     * Sap cot theo so thu tu KHTH khai; cot khong khai xep sau, giu thu tu xuat hien dau tien.
     *
     * Sap bang khoa kep (so, vi tri) chu khong chi theo so: usort cua PHP 7.4 KHONG on dinh
     * (chi on dinh tu PHP 8.0), hai cot cung so ma thieu khoa phu thi thu tu nhay giua cac lan
     * chay — dung cai benh dang chua.
     */
    protected static function sapTheoOrder(array $thuTu, array $viTri, array $order)
    {
        if (empty($order)) {
            return $thuTu;
        }

        usort($thuTu, function ($a, $b) use ($viTri, $order) {
            $ca = array_key_exists($a, $order);
            $cb = array_key_exists($b, $order);

            if ($ca !== $cb) {
                return $ca ? -1 : 1;
            }

            if ($ca && $order[$a] !== $order[$b]) {
                return $order[$a] < $order[$b] ? -1 : 1;
            }

            $va = isset($viTri[$a]) ? $viTri[$a] : 0;
            $vb = isset($viTri[$b]) ? $viTri[$b] : 0;

            return $va === $vb ? 0 : ($va < $vb ? -1 : 1);
        });

        return $thuTu;
    }

    /** So thu tu cot KHTH khai, null neu bo trong. */
    protected static function thuTuCot(array $m)
    {
        if (!isset($m['dieu_tri_order'])) {
            return null;
        }

        $v = $m['dieu_tri_order'];

        if ($v === '' || $v === null || is_bool($v) || is_array($v)) {
            return null;
        }

        return is_numeric($v) ? (int) $v : null;
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

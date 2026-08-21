<?php

namespace App\Services\Dashboard;

use Illuminate\Support\Facades\DB;
use App\Models\BHYT\Ctdt\CtdtHoSo;
use App\Services\Ctdt\CtdtDanhSach;
use App\Services\Ctdt\CtdtHangDoi;
use App\Services\Ctdt\CtdtTrangThaiGui;

/**
 * Truy van cho man hinh dashboard chung tu dien tu.
 *
 * NGUYEN TAC: khong lop nay viet lai bat ky luat nao da co noi khac. Dem theo trang thai thi
 * goi lai CtdtDanhSach::truyVan(['trang_thai_gui' => ...]) - ban SQL da duoc mot test tinh
 * chat rang voi CtdtTrangThaiGui::cua(). Viet ban SQL thu ba o day la tao ra mot man hinh
 * bao so khac voi chinh bo loc ngay ben canh no, va nguoi dung se thoi tin ca hai.
 *
 * Chin truy van dem thay vi mot cau GROUP BY: doi lay viec KHONG chep luat. Cac cot
 * so_loi, ma_ket_qua, submit_error co index; checked_at va is_signed thi KHONG - doi
 * schema khong thuoc pham vi task nay, chi ghi dung su that o day.
 */
class CtdtDashboardService
{
    /**
     * Tran so id ho so lay ve truoc khi join sang ctdt_loi trong chatLuong(). Vuot tran la
     * CAT bot ho so cu nhat theo thu tu mac dinh cua truy van, khong duoc cat im lang - so
     * lieu chat luong khi do chi la mot phan, khong con dai dien cho toan bo bo loc.
     */
    const TRAN_ID_HO_SO = 20000;

    /**
     * @param  array $loc cung dinh dang bo loc cua CtdtDanhSach::truyVan()
     * @return array theo_trang_thai, hang_doi, ton_dong
     */
    public function sucKhoe(array $loc)
    {
        return [
            'theo_trang_thai' => $this->demTheoTrangThai($loc),
            'hang_doi'        => $this->doSauHangDoi(),
            'ton_dong'        => $this->tonDong($loc),
        ];
    }

    /**
     * Liet ke DU moi trang thai, ke ca cai dang bang 0.
     *
     * Trang thai bien mat khoi bieu do khi bang 0 la trang thai khong ai theo doi duoc:
     * "Ky so that bai: 0" la mot thong tin, mot cot vang la mot cau hoi.
     */
    protected function demTheoTrangThai(array $loc)
    {
        $ket = [];

        foreach (array_keys(CtdtTrangThaiGui::NHAN) as $ma) {
            $ket[] = [
                'ma'       => $ma,
                'nhan'     => CtdtTrangThaiGui::nhan($ma),
                'so_luong' => (int) CtdtDanhSach::truyVan(
                    array_merge($loc, ['trang_thai_gui' => $ma])
                )->count(),
            ];
        }

        return $ket;
    }

    /**
     * Do sau ba hang doi.
     *
     * Tra null - KHONG phai 0 - khi khong dem duoc. Tra 0 la noi doi: nguoi doc se thay
     * "0 job dang cho" va yen tam, trong khi that ra man hinh khong biet gi ca.
     */
    protected function doSauHangDoi()
    {
        $ten = [CtdtHangDoi::kiem(), CtdtHangDoi::ky(), CtdtHangDoi::gui()];
        $demDuoc = config('queue.default') === 'database';

        $ket = [];

        foreach ($ten as $hd) {
            $soJob = null;

            if ($demDuoc) {
                try {
                    $soJob = (int) DB::table('jobs')->where('queue', $hd)->count();
                } catch (\Exception $e) {
                    // Bang jobs chua migrate - van la "khong dem duoc", khong phai "bang 0".
                    $soJob = null;
                }
            }

            $ket[] = ['ten' => $hd, 'so_job' => $soJob];
        }

        return $ket;
    }

    /**
     * Ho so da nap nhung chua len duoc cong, va cai cu nhat da nam bao nhieu ngay.
     *
     * "Cu nhat da nam bao lau" la cau hoi bat duoc mot hang doi chet CHAM - thu ma bieu do
     * so luong khong bao gio chi ra, vi so luong nap moi ngay van binh thuong.
     */
    protected function tonDong(array $loc)
    {
        // "Chua co ket qua tu cong" phai khop CHINH XAC voi dinh nghia duy nhat cua no o
        // CtdtDanhSach::chuaCoKetQua() (NULL / '' / '0') - khong duoc viet lai o day. Mot
        // ban sao thu ba tung bo sot chuoi '0' va khien khoi ton dong bao thieu, dung luc
        // can chinh xac nhat.
        $soHoSo = (int) CtdtDanhSach::chuaCoKetQua(CtdtDanhSach::truyVan($loc))->count();

        if ($soHoSo === 0) {
            return ['so_ho_so' => 0, 'cu_nhat_ngay' => null];
        }

        $cuNhat = CtdtDanhSach::chuaCoKetQua(CtdtDanhSach::truyVan($loc))
            ->min('imported_at');

        return [
            'so_ho_so'     => $soHoSo,
            'cu_nhat_ngay' => $cuNhat === null
                ? null
                : (int) now()->diffInDays(\Carbon\Carbon::parse($cuNhat)),
        ];
    }

    /**
     * So ho so nap moi ngay, tach theo dich vu.
     *
     * DUNG KHUNG NGAY DAY DU chu khong chi nhung ngay co du lieu: GROUP BY chi tra ve ngay
     * CO ban ghi, va ve thang len bieu do duong thi mot ngay he thong chet hoan toan se bien
     * mat khoi truc - duong noi lien tu ngay truoc sang ngay sau, trong y het nhu khong co
     * gi xay ra.
     *
     * @param  array $loc
     * @return array ngay, chuoi
     */
    public function sanLuong(array $loc)
    {
        // Bu ngay bang CHINH ham ma controller dung, khong tu che moc rieng: ban truoc bu
        // 29 ngay o day trong khi khoangMacDinh() bu 30, va sucKhoe()/chatLuong() khong bu
        // gi ca - ba khoi cua cung mot man hinh doc ba khoang thoi gian khac nhau.
        $loc     = CtdtDanhSach::khoangMacDinh($loc);
        $tuNgay  = $loc['tu_ngay'];
        $denNgay = $loc['den_ngay'];

        $ngay = $this->khungNgay($tuNgay, $denNgay);

        $tho = CtdtDanhSach::truyVan(array_merge($loc, [
                'tu_ngay' => $tuNgay, 'den_ngay' => $denNgay,
            ]))
            ->select(
                'dich_vu',
                DB::raw('DATE(imported_at) as ngay'),
                DB::raw('COUNT(*) as so_luong')
            )
            ->groupBy('dich_vu', DB::raw('DATE(imported_at)'))
            ->get();

        // Gom ve dang [dich_vu][ngay] => so_luong de tra cuu O(1) khi to khung. Cat chuoi
        // ve 10 ky tu dau: SQLite co the tra kem gio, MySQL thi khong - dung chung mot cach
        // cat cho ca hai thay vi doi ham rieng cua tung driver.
        $bang = [];

        foreach ($tho as $dong) {
            $bang[(string) $dong->dich_vu][substr((string) $dong->ngay, 0, 10)] = (int) $dong->so_luong;
        }

        $chuoi = [];

        foreach ($bang as $dichVu => $theoNgay) {
            $duLieu = [];

            foreach ($ngay as $n) {
                $duLieu[] = isset($theoNgay[$n]) ? $theoNgay[$n] : 0;
            }

            $chuoi[] = ['ten' => $dichVu, 'du_lieu' => $duLieu];
        }

        return ['ngay' => $ngay, 'chuoi' => $chuoi];
    }

    /**
     * Moi ngay tu $tuNgay den $denNgay, ke ca ngay khong co du lieu.
     *
     * @return array chuoi 'Y-m-d'
     */
    protected function khungNgay($tuNgay, $denNgay)
    {
        $moc = \Carbon\Carbon::parse($tuNgay)->startOfDay();
        $het = \Carbon\Carbon::parse($denNgay)->startOfDay();
        $ngay = [];

        // Tran cung 366 ngay: mot khoang ngay go nham (vd. 2020-2026) se sinh hang nghin
        // diem va lam trinh duyet dung hinh - tren may chu gioi han PHP 128MB thi con truoc
        // do nua.
        $dem = 0;

        while ($moc->lte($het) && $dem < 366) {
            $ngay[] = $moc->format('Y-m-d');
            $moc = $moc->copy()->addDay();
            $dem++;
        }

        return $ngay;
    }

    /**
     * Ma loi hay gap nhat, va co so nao sai nhieu nhat.
     *
     * TACH muc chan khoi muc canh bao trong tung hang: mot ma loi muc CANH BAO xep tren mot
     * ma loi muc CHAN se dua nguoi ta di sua sai cho - canh bao khong chan ho so nao ca,
     * con chan thi co.
     *
     * @param  array $loc
     * @param  int   $soDong tran so hang, tranh do ca tram ma loi ra bieu do
     * @return array theo_ma_loi, theo_cskcb
     */
    public function chatLuong(array $loc, $soDong = 15)
    {
        // Lay danh sach id ho so tu bo loc man hinh roi moi join sang bang loi: bo loc la bo
        // loc theo HO SO (ngay nap, dich vu, co so), khong ap thang len ctdt_loi duoc.
        //
        // Dung pluck('id')->all() chu KHONG dung whereIn voi truy vasn con truc tiep: CtdtHoSo
        // khong gan select() rieng nen truy van con se tra ve TAT CA cot cua ctdt_ho_so, va
        // MySQL nem loi voi whereIn nhieu cot. Tran cung TRAN_ID_HO_SO phan tu: vuot tran la
        // CAT, khong duoc cat im lang - ghi ro o day de nguoi doc sau khong tuong nham la day du.
        $idHoSo = CtdtDanhSach::truyVan($loc)->limit(self::TRAN_ID_HO_SO)->pluck('id')->all();

        if (empty($idHoSo)) {
            return ['theo_ma_loi' => [], 'theo_cskcb' => []];
        }

        $theoMaLoi = DB::table('ctdt_loi')
            ->whereIn('ho_so_id', $idHoSo)
            ->select(
                'ma_loi',
                'ten_truong',
                'muc_do',
                DB::raw('COUNT(*) as so_luong')
            )
            ->groupBy('ma_loi', 'ten_truong', 'muc_do')
            ->orderByDesc('so_luong')
            ->limit($soDong)
            ->get();

        $theoCskcb = CtdtDanhSach::truyVan($loc)
            ->where('so_loi', '>', 0)
            ->select(
                'macskcb',
                DB::raw('SUM(so_loi) as so_loi'),
                DB::raw('COUNT(*) as so_ho_so')
            )
            ->groupBy('macskcb')
            ->orderByDesc(DB::raw('SUM(so_loi)'))
            ->limit($soDong)
            ->get();

        return [
            'theo_ma_loi' => array_map(function ($d) {
                return [
                    'ma_loi'     => (string) $d->ma_loi,
                    'ten_truong' => (string) $d->ten_truong,
                    'muc_do'     => (string) $d->muc_do,
                    'so_luong'   => (int) $d->so_luong,
                ];
            }, $theoMaLoi->all()),

            'theo_cskcb' => array_map(function ($d) {
                return [
                    'macskcb'  => (string) $d->macskcb,
                    'so_loi'   => (int) $d->so_loi,
                    'so_ho_so' => (int) $d->so_ho_so,
                ];
            }, $theoCskcb->all()),
        ];
    }
}

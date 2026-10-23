<?php

namespace App\Http\Controllers\BHYT;

use App\Http\Controllers\Controller;
use App\Models\SinhXml3176\LanSinh;
use App\Models\SinhXml3176\Moc;
use App\Services\SinhXml3176\CauHinhSinhXml3176;
use App\Services\SinhXml3176\LuuTruXml;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Man theo doi sinh XML3176 truc tiep tu HIS: danh sach (mot dong mot ho so) va lich su cac lan sinh.
 * Chi doc bang sinh_xml3176_lan_sinh, sinh_xml3176_moc va tep luu tru; khong cham HIS.
 * Quyen xml-man nam o nhom route.
 */
class SinhXml3176Controller extends Controller
{
    /** Khoang ngay loc toi da (tinh ca hai dau). */
    const NGAY_TOI_DA = 31;

    /** Tran so dong moi trang DataTables. */
    const DONG_TOI_DA_MOI_TRANG = 100;

    private static $cacKieuNgay = ['sinh', 'ra_vien', 'khoa_vp'];
    private static $cacTrangThai = ['da_ghi', 'bi_loai', 'loi'];
    private static $cacLoaiBan = [CauHinhSinhXml3176::CHINH_THUC, CauHinhSinhXml3176::THONG_TUYEN];

    public function index()
    {
        return view('bhyt.sinh-xml3176.index', [
            'homNay' => date('Y-m-d'),
        ]);
    }

    /** Nguon DataTables: mot dong mot ho so, lan sinh moi nhat tren toan bo lich su. */
    public function fetchData(Request $request)
    {
        $loc = $this->docBoLoc($request, $loi);
        if ($loi !== null) {
            return response()->json(['thanh_cong' => false, 'thong_diep' => $loi], 422);
        }

        $bang = (new LanSinh())->getTable();

        // Ho so hien ra: co it nhat mot dong trong khoang loc (theo kieu ngay, tren chinh dong do).
        $ungVien = function ($q) use ($bang, $loc) {
            $q->select('treatment_id')->from($bang);
            $this->locNgay($q, $loc);
            if ($loc['ma'] !== '') {
                $q->where('treatment_code', $loc['ma']);
            }
        };
        // Dong moi nhat (id lon nhat) cua tung ung vien tren toan bo lich su.
        $moiNhat = function ($q) use ($bang, $ungVien) {
            $q->select(DB::raw('MAX(id)'))->from($bang)->whereIn('treatment_id', $ungVien)->groupBy('treatment_id');
        };

        $goc = LanSinh::query()->whereIn('id', $moiNhat);
        if ($loc['loai_ban'] !== '') {
            $goc->where('loai_ban', $loc['loai_ban']);
        }

        // Tong hop: tinh tren tap da loc loai ban/ma nhung CHUA loc trang thai, de thay du ba trang thai.
        $tongHop = $this->tongHop(clone $goc);

        $dongLoc = clone $goc;
        if ($loc['trang_thai'] !== '') {
            $dongLoc->where('trang_thai', $loc['trang_thai']);
        }
        $tong = (clone $dongLoc)->count();

        $batDau = max(0, (int) $request->input('start', 0));
        $soDong = (int) $request->input('length', 25);
        $soDong = $soDong > 0 ? min($soDong, self::DONG_TOI_DA_MOI_TRANG) : 25;

        $cacDong = $dongLoc->orderBy('id', 'desc')->skip($batDau)->take($soDong)->get();

        // Mot truy van gom cho so lan sinh cua ca trang (tranh N+1).
        $soLan = [];
        if (!$cacDong->isEmpty()) {
            $dem = LanSinh::query()
                ->select('treatment_id', DB::raw('COUNT(*) as so'))
                ->whereIn('treatment_id', $cacDong->pluck('treatment_id')->all())
                ->where('trang_thai', 'da_ghi')
                ->groupBy('treatment_id')
                ->get();
            foreach ($dem as $d) {
                $soLan[(int) $d->treatment_id] = (int) $d->so;
            }
        }

        $cauHinh = CauHinhSinhXml3176::tuConfig();
        $hang = [];
        foreach ($cacDong as $d) {
            $hang[] = [
                'id'             => (int) $d->id,
                'treatment_id'   => (int) $d->treatment_id,
                'treatment_code' => $d->treatment_code,
                'created_at'     => $d->created_at !== null ? $d->created_at->format('Y-m-d H:i:s') : null,
                'loai_ban'       => $d->loai_ban,
                'trang_thai'     => $d->trang_thai,
                'ly_do'          => $d->ly_do,
                'cac_bang'       => $d->cac_bang,
                'thu_muc'        => $d->thu_muc,
                'thu_muc_loai'   => $this->loaiThuMuc($cauHinh, $d->thu_muc),
                'so_lan_sinh'    => isset($soLan[(int) $d->treatment_id]) ? $soLan[(int) $d->treatment_id] : 0,
                'out_time'       => $d->out_time !== null ? (string) $d->out_time : null,
                'fee_lock_time'  => $d->fee_lock_time !== null ? (string) $d->fee_lock_time : null,
                'url_lich_su'    => route('bhyt.sinh-xml3176.lich-su', ['treatmentId' => $d->treatment_id]),
            ];
        }

        return response()->json([
            'draw'            => (int) $request->input('draw', 1),
            'recordsTotal'    => $tong,
            'recordsFiltered' => $tong,
            'data'            => $hang,
            'tong_hop'        => $tongHop,
        ]);
    }

    /** Lich su moi lan sinh (ca hai loai ban) cua mot ho so, theo id tang. */
    public function lichSu($treatmentId)
    {
        $cacDong = LanSinh::where('treatment_id', (int) $treatmentId)->orderBy('id', 'asc')->get();
        if ($cacDong->isEmpty()) {
            abort(404);
        }

        $cauHinh = CauHinhSinhXml3176::tuConfig();
        $luuTru = new LuuTruXml($cauHinh->thuMucLuu());

        $hang = [];
        $truoc = []; // loai_ban => dong da_ghi co XML ngay truoc (con tep tren dia)
        foreach ($cacDong as $i => $d) {
            $trangThaiXml = $this->tinhTrangXml($d, $luuTru);
            $coXem = $trangThaiXml === 'co';
            $soSanh = null;
            if ($coXem && $d->trang_thai === 'da_ghi' && isset($truoc[$d->loai_ban])) {
                $soSanh = $truoc[$d->loai_ban];
            }
            if ($coXem && $d->trang_thai === 'da_ghi') {
                $truoc[$d->loai_ban] = $d->id;
            }
            $hang[] = [
                'stt'          => $i + 1,
                'dong'         => $d,
                'tinh_trang'   => $trangThaiXml,
                'thu_muc_loai' => $this->loaiThuMuc($cauHinh, $d->thu_muc),
                'url_xem'      => $coXem ? url('bhyt/sinh-xml3176/lan/' . $d->id . '/xem') : null,
                'url_so_sanh'  => $soSanh !== null
                    ? url('bhyt/sinh-xml3176/so-sanh') . '?a=' . $soSanh . '&b=' . $d->id : null,
            ];
        }

        return view('bhyt.sinh-xml3176.lich-su', [
            'treatmentId'   => (int) $treatmentId,
            'treatmentCode' => $cacDong->last()->treatment_code,
            'hang'          => $hang,
        ]);
    }

    /** 'co' | 'da_xoa' | 'mat_tep' | 'khong_co' theo ban sao luu tru cua dong. */
    private function tinhTrangXml(LanSinh $d, LuuTruXml $luuTru)
    {
        if ($d->xoa_luu_luc !== null) {
            return 'da_xoa';
        }
        if ($d->tep_luu === null || trim((string) $d->tep_luu) === '') {
            return 'khong_co';
        }

        return $luuTru->coTep($d) ? 'co' : 'mat_tep';
    }

    /** 'that' | 'thu' | 'khac' (thu muc da doi cau hinh) cua thu muc ghi tren dong. */
    private function loaiThuMuc(CauHinhSinhXml3176 $cauHinh, $thuMuc)
    {
        if ($thuMuc === null || trim((string) $thuMuc) === '') {
            return 'khac';
        }
        if (CauHinhSinhXml3176::namTrong($thuMuc, $cauHinh->thuMucThu())
            && CauHinhSinhXml3176::namTrong($cauHinh->thuMucThu(), $thuMuc)) {
            return 'thu';
        }
        foreach ([CauHinhSinhXml3176::CHINH_THUC, CauHinhSinhXml3176::THONG_TUYEN] as $loai) {
            $that = $cauHinh->thuMucThat($loai);
            if (CauHinhSinhXml3176::namTrong($thuMuc, $that) && CauHinhSinhXml3176::namTrong($that, $thuMuc)) {
                return 'that';
            }
        }

        return 'khac';
    }

    /** Dem ho so theo trang thai moi nhat trong tap $goc va hai moc quet. */
    private function tongHop($goc)
    {
        $kq = ['da_ghi' => 0, 'bi_loai' => 0, 'loi' => 0, 'tong' => 0];
        $dem = $goc->select('trang_thai', DB::raw('COUNT(*) as so'))->groupBy('trang_thai')->get();
        foreach ($dem as $d) {
            $so = (int) $d->so;
            if (isset($kq[$d->trang_thai])) {
                $kq[$d->trang_thai] = $so;
            }
            $kq['tong'] += $so;
        }

        $moc = [];
        foreach (self::$cacLoaiBan as $loai) {
            $moc[$loai] = ['moc' => null, 'cap_nhat' => null];
        }
        foreach (Moc::all() as $m) {
            if (isset($moc[$m->loai_ban])) {
                $moc[$m->loai_ban] = [
                    'moc'      => (int) $m->moc,
                    'cap_nhat' => $m->updated_at !== null ? (string) $m->updated_at : null,
                ];
            }
        }
        $kq['moc'] = $moc;

        return $kq;
    }

    /** Ap dieu kien khoang ngay theo kieu ngay len truy van tren bang theo doi. */
    private function locNgay($q, array $loc)
    {
        if ($loc['kieu_ngay'] === 'sinh') {
            $q->where('created_at', '>=', $loc['tu'] . ' 00:00:00')
              ->where('created_at', '<=', $loc['den'] . ' 23:59:59');

            return;
        }
        $cot = $loc['kieu_ngay'] === 'ra_vien' ? 'out_time' : 'fee_lock_time';
        $q->where($cot, '>=', (int) (str_replace('-', '', $loc['tu']) . '000000'))
          ->where($cot, '<=', (int) (str_replace('-', '', $loc['den']) . '235959'));
    }

    /**
     * Doc va kiem tra bo loc. Sai thi $loi la thong diep (tieng Viet) va ham tra ve null.
     */
    private function docBoLoc(Request $request, &$loi)
    {
        $loi = null;
        $homNay = date('Y-m-d');
        $tu = trim((string) $request->input('tu', $homNay));
        $den = trim((string) $request->input('den', $homNay));
        $kieuNgay = (string) $request->input('kieu_ngay', 'sinh');
        $trangThai = (string) $request->input('trang_thai', '');
        $loaiBan = (string) $request->input('loai_ban', '');

        if (!$this->ngayHopLe($tu) || !$this->ngayHopLe($den)) {
            $loi = 'Ngày lọc phải có dạng Y-m-d và hợp lệ.';
        } elseif ($den < $tu) {
            $loi = 'Đến ngày phải không nhỏ hơn Từ ngày.';
        } elseif ((strtotime($den) - strtotime($tu)) / 86400 + 1 > self::NGAY_TOI_DA) {
            $loi = 'Khoảng ngày tối đa ' . self::NGAY_TOI_DA . ' ngày.';
        } elseif (!in_array($kieuNgay, self::$cacKieuNgay, true)) {
            $loi = 'Kiểu ngày không hợp lệ.';
        } elseif ($trangThai !== '' && !in_array($trangThai, self::$cacTrangThai, true)) {
            $loi = 'Trạng thái không hợp lệ.';
        } elseif ($loaiBan !== '' && !in_array($loaiBan, self::$cacLoaiBan, true)) {
            $loi = 'Loại bản không hợp lệ.';
        }
        if ($loi !== null) {
            return null;
        }

        return [
            'tu'         => $tu,
            'den'        => $den,
            'kieu_ngay'  => $kieuNgay,
            'trang_thai' => $trangThai,
            'loai_ban'   => $loaiBan,
            'ma'         => trim((string) $request->input('ma', '')),
        ];
    }

    private function ngayHopLe($ngay)
    {
        if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $ngay, $m)) {
            return false;
        }

        return checkdate((int) $m[2], (int) $m[3], (int) $m[1]);
    }
}

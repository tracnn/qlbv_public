<?php

namespace App\Http\Controllers\BHYT;

use App\Http\Controllers\Controller;
use App\Models\SinhXml3176\LanSinh;
use App\Models\SinhXml3176\Moc;
use App\Services\SinhXml3176\CauHinhSinhXml3176;
use App\Services\SinhXml3176\LuuTruXml;
use App\Services\SinhXml3176\SoSanh\DocTepGiamDinh;
use App\Services\SinhXml3176\SoSanh\SoSanhBang;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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

    /** Bang nhieu dong chia tab con theo 8 ky tu dau cua truong ngay nay. */
    private static $truongNgayNhom = ['XML2' => 'NGAY_YL', 'XML3' => 'NGAY_YL', 'XML4' => 'NGAY_KQ'];

    public function index()
    {
        return view('bhyt.sinh-xml3176.index', [
            'homNay'  => date('Y-m-d'),
            'duocXoa' => $this->duocXoa(),
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
                'chon_so'      => $coXem,
                'url_xem'      => $coXem ? route('bhyt.sinh-xml3176.xem', ['id' => $d->id]) : null,
                'url_so_sanh'  => $soSanh !== null
                    ? route('bhyt.sinh-xml3176.so-sanh', ['a' => $soSanh, 'b' => $d->id]) : null,
            ];
        }

        return view('bhyt.sinh-xml3176.lich-su', [
            'treatmentId'   => (int) $treatmentId,
            'treatmentCode' => $cacDong->last()->treatment_code,
            'hang'          => $hang,
            'duocXoa'       => $this->duocXoa(),
        ]);
    }

    /** Xem XML luu tru cua mot lan sinh: moi bang mot tab (bang co cot), them tab XML tho. */
    public function xem($id)
    {
        $dong = LanSinh::find((int) $id);
        if ($dong === null) {
            abort(404);
        }
        $luuTru = new LuuTruXml(CauHinhSinhXml3176::tuConfig()->thuMucLuu());
        $nap = $this->napLan($dong, $luuTru);

        return view('bhyt.sinh-xml3176.xem', [
            'dong'      => $dong,
            'tinhTrang' => $nap['tinh_trang'],
            'loiTep'    => $nap['loi_tep'],
            'cacTab'    => $nap['tab'],
            'urlTai'    => $nap['tinh_trang'] === 'co' ? route('bhyt.sinh-xml3176.tai', ['id' => $dong->id]) : null,
            'urlLichSu' => route('bhyt.sinh-xml3176.lich-su', ['treatmentId' => $dong->treatment_id]),
        ]);
    }

    /** Tai tep luu tru voi ten goc ten_tep; duong khong hop le, tep mat, da xoa -> 404. */
    public function tai($id)
    {
        $dong = LanSinh::find((int) $id);
        if ($dong === null) {
            abort(404);
        }
        $luuTru = new LuuTruXml(CauHinhSinhXml3176::tuConfig()->thuMucLuu());
        if ($this->tinhTrangXml($dong, $luuTru) !== 'co') {
            abort(404);
        }
        $duong = $luuTru->duongTuyetDoi($dong->tep_luu);
        if ($duong === null || !is_file($duong)) {
            abort(404);
        }
        $ten = basename(str_replace('\\', '/', (string) $dong->ten_tep));
        if ($ten === '' || $ten === '.' || $ten === '..') {
            $ten = 'sinh_xml3176_' . $dong->id . '.xml';
        }

        return response()->download($duong, $ten, ['Content-Type' => 'application/xml']);
    }

    /**
     * Xoa ban luu tru cua cac lan sinh da chon (chi superadministrator, chan o route).
     * Tra {xoa, bo_qua}: bo_qua gom dong khong ton tai, chua co tep, da xoa hoac duong khong hop le.
     */
    public function xoa(Request $request)
    {
        $ids = $request->input('ids');
        if (!is_array($ids) || empty($ids)) {
            return response()->json(['thanh_cong' => false, 'thong_diep' => 'Chưa chọn lần sinh nào.'], 422);
        }
        $sach = [];
        foreach ($ids as $id) {
            if (is_scalar($id) && ctype_digit((string) $id)) {
                $sach[(int) $id] = true;
            }
        }
        $ids = array_keys($sach);
        if (empty($ids)) {
            return response()->json(['thanh_cong' => false, 'thong_diep' => 'Mã lần sinh không hợp lệ.'], 422);
        }

        $luuTru = new LuuTruXml(CauHinhSinhXml3176::tuConfig()->thuMucLuu());
        $userId = (int) Auth::id();
        $xoa = 0;
        foreach (LanSinh::whereIn('id', $ids)->get() as $dong) {
            if ($luuTru->xoa($dong, $userId)) {
                $xoa++;
            }
        }
        Log::info('sinhxml3176 xoa ban luu tru: nguoi dung #' . $userId . ' xoa ' . $xoa . ' tep (da chon ' . count($ids) . ' lan sinh)');

        return response()->json(['xoa' => $xoa, 'bo_qua' => count($ids) - $xoa]);
    }

    /** So tep luu tru con lai cua cac lan sinh sinh ra trong mot ngay (de hop xac nhan hien con so). */
    public function demTheoNgay(Request $request)
    {
        $ngay = $this->docNgayXoa($request->query('ngay'));
        if ($ngay === null) {
            return response()->json(['thanh_cong' => false, 'thong_diep' => 'Ngày phải có dạng Y-m-d và hợp lệ.'], 422);
        }

        return response()->json(['ngay' => $ngay, 'so_tep' => $this->truyVanTheoNgay($ngay)->count()]);
    }

    /** Xoa moi ban luu tru cua cac lan sinh co created_at trong ngay (thu muc ngay luu tru). */
    public function xoaTheoNgay(Request $request)
    {
        $ngay = $this->docNgayXoa($request->input('ngay'));
        if ($ngay === null) {
            return response()->json(['thanh_cong' => false, 'thong_diep' => 'Ngày phải có dạng Y-m-d và hợp lệ.'], 422);
        }

        @set_time_limit(0);
        $luuTru = new LuuTruXml(CauHinhSinhXml3176::tuConfig()->thuMucLuu());
        $userId = (int) Auth::id();
        $xoa = 0;
        $boQua = 0;
        $truyVan = $this->truyVanTheoNgay($ngay);
        // Doi trang theo id: dong da xoa roi khoi tap loc nen khong dung offset.
        $idCuoi = 0;
        do {
            $lo = (clone $truyVan)->where('id', '>', $idCuoi)->orderBy('id')->take(200)->get();
            foreach ($lo as $dong) {
                $idCuoi = $dong->id;
                $luuTru->xoa($dong, $userId, false) ? $xoa++ : $boQua++;
            }
        } while (!$lo->isEmpty());
        // Don thu muc ngay mot lan o cuoi thay vi quet lai sau tung tep.
        $luuTru->donThuMucNgay(str_replace('-', '', $ngay));
        Log::info('sinhxml3176 xoa ban luu tru theo ngay ' . $ngay . ': nguoi dung #' . $userId . ' xoa ' . $xoa . ' tep, bo qua ' . $boQua);

        return response()->json(['ngay' => $ngay, 'xoa' => $xoa, 'bo_qua' => $boQua]);
    }

    /** Cac dong con ban luu tru chua danh dau xoa, sinh ra trong ngay Y-m-d. */
    private function truyVanTheoNgay($ngay)
    {
        return LanSinh::query()
            ->where('created_at', '>=', $ngay . ' 00:00:00')
            ->where('created_at', '<=', $ngay . ' 23:59:59')
            ->whereNotNull('tep_luu')
            ->where('tep_luu', '<>', '')
            ->whereNull('xoa_luu_luc');
    }

    /** Ngay Y-m-d hop le (chan "..", ky tu la), nguoc lai null. */
    private function docNgayXoa($ngay)
    {
        $ngay = is_string($ngay) ? $ngay : '';

        return $this->ngayHopLe($ngay) ? $ngay : null;
    }

    /** Nguoi dung hien tai co quyen xoa (cung kiem tra voi middleware CheckRole). */
    private function duocXoa()
    {
        $user = Auth::user();

        return $user !== null && ($user->hasRole('superadministrator') || $user->can('superadministrator'));
    }

    /** So hai lan sinh cung ho so, theo tung bang. */
    public function soSanh(Request $request)
    {
        $a = $this->thamSoChuoi($request, 'a', '');
        $b = $this->thamSoChuoi($request, 'b', '');
        if (!ctype_digit($a) || !ctype_digit($b)) {
            abort(422, 'Tham số a và b phải là mã hai lần sinh.');
        }
        $lanA = LanSinh::find((int) $a);
        $lanB = LanSinh::find((int) $b);
        if ($lanA === null || $lanB === null) {
            abort(404);
        }
        if ((int) $lanA->treatment_id !== (int) $lanB->treatment_id) {
            abort(422, 'Hai lần sinh phải thuộc cùng một hồ sơ.');
        }
        if ((int) $lanA->id === (int) $lanB->id) {
            abort(422, 'Hãy chọn hai lần sinh khác nhau.');
        }

        $luuTru = new LuuTruXml(CauHinhSinhXml3176::tuConfig()->thuMucLuu());
        $napA = $this->napLan($lanA, $luuTru);
        $napB = $this->napLan($lanB, $luuTru);

        $ketQua = [];
        if ($napA['doc'] !== null && $napB['doc'] !== null && $napA['loi_tep'] === null && $napB['loi_tep'] === null) {
            $coMat = array_merge(array_keys($napA['doc']['bang']), array_keys($napA['doc']['loi']),
                array_keys($napB['doc']['bang']), array_keys($napB['doc']['loi']));
            foreach (array_keys(SoSanhBang::CAC_BANG) as $loai) {
                if (!in_array($loai, $coMat, true)) {
                    continue;
                }
                $loiA = isset($napA['doc']['loi'][$loai]) ? $this->chuLoiBang($napA['doc']['loi'][$loai]) : null;
                $loiB = isset($napB['doc']['loi'][$loai]) ? $this->chuLoiBang($napB['doc']['loi'][$loai]) : null;
                $dongA = isset($napA['doc']['bang'][$loai]) ? $napA['doc']['bang'][$loai] : [];
                $dongB = isset($napB['doc']['bang'][$loai]) ? $napB['doc']['bang'][$loai] : [];
                $muc = [];
                if ($loiA === null && $loiB === null) {
                    // Bang chi co o mot ben: so voi ben kia rong thi moi dong la chi_co_*
                    $muc = SoSanhBang::so($loai, $dongA, $dongB);
                }
                $ketQua[$loai] = [
                    'loi_a' => $loiA,
                    'loi_b' => $loiB,
                    'chi_a' => !isset($napB['doc']['bang'][$loai]) && $loiB === null,
                    'chi_b' => !isset($napA['doc']['bang'][$loai]) && $loiA === null,
                    'nhom'  => $this->nhomTheoKhoa($muc),
                ];
            }
        }

        return view('bhyt.sinh-xml3176.so-sanh', [
            'lanA'       => $lanA,
            'lanB'       => $lanB,
            'khacLoai'   => $lanA->loai_ban !== $lanB->loai_ban,
            'tinhTrangA' => $napA['tinh_trang'],
            'tinhTrangB' => $napB['tinh_trang'],
            'loiTepA'    => $napA['loi_tep'],
            'loiTepB'    => $napB['loi_tep'],
            'ketQua'     => $ketQua,
            'urlLichSu'  => route('bhyt.sinh-xml3176.lich-su', ['treatmentId' => $lanA->treatment_id]),
        ]);
    }

    /**
     * Doc ban sao cua mot lan sinh. 'tinh_trang' co|da_xoa|mat_tep|khong_co; 'doc' ket qua DocTepGiamDinh (khi doc duoc);
     * 'loi_tep' thong diep neu chinh tep ngoai khong doc duoc; 'tab' du lieu tung bang cho view xem.
     */
    private function napLan(LanSinh $dong, LuuTruXml $luuTru)
    {
        $kq = ['tinh_trang' => $this->tinhTrangXml($dong, $luuTru), 'doc' => null, 'loi_tep' => null, 'tab' => []];
        if ($kq['tinh_trang'] !== 'co') {
            return $kq;
        }
        $noiDung = $luuTru->doc($dong);
        if ($noiDung === null) {
            // tep vua mat giua hai lan kiem tra
            $kq['tinh_trang'] = 'mat_tep';

            return $kq;
        }
        $doc = (new DocTepGiamDinh())->doc($noiDung);
        $kq['doc'] = $doc;
        if (isset($doc['loi']['TEP'])) {
            $kq['loi_tep'] = 'Tệp lưu trữ không đọc được (không phải XML GIAMDINHHS hợp lệ).';

            return $kq;
        }
        $kq['tab'] = $this->dungTab($doc);

        return $kq;
    }

    /** Du lieu hien thi tung bang theo thu tu XML1..XML15: cot, dong (nhom theo ngay), XML tho, loi. */
    private function dungTab(array $doc)
    {
        $cacTab = [];
        foreach (array_keys(SoSanhBang::CAC_BANG) as $loai) {
            $coBang = isset($doc['bang'][$loai]);
            $coLoi = isset($doc['loi'][$loai]);
            if (!$coBang && !$coLoi) {
                continue;
            }
            $tab = [
                'loai'    => $loai,
                'loi'     => $coLoi ? $this->chuLoiBang($doc['loi'][$loai]) : null,
                'phang'   => SoSanhBang::CAC_BANG[$loai][0] === null,
                'cot'     => [],
                'nhom'    => [],
                'so_dong' => 0,
                'tho'     => isset($doc['tho'][$loai]) ? $doc['tho'][$loai] : null,
            ];
            if ($coBang) {
                $dong = $doc['bang'][$loai];
                $tab['so_dong'] = count($dong);
                $cot = [];
                foreach ($dong as $d) {
                    foreach (array_keys($d) as $k) {
                        $cot[$k] = true;
                    }
                }
                $tab['cot'] = array_keys($cot);
                if (!$tab['phang'] && isset(self::$truongNgayNhom[$loai])) {
                    $truong = self::$truongNgayNhom[$loai];
                    foreach ($dong as $d) {
                        $ngay = isset($d[$truong]) ? substr(trim($d[$truong]), 0, 8) : '';
                        $tab['nhom'][$ngay][] = $d;
                    }
                    ksort($tab['nhom'], SORT_STRING);
                } else {
                    $tab['nhom'] = ['' => $dong];
                }
            }
            $cacTab[] = $tab;
        }

        return $cacTab;
    }

    /** Cac muc so sanh gom theo khoa dong (giu thu tu): kieu va (neu khac) danh sach truong A -> B. */
    private function nhomTheoKhoa(array $muc)
    {
        $nhom = [];
        foreach ($muc as $m) {
            $nhom[$m['khoa']]['kieu'] = $m['kieu'];
            if ($m['kieu'] === 'khac') {
                $nhom[$m['khoa']]['truong'][] = ['ten' => $m['truong'], 'a' => $m['a'], 'b' => $m['b']];
            }
        }

        return $nhom;
    }

    /** Thong diep loi cua mot bang (DocTepGiamDinh khong dau) sang tieng Viet. */
    private function chuLoiBang($loi)
    {
        return strpos((string) $loi, 'Khong giai ma') === 0
            ? 'Không giải mã được NOIDUNGFILE của bảng này (base64 hỏng).'
            : 'XML con của bảng này không đọc được.';
    }

    /** 'co' | 'da_xoa' | 'mat_tep' | 'khong_co' | 'chua_cau_hinh' theo ban sao luu tru cua dong. */
    private function tinhTrangXml(LanSinh $d, LuuTruXml $luuTru)
    {
        if ($d->xoa_luu_luc !== null) {
            return 'da_xoa';
        }
        if ($d->tep_luu === null || trim((string) $d->tep_luu) === '') {
            return 'khong_co';
        }
        if (!$luuTru->daCauHinh()) {
            return 'chua_cau_hinh';
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
        $tu = $this->thamSoChuoi($request, 'tu', $homNay);
        $den = $this->thamSoChuoi($request, 'den', $homNay);
        $kieuNgay = $this->thamSoChuoi($request, 'kieu_ngay', 'sinh');
        $trangThai = $this->thamSoChuoi($request, 'trang_thai', '');
        $loaiBan = $this->thamSoChuoi($request, 'loai_ban', '');

        $dangMang = false;
        foreach (['tu', 'den', 'kieu_ngay', 'trang_thai', 'loai_ban', 'ma'] as $khoa) {
            $dangMang = $dangMang || is_array($request->input($khoa));
        }

        if ($dangMang) {
            $loi = 'Tham số lọc phải là chuỗi.';
        } elseif (!$this->ngayHopLe($tu) || !$this->ngayHopLe($den)) {
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
            'ma'         => $this->thamSoChuoi($request, 'ma', ''),
        ];
    }

    /** Tham so dang chuoi da trim; tham so dang mang (tu[]=x) coi nhu rong de bo loc tra 422 thay vi 500. */
    private function thamSoChuoi(Request $request, $khoa, $macDinh)
    {
        $v = $request->input($khoa, $macDinh);

        return is_scalar($v) ? trim((string) $v) : '';
    }

    private function ngayHopLe($ngay)
    {
        if (!preg_match('/^(\d{4})-(\d{2})-(\d{2})\z/', $ngay, $m)) {
            return false;
        }

        return checkdate((int) $m[2], (int) $m[3], (int) $m[1]);
    }
}

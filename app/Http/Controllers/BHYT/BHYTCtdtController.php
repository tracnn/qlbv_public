<?php

namespace App\Http\Controllers\BHYT;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

use App\Services\BHYT\DanhSachCoSo;
use Yajra\Datatables\Datatables;
use App\Services\Ctdt\CtdtDanhSach;
use App\Services\Ctdt\CtdtTrangThaiGui;
use App\Services\Ctdt\CtdtImporter;
use App\Models\BHYT\Ctdt\CtdtChungTu;
use App\Services\Ctdt\CtdtDetailTabs;
use App\Services\Ctdt\CtdtNhanTruong;
use App\Services\Ctdt\CtdtLoaiRegistry;
use App\Services\Ctdt\CtdtLuuHoSo;
use App\Models\BHYT\Ctdt\CtdtHoSo;
use App\Models\BHYT\Ctdt\CtdtLoi;
use App\Services\Ctdt\CtdtQuyetDinhGui;
use App\Services\Ctdt\CtdtXepHangKyGui;
use App\Services\Ctdt\CtdtDuDieuKienGui;
use App\Services\Ctdt\CtdtSuaXml;
use App\Exports\CtdtDanhSachExport;
use App\Exports\CtdtLoiExport;
use App\Exports\CtdtNhatKyGuiExport;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Ba man hinh cua module chung tu dien tu: danh sach, nap tep, chi tiet.
 *
 * Controller giu MONG: moi quyet dinh nghiep vu nam o cac lop thuan trong App\Services\Ctdt
 * de kiem duoc ma khong can dung HTTP. Bai hoc tu BHYTXml3176Controller (753 dong).
 */
class BHYTCtdtController extends Controller
{
    /**
     * Cac cot duoc phep di ra ngoai trong JSON cua DataTables.
     *
     * Danh sach TRANG, khong phai danh sach den: quan he them vao truy van sau nay se khong
     * tu dong lot ra ngoai. CtdtDatatableCotTest khoa danh sach nay khop dung cac cot blade
     * doc, va chan noi_dung_goc (XML nguyen van, hang chuc KB moi dong) lot vao.
     */
    const DATATABLE_COLUMNS = [
        'co_the_gui',
        'ma_ho_so', 'dich_vu', 'macskcb', 'ho_ten', 'ma_the', 'so_cccd', 'ma_bhxh',
        'so_chung_tu', 'so_loi',
        'is_signed', 'trang_thai_gui', 'trang_thai_nhan', 'ma_gd', 'ma_ket_qua',
        'thoi_gian_tiep_nhan', 'imported_at', 'imported_by', 'action',
    ];

    /**
     * Tran so ho so mot luot gui hang loat.
     *
     * Kiem o SERVER chu khong chi o JavaScript: gioi han phia trinh duyet chi la tien nghi
     * cho nguoi dung, ai goi thang endpoint se lot qua het. Va endpoint nay xep hang GUI
     * THAT len cong BHXH.
     */
    const TRAN_GUI_NHIEU = 50;

    public function index()
    {
        return view('bhyt.ctdt.index', [
            'danhSachCoSo'      => DanhSachCoSo::danhSach(),
            'danhSachLoai'      => $this->danhSachLoai(),
            'danhSachTrangThai' => CtdtTrangThaiGui::NHAN,
        ]);
    }

    /**
     * Chin loai chung tu de do vao o loc. Lay tu registry chu khong go tay: go tay thi mot
     * ngay nao do registry them loai moi ma o loc khong co, va khong ai phat hien.
     *
     * @return array LOAIHOSO => nhan tab
     */
    private function danhSachLoai()
    {
        $ds = [];

        foreach (\App\Services\Ctdt\CtdtLoaiRegistry::tatCa() as $ma => $lop) {
            $ds[$ma] = $lop::tenTab();
        }

        return $ds;
    }

    public function fetchData(Request $request)
    {
        $truyVan = CtdtDanhSach::truyVan($this->locTu($request));

        // Nap kem chung tu: cot ho ten / ma the lay tu chung tu DAU TIEN cua ho so. Khong
        // nap kem thi moi dong la mot truy van rieng - 200 dong thanh 201 truy van.
        $truyVan->with(['chungTu' => function ($q) {
            $q->select('id', 'ho_so_id', 'ma_the', 'so_cccd', 'ma_bhxh', 'ho_ten')
                ->orderBy('id');
        }]);

        return Datatables::of($truyVan)
            ->addColumn('ho_ten', function ($hoSo) {
                $dau = $hoSo->chungTu->first();

                return $dau ? (string) $dau->ho_ten : '';
            })
            ->addColumn('ma_the', function ($hoSo) {
                $dau = $hoSo->chungTu->first();

                return $dau ? (string) $dau->ma_the : '';
            })
            ->addColumn('so_cccd', function ($hoSo) {
                $dau = $hoSo->chungTu->first();

                return $dau ? (string) $dau->so_cccd : '';
            })
            ->addColumn('ma_bhxh', function ($hoSo) {
                $dau = $hoSo->chungTu->first();

                return $dau ? (string) $dau->ma_bhxh : '';
            })
            ->addColumn('co_the_gui', function ($hoSo) {
                // Ai duoc tich chon la do SERVER quyet dinh, khong phai JavaScript suy tu
                // cot trang_thai_gui. Suy o trinh duyet la chep lai luat cua
                // CtdtDuDieuKienGui lan thu hai, va hai ban se lech: o tich hien ra cho mot
                // ho so ma endpoint se tu choi, hoac nguoc lai - o tich BIEN MAT cho ho so
                // that su gui duoc, va khong ai hieu vi sao.
                return CtdtDuDieuKienGui::cua($hoSo) === CtdtDuDieuKienGui::DUOC;
            })
            ->addColumn('trang_thai_gui', function ($hoSo) {
                return CtdtTrangThaiGui::cua($hoSo);
            })
            ->addColumn('trang_thai_nhan', function ($hoSo) {
                return CtdtTrangThaiGui::nhan(CtdtTrangThaiGui::cua($hoSo));
            })
            ->addColumn('action', function ($hoSo) {
                return $hoSo->ma_ho_so;
            })
            ->only(self::DATATABLE_COLUMNS)
            ->rawColumns([])
            ->make(true);
    }

    /**
     * Doc bo loc tu request. MOT ban duy nhat cho ca man hinh lan tep xuat.
     *
     * Hai ban se lech nhau: them mot o loc ma quen ben kia se lam tep xuat khac han man
     * hinh, va khong co dau hieu gi cho toi luc ai do ngoi doi chieu tung dong voi ban cua
     * BHXH.
     *
     * @return array
     */
    private function locTu(Request $request)
    {
        return [
            'tu_ngay'        => $request->input('tu_ngay'),
            'den_ngay'       => $request->input('den_ngay'),
            'dich_vu'        => $request->input('dich_vu'),
            'loai_ho_so'     => $request->input('loai_ho_so'),
            'macskcb'        => $request->input('macskcb'),
            'imported_by'    => $request->input('imported_by'),
            'tim'            => $request->input('tim'),
            // Laravel 5.5 KHONG co Request::boolean() (them tu 5.8). DataTables gui '0'/'1'
            // dang chuoi, ma (bool) '0' la TRUE - o loc se luon bat.
            'chi_con_loi'    => filter_var($request->input('chi_con_loi'), FILTER_VALIDATE_BOOLEAN),
            'trang_thai_gui' => $request->input('trang_thai_gui'),
        ];
    }

    /**
     * Bo loc cho MOT LAN XUAT: giong locTu() nhung bat buoc co khoang ngay.
     *
     * Goi thang URL xuat khong kem tham so - hoac man hinh chua kip dat ctdtRange - se quet
     * TOAN BO ctdt_ho_so. Mot cu bam ra mot truy van toan bang tren may chu 128MB/120s la
     * mot yeu cau chet giua chung, khong phai mot tep xuat lon.
     *
     * @return array
     */
    private function locXuatTu(Request $request)
    {
        return CtdtDanhSach::khoangMacDinh($this->locTu($request));
    }

    /**
     * Phan ngay cua mot moc loc, dung dat ten tep.
     *
     * Man hinh gui dang 'YYYY-MM-DD HH:mm:ss'. Ghep nguyen van vao ten tep cho ra dau ':' -
     * ky tu KHONG hop le trong ten tep tren Windows, tuc tep khong luu duoc.
     *
     * @return string
     */
    private function phanNgay($moc)
    {
        return substr(trim((string) $moc), 0, 10);
    }

    /** Tai danh sach ho so theo dung bo loc dang xem */
    public function xuatDanhSach(Request $request)
    {
        $ten = 'chung-tu-dien-tu-' . now()->format('Ymd-His') . '.xlsx';

        return Excel::download(
            new CtdtDanhSachExport(CtdtDanhSach::truyVan($this->locXuatTu($request))),
            $ten
        );
    }

    /** Tai bang loi de dua nguoi nhap lieu di sua */
    public function xuatLoi(Request $request)
    {
        $ten = 'loi-chung-tu-dien-tu-' . now()->format('Ymd-His') . '.xlsx';

        return Excel::download(
            new CtdtLoiExport(CtdtDanhSach::truyVan($this->locXuatTu($request))),
            $ten
        );
    }

    /** Tai nhat ky gui trong mot khoang ngay */
    public function xuatNhatKy(Request $request)
    {
        // Lui ve 30 ngay gan nhat khi nguoi dung khong chon: bang nay chi tang, khong bao
        // gio giam, nen mac dinh "tat ca" la mot truy van toan bang.
        $khoang = CtdtDanhSach::khoangMacDinh([
            'tu_ngay'  => $request->input('tu_ngay'),
            'den_ngay' => $request->input('den_ngay'),
        ]);

        // CHI lay phan ngay: moc tu man hinh co dang '2026-08-01 00:00:00', ghep nguyen van
        // vao ten tep se sinh dau ':' - Windows khong luu duoc tep do.
        $ten = 'nhat-ky-gui-ctdt-' . $this->phanNgay($khoang['tu_ngay'])
             . '-den-' . $this->phanNgay($khoang['den_ngay']) . '.xlsx';

        try {
            $xuat = new CtdtNhatKyGuiExport($khoang['tu_ngay'], $khoang['den_ngay']);
        } catch (\InvalidArgumentException $e) {
            // Bao ro thay vi de yeu cau chet giua chung voi mot trang trang: tran khoang
            // ngay la mot lua chon sai cua nguoi dung, khong phai loi he thong.
            abort(422, $e->getMessage());
        }

        return Excel::download($xuat, $ten);
    }

    public function importIndex()
    {
        return view('bhyt.ctdt.import', [
            'danhSachCoSo' => DanhSachCoSo::danhSach(),
        ]);
    }

    /**
     * Nhan tep tai len, nap dong bo va tra ket qua theo TUNG tep.
     *
     * Nap dong bo (khong day job) giong BHYTXml3176Controller: ket qua hien ngay, va noi
     * gioi han bo nho tai cho thay vi phu thuoc mac dinh 128MB cua may chu.
     */
    public function uploadData(Request $request)
    {
        // Mot goi chung tu duoc phep toi 100MB. Giai base64 roi dung SimpleXML cho tung
        // phan lam bo nho phinh gap nhieu lan kich thuoc tep, ma may chu chi cho 128MB.
        // KHONG dung muc 4096M nhu cac lop Exports/: day la endpoint web ma Dropzone ban
        // nhieu request song song, cho moi request 4GB co the lam can RAM that.
        set_time_limit(600);
        ini_set('memory_limit', '512M');

        $tep = $request->file('xmls');

        if (empty($tep)) {
            return response()->json([
                'thanh_cong' => false,
                'thong_diep' => 'Không nhận được tệp nào.',
                'chi_tiet'   => [],
            ], 400);
        }

        $tep = is_array($tep) ? $tep : [$tep];

        $importer = new CtdtImporter();
        $tuyChon = [
            'macskcb'     => $request->input('macskcb'),
            'imported_by' => $request->user() ? $request->user()->loginname : null,
        ];

        $chiTiet = [];
        $tatCaThanhCong = true;

        // Toi da 100MB moi tep - phai khop voi con so acceptedFiles/maxFilesize trong blade.
        // Kiem o day: "acceptedFiles" va "maxFilesize" cua Dropzone CHI la kiem phia trinh
        // duyet, ai goi thang endpoint (khong qua form) se lot qua het.
        $kichThuocToiDa = 100 * 1024 * 1024;

        foreach ($tep as $mot) {
            $ten = $mot->getClientOriginalName();

            // Ba kiem server-side ma trinh duyet khong the thay the: tep tai len loi giua
            // chung, khong phai .xml, hoac vuot 100MB deu phai bi chan TRUOC khi cham toi
            // SimpleXML - nap thang mot tep nhi phan lon vao file_get_contents() la fatal
            // het bo nho thay vi mot dong "tep hong" tu te.
            if (!$mot->isValid()) {
                $tatCaThanhCong = false;
                $chiTiet[] = $this->chiTietLoiTaiLen($ten, 'Tải lên lỗi, mã lỗi: ' . $mot->getError());
                continue;
            }

            $phanMoRong = strtolower($mot->getClientOriginalExtension());

            if ($phanMoRong !== 'xml') {
                $tatCaThanhCong = false;
                $chiTiet[] = $this->chiTietLoiTaiLen(
                    $ten,
                    'Chỉ nhận tệp .xml, tệp này có phần mở rộng "' . $phanMoRong . '"'
                );
                continue;
            }

            if ($mot->getSize() > $kichThuocToiDa) {
                $tatCaThanhCong = false;
                $chiTiet[] = $this->chiTietLoiTaiLen(
                    $ten,
                    'Tệp vượt quá 100MB (kích thước thực: ' . $mot->getSize() . ' byte)'
                );
                continue;
            }

            // duong_dan_goc ghi TEN NGUOI DUNG THAY, khong phai duong dan tam cua PHP: tep
            // tam bi xoa ngay sau request nen luu duong dan do la luu mot con tro chet.
            $kq = $importer->nhapTuTep($mot->getRealPath(), array_merge($tuyChon, [
                'duong_dan_goc' => $ten,
            ]));

            $tatCaThanhCong = $tatCaThanhCong && $kq->thanhCong;

            $chiTiet[] = [
                'tep'           => $ten,
                'thanh_cong'    => (bool) $kq->thanhCong,
                'so_thanh_cong' => (int) $kq->soThanhCong,
                'so_that_bai'   => (int) $kq->soThatBai,
                'ly_do'         => $kq->lyDoThatBai,
                'ghi_de_da_gui' => $kq->dsGhiDeDaGui,
            ];
        }

        return response()->json([
            'thanh_cong' => $tatCaThanhCong,
            'thong_diep' => $tatCaThanhCong
                ? 'Đã nạp xong ' . count($chiTiet) . ' tệp.'
                : 'Có tệp không nạp được, xem chi tiết bên dưới.',
            'chi_tiet'   => $chiTiet,
        ]);
    }

    /**
     * Mot dong chi_tiet cho tep bi chan TRUOC khi cham toi CtdtImporter - cung hinh dang voi
     * dong duoc dung tu CtdtImportFileResult, de phia trinh duyet khong phai phan biet hai
     * nguon.
     */
    private function chiTietLoiTaiLen($ten, $lyDo)
    {
        return [
            'tep'           => $ten,
            'thanh_cong'    => false,
            'so_thanh_cong' => 0,
            'so_that_bai'   => 0,
            'ly_do'         => $lyDo,
            'ghi_de_da_gui' => [],
        ];
    }

    public function detail($ma_ho_so)
    {
        $hoSo = CtdtHoSo::with('chungTu')->where('ma_ho_so', $ma_ho_so)->firstOrFail();

        return view('bhyt.ctdt.detail', [
            'hoSo' => $hoSo,
            'tabs' => CtdtDetailTabs::cua($hoSo),
        ]);
    }

    /**
     * Chi THAN cua man chi tiet, khong layout - de modal tren man danh sach nap bang AJAX.
     *
     * VI SAO KHONG dung lai detail(): detail() tra view co @extends('adminlte::page'), nap
     * vao modal se long mot ban AdminLTE thu hai vao trong ban dang chay - menu trong menu,
     * va hai bo JS cua cung mot thu viện chay song song.
     */
    public function detailThan($ma_ho_so)
    {
        $hoSo = CtdtHoSo::with('chungTu')->where('ma_ho_so', $ma_ho_so)->firstOrFail();

        return view('bhyt.ctdt.partials.than-chi-tiet', [
            'hoSo' => $hoSo,
            'tabs' => CtdtDetailTabs::cua($hoSo),
        ]);
    }

    /**
     * Mot tab, nap luoi khi nguoi dung bam vao.
     *
     * Chin loai x toi 69 truong ma nap het mot luot thi trang nang vo ich - phan lon tab
     * khong bao gio duoc mo.
     */
    public function detailTab($ma_ho_so, $loai)
    {
        $hoSo = CtdtHoSo::with('chungTu')->where('ma_ho_so', $ma_ho_so)->firstOrFail();

        if (!CtdtDetailTabs::hopLe($hoSo, $loai)) {
            abort(404);
        }

        if ($loai === CtdtDetailTabs::TAB_LOI) {
            // Loi muc chan hien TRUOC: nguoi doc can thay ngay thu dang chan minh gui,
            // khong phai loc bang mat qua mot danh sach tron lan.
            $loi = CtdtLoi::with('chungTu')
                ->where('ho_so_id', $hoSo->id)
                ->orderByRaw("CASE WHEN muc_do = 'chan' THEN 0 ELSE 1 END")
                ->orderBy('id')
                ->get();

            return view('bhyt.ctdt.tab-loi', [
                'hoSo' => $hoSo,
                'loi'  => $loi,
            ]);
        }

        if ($loai === CtdtDetailTabs::TAB_XML) {
            return view('bhyt.ctdt.tab-xml-goc', [
                'hoSo'    => $hoSo,
                'chungTu' => $hoSo->chungTu->values(),
                // Quyen quyet dinh o SERVER va truyen xuong view. Khong de JavaScript tu
                // hoi: an nut bang JS chi la trang tri, ai cung goi thang endpoint duoc -
                // endpoint co middleware checkrole rieng, va day chi la phan hien thi.
                'coQuyenSuaXml' => self::duocSuaXml(),
            ]);
        }

        // hopLe() lui ve chinh ma loai khi registry khong biet no, nen no van tra true cho
        // mot loai da bi go khoi registry (vd sau khi Giai doan 3/4 thu hep dang ky). Khong
        // kiem lai o day thi CtdtLoaiRegistry::cho() ben duoi nem LoaiKhongBietException va
        // nguoi dung thay 500 thay vi 404.
        if (!CtdtLoaiRegistry::co($loai)) {
            abort(404);
        }

        $lop = CtdtLoaiRegistry::cho($loai);
        $truong = $lop::truong();
        $tenModel = $lop::model();

        $banGhi = [];

        foreach ($hoSo->chungTu->where('loai_ho_so', $loai) as $chungTu) {
            $chiTiet = $tenModel::where('chung_tu_id', $chungTu->id)->first();

            if ($chiTiet === null) {
                continue;
            }

            $dong = [];

            foreach ($truong as $the => $cot) {
                $giaTri = $chiTiet->{$cot};

                // Bo qua o trong: CT03 co 33 truong, giay chung sinh 69, ma mot ho so that
                // thuong chi dien mot phan. Hien du ca truong trong lam nguoi doc phai loc
                // bang mat.
                if ($giaTri === null || trim((string) $giaTri) === '') {
                    continue;
                }

                $dong[] = ['nhan' => CtdtNhanTruong::cua($the), 'gia_tri' => (string) $giaTri];
            }

            $banGhi[] = $dong;
        }

        return view('bhyt.ctdt.tab-chung-tu', [
            'hoSo'   => $hoSo,
            'loai'   => $loai,
            'nhan'   => $lop::tenTab(),
            'banGhi' => $banGhi,
        ]);
    }

    /**
     * Ky so roi gui mot ho so len cong BHXH.
     *
     * Kiem dieu kien NGAY TAI DAY thay vi de job tu tu choi: nguoi bam nut phai biet VI SAO
     * khong co gi xay ra, khong thi ho bam lai mai. Job van kiem lai lan nua vi no co the
     * nam cho trong hang doi rat lau, giua luc do cau hinh hoac ho so co the da doi.
     */
    public function kyVaGui($ma_ho_so, Request $request = null)
    {
        // Dong nay BAT BUOC cho CA route lan test, khong phai chi de test.
        //
        // Tham so vua co typehint lop VUA co gia tri mac dinh thi
        // RouteDependencyResolverTrait::transformDependency() tra getDefaultValue() chu
        // KHONG goi container->make() - tuc router cung truyen null. Da kiem chung bang
        // ControllerDispatcher::resolveClassMethodDependencies: ket qua la
        // ['ma_ho_so' => ..., 0 => NULL].
        //
        // Go dong nay di la route chet bang fatal "Call to a member function input() on
        // null", va chi lo ra khi co nguoi bam nut tren moi truong that.
        $request = $request ?: request();

        $hoSo = CtdtHoSo::where('ma_ho_so', $ma_ho_so)->firstOrFail();

        $cua = CtdtDuDieuKienGui::cua($hoSo);

        // DA_CO_MA_GD di tiep o duong DON LE, khac han duong hang loat. Nhan nut da doi
        // thanh "Ky va gui lai" va nguoi bam dang nhin man chi tiet cua dung ho so do, nen
        // gui lai la thao tac co y - giu nguyen hanh vi von co. Duong hang loat tu choi ma
        // nay, vi o do khong ai nhin tung ho so.
        if ($cua !== CtdtDuDieuKienGui::DUOC && $cua !== CtdtDuDieuKienGui::DA_CO_MA_GD) {
            // Laravel 5.5 KHONG co Request::boolean(), nen dung filter_var().
            //
            // Canh bao chu khong chan cung: gui lai sau khi sua noi dung la viec HOP LE. Chi
            // buoc nguoi bam nhin thay minh dang gui lai mot ho so cong da nhan.
            $daXacNhan = $cua === CtdtDuDieuKienGui::CAN_XAC_NHAN
                && filter_var($request->input('xac_nhan_gui_lai'), FILTER_VALIDATE_BOOLEAN);

            if (!$daXacNhan) {
                $phanHoi = [
                    'thanh_cong' => false,
                    'thong_diep' => CtdtDuDieuKienGui::lyDo($cua),
                ];

                // Chi gan khoa nay khi that su can xac nhan: JS doc `if (kq.can_xac_nhan)`
                // de quyet dinh hien hop thoai, nen gui kem no o moi nhanh tu choi la hien
                // hop thoai "gui lai?" cho ca ho so con loi chan.
                if ($cua === CtdtDuDieuKienGui::CAN_XAC_NHAN) {
                    $phanHoi['can_xac_nhan'] = true;
                }

                return response()->json($phanHoi);
            }
        }

        // Model App\User dung cot 'loginname' de dinh danh nguoi dang nhap (khong phai
        // 'username' - cot nay khong ton tai trong $fillable cua User).
        $nguoiGui = auth()->check() ? auth()->user()->loginname : null;

        // Xep hang qua CtdtXepHangKyGui chu khong tu dat khoa va tu dispatch: lenh Console
        // ctdt:import va nut gui hang loat can dung mot viec nay.
        //
        // Dat khoa NGAY TRUOC dispatch, SAU moi nhanh tu choi phia tren: mot lan bam bi tu
        // choi khong lam gi ca, giu khoa se khoa nguoi dung ra ngoai het thoi han ma khong
        // duoc gi.
        if (!CtdtXepHangKyGui::xep($ma_ho_so, $nguoiGui)) {
            return response()->json([
                'thanh_cong' => false,
                'thong_diep' => 'Hồ sơ này đang xử lý. Chờ ít phút rồi tải lại trang để xem kết quả.',
            ]);
        }

        return response()->json([
            'thanh_cong' => true,
            'thong_diep' => 'Đã xếp hàng ký số và gửi. Tải lại trang sau ít phút để xem kết quả.',
        ]);
    }

    /**
     * Ky so va gui HANG LOAT nhung ho so nguoi dung da tich chon.
     *
     * KHONG co duong tat nao rieng: moi ho so di qua dung cua chan CtdtDuDieuKienGui va dung
     * CtdtXepHangKyGui::xep() ma nut don le dung. Ho so nao khong qua thi bi BO QUA kem ly
     * do, khong lam ca lo that bai - mot lo 50 ho so ma dung lai o ho so thu ba la buoc
     * nguoi dung bam lai 47 lan.
     *
     * DA_CO_MA_GD va CAN_XAC_NHAN deu bi tu choi o day. Ca hai can nguoi doc lich su gui cua
     * TUNG ho so truoc khi quyet dinh, va chung tu PL02 khong mang ma giao dich phia nguoi
     * gui nen cong BHXH KHONG the nhan ra ban trung.
     */
    public function kyVaGuiNhieu(Request $request)
    {
        $ds = $request->input('ma_ho_so');
        $ds = is_array($ds) ? $ds : [];

        // Ep ve chuoi roi loai trung: tich cung mot ho so hai lan (hai trang, hai lan bam)
        // khong duoc thanh hai lan xep hang. Khoa CSDL cung chan, nhung chan o day thi con
        // so bao ve cho nguoi dung moi dung.
        $ds = array_values(array_unique(array_filter(array_map(function ($m) {
            return trim((string) $m);
        }, $ds), 'strlen')));

        if (empty($ds)) {
            return response()->json([
                'thanh_cong' => false,
                'thong_diep' => 'Chưa chọn hồ sơ nào.',
            ], 400);
        }

        if (count($ds) > self::TRAN_GUI_NHIEU) {
            return response()->json([
                'thanh_cong' => false,
                'thong_diep' => 'Mỗi lượt chỉ gửi tối đa ' . self::TRAN_GUI_NHIEU
                    . ' hồ sơ. Đang chọn ' . count($ds) . ' hồ sơ.',
            ], 422);
        }

        $nguoiGui = auth()->check() ? auth()->user()->loginname : null;

        $daXep = [];
        $boQua = [];

        // Doc TUNG ho so trong vong lap chu khong whereIn() mot lan: 50 truy van la khong
        // dang ke, con doc mot lan roi xep hang dan thi ho so cuoi cung duoc quyet dinh dua
        // tren trang thai da cu vai giay - trong khi bo kiem hoac lenh nen co the vua doi no.
        foreach ($ds as $maHoSo) {
            $hoSo = CtdtHoSo::where('ma_ho_so', $maHoSo)->first();

            if ($hoSo === null) {
                $boQua[] = ['ma_ho_so' => $maHoSo, 'ly_do' => 'Không tìm thấy hồ sơ này.'];
                continue;
            }

            $cua = CtdtDuDieuKienGui::cua($hoSo);

            if ($cua !== CtdtDuDieuKienGui::DUOC) {
                $boQua[] = [
                    'ma_ho_so' => $maHoSo,
                    'ly_do'    => CtdtDuDieuKienGui::lyDo($cua),
                ];
                continue;
            }

            if (!CtdtXepHangKyGui::xep($maHoSo, $nguoiGui)) {
                $boQua[] = [
                    'ma_ho_so' => $maHoSo,
                    'ly_do'    => 'Hồ sơ đang xử lý ở một lượt khác.',
                ];
                continue;
            }

            $daXep[] = $maHoSo;
        }

        return response()->json([
            // thanh_cong = da xep duoc IT NHAT mot ho so. Mot lo toan bo bi bo qua la
            // that bai - khong thi man hinh bao mau xanh trong khi khong co gi duoc gui.
            'thanh_cong' => count($daXep) > 0,
            'so_da_xep'  => count($daXep),
            'so_bo_qua'  => count($boQua),
            'da_xep'     => $daXep,
            'bo_qua'     => $boQua,
            'thong_diep' => count($daXep) > 0
                ? 'Đã xếp hàng ký số và gửi ' . count($daXep) . ' hồ sơ. Tải lại danh sách '
                    . 'sau ít phút để xem kết quả.'
                : 'Không hồ sơ nào đủ điều kiện gửi. Xem lý do từng hồ sơ bên dưới.',
        ]);
    }


    /**
     * Xoa han mot ho so. Route da gioi han checkrole:superadministrator - xoa mot ho so da
     * co MaGD la xoa dau vet doi soat voi BHXH.
     */
    public function delete($ma_ho_so)
    {
        $hoSo = CtdtHoSo::where('ma_ho_so', $ma_ho_so)->firstOrFail();

        // Dung lai CtdtLuuHoSo::xoaHoSoCu(): no biet xoa ban ghi chi tiet o dung bang cua
        // tung loai. Dua vao khoa ngoai cascade thi tren SQLite (va tren may chu neu bang
        // khong phai InnoDB) se de lai rac ma khong ai phat hien.
        //
        // Boc trong transaction: neu $hoSo->delete() hong giua chung, khong duoc de lai
        // mot ctdt_ho_so mo coi (so_chung_tu > 0 nhung chung tu da bi xoa het) - man danh
        // sach van dem no, man chi tiet mo ra rong.
        DB::transaction(function () use ($ma_ho_so, $hoSo) {
            $luu = new CtdtLuuHoSo();
            $luu->xoaHoSoCu($ma_ho_so);

            $hoSo->delete();
        });

        return response()->json(['thanh_cong' => true]);
    }
    /** @return bool Nguoi dang dang nhap co quyen sua XML goc khong */
    private static function duocSuaXml()
    {
        return auth()->check() && auth()->user()->hasRole('ctdt-sua-xml');
    }

    /**
     * Sua VAN BAN THO cua XML goc mot chung tu.
     *
     * Route da co middleware checkrole:ctdt-sua-xml - quyen nay TACH KHOI xml-man vi noi
     * dung sua o day duoc ky so va gui len cong BHXH.
     *
     * Cac chot ve NOI DUNG nam o CtdtSuaXml::kiem(). O day chi ba chot ve TRANG THAI ho so,
     * thu ma mot ham thuan khong biet duoc.
     */
    public function suaXml(Request $request, $ma_ho_so, $chung_tu_id)
    {
        $hoSo = CtdtHoSo::where('ma_ho_so', $ma_ho_so)->firstOrFail();

        // Doi chieu chung tu voi HO SO tren duong dan, khong chi tim theo id. Thieu buoc nay
        // thi mot nguoi co quyen sua ho so A co the sua chung tu cua ho so B chi bang cach
        // doi so id tren URL.
        $chungTu = CtdtChungTu::where('id', $chung_tu_id)
            ->where('ho_so_id', $hoSo->id)
            ->firstOrFail();

        // Dang chay chuoi ky - gui thi KHONG duoc doi noi dung: job ky co the da doc ban cu
        // va dang gui ban do, trong khi CSDL hien ban moi. Luc doi soat se khong ai biet ban
        // nao that su len cong.
        if (CtdtXepHangKyGui::dangXuLy($ma_ho_so)) {
            return response()->json([
                'thanh_cong' => false,
                'thong_diep' => 'Hồ sơ đang trong lượt ký và gửi. Chờ xong rồi sửa, '
                    . 'không sửa nội dung giữa lúc đang gửi.',
            ]);
        }

        // Laravel 5.5 KHONG co Request::boolean().
        $daXacNhan = filter_var($request->input('xac_nhan_da_gui'), FILTER_VALIDATE_BOOLEAN);

        if (!empty($hoSo->ma_gd) && !$daXacNhan) {
            return response()->json([
                'thanh_cong'   => false,
                'can_xac_nhan' => true,
                'thong_diep'   => 'Cổng BHXH đã tiếp nhận hồ sơ này (mã giao dịch '
                    . $hoSo->ma_gd . '). Sửa bản trong phần mềm KHÔNG sửa được chứng từ đã '
                    . 'nằm trên cổng — muốn sửa thật thì phải theo quy trình nghiệp vụ với '
                    . 'cơ quan bảo hiểm. Vẫn sửa?',
            ]);
        }

        $kiem = CtdtSuaXml::kiem($request->input('noi_dung'), $chungTu);

        if ($kiem['ma'] !== CtdtSuaXml::OK) {
            return response()->json([
                'thanh_cong' => false,
                'thong_diep' => CtdtSuaXml::lyDo($kiem['ma'], $kiem['chi_tiet']),
            ], 422);
        }

        $nguoiSua = auth()->check() ? auth()->user()->loginname : null;

        CtdtSuaXml::ap($chungTu, $kiem['xml'], $request->input('noi_dung'), $nguoiSua);

        // Chay lai bo kiem NGAY, khong day vao hang doi: nguoi vua sua can thay so loi moi
        // lien de biet minh da sua dung chua. Day vao hang doi thi ho phai doi va tai lai,
        // va neu worker chet thi ho so nam mai o so loi CU - tuc so loi cua noi dung da
        // khong con ton tai.
        (new \App\Jobs\CheckCtdtJob($ma_ho_so))->handle();

        $moi = $hoSo->fresh();

        return response()->json([
            'thanh_cong' => true,
            'so_loi'     => (int) $moi->so_loi,
            'thong_diep' => 'Đã lưu XML gốc và kiểm lại. Chữ ký cũ đã bị vô hiệu, hồ sơ cần '
                . 'ký và gửi lại. Số lỗi chặn hiện tại: ' . (int) $moi->so_loi . '.',
        ]);
    }

}

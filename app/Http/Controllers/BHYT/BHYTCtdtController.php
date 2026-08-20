<?php

namespace App\Http\Controllers\BHYT;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
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
use App\Jobs\SignCtdtJob;
use App\Jobs\SubmitCtdtJob;
use App\Services\Ctdt\CtdtQuyetDinhGui;

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
        'ma_ho_so', 'dich_vu', 'macskcb', 'ho_ten', 'ma_the', 'so_chung_tu', 'so_loi',
        'is_signed', 'trang_thai_gui', 'trang_thai_nhan', 'ma_gd', 'ma_ket_qua',
        'thoi_gian_tiep_nhan', 'imported_at', 'imported_by', 'khong_co_ma_yte', 'action',
    ];

    /**
     * Tien to khoa cache chong bam trung nut "Ky va gui".
     *
     * VI SAO CACHE chu khong phai mot cot moi: day la trang thai TAM THOI. Mot cot phai co
     * duong don khi tien trinh chet giua chung; khoa cache tu het han. Cache driver cua du
     * an la 'file' (mot may chu) nen du dung.
     *
     * Hai job nha khoa nay khi chay xong - xem SubmitCtdtJob va SignCtdtJob.
     *
     * KHONG phai mutex that: FileStore va ArrayStore cua Laravel 5.5 khong co add() rieng,
     * nen Repository::add() lui ve get() roi put() - co cua so TOCTOU. Hai request that su
     * dong thoi (cach nhau mili-giay) van lot ca hai. Du cho ca dung that o day (mot nguoi
     * bam hai lan cach nhau vai tram mili-giay); neu sau nay can chan that thi phai dung
     * khoa o tang CSDL.
     */
    const KHOA_XU_LY = 'ctdt:dang-xu-ly:';

    /** Muoi PHUT - Cache::add() cua Laravel 5.5 nhan phut, khong phai giay. Chi la luoi
     *  chan cuoi: duong nha khoa binh thuong la o cuoi chuoi job. */
    const KHOA_XU_LY_PHUT = 10;

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
        $truyVan = CtdtDanhSach::truyVan([
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
        ]);

        // Nap kem chung tu: cot ho ten / ma the lay tu chung tu DAU TIEN cua ho so. Khong
        // nap kem thi moi dong la mot truy van rieng - 200 dong thanh 201 truy van.
        $truyVan->with(['chungTu' => function ($q) {
            $q->select('id', 'ho_so_id', 'ma_the', 'ho_ten')->orderBy('id');
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
            ->addColumn('trang_thai_gui', function ($hoSo) {
                return CtdtTrangThaiGui::cua($hoSo);
            })
            ->addColumn('trang_thai_nhan', function ($hoSo) {
                return CtdtTrangThaiGui::nhan(CtdtTrangThaiGui::cua($hoSo));
            })
            ->addColumn('khong_co_ma_yte', function ($hoSo) {
                // Ho so roi vao nhanh lui GUID: nap lai se tao ban ghi MOI chu khong ghi de.
                // Nguoi van hanh phai biet truoc, khong phai phat hien sau khi da nap hai lan.
                return strpos((string) $hoSo->ma_ho_so, '#') !== false;
            })
            ->addColumn('action', function ($hoSo) {
                return $hoSo->ma_ho_so;
            })
            ->only(self::DATATABLE_COLUMNS)
            ->rawColumns([])
            ->make(true);
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
    public function kyVaGui($ma_ho_so)
    {
        $hoSo = CtdtHoSo::where('ma_ho_so', $ma_ho_so)->firstOrFail();

        $quyetDinh = CtdtQuyetDinhGui::nen(
            config('organization.chung_tu_dien_tu.submit_enabled', false),
            $hoSo->checked_at,
            $hoSo->so_loi,
            // KHONG truyen $hoSo->is_signed: ho so chua ky la binh thuong o day - ta sap ky
            // no. Truyen gia tri that se lam moi ho so chua ky bi tu choi ngay tai nut.
            true
        );

        if ($quyetDinh !== CtdtQuyetDinhGui::GUI) {
            return response()->json([
                'thanh_cong' => false,
                'thong_diep' => $this->lyDoKhongGui($quyetDinh),
            ]);
        }

        // Chuc nang ky tat + ho so CHUA ky = bam nut cung khong di den dau. Ho so DA ky roi
        // thi van gui lai duoc binh thuong: khong can ky lai.
        $kyBat = (bool) config('organization.chung_tu_dien_tu.sign_enabled', false);

        if (!$kyBat && !(bool) $hoSo->is_signed) {
            return response()->json([
                'thanh_cong' => false,
                'thong_diep' => 'Chức năng ký số đang tắt trong cấu hình, và hồ sơ này chưa ký. Liên hệ quản trị để bật.',
            ]);
        }

        // XAU CHUOI chu khong day hai job doc lap: ky xong moi gui duoc. Hai job doc lap thi
        // job gui co the chay truoc job ky va luon thay is_signed = false.
        //
        // Model App\User dung cot 'loginname' de dinh danh nguoi dang nhap (khong phai
        // 'username' - cot nay khong ton tai trong $fillable cua User). Cach dung khac o
        // chinh controller nay, o import(): $request->user()->loginname.
        $nguoiGui = auth()->check() ? auth()->user()->loginname : null;

        // config(..., default) CHI lui ve default khi thieu khoa (Arr::get dung
        // array_key_exists) - mot config/organization.php ghi de khoa nay thanh null (vd.
        // ban config:cache cu) van khien config() tra ve null chu KHONG tu lui ve default.
        // Dung ?: de lui ve ten hang doi mac dinh trong ca hai truong hop thieu khoa LAN
        // khoa ton tai nhung rong/null.
        $hangDoiKy  = config('organization.chung_tu_dien_tu.sign_queue_name') ?: 'JobSignCtdt';
        $hangDoiGui = config('organization.chung_tu_dien_tu.submit_queue_name') ?: 'JobSubmitCtdt';

        // Dat khoa NGAY TRUOC dispatch, sau moi nhanh tu choi: mot lan bam bi tu choi khong
        // lam gi ca, giu khoa se khoa nguoi dung ra ngoai muoi phut ma khong duoc gi.
        //
        // Cache::add() tra false khi khoa da ton tai - do chinh la phep thu "da co nguoi bam
        // chua". Khoa theo TUNG ma ho so, khong phai mot khoa chung.
        if (!Cache::add(self::KHOA_XU_LY . $ma_ho_so, true, self::KHOA_XU_LY_PHUT)) {
            return response()->json([
                'thanh_cong' => false,
                'thong_diep' => 'Hồ sơ này đang xử lý. Chờ ít phút rồi tải lại trang để xem kết quả.',
            ]);
        }

        SignCtdtJob::withChain([
            (new SubmitCtdtJob($ma_ho_so, $nguoiGui))->onQueue($hangDoiGui),
        ])
        ->dispatch($ma_ho_so)
        ->onQueue($hangDoiKy);

        return response()->json([
            'thanh_cong' => true,
            'thong_diep' => 'Đã xếp hàng ký số và gửi. Tải lại trang sau ít phút để xem kết quả.',
        ]);
    }

    /** Ly do doc duoc cho nguoi bam nut, khong phai ma trang thai */
    private function lyDoKhongGui($quyetDinh)
    {
        if ($quyetDinh === CtdtQuyetDinhGui::KHONG_GUI) {
            return 'Chức năng gửi đang tắt trong cấu hình. Liên hệ quản trị để bật.';
        }

        if ($quyetDinh === CtdtQuyetDinhGui::CHUA_KIEM) {
            return 'Hồ sơ chưa kiểm — công việc kiểm còn nằm trong hàng đợi. Thử lại sau ít phút.';
        }

        if ($quyetDinh === CtdtQuyetDinhGui::CON_LOI) {
            return 'Hồ sơ còn lỗi chặn gửi. Xem tab Lỗi, sửa ở phần mềm sinh XML rồi nạp lại.';
        }

        return 'Hồ sơ chưa đủ điều kiện gửi.';
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
}

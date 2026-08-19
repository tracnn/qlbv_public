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

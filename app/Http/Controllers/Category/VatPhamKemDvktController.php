<?php

namespace App\Http\Controllers\Category;

use App\Exports\LoiNhapDanhMucExport;
use App\Exports\VatPhamKemDvktExport;
use App\Http\Controllers\Controller;
use App\Imports\VatPhamKemDvktImport;
use App\Models\BHYT\Xml3176VatPhamKemDvkt;
use App\Models\BHYT\Xml3176VpkdUngVien;
use App\Services\Xml3176\VatPhamKemDvktHocLaiService;
use App\Services\Xml3176\VatPhamKemDvktLearner;
use App\Services\Xml3176\VatPhamKemDvktQuanTri;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use Yajra\Datatables\Datatables;

/**
 * Man quan tri bang tra "Vat pham kem DVKT me" (XML3176). Controller mong: nghiep vu o
 * VatPhamKemDvktQuanTri / VatPhamKemDvktHocLaiService / VatPhamKemDvktLearner;
 * InvalidArgumentException -> 422.
 */
class VatPhamKemDvktController extends Controller
{
    private $quanTri;
    private $hocLai;
    private $learner;

    public function __construct(VatPhamKemDvktQuanTri $quanTri, VatPhamKemDvktHocLaiService $hocLai, VatPhamKemDvktLearner $learner)
    {
        $this->quanTri = $quanTri;
        $this->hocLai = $hocLai;
        $this->learner = $learner;
    }

    public function index()
    {
        $this->hocLai->danhDauTreo();

        return view('category.bhyt.vat_pham_kem_dvkt', ['lanHoc' => $this->hocLai->moTa($this->hocLai->ganNhat())]);
    }

    public function datatable(Request $request)
    {
        $q = Xml3176VatPhamKemDvkt::query()
            ->leftJoin('users', 'users.id', '=', 'xml3176_vat_pham_kem_dvkt.nguoi_sua_id')
            ->select('xml3176_vat_pham_kem_dvkt.*', 'users.name as nguoi_sua');
        foreach (['loai', 'nguon'] as $cot) {
            if ($request->filled($cot)) {
                $q->where('xml3176_vat_pham_kem_dvkt.' . $cot, $request->input($cot));
            }
        }
        if ($request->filled('hieu_luc')) {
            $q->where('xml3176_vat_pham_kem_dvkt.hieu_luc', (int) $request->input('hieu_luc'));
        }

        return Datatables::of($q)
            ->editColumn('ty_le_gan', function ($m) {
                return $m->ty_le_gan === null ? '' : number_format($m->ty_le_gan * 100, 1, ',', '.') . '%';
            })
            ->editColumn('hieu_luc', function ($m) {
                return '<input type="checkbox" class="vpkd-hieu-luc" data-id="' . $m->id . '"'
                    . ' data-ghi-chu="' . e($m->ghi_chu) . '"' . ($m->hieu_luc ? ' checked' : '') . '>';
            })
            ->editColumn('ghi_chu', function ($m) {
                return e($m->ghi_chu) . ' <a href="#" class="vpkd-ghi-chu" data-id="' . $m->id . '" data-ghi-chu="'
                    . e($m->ghi_chu) . '" title="Sửa ghi chú"><i class="fa fa-pencil"></i></a>';
            })
            ->addColumn('nguoi_sua_luc', function ($m) {
                return $m->sua_luc ? e($m->nguoi_sua ?: '#' . $m->nguoi_sua_id) . '<br>' . $m->sua_luc->format('d/m/Y H:i') : '';
            })
            ->addColumn('thao_tac', function ($m) {
                return $m->nguon === Xml3176VatPhamKemDvkt::NGUON_TAY
                    ? '<button class="btn btn-xs btn-danger vpkd-xoa" data-id="' . $m->id . '"><i class="fa fa-trash"></i></button>'
                    : '';
            })
            ->rawColumns(['hieu_luc', 'ghi_chu', 'nguoi_sua_luc', 'thao_tac'])
            ->toJson();
    }

    public function ungVienDatatable(Request $request)
    {
        $q = Xml3176VpkdUngVien::query();
        if ($request->filled('loai')) {
            $q->where('loai', $request->input('loai'));
        }

        // Cac cap da co trong bang tra (moi nguon) - danh dau "da co" tren o tick.
        $daCo = [];
        foreach (Xml3176VatPhamKemDvkt::get(['loai', 'ma_vat_pham', 'ten_khop', 'ma_dvkt']) as $m) {
            $daCo[$m->loai . '|' . $m->ma_vat_pham . '|' . $m->ten_khop . '|' . $m->ma_dvkt] = true;
        }

        return Datatables::of($q)
            ->editColumn('ty_le_gan', function ($u) {
                return number_format($u->ty_le_gan * 100, 1, ',', '.') . '%';
            })
            ->editColumn('dvkt', function ($u) use ($daCo) {
                $html = '';
                foreach ((array) $u->dvkt as $d) {
                    $co = isset($daCo[$u->loai . '|' . $u->ma_vat_pham . '|' . $u->ten_khop . '|' . $d['ma']]);
                    $html .= '<label style="display:block;font-weight:normal"><input type="checkbox" class="vpkd-uv-dv" data-uv="'
                        . $u->id . '" value="' . e($d['ma']) . '"' . ($co ? ' checked disabled' : '') . '> '
                        . e($d['ma']) . ' — ' . e($d['ten']) . ' (' . (int) $d['so_lan'] . ' lần)'
                        . ($co ? ' <span class="label label-default">đã có</span>' : '') . '</label>';
                }

                return $html;
            })
            ->addColumn('thao_tac', function ($u) {
                return '<button class="btn btn-xs btn-primary vpkd-uv-them" data-id="' . $u->id . '">Thêm thành dòng tay</button>';
            })
            ->rawColumns(['dvkt', 'thao_tac'])
            ->toJson();
    }

    public function hieuLuc(Request $request, $id)
    {
        return $this->voiDong($id, function ($m) use ($request) {
            $this->quanTri->datHieuLuc($m, (bool) (int) $request->input('hieu_luc'), $request->input('ghi_chu'), Auth::id());
        });
    }

    public function ghiChu(Request $request, $id)
    {
        return $this->voiDong($id, function ($m) use ($request) {
            $this->quanTri->datGhiChu($m, $request->input('ghi_chu'), Auth::id());
        });
    }

    public function xoa($id)
    {
        return $this->voiDong($id, function ($m) {
            $this->quanTri->xoa($m);
        });
    }

    public function kiemKhop(Request $request)
    {
        return $this->bat(function () use ($request) {
            return response()->json($this->quanTri->kiemKhop($request->input('loai'), $request->input('ma')));
        });
    }

    public function timVatPham(Request $request)
    {
        return $this->bat(function () use ($request) {
            return response()->json(['results' => $this->quanTri->timVatPham($request->input('loai'), $request->input('q'))]);
        });
    }

    public function timDvkt(Request $request)
    {
        return response()->json(['results' => $this->quanTri->timDvkt($request->input('q'))]);
    }

    public function them(Request $request)
    {
        return $this->bat(function () use ($request) {
            $kq = $this->quanTri->themDongTay(
                $request->input('loai'), $request->input('ma_vat_pham'), $request->input('ten_vat_pham'),
                (array) $request->input('ma_dvkt', []), [], $request->input('ghi_chu'), Auth::id()
            );

            return response()->json(['message' => self::thongDiepThem($kq)]);
        });
    }

    public function ungVienThem(Request $request, $id)
    {
        $uv = Xml3176VpkdUngVien::find($id);
        if ($uv === null) {
            return response()->json(['message' => 'Không tìm thấy ứng viên'], 404);
        }

        return $this->bat(function () use ($request, $uv) {
            $ten = [];
            foreach ((array) $uv->dvkt as $d) {
                $ten[$d['ma']] = $d['ten'];
            }
            $chon = array_values(array_unique(array_map('strval', (array) $request->input('ma_dvkt', []))));
            $la = array_values(array_diff($chon, array_keys($ten)));
            if (!empty($la)) {
                throw new \InvalidArgumentException('DVKT không thuộc ứng viên: ' . implode(', ', $la));
            }
            $kq = $this->quanTri->themDongTay(
                $uv->loai, $uv->ma_vat_pham, $uv->ten_vat_pham, $chon, $ten,
                'Duyệt từ ứng viên lượt #' . $uv->lan_hoc_id, Auth::id()
            );

            return response()->json(['message' => self::thongDiepThem($kq)]);
        });
    }

    public function hocLai()
    {
        $r = $this->hocLai->yeuCau(Auth::id());

        return response()->json(['moi' => $r['moi'], 'lan_hoc' => $this->hocLai->moTa($r['lan_hoc'])]);
    }

    public function trangThai()
    {
        $this->hocLai->danhDauTreo();

        return response()->json(['lan_hoc' => $this->hocLai->moTa($this->hocLai->ganNhat())]);
    }

    public function xuat()
    {
        $dong = Xml3176VatPhamKemDvkt::orderBy('loai')->orderBy('ma_vat_pham')->orderBy('ma_dvkt')->get()->all();

        return Excel::download(new VatPhamKemDvktExport($dong), 'vat_pham_kem_dvkt_' . date('Ymd_His') . '.xlsx');
    }

    public function nhap(Request $request)
    {
        $tep = $request->file('file');
        if ($tep === null || strtolower($tep->getClientOriginalExtension()) !== 'xlsx') {
            return response()->json(['message' => 'Tệp phải là .xlsx'], 422);
        }

        $imp = new VatPhamKemDvktImport();
        Excel::import($imp, $tep);
        $kq = $this->learner->nap($imp->hang, Auth::id());

        return response()->json([
            'message' => "Thêm {$kq['them']}, cập nhật {$kq['cap_nhat']}, lỗi " . count($kq['loi']),
            'ket_qua' => $kq,
        ]);
    }

    public function nhapLoi(Request $request)
    {
        $dongLoi = [];
        foreach ((array) json_decode((string) $request->input('dong_loi'), true) as $l) {
            $dongLoi[] = ['dong' => (int) ($l['dong'] ?? 0), 'loai' => 'loi', 'ly_do' => (string) ($l['ly_do'] ?? '')];
        }

        return Excel::download(new LoiNhapDanhMucExport($dongLoi, (string) $request->input('ten_tep')), 'loi_nhap_vat_pham_kem_dvkt.xlsx');
    }

    private function voiDong($id, callable $f)
    {
        $m = Xml3176VatPhamKemDvkt::find($id);
        if ($m === null) {
            return response()->json(['message' => 'Không tìm thấy dòng'], 404);
        }

        return $this->bat(function () use ($f, $m) {
            $f($m);

            return response()->json(['success' => true]);
        });
    }

    private function bat(callable $f)
    {
        try {
            return $f();
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    private static function thongDiepThem(array $kq): string
    {
        return 'Đã thêm ' . count($kq['them']) . ' dòng.' . (empty($kq['da_co']) ? '' : ' Đã có: ' . implode(', ', $kq['da_co']));
    }
}

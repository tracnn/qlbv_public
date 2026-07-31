<?php

namespace App\Http\Controllers\BHYT;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\CheckBHYT\check_hein_card;
use App\Services\BHYT\DanhSachCoSo;
use App\Services\BHYT\NhanMaThe;
use App\Exports\KetQuaTraCuuTheExport;
use Yajra\Datatables\Datatables;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;

/**
 * Man danh sach ket qua tra cuu the BHYT (bang check_hein_cards).
 *
 * Phuc vu ca hai nhu cau: tim ho so co van de ve the, va tra cuu lich su theo ho so / so the
 * / ho ten. Vi vay mac dinh hien TAT CA, va co ca bo loc trang thai lan o tim kiem.
 */
class CheckHeinCardController extends Controller
{
    public function index()
    {
        $danhSachCoSo = DanhSachCoSo::danhSach();

        return view('bhyt.check-hein-card.index', compact('danhSachCoSo'));
    }

    /**
     * Dung truy van theo bo loc - DUNG CHUNG cho ca fetch() lan xuatExcel().
     *
     * Neu moi ben tu dung thi them mot bo loc ma quen ben kia se lam tep xuat khac han man
     * hinh, va khong co dau hieu gi cho toi luc ai do ngoi doi chieu tung dong.
     *
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function locTheoYeuCau(Request $request)
    {
        $q = check_hein_card::query()
            ->cuaCoSo($request->get('ma_cskcb'));

        // Trang thai: rong = tat ca. Dung scope cua model chu khong viet lai dieu kien o day
        // - de chi co MOT dinh nghia "loi" trong toan he thong.
        if ($request->get('trang_thai') === 'loi') {
            $q->chiLoi();
        } elseif ($request->get('trang_thai') === 'hop_le') {
            $q->chiHopLe();
        }

        if ($tu = trim((string) $request->get('tu_ngay'))) {
            $q->whereDate('updated_at', '>=', $tu);
        }

        if ($den = trim((string) $request->get('den_ngay'))) {
            $q->whereDate('updated_at', '<=', $den);
        }

        if ($tim = trim((string) $request->get('tim'))) {
            $q->where(function ($w) use ($tim) {
                $w->where('ma_lk', 'like', '%' . $tim . '%')
                  ->orWhere('ma_the', 'like', '%' . $tim . '%')
                  ->orWhere('ho_ten', 'like', '%' . $tim . '%');
            });
        }

        return $q;
    }

    public function fetch(Request $request)
    {
        $q = $this->locTheoYeuCau($request);

        return Datatables::of($q)
            // Nhan tieng Viet: ma tran khong noi gi cho nguoi doc. NhanMaThe tra ma tran khi
            // gap ma la thay vi nem "Undefined index" nhu cac blade cu.
            ->addColumn('nhan_tracuu', function ($r) {
                return NhanMaThe::traCuu($r->ma_tracuu);
            })
            ->addColumn('nhan_kiemtra', function ($r) {
                return NhanMaThe::kiemTra($r->ma_kiemtra);
            })
            // De blade to mau dong loi ma khong phai lap lai dieu kien o phia trinh duyet.
            ->addColumn('co_loi', function ($r) {
                return $r->ma_tracuu !== check_hein_card::TRA_CUU_SACH
                    || $r->ma_kiemtra !== check_hein_card::KIEM_TRA_SACH;
            })
            ->make(true);
    }

    public function xuatExcel(Request $request)
    {
        // Bang nay phinh theo thoi gian - moi ho so mot dong. FromQuery duyet theo lo nen
        // khong nap ca bang, nhung van can noi gioi han mac dinh cho tep lon.
        ini_set('memory_limit', '512M');
        set_time_limit(600);

        // CUNG mot nguon truy van voi fetch(): tep xuat ra dung bang thu dang hien tren man.
        $ten = 'ket_qua_tra_cuu_the_' . Carbon::now()->format('YmdHis') . '.xlsx';

        return Excel::download(new KetQuaTraCuuTheExport($this->locTheoYeuCau($request)), $ten);
    }
}

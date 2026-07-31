<?php

namespace App\Http\Controllers\BHYT;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\CheckBHYT\check_hein_card;
use App\Services\BHYT\DanhSachCoSo;
use App\Services\BHYT\NhanMaThe;
use Yajra\Datatables\Datatables;

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

    public function fetch(Request $request)
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
}

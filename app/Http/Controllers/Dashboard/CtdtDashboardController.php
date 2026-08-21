<?php

namespace App\Http\Controllers\Dashboard;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\Ctdt\CtdtDanhSach;
use App\Services\Dashboard\CtdtDashboardService;

/**
 * Controller MONG: moi truy van nam trong CtdtDashboardService. Xem chu thich lop do ve vi
 * sao khong tu viet SQL trang thai.
 */
class CtdtDashboardController extends Controller
{
    /** @var CtdtDashboardService */
    protected $service;

    public function __construct(CtdtDashboardService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        return view('dashboard.ctdt');
    }

    public function sucKhoe(Request $request)
    {
        return response()->json($this->service->sucKhoe($this->locTu($request)));
    }

    public function sanLuong(Request $request)
    {
        return response()->json($this->service->sanLuong($this->locTu($request)));
    }

    public function chatLuong(Request $request)
    {
        return response()->json($this->service->chatLuong($this->locTu($request)));
    }

    /**
     * Bo loc dung DUNG dinh dang cua CtdtDanhSach::truyVan(), de man dashboard va man danh
     * sach luon noi cung mot thu.
     *
     * PHAI di qua CtdtDanhSach::khoangMacDinh(): ba khoi cua man nay tung co BA moc ngay
     * mac dinh khac nhau - sanLuong() tu bu 30 ngay, sucKhoe()/chatLuong() khong bu gi (tuc
     * toan thoi gian), con o nhap trong view lai mac dinh dau thang. Cung mot lan tai trang,
     * khoi tren bao "co ho so con loi" con duong san luong ngay duoi phang bang 0.
     *
     * Va do cung la duong QUET TOAN BANG: khong bu ngay thi mot yeu cau khong tham so sinh
     * chin COUNT(*) toan bang tren checked_at/is_signed (hai cot KHONG co index), mot
     * pluck toi 20000 id va mot GROUP BY toan bang nua - tren may chu 128MB/120s do la mot
     * yeu cau chet giua chung. khoangMacDinh() co san cho dung viec nay.
     *
     * KHONG nhan 'macskcb': view khong co o nhap va JS khong gui, nen no la tham so chet -
     * nhan mot tham so khong duong nao dat duoc chi lam nguoi doc sau tuong man hinh co bo
     * loc co so. Man DANH SACH van loc theo macskcb binh thuong qua truyVan().
     *
     * @return array
     */
    protected function locTu(Request $request)
    {
        return CtdtDanhSach::khoangMacDinh([
            'tu_ngay'  => $request->input('tu_ngay'),
            'den_ngay' => $request->input('den_ngay'),
            'dich_vu'  => $request->input('dich_vu'),
        ]);
    }
}

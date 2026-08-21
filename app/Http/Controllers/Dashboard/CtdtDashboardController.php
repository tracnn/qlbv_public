<?php

namespace App\Http\Controllers\Dashboard;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
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

    /**
     * Bo loc dung DUNG dinh dang cua CtdtDanhSach::truyVan(), de man dashboard va man danh
     * sach luon noi cung mot thu.
     *
     * @return array
     */
    protected function locTu(Request $request)
    {
        return [
            'tu_ngay'  => $request->input('tu_ngay'),
            'den_ngay' => $request->input('den_ngay'),
            'dich_vu'  => $request->input('dich_vu'),
            'macskcb'  => $request->input('macskcb'),
        ];
    }
}

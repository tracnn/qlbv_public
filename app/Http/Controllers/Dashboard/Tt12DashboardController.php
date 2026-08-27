<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Services\Dashboard\Tt12DashboardService;

/**
 * Controller MONG: moi truy van nam trong Tt12DashboardService. Xem chu thich lop do ve vi
 * sao khong tu viet SQL trang thai.
 *
 * KHONG nhan tham so loc: man nay khong co bo loc thoi gian - xem muc 7.1 cua spec. Nhan mot
 * tham so khong duong nao dat duoc chi lam nguoi doc sau tuong man hinh co bo loc.
 */
class Tt12DashboardController extends Controller
{
    /** @var Tt12DashboardService */
    protected $service;

    public function __construct(Tt12DashboardService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        return view('dashboard.tt12');
    }

    public function doPhu()
    {
        return response()->json($this->service->doPhu());
    }
}

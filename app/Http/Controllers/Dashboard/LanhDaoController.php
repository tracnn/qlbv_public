<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Services\BHYT\DanhSachCoSo;
use App\Services\Dashboard\LanhDao\KyLoc;
use App\Services\Dashboard\LanhDao\LanhDaoService;
use Illuminate\Http\Request;

class LanhDaoController extends Controller
{
    protected $service;

    public function __construct(LanhDaoService $service)
    {
        $this->service = $service;
    }

    public function index()
    {
        $danhSachCoSo = KyLoc::luaChonCoSo(DanhSachCoSo::danhSach());

        return view('dashboard.lanh-dao', compact('danhSachCoSo'));
    }

    /**
     * GET /dashboard/lanh-dao/{phan}
     */
    public function phan(Request $request, string $phan)
    {
        $ky = KyLoc::tuRequest($request->all());

        return response()->json($this->service->lay($phan, $ky));
    }
}

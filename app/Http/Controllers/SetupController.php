<?php

namespace App\Http\Controllers;

use App\Exceptions\DaKhoiTaoException;
use App\Services\SuperAdminBootstrap;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Man khoi tao quan tri vien dau tien.
 *
 * Cong chi mo khi va chi khi he thong chua co superadministrator nao. Sau lan gan
 * dau tien, ca hai hanh dong tra 404 vinh vien.
 */
class SetupController extends Controller
{
    protected $bootstrap;

    public function __construct(SuperAdminBootstrap $bootstrap)
    {
        $this->bootstrap = $bootstrap;
    }

    public function hienThi()
    {
        abort_unless($this->bootstrap->chuaKhoiTao(), 404);

        return view('setup.quan-tri-dau-tien', [
            'nguoiDung' => Auth::user(),
        ]);
    }

    public function gan(Request $request)
    {
        try {
            $this->bootstrap->gan(Auth::user());
        } catch (DaKhoiTaoException $e) {
            abort(404);
        }

        $request->session()->forget('setup.can_khoi_tao');

        return redirect('/home')
            ->with('success', 'Da cap quyen quan tri cao nhat cho tai khoan cua ban.');
    }
}

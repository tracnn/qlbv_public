<?php

namespace App\Http\Controllers\KHTH;

use App\Http\Controllers\Controller;
use App\Services\OrderCheck\TreatmentIssueService;
use App\Services\OrderCheck\TreatmentProfileService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class TraCuuLoiHoSoController extends Controller
{
    protected $loi;
    protected $hoSo;

    public function __construct(TreatmentIssueService $loi, TreatmentProfileService $hoSo)
    {
        $this->loi = $loi;
        $this->hoSo = $hoSo;
    }

    public function index()
    {
        return view('khth.tra-cuu-loi-ho-so');
    }

    public function traCuu(Request $request)
    {
        $ma = trim((string) $request->input('treatment_code'));

        if ($ma === '') {
            return response()->json(['message' => 'Chưa nhập mã điều trị'], 422);
        }

        $ketQua = $this->loi->cua($ma);

        // Oracle hong khong duoc keo theo phan loi doc tu MySQL: bat rieng o day, tra
        // profile_error de man hinh hien mot dong canh bao thay vi trang trang.
        $hoSo = null;
        $loiHoSo = null;

        try {
            $hoSo = $this->hoSo->cua($ma);
        } catch (\Exception $e) {
            $loiHoSo = 'Không lấy được thông tin từ HIS';
            Log::error('Tra cuu loi ho so: loi doc HIS', [
                'treatment_code' => $ma,
                'loi' => $e->getMessage(),
            ]);
        }

        return response()->json([
            'profile' => $hoSo,
            'profile_error' => $loiHoSo,
            'data' => $ketQua['data'],
            'summary' => $ketQua['summary'],
        ]);
    }

    public function in(Request $request)
    {
        $ma = trim((string) $request->input('treatment_code'));

        if ($ma === '') {
            return response('Chưa nhập mã điều trị', 422);
        }

        $ketQua = $this->loi->cua($ma);

        $hoSo = null;

        try {
            $hoSo = $this->hoSo->cua($ma);
        } catch (\Exception $e) {
            Log::error('Tra cuu loi ho so: loi doc HIS khi in', [
                'treatment_code' => $ma,
                'loi' => $e->getMessage(),
            ]);
        }

        return view('khth.tra-cuu-loi-ho-so-in', [
            'ma' => $ma,
            'hoSo' => $hoSo,
            'data' => $ketQua['data'],
            'summary' => $ketQua['summary'],
        ]);
    }
}

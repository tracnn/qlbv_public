<?php

namespace App\Http\Controllers\KHTH;

use App\Http\Controllers\Controller;
use App\Services\BHYT\TraLaiThe;
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
        $ma = $this->layMa($request);

        if ($ma === '') {
            return response()->json(['message' => 'Chưa nhập mã điều trị'], 422);
        }

        $kq = $this->docHoSoVaLoi($ma);

        return response()->json([
            'profile' => $kq['ho_so'],
            'profile_error' => $kq['loi_ho_so'],
            'data' => $kq['ket_qua']['data'],
            'summary' => $kq['ket_qua']['summary'],
            'data_error' => $kq['loi_ket_qua'],
        ]);
    }

    public function in(Request $request)
    {
        $ma = $this->layMa($request);

        if ($ma === '') {
            return response('Chưa nhập mã điều trị', 422);
        }

        $kq = $this->docHoSoVaLoi($ma);

        return view('khth.tra-cuu-loi-ho-so-in', [
            'ma' => $ma,
            'hoSo' => $kq['ho_so'],
            'loiHoSo' => $kq['loi_ho_so'],
            'data' => $kq['ket_qua']['data'],
            'summary' => $kq['ket_qua']['summary'],
            'loiKetQua' => $kq['loi_ket_qua'],
        ]);
    }

    /**
     * Ma dieu tri da trim, dung chung cho ca ba action - tranh lap trim() ba noi.
     */
    protected function layMa(Request $request)
    {
        return trim((string) $request->input('treatment_code'));
    }

    /**
     * Doc ho so (Oracle) va loi (MySQL) cho mot ma dieu tri. Hai try/catch TACH RIENG:
     * dung nghia cua man hinh la mot nguon hong khong duoc keo theo nguon kia. Dung chung
     * cho traCuu() va in() nen cau tra loi HIS hong ("Khong lay duoc thong tin tu HIS")
     * chi song o mot cho - truoc day in() bo qua loi nay va phieu in ghi nham "khong tim
     * thay ho so".
     *
     * @return array ['ho_so' => array|null, 'loi_ho_so' => string|null,
     *                'ket_qua' => array, 'loi_ket_qua' => string|null]
     */
    protected function docHoSoVaLoi($ma)
    {
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

        $ketQua = $this->ketQuaRong();
        $loiKetQua = null;

        try {
            $ketQua = $this->loi->cua($ma);
        } catch (\Exception $e) {
            $loiKetQua = 'Không lấy được dữ liệu lỗi từ MySQL';
            Log::error('Tra cuu loi ho so: loi doc du lieu loi (MySQL)', [
                'treatment_code' => $ma,
                'loi' => $e->getMessage(),
            ]);
        }

        return [
            'ho_so' => $hoSo,
            'loi_ho_so' => $loiHoSo,
            'ket_qua' => $ketQua,
            'loi_ket_qua' => $loiKetQua,
        ];
    }

    /** Khung du lieu rong dung khi TreatmentIssueService nem loi - de view khong vo. */
    protected function ketQuaRong()
    {
        return [
            'data' => [
                'treatment_code' => null,
                'order_check' => [],
                'hein_card' => [],
                'xml3176' => [],
            ],
            'summary' => [
                'total' => 0,
                'order_check' => 0,
                'hein_card' => 0,
                'xml3176' => 0,
                'critical' => 0,
                'has_error' => false,
                'truncated' => false,
            ],
        ];
    }

    public function traLaiThe(Request $request, TraLaiThe $traLai)
    {
        // Moi buoc kiem ho so HIS nam trong TraLaiThe - dung chung voi man Ket qua tra cuu the.
        $kq = $traLai->gui($this->layMa($request));

        return response()->json(['message' => $kq['message']], $kq['ok'] ? 200 : 422);
    }
}

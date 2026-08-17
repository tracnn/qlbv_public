<?php

namespace App\Http\Controllers\KHTH;

use App\Http\Controllers\Controller;
use App\Jobs\jobKtTheBHYT;
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

    public function traLaiThe(Request $request)
    {
        $ma = trim((string) $request->input('treatment_code'));

        if ($ma === '') {
            return response()->json(['message' => 'Chưa nhập mã điều trị'], 422);
        }

        try {
            $hoSo = $this->hoSo->cua($ma);
        } catch (\Exception $e) {
            Log::error('Tra lai the: loi doc HIS', ['treatment_code' => $ma, 'loi' => $e->getMessage()]);

            return response()->json(['message' => 'Không lấy được thông tin từ HIS'], 422);
        }

        if (!$hoSo) {
            return response()->json(['message' => 'Không tìm thấy hồ sơ với mã này trên HIS'], 422);
        }

        if (trim((string) $hoSo['hein_card_number']) === '') {
            return response()->json(['message' => 'Hồ sơ không có mã thẻ BHYT'], 422);
        }

        // Left join his_gender (xem TreatmentProfileService) nen gioi tinh co the rong.
        // Gui rong len cong chi doi mot loi ro rang lay mot ket qua sai.
        if (trim((string) $hoSo['gender_code']) === '') {
            return response()->json(['message' => 'Hồ sơ thiếu giới tính'], 422);
        }

        $maCskcb = trim((string) $hoSo['ma_cskcb']);
        $dsCoSo = config('organization.BHYT_CO_SO', []);

        if ($maCskcb === '' || !isset($dsCoSo[$maCskcb])) {
            return response()->json(['message' => 'Không xác định được cơ sở của hồ sơ'], 422);
        }

        jobKtTheBHYT::dispatch([
            'maThe'    => $hoSo['hein_card_number'],
            'hoTen'    => $hoSo['patient_name'],
            'ngaySinh' => dob($hoSo['patient_dob']),
            'ma_lk'    => $hoSo['treatment_code'],
            'maCskcb'  => $maCskcb,
            // maDkbd la noi DKBD ghi tren THE, khac maCskcb la co so DIEU TRI. Job dung
            // maCskcb de chon tai khoan cong BHXH va maDkbd de doi chieu ket qua tra ve.
            'maDkbd'   => $hoSo['hein_medi_org_code'],
            'gioiTinh' => $this->gioiTinhCongBhxh($hoSo['gender_code']),
        // checkOldValue = false: de mac dinh true thi job thay ket qua cu con hop le va
        // thoat ngay - dung nghia "bam nut xong khong co gi xay ra".
        ], false)->onQueue('JobKtTheBHYT');

        return response()->json([
            'message' => 'Đã gửi yêu cầu tra lại thẻ, bấm Tra cứu lại sau ít giây để xem kết quả',
        ]);
    }

    /**
     * HIS dung gender_code 1 = Nam, 2 = Nu; cong BHXH dung quy uoc nguoc lai. Lenh quet
     * HISProKiemTraTheBHYT dao o cung cho nay - bo qua thi cong tra ve ket qua sai.
     */
    protected function gioiTinhCongBhxh($genderCode)
    {
        $g = (int) $genderCode;

        if ($g === 1) {
            return 2;
        }

        if ($g === 2) {
            return 1;
        }

        return $g;
    }
}

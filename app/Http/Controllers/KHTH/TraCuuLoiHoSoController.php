<?php

namespace App\Http\Controllers\KHTH;

use App\Http\Controllers\Controller;
use App\Jobs\jobKtTheBHYT;
use App\Services\OrderCheck\TreatmentIssueService;
use App\Services\OrderCheck\TreatmentProfileService;
use App\Services\Xml3176\Support\TheTamSoSinh;
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

    public function traLaiThe(Request $request)
    {
        $ma = $this->layMa($request);

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

        // Job bỏ qua thẻ tạm - không chặn ở đây thì bấm nút xong "không có gì xảy ra".
        if (TheTamSoSinh::la($hoSo['hein_card_number'], $hoSo['hein_medi_org_code'])) {
            return response()->json([
                'message' => 'Thẻ tạm trẻ sơ sinh (nơi ĐKBĐ ' . trim((string) $hoSo['hein_medi_org_code'])
                    . '), không tra cổng BHXH',
            ], 422);
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
